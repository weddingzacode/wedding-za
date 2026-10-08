<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$installer = $root . '/scripts/apply-homepage-refresh.php';
$installerSource = file_get_contents($installer);
$marker = "<<<'WZ_HOME_PAYLOAD'\n";
$start = strpos($installerSource, $marker);
$end = $start === false ? false : strpos($installerSource, "\nWZ_HOME_PAYLOAD", $start + strlen($marker));
if ($start === false || $end === false) {
    throw new RuntimeException('Homepage installer payload is missing.');
}
$start += strlen($marker);
$manifest = json_decode(substr($installerSource, $start, $end - $start), true, 512, JSON_THROW_ON_ERROR);
$fixture = sys_get_temp_dir() . '/wz-home-qa-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);

function wz_home_qa_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo 'PASS — ' . $message . PHP_EOL;
}

function wz_home_qa_decode(string $payload, string $expectedHash): string
{
    $source = gzdecode(base64_decode($payload, true));
    wz_home_qa_assert(is_string($source) && hash('sha256', $source) === $expectedHash, 'Release content has its verified hash');
    return $source;
}

function wz_home_qa_run(string $script, string $cwd): array
{
    $command = [PHP_BINARY, '-n'];
    $tokenizer = ini_get('extension_dir') . '/tokenizer.so';
    if (is_file($tokenizer)) {
        $command = array_merge($command, ['-d', 'extension_dir=' . ini_get('extension_dir'), '-d', 'extension=tokenizer']);
    }
    $command[] = $script;
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['code' => proc_close($process), 'output' => $output, 'error' => $error];
}

