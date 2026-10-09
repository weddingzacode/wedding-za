<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$installer = $root . '/scripts/apply-venue-refresh.php';
$installerSource = file_get_contents($installer);
$marker = "<<<'WZ_VENUE_PAYLOAD'\n";
$start = strpos($installerSource, $marker);
$end = $start === false ? false : strpos($installerSource, "\nWZ_VENUE_PAYLOAD", $start + strlen($marker));

if ($start === false || $end === false) {
    throw new RuntimeException('Venue installer payload is missing.');
}

$start += strlen($marker);
$payload = json_decode(substr($installerSource, $start, $end - $start), true, 512, JSON_THROW_ON_ERROR);
$fixture = sys_get_temp_dir() . '/wz-venue-qa-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);

function wz_venue_update_qa_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }

    echo 'PASS — ' . $message . PHP_EOL;
}

function wz_venue_update_qa_decode(array $entry): string
{
    $decoded = gzdecode(base64_decode($entry['content_gzip_base64'], true));

    if (!is_string($decoded) || hash('sha256', $decoded) !== $entry['sha256']) {
        throw new RuntimeException('Venue release content has an invalid hash.');
    }

    return $decoded;
}

function wz_venue_update_qa_run(string $script, string $cwd): array
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

function wz_venue_update_qa_fixture(string $webRoot, array $files, array $catalogue, int $baseline = 0): array
{
    foreach (['includes', 'assets/css', 'assets/js', 'assets/images', 'assets/data', 'uploads', 'storage'] as $directory) {
        mkdir($webRoot . '/' . $directory, 0700, true);
    }

    foreach ($files as $path => $entry) {
        $version = $entry['baselines'][$baseline] ?? $entry['baselines'][0];

        if ($version['sha256'] !== null) {
            file_put_contents($webRoot . '/' . $path, wz_venue_update_qa_decode($version));
        }
    }

    $preserved = [
        'includes/bootstrap.php' => "<?php\n",
        'includes/database.php' => "<?php\n",
        'config.local.php' => "<?php return ['database' => ['password' => 'private-fixture-secret']];\n",
        'includes/policy-details.local.php' => "<?php return ['operator_name' => 'Owner Business'];\n",
        'privacy.php' => 'Owner privacy policy',
        'terms.php' => 'Owner terms',
        'cancellation.php' => 'Owner cancellation policy',
        'uploads/owner.jpg' => 'Owner uploaded photo',
        'storage/owner-data.json' => 'Owner planning data',
    ];

    foreach ($preserved as $path => $content) {
        file_put_contents($webRoot . '/' . $path, $content);
    }

    file_put_contents($webRoot . '/assets/data/site.json', json_encode($catalogue, JSON_THROW_ON_ERROR));

    return $preserved;
}

function wz_venue_update_qa_remove(string $directory): void
{
    foreach (scandir($directory) as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }

        $path = $directory . '/' . $name;

        if (is_dir($path) && !is_link($path)) {
            wz_venue_update_qa_remove($path);
        } else {
            unlink($path);
        }
    }

    rmdir($directory);
}

