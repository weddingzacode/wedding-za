<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Isolated fixtures keep production credentials and accounts untouched.
$root = dirname(__DIR__);
$fixture = sys_get_temp_dir() . '/wz-launch-qa-' . bin2hex(random_bytes(8));
mkdir($fixture . '/includes', 0700, true);
mkdir($fixture . '/assets/data', 0700, true);
mkdir($fixture . '/scripts', 0700, true);

foreach (['auth.php', 'database.php', 'bootstrap.php', 'policies.php'] as $file) {
    copy($root . '/includes/' . $file, $fixture . '/includes/' . $file);
}
copy($root . '/scripts/go-live-check.php', $fixture . '/scripts/go-live-check.php');
file_put_contents($fixture . '/assets/data/site.json', '{}');
file_put_contents($fixture . '/runner.php', <<<'PHP'
<?php
$scenario = json_decode(file_get_contents(__DIR__ . '/scenario.json'), true);
$_SERVER['HTTP_HOST'] = $scenario['host'];
$_SERVER['REMOTE_ADDR'] = $scenario['ip'];
require __DIR__ . '/includes/auth.php';
$allowed = wz_demo_login_allowed();
$created = false;
try {
    wz_login_demo('Local Preview', 'preview@example.com');
    $created = wz_user() !== null;
} catch (RuntimeException $exception) {
    // Expected for every disabled or non-local scenario.
}
$_SESSION['wz_user'] = ['id' => null, 'role' => 'host'];
echo json_encode([
    'allowed' => $allowed,
    'created' => $created,
    'old_demo_session_valid' => wz_user() !== null,
]);
PHP);

function wz_qa_child(string $script, bool $curl = true): array
{
    $command = [PHP_BINARY, '-n', '-d', 'extension_dir=' . ini_get('extension_dir')];
    foreach (['pdo', 'mysqlnd', 'pdo_mysql', 'fileinfo', 'mbstring', 'curl'] as $extension) {
        if (extension_loaded($extension) && ($curl || $extension !== 'curl')) {
            $command[] = '-d';
            $command[] = 'extension=' . $extension;
        }
    }
    $command[] = '-d';
    $command[] = 'session.save_path=' . sys_get_temp_dir();
    $command[] = $script;
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['code' => proc_close($process), 'output' => $output, 'error' => $error];
}

$scenarios = [
    ['default disabled', false, 'localhost', '127.0.0.1', '', false, false],
    ['explicit local preview', true, 'localhost', '127.0.0.1', '', false, true],
    ['public hostname', true, 'weddingza.invalid', '127.0.0.1', '', false, false],
    ['remote client', true, 'localhost', '203.0.113.10', '', false, false],
    ['production app URL', true, 'localhost', '127.0.0.1', 'https://weddingza.invalid', false, false],
    ['configured database outage', true, 'localhost', '127.0.0.1', '', true, false],
];

$failed = false;
try {
    foreach ($scenarios as [$label, $enabled, $host, $ip, $url, $database, $expected]) {
        $config = [
            'app_url' => $url,
            'operations' => ['allow_demo_login' => $enabled],
            'database' => $database
                ? ['host' => '127.0.0.1', 'name' => 'unreachable', 'user' => 'preview']
                : ['host' => '', 'name' => '', 'user' => ''],
        ];
        file_put_contents($fixture . '/config.local.php', '<?php return ' . var_export($config, true) . ';');
        file_put_contents($fixture . '/scenario.json', json_encode(['host' => $host, 'ip' => $ip]));
        $result = wz_qa_child($fixture . '/runner.php');
        $values = json_decode($result['output'], true);
        $passed = $result['code'] === 0 && $values === [
            'allowed' => $expected,
            'created' => $expected,
            'old_demo_session_valid' => $expected,
        ];
        echo ($passed ? 'PASS' : 'FAIL') . ' — ' . $label . PHP_EOL;
        $failed = $failed || !$passed;
    }

    file_put_contents($fixture . '/config.local.php', <<<'PHP'
<?php return ['app_url' => 'https://your-domain.com', 'operations' => ['allow_demo_login' => true]];
PHP);
    $result = wz_qa_child($fixture . '/scripts/go-live-check.php', false);
    foreach (['cURL extension', 'Production app URL', 'Demo account access', 'Published business policies', 'Business and grievance details'] as $label) {
        $passed = $result['code'] === 1 && str_contains($result['output'], '[BLOCK]  ' . $label);
        echo ($passed ? 'PASS' : 'FAIL') . ' — checker rejects ' . $label . PHP_EOL;
        $failed = $failed || !$passed;
    }
} finally {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($fixture);
}

exit($failed ? 1 : 0);
