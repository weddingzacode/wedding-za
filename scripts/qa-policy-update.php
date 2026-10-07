<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$installer = $root . '/scripts/apply-policy-pages.php';
$source = file_get_contents($installer);
if (!preg_match("/<<<'WZ_POLICY_PAYLOAD'\n(.*?)\nWZ_POLICY_PAYLOAD/s", $source, $match)) {
    throw new RuntimeException('Policy installer payload is missing.');
}
$manifest = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
$fixture = sys_get_temp_dir() . '/wz-policy-qa-' . bin2hex(random_bytes(8));
$webRoot = $fixture . '/public_html';
mkdir($webRoot, 0700, true);

function wz_policy_qa_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo 'PASS — ' . $message . PHP_EOL;
}

function wz_policy_qa_run(string $script, string $cwd, string $input = ''): array
{
    $command = [PHP_BINARY, '-n'];
    $tokenizer = ini_get('extension_dir') . '/tokenizer.so';
    if (is_file($tokenizer)) {
        $command = array_merge($command, ['-d', 'extension_dir=' . ini_get('extension_dir'), '-d', 'extension=tokenizer']);
    }
    $command[] = $script;
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['code' => proc_close($process), 'output' => $output, 'error' => $error];
}

try {
    foreach ($manifest as $path => $entry) {
        wz_policy_qa_assert(hash_file('sha256', $root . '/' . $path) === $entry['sha256'], 'Installer matches checked source: ' . $path);
        if (!is_dir(dirname($webRoot . '/' . $path))) {
            mkdir(dirname($webRoot . '/' . $path), 0700, true);
        }
        copy($root . '/' . $path, $webRoot . '/' . $path);
    }
    foreach (['bootstrap.php', 'database.php'] as $path) {
        copy($root . '/includes/' . $path, $webRoot . '/includes/' . $path);
    }
    mkdir($webRoot . '/assets/data', 0700, true);
    file_put_contents($webRoot . '/assets/data/site.json', '{}');
    $privateConfig = "<?php return ['database' => ['password' => 'fixture-secret-must-stay']];\n";
    file_put_contents($webRoot . '/config.local.php', $privateConfig);

    $result = wz_policy_qa_run($installer, $webRoot);
    wz_policy_qa_assert($result['code'] === 1 && !is_file($webRoot . '/includes/policy-details.local.php'), 'Incomplete input aborts before publishing business details');

    $answers = "QA & <Business>\nQA Address, Test City, Test State 123456\n+91 1234567890\n\nQA Contact\n\n\n";
    $result = wz_policy_qa_run($installer, $webRoot, $answers);
    wz_policy_qa_assert($result['code'] === 0 && is_file($webRoot . '/includes/policy-details.local.php'), 'Complete public details install successfully');
    wz_policy_qa_assert(file_get_contents($webRoot . '/config.local.php') === $privateConfig, 'Private configuration stays unchanged');
    $detailsHash = hash_file('sha256', $webRoot . '/includes/policy-details.local.php');
    $result = wz_policy_qa_run($installer, $webRoot);
    wz_policy_qa_assert($result['code'] === 0 && hash_file('sha256', $webRoot . '/includes/policy-details.local.php') === $detailsHash, 'Rerunning is idempotent and preserves public details');

    file_put_contents($webRoot . '/check-details.php', <<<'PHP'
<?php
require __DIR__ . '/includes/policies.php';
if (wz_policy_missing_details(wz_policy_details()) !== []) { exit(1); }
ob_start();
wz_policy_contact();
$html = ob_get_clean();
if (!str_contains($html, 'QA &amp; &lt;Business&gt;') || str_contains($html, 'QA & <Business>')) { exit(1); }
echo 'Details escaped';
PHP);
    $result = wz_policy_qa_run($webRoot . '/check-details.php', $webRoot);
    wz_policy_qa_assert($result['code'] === 0 && str_contains($result['output'], 'Details escaped'), 'Public operator details are complete and HTML escaped');

    $customPolicy = file_get_contents($webRoot . '/privacy.php') . "\n<!-- local custom policy -->\n";
    file_put_contents($webRoot . '/privacy.php', $customPolicy);
    $result = wz_policy_qa_run($installer, $webRoot);
    wz_policy_qa_assert($result['code'] === 1 && str_contains($result['error'], 'local edits')
        && file_get_contents($webRoot . '/privacy.php') === $customPolicy, 'Unknown versions or local edits are preserved');
    wz_policy_qa_assert(hash_file('sha256', $webRoot . '/includes/policy-details.local.php') === $detailsHash
        && file_get_contents($webRoot . '/config.local.php') === $privateConfig, 'Rejected update changes neither business details nor secrets');
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($fixture);
}
