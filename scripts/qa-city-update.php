<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$installer = $root . '/scripts/apply-city-discovery.php';
if (!preg_match("/<<<'WZ_CITY_PAYLOAD'\n(.*?)\nWZ_CITY_PAYLOAD/s", file_get_contents($installer), $match)) {
    throw new RuntimeException('City installer payload is missing.');
}
$manifest = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
$fixture = sys_get_temp_dir() . '/wz-city-qa-' . bin2hex(random_bytes(8));
$webRoot = $fixture . '/public_html';
mkdir($webRoot . '/assets/css', 0700, true);
mkdir($webRoot . '/assets/data', 0700, true);
mkdir($webRoot . '/includes', 0700, true);

function wz_city_update_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo 'PASS — ' . $message . PHP_EOL;
}

function wz_city_update_run(string $script, string $cwd): array
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
    foreach ($manifest as $path => $entry) {
        $snapshot = gzdecode(base64_decode($entry['content_gzip_base64'], true));
        wz_city_update_assert(hash('sha256', $snapshot) === $entry['sha256'], 'Verified release snapshot: ' . $path);
        // Keep this immutable installer self-contained. The later header release
        // owns homepage layout changes and checks its source in qa-header-update.php.
        if ($path !== 'index.php') {
            wz_city_update_assert(hash_file('sha256', $root . '/' . $path) === $entry['sha256'], 'Installer matches source: ' . $path);
        }
        if ($entry['previous_sha256'] !== null) {
            $previous = gzdecode(base64_decode($entry['previous_content_gzip_base64'], true));
            wz_city_update_assert(hash('sha256', $previous) === $entry['previous_sha256'], 'Verified upgrade baseline: ' . $path);
            file_put_contents($webRoot . '/' . $path, $previous);
        }
    }
    foreach (['bootstrap.php', 'database.php'] as $path) {
        copy($root . '/includes/' . $path, $webRoot . '/includes/' . $path);
    }
    $preserved = [
        'assets/data/site.json' => '{"cities":["Owner City","Another City"]}',
        'config.local.php' => "<?php return ['database' => ['password' => 'fixture-secret']];\n",
        'includes/policy-details.local.php' => "<?php return ['operator_name' => 'Fixture Owner'];\n",
    ];
    foreach ($preserved as $path => $content) {
        file_put_contents($webRoot . '/' . $path, $content);
    }
    $originalIndex = file_get_contents($webRoot . '/index.php');
    $customIndex = $originalIndex . "\n<!-- Owner customization -->\n";
    file_put_contents($webRoot . '/index.php', $customIndex);
    $result = wz_city_update_run($installer, $webRoot);
    wz_city_update_assert($result['code'] === 1 && str_contains($result['error'], 'local edits'), 'Unknown local versions are refused');
    wz_city_update_assert(file_get_contents($webRoot . '/index.php') === $customIndex && !is_file($webRoot . '/cities.php'), 'Validation failure leaves all website files untouched');
    file_put_contents($webRoot . '/index.php', $originalIndex);
    $result = wz_city_update_run($installer, $webRoot);
    wz_city_update_assert($result['code'] === 0, 'An original deployment upgrades successfully: ' . $result['error']);
    foreach ($manifest as $path => $entry) {
        wz_city_update_assert(hash_file('sha256', $webRoot . '/' . $path) === $entry['sha256'], 'Installed content verified: ' . $path);
    }
    foreach ($preserved as $path => $content) {
        wz_city_update_assert(file_get_contents($webRoot . '/' . $path) === $content, 'Owner data preserved: ' . $path);
    }
    $backups = glob($fixture . '/weddingza-cities-backup-*');
    wz_city_update_assert(count($backups) === 1 && (fileperms($backups[0]) & 0777) === 0700, 'Backup lives privately outside the web root');
    wz_city_update_assert(file_get_contents($backups[0] . '/index.php') === $originalIndex, 'The original homepage is recoverable');
    $result = wz_city_update_run($installer, $webRoot);
    wz_city_update_assert($result['code'] === 0 && str_contains($result['output'], 'already installed') && count(glob($fixture . '/weddingza-cities-backup-*')) === 1, 'Rerunning is idempotent');
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($fixture);
}
