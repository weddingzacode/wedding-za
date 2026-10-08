<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$installer = $root . '/scripts/apply-header-refresh.php';
if (!preg_match("/<<<'WZ_HEADER_PAYLOAD'\n(.*?)\nWZ_HEADER_PAYLOAD/s", file_get_contents($installer), $match)) {
    throw new RuntimeException('Header installer payload is missing.');
}
$manifest = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
$fixture = sys_get_temp_dir() . '/wz-header-qa-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);

function wz_header_qa_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo 'PASS — ' . $message . PHP_EOL;
}

function wz_header_qa_decode(string $payload, string $expectedHash): string
{
    $source = gzdecode(base64_decode($payload, true));
    wz_header_qa_assert(is_string($source) && hash('sha256', $source) === $expectedHash, 'Release content has its verified hash');
    return $source;
}

function wz_header_qa_run(string $script, string $cwd): array
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

// The header installer is an immutable release snapshot.
$headerSnapshots = [
    'index.php' => '72127c0dfd2fe94d659b10ef75244036181e2623b45fcc2079cf370989f77476',
    'assets/js/vision.js' => 'd0dac64ad7c91061a799fbdc5caadae9e7ad2110f9bfe892131fe4eab4188fcb',
    'includes/footer.php' => '7497791d8e40ecf8e6174c9a5051a12355ed0839c85c29c8d098241297870ee3',
    'sw.js' => 'daec2f11e9fc5a9e8b4ecb36c77562b997f821584c8431f0b2dc3da70fe386f5',
];

try {
    wz_header_qa_assert(count($manifest) === 8 && count($manifest['index.php']) === 2, 'The release has eight files and both city layout baselines');
    foreach ($manifest as $path => $versions) {
        wz_header_qa_assert(($headerSnapshots[$path] ?? hash_file('sha256', $root . '/' . $path)) === $versions[0]['sha256'], 'Header release content verified: ' . $path);
        foreach ($versions as $entry) {
            $next = wz_header_qa_decode($entry['content_gzip_base64'], $entry['sha256']);
            if ($entry['previous_sha256'] !== null) {
                $previous = wz_header_qa_decode($entry['previous_content_gzip_base64'], $entry['previous_sha256']);
                if ($path === 'index.php') {
                    $expected = $previous;
                    foreach (['vision-hero-eyebrow' => 12, 'vision-scroll-note' => 8] as $class => $indent) {
                        $expected = preg_replace('/^' . str_repeat(' ', $indent) . '<div class="' . $class . '">.*?<\/div>\n/ms', '', $expected, 1, $count);
                        wz_header_qa_assert($count === 1, 'The requested hero label exists in this baseline');
                    }
                    wz_header_qa_assert($expected === $next, 'Only the two hero labels change in this homepage variant');
                }
            }
        }
    }

    foreach (['compact-cities' => 0, 'original-cities' => 1] as $label => $indexVariant) {
        $webRoot = $fixture . '/' . $label . '/public_html';
        mkdir($webRoot . '/includes', 0700, true);
        mkdir($webRoot . '/assets/css', 0700, true);
        mkdir($webRoot . '/assets/images', 0700, true);
        mkdir($webRoot . '/assets/js', 0700, true);
        mkdir($webRoot . '/assets/data', 0700, true);
        $selected = [];
        foreach ($manifest as $path => $versions) {
            $entry = $versions[$path === 'index.php' ? $indexVariant : 0];
            $selected[$path] = $entry;
            if ($entry['previous_sha256'] !== null) {
                file_put_contents($webRoot . '/' . $path, gzdecode(base64_decode($entry['previous_content_gzip_base64'], true)));
            }
        }
        foreach (['bootstrap.php', 'database.php'] as $path) {
            copy($root . '/includes/' . $path, $webRoot . '/includes/' . $path);
        }
        $preserved = [
            'config.local.php' => "<?php return ['database' => ['password' => 'private-fixture-secret']];\n",
            'includes/policy-details.local.php' => "<?php return ['operator_name' => 'Owner Business'];\n",
            'assets/data/site.json' => '{"cities":["Owner City","Another City"]}',
            'privacy.php' => 'Owner privacy policy',
            'terms.php' => 'Owner terms',
            'cancellation.php' => 'Owner cancellation policy',
        ];
        foreach ($preserved as $path => $content) {
            file_put_contents($webRoot . '/' . $path, $content);
        }

        $originalIndex = file_get_contents($webRoot . '/index.php');
        $originalHeader = file_get_contents($webRoot . '/includes/header.php');
        $customIndex = $originalIndex . "\n<!-- Owner customization -->\n";
        file_put_contents($webRoot . '/index.php', $customIndex);
        $result = wz_header_qa_run($installer, $webRoot);
        wz_header_qa_assert($result['code'] === 1 && str_contains($result['error'], 'local edits'), 'Unknown local edits stop the ' . $label . ' update');
        wz_header_qa_assert(file_get_contents($webRoot . '/index.php') === $customIndex
            && file_get_contents($webRoot . '/includes/header.php') === $originalHeader
            && !file_exists($webRoot . '/assets/css/header.css'), 'A rejected update changes no website files');
        file_put_contents($webRoot . '/index.php', $originalIndex);

        $outside = $fixture . '/outside-mark.svg';
        file_put_contents($outside, 'Do not overwrite');
        symlink($outside, $webRoot . '/assets/images/weddingza-mark.svg');
        $result = wz_header_qa_run($installer, $webRoot);
        wz_header_qa_assert($result['code'] === 1 && str_contains($result['error'], 'Unsafe')
            && file_get_contents($outside) === 'Do not overwrite'
            && !file_exists($webRoot . '/assets/css/header.css'), 'Symlink destinations are refused before publication');
        unlink($webRoot . '/assets/images/weddingza-mark.svg');

        $result = wz_header_qa_run($installer, $webRoot);
        wz_header_qa_assert($result['code'] === 0, 'The ' . $label . ' deployment upgrades: ' . $result['error']);
        foreach ($selected as $path => $entry) {
            wz_header_qa_assert(hash_file('sha256', $webRoot . '/' . $path) === $entry['sha256'], 'Installed file verified: ' . $path);
        }
        foreach ($preserved as $path => $content) {
            wz_header_qa_assert(file_get_contents($webRoot . '/' . $path) === $content, 'Owner content preserved: ' . $path);
        }
        $backups = glob(dirname($webRoot) . '/weddingza-header-backup-*');
        wz_header_qa_assert(count($backups) === 1 && (fileperms($backups[0]) & 0777) === 0700, 'The backup stays private outside public_html');
        wz_header_qa_assert(file_get_contents($backups[0] . '/index.php') === $originalIndex
            && file_get_contents($backups[0] . '/includes/header.php') === $originalHeader, 'Original layout files are recoverable');
        $result = wz_header_qa_run($installer, $webRoot);
        wz_header_qa_assert($result['code'] === 0 && str_contains($result['output'], 'already installed')
            && count(glob(dirname($webRoot) . '/weddingza-header-backup-*')) === 1, 'Rerunning the ' . $label . ' update is idempotent');
    }
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($fixture);
}