try {
    $files = $payload['files'];
    $seeds = json_decode(wz_venue_update_qa_decode($payload['venues']), true, 512, JSON_THROW_ON_ERROR);
    wz_venue_update_qa_assert(count($files) === 35 && count($seeds) === 2, 'The release contains 35 runtime files and two venue additions');

    foreach ($files as $path => $entry) {
        $source = wz_venue_update_qa_decode($entry);
        wz_venue_update_qa_assert(hash_file('sha256', $root . '/' . $path) === $entry['sha256'], 'Installer matches source: ' . $path);

        foreach ($entry['baselines'] as $version) {
            if ($version['sha256'] !== null) {
                wz_venue_update_qa_decode($version);
            }
        }

        if (str_ends_with($path, '.webp')) {
            $image = getimagesizefromstring($source);
            wz_venue_update_qa_assert($image !== false && $image[0] > 0, 'Embedded image is valid: ' . $path);
        }
    }

    $catalogue = json_decode(file_get_contents($root . '/assets/data/site.json'), true, 512, JSON_THROW_ON_ERROR);
    $seedIds = array_column($seeds, 'id');
    $catalogue['vendors'] = array_values(array_filter($catalogue['vendors'], fn (array $venue): bool => !in_array($venue['id'], $seedIds, true)));
    $catalogue['vendors'][] = ['id' => 'owner-venue', 'name' => 'Owner Venue', 'rooms' => 91, 'custom' => ['capacity' => 'Owner value']];
    $catalogue['cities'][] = 'Owner City';
    $catalogue['owner_setting'] = ['active' => true, 'value' => 'Keep this'];
    $webRoot = $fixture . '/current/public_html';
    $preserved = wz_venue_update_qa_fixture($webRoot, $files, $catalogue);
    $originals = [];

    foreach (array_merge(array_keys($files), ['assets/data/site.json']) as $path) {
        $originals[$path] = is_file($webRoot . '/' . $path) ? file_get_contents($webRoot . '/' . $path) : null;
    }

    $customVenuePage = $originals['venues.php'] . "\n<!-- Owner customization -->\n";
    file_put_contents($webRoot . '/venues.php', $customVenuePage);
    $result = wz_venue_update_qa_run($installer, $webRoot);
    wz_venue_update_qa_assert($result['code'] === 1 && str_contains($result['error'], 'local edits'), 'Unknown edits stop the update: ' . $result['error']);
    wz_venue_update_qa_assert(file_get_contents($webRoot . '/venues.php') === $customVenuePage && !file_exists($webRoot . '/includes/venue-profile.php'), 'Rejected updates publish no files');
    file_put_contents($webRoot . '/venues.php', $originals['venues.php']);

    $outside = $fixture . '/outside';
    mkdir($outside, 0700);
    file_put_contents($outside . '/owner.txt', 'Do not overwrite');
    symlink($outside, $webRoot . '/assets/images/venues');
    $result = wz_venue_update_qa_run($installer, $webRoot);
    wz_venue_update_qa_assert($result['code'] === 1 && str_contains($result['error'], 'Unsafe symlink') && count(scandir($outside)) === 3, 'Symlink parent folders cannot receive files');
    unlink($webRoot . '/assets/images/venues');
    symlink($outside . '/owner.txt', $webRoot . '/includes/venue-profile.php');
    $result = wz_venue_update_qa_run($installer, $webRoot);
    wz_venue_update_qa_assert($result['code'] === 1 && str_contains($result['error'], 'Unsafe symlink') && file_get_contents($outside . '/owner.txt') === 'Do not overwrite', 'Symlink destinations remain untouched');
    unlink($webRoot . '/includes/venue-profile.php');

    file_put_contents($webRoot . '/assets/data/site.json', '{bad json');
    $result = wz_venue_update_qa_run($installer, $webRoot);
    wz_venue_update_qa_assert($result['code'] === 1 && !file_exists($webRoot . '/includes/venue-profile.php'), 'An invalid catalogue stops before publication');
    file_put_contents($webRoot . '/assets/data/site.json', $originals['assets/data/site.json']);

    $corruptInstaller = $fixture . '/corrupt.php';
    file_put_contents($corruptInstaller, str_replace($files['includes/venue-directory.php']['sha256'], str_repeat('0', 64), $installerSource));
    $result = wz_venue_update_qa_run($corruptInstaller, $webRoot);
    wz_venue_update_qa_assert($result['code'] === 1 && str_contains($result['error'], 'Invalid payload') && !file_exists($webRoot . '/includes/venue-profile.php'), 'Corrupt payloads publish no files');

    $faultInstaller = $fixture . '/failure.php';
    $faultCode = "\n            if (count(\$installed) === count(\$changes)) {\n                throw new RuntimeException('Simulated publication failure');\n            }";
    file_put_contents($faultInstaller, str_replace('$installed[] = $relativePath;', '$installed[] = $relativePath;' . $faultCode, $installerSource));
    $result = wz_venue_update_qa_run($faultInstaller, $webRoot);
    wz_venue_update_qa_assert($result['code'] === 1 && str_contains($result['error'], 'rolled back'), 'Publication failure triggers rollback');

    foreach ($originals as $path => $content) {
        wz_venue_update_qa_assert($content === null ? !file_exists($webRoot . '/' . $path) : file_get_contents($webRoot . '/' . $path) === $content, 'Rollback restores the original: ' . $path);
    }

    wz_venue_update_qa_assert(!file_exists($webRoot . '/assets/images/venues'), 'Rollback removes newly created empty folders');
    $result = wz_venue_update_qa_run($installer, $webRoot);
    wz_venue_update_qa_assert($result['code'] === 0 && str_contains($result['output'], 'New venue records added: 2'), 'The audited homepage release upgrades successfully: ' . $result['error']);

    foreach ($files as $path => $entry) {
        wz_venue_update_qa_assert(hash_file('sha256', $webRoot . '/' . $path) === $entry['sha256'], 'Installed content verified: ' . $path);
    }

    $updated = json_decode(file_get_contents($webRoot . '/assets/data/site.json'), true, 512, JSON_THROW_ON_ERROR);
    $expected = $catalogue;
    $expected['vendors'] = array_merge($expected['vendors'], $seeds);
    wz_venue_update_qa_assert($updated === $expected, 'Only two additions change the owner catalogue');

    foreach ($preserved as $path => $content) {
        wz_venue_update_qa_assert(file_get_contents($webRoot . '/' . $path) === $content, 'Owner content preserved: ' . $path);
    }

    $backups = glob(dirname($webRoot) . '/weddingza-venue-backup-*');
    wz_venue_update_qa_assert(count($backups) === 2, 'Rollback and successful publication retain separate recoverable backups');

    foreach ($backups as $backup) {
        wz_venue_update_qa_assert((fileperms($backup) & 0777) === 0700 && file_get_contents($backup . '/assets/data/site.json') === $originals['assets/data/site.json'] && (fileperms($backup . '/assets/data/site.json') & 0777) === 0600, 'Catalogue backups stay private outside public_html');
    }

    $installedCatalogue = file_get_contents($webRoot . '/assets/data/site.json');
    $result = wz_venue_update_qa_run($installer, $webRoot);
    wz_venue_update_qa_assert($result['code'] === 0 && str_contains($result['output'], 'already installed') && file_get_contents($webRoot . '/assets/data/site.json') === $installedCatalogue && count(glob(dirname($webRoot) . '/weddingza-venue-backup-*')) === 2, 'Rerunning creates no duplicates or new backups');

    $ownerSeed = $seeds[0];
    $ownerSeed['rooms'] = 80;
    $ownerSeed['price'] = 'Owner quote';
    $olderCatalogue = $catalogue;
    $olderCatalogue['vendors'][] = $ownerSeed;
    $olderRoot = $fixture . '/no-pause-release/public_html';
    wz_venue_update_qa_fixture($olderRoot, $files, $olderCatalogue, 1);
    $result = wz_venue_update_qa_run($installer, $olderRoot);
    wz_venue_update_qa_assert($result['code'] === 0 && str_contains($result['output'], 'New venue records added: 1'), 'The no-pause homepage release upgrades directly');
    $olderUpdated = json_decode(file_get_contents($olderRoot . '/assets/data/site.json'), true, 512, JSON_THROW_ON_ERROR);
    $olderExpected = $olderCatalogue;
    $olderExpected['vendors'][] = $seeds[1];
    wz_venue_update_qa_assert($olderUpdated === $olderExpected, 'Existing venue customizations remain authoritative');
    echo "Venue updater QA complete.\n";
} finally {
    wz_venue_update_qa_remove($fixture);
}