try {
    wz_home_qa_assert(count($manifest) === 28, 'The homepage release contains exactly 28 runtime files');
    $webRoot = $fixture . '/public_html';
    foreach (['includes', 'assets/css', 'assets/js', 'assets/images', 'assets/data', 'uploads', 'storage'] as $directory) {
        mkdir($webRoot . '/' . $directory, 0700, true);
    }
    foreach ($manifest as $path => $versions) {
        wz_home_qa_assert(count($versions) === 1, 'One verified homepage upgrade: ' . $path);
        $entry = $versions[0];
        $next = wz_home_qa_decode($entry['content_gzip_base64'], $entry['sha256']);
        wz_home_qa_assert(hash_file('sha256', $root . '/' . $path) === $entry['sha256'], 'Installer matches current source: ' . $path);
        if (str_ends_with($path, '.webp')) {
            $image = getimagesizefromstring($next);
            wz_home_qa_assert($image !== false && $image[0] > 0 && $image[1] > 0, 'Photo is valid and nonempty: ' . $path);
        }
        if ($entry['previous_sha256'] !== null) {
            file_put_contents($webRoot . '/' . $path, wz_home_qa_decode($entry['previous_content_gzip_base64'], $entry['previous_sha256']));
        }
    }
    foreach (['bootstrap.php', 'database.php'] as $path) {
        copy($root . '/includes/' . $path, $webRoot . '/includes/' . $path);
    }
    $preserved = [
        'config.local.php' => "<?php return ['database' => ['password' => 'private-fixture-secret']];\n",
        'includes/policy-details.local.php' => "<?php return ['operator_name' => 'Owner Business'];\n",
        'assets/data/site.json' => '{"cities":["Owner City"],"event_types":[{"image":"uploads/owner.jpg"}]}',
        'privacy.php' => 'Owner privacy policy',
        'terms.php' => 'Owner terms',
        'cancellation.php' => 'Owner cancellation policy',
        'uploads/owner.jpg' => 'Owner uploaded photo',
        'storage/owner-data.json' => 'Owner planning data',
    ];
    foreach ($preserved as $path => $content) {
        file_put_contents($webRoot . '/' . $path, $content);
    }
    $originals = [];
    foreach ($manifest as $path => $versions) {
        $originals[$path] = is_file($webRoot . '/' . $path) ? file_get_contents($webRoot . '/' . $path) : null;
    }

    $customIndex = $originals['index.php'] . "\n<!-- Owner customization -->\n";
    file_put_contents($webRoot . '/index.php', $customIndex);
    $result = wz_home_qa_run($installer, $webRoot);
    wz_home_qa_assert($result['code'] === 1 && str_contains($result['error'], 'local edits'), 'Unknown local edits stop the update');
    wz_home_qa_assert(file_get_contents($webRoot . '/index.php') === $customIndex
        && file_get_contents($webRoot . '/includes/footer.php') === $originals['includes/footer.php']
        && !file_exists($webRoot . '/assets/css/home.css'), 'A rejected update changes no website files');
    file_put_contents($webRoot . '/index.php', $originals['index.php']);

    $outside = $fixture . '/outside-photo.webp';
    file_put_contents($outside, 'Do not overwrite');
    symlink($outside, $webRoot . '/assets/images/home-birthday.webp');
    $result = wz_home_qa_run($installer, $webRoot);
    wz_home_qa_assert($result['code'] === 1 && str_contains($result['error'], 'Unsafe')
        && file_get_contents($outside) === 'Do not overwrite'
        && !file_exists($webRoot . '/assets/css/home.css'), 'Symlink destinations are refused before publication');
    unlink($webRoot . '/assets/images/home-birthday.webp');

    $corrupt = $fixture . '/corrupt-installer.php';
    $corruptSource = str_replace($manifest['index.php'][0]['sha256'], str_repeat('0', 64), file_get_contents($installer));
    file_put_contents($corrupt, $corruptSource);
    $result = wz_home_qa_run($corrupt, $webRoot);
    wz_home_qa_assert($result['code'] === 1 && str_contains($result['error'], 'Invalid payload')
        && file_get_contents($webRoot . '/index.php') === $originals['index.php'], 'Corrupt release content stops before any replacement');

    $result = wz_home_qa_run($installer, $webRoot);
    wz_home_qa_assert($result['code'] === 0, 'The installed header release upgrades: ' . $result['error']);
    foreach ($manifest as $path => $versions) {
        wz_home_qa_assert(hash_file('sha256', $webRoot . '/' . $path) === $versions[0]['sha256'], 'Installed file verified: ' . $path);
    }
    foreach ($preserved as $path => $content) {
        wz_home_qa_assert(file_get_contents($webRoot . '/' . $path) === $content, 'Owner content preserved: ' . $path);
    }
    $backups = glob(dirname($webRoot) . '/weddingza-homepage-backup-*');
    wz_home_qa_assert(count($backups) === 1 && (fileperms($backups[0]) & 0777) === 0700, 'The backup stays private outside public_html');
    foreach ($originals as $path => $content) {
        if ($content !== null) {
            wz_home_qa_assert(file_get_contents($backups[0] . '/' . $path) === $content
                && (fileperms($backups[0] . '/' . $path) & 0777) === 0600, 'Original file is privately recoverable: ' . $path);
        }
    }
    $result = wz_home_qa_run($installer, $webRoot);
    wz_home_qa_assert($result['code'] === 0 && str_contains($result['output'], 'already installed')
        && count(glob(dirname($webRoot) . '/weddingza-homepage-backup-*')) === 1, 'Rerunning the homepage update is idempotent');

    require $root . '/includes/home-content.php';
    $ownerEvent = ['id' => 'birthday', 'name' => 'Owner celebration', 'image' => 'uploads/owner.jpg'];
    $ownerIdea = ['id' => 7, 'title' => 'Owner idea', 'image' => 'https://example.com/owner-photo.jpg'];
    $ownerArticle = ['id' => 'guest-experience', 'title' => 'Owner guide', 'image' => 'uploads/guide.jpg'];
    wz_home_qa_assert(wz_home_events([$ownerEvent]) === [$ownerEvent]
        && wz_home_ideas([$ownerIdea]) === [$ownerIdea]
        && wz_home_articles([$ownerArticle]) === [$ownerArticle], 'Custom catalogue photos and text are preserved');
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($fixture);
}
