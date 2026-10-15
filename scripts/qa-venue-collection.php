<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/venue-collection.php';

function collection_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS — ' . $message . PHP_EOL;
}

function collection_decode(array $entry): string
{
    $content = gzdecode(base64_decode($entry['content_gzip_base64'], true));
    collection_assert(is_string($content) && hash('sha256', $content) === $entry['sha256'], 'Payload hash is valid');
    return $content;
}

function collection_run(string $script, string $directory): array
{
    $command = [PHP_BINARY, '-n'];
    if (is_file(ini_get('extension_dir') . '/tokenizer.so')) {
        $command = array_merge($command, ['-d', 'extension_dir=' . ini_get('extension_dir'), '-d', 'extension=tokenizer']);
    }
    $command[] = $script;
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $directory);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $output, $error];
}

function collection_remove(string $directory): void
{
    foreach (scandir($directory) as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $directory . '/' . $name;
        is_dir($path) && !is_link($path) ? collection_remove($path) : unlink($path);
    }
    rmdir($directory);
}

$root = dirname(__DIR__);
$source = file_get_contents($root . '/scripts/apply-venue-collection.php');
$marker = "<<<'WZ_VENUE_PAYLOAD'\n";
$start = strpos($source, $marker) + strlen($marker);
$end = strpos($source, "\nWZ_VENUE_PAYLOAD", $start);
$payload = json_decode(substr($source, $start, $end - $start), true, 512, JSON_THROW_ON_ERROR);
$records = wz_collection_venues(wz_data('vendors'));
$byId = array_column($records, null, 'id');
collection_assert(count($records) === 13 && count($byId) === 13, 'The thirteen venues have unique identities');
collection_assert($byId['amber-courtyard']['capacity_max'] === 800, 'Legacy formatted capacities remain searchable');
collection_assert($byId['caelum-atharva-palace-jaipur']['capacity_label'] === '2,000+', 'Open-ended capacity is retained');
collection_assert($byId['caelum-atharva-palace-jaipur']['rooms'] === 70, 'Nearby Caelum rooms are not counted as on-site rooms');
collection_assert(wz_collection_price($byId['hari-van-jaipur']) === [1150000, 'function'], 'Hari Van retains the supplied one-function price');
collection_assert(wz_collection_price($byId['jisaa-eco-resort-jaipur']) === [1150000, 'function'], 'JISAA retains the supplied one-function price');
collection_assert(wz_collection_price($byId['amber-courtyard']) === [3200, 'plate'], 'Plate and function rates remain distinct');
$owner = ['id' => 'owner-urban', 'name' => 'Urban Suites Blusalzz', 'category' => 'Venues', 'rooms' => 321, 'capacity' => 'Up to 1,400 guests', 'image' => 'uploads/owner.webp', 'price' => '₹12,00,000 per function', 'rating' => 4.7, 'reviews' => 10];
$merged = wz_collection_venues([$owner]);
$owned = array_values(array_filter($merged, fn (array $v): bool => $v['id'] === 'owner-urban'))[0];
collection_assert(count($merged) === 8 && $owned['rooms'] === 321 && $owned['capacity_max'] === 1400 && $owned['price'] === $owner['price'] && $owned['image'] === $owner['image'] && $owned['reviews'] === 10, 'Existing owner identities, room counts, rates, photos and reviews win');

$fixture = sys_get_temp_dir() . '/wz-collection-qa-' . bin2hex(random_bytes(6));
mkdir($fixture, 0700);
try {
    collection_assert(count($payload['files']) === 22, 'The installer contains all 22 runtime dependencies');
    foreach ($payload['files'] as $path => $entry) {
        collection_assert(hash_file('sha256', $root . '/' . $path) === $entry['sha256'], 'Installer matches ' . $path);
        collection_decode($entry);
    }
    foreach (['first-venue-release', 'first-collection-release'] as $release) {
        $webRoot = $fixture . '/' . $release . '/public_html';
        mkdir($webRoot, 0700, true);
        $originals = [];
        foreach ($payload['files'] as $path => $entry) {
            $version = $entry['baselines'][0];
            if ($release === 'first-collection-release') {
                foreach ($entry['baselines'] as $candidate) {
                    if ($candidate['sha256'] === $entry['previous_release_sha256']) $version = $candidate;
                }
            }
            $originals[$path] = $version['sha256'] === null ? null : collection_decode($version);
            if ($originals[$path] !== null) {
                if (!is_dir(dirname($webRoot . '/' . $path))) mkdir(dirname($webRoot . '/' . $path), 0700, true);
                file_put_contents($webRoot . '/' . $path, $originals[$path]);
            }
        }
        $preserved = ['includes/bootstrap.php' => '<?php', 'includes/database.php' => '<?php', 'config.local.php' => 'owner-private-config', 'assets/data/site.json' => '{"vendors":[{"id":"owner","name":"Owner Venue","custom":"keep"}],"cities":["Owner City"],"custom":true}', 'uploads/owner.jpg' => 'owner-photo', 'storage/owner.txt' => 'owner-data'];
        foreach ($preserved as $path => $content) {
            if (!is_dir(dirname($webRoot . '/' . $path))) mkdir(dirname($webRoot . '/' . $path), 0700, true);
            file_put_contents($webRoot . '/' . $path, $content);
        }
        file_put_contents($webRoot . '/venues.php', $originals['venues.php'] . '\nOwner local edit');
        [$code, $output, $error] = collection_run($root . '/scripts/apply-venue-collection.php', $webRoot);
        collection_assert($code === 1 && str_contains($error, 'local edits'), 'Unknown edits stop publication for ' . $release);
        file_put_contents($webRoot . '/venues.php', $originals['venues.php']);

        $fault = $fixture . '/fault.php';
        $injection = "\n            if (count(\$installed) === count(\$changes)) throw new RuntimeException('Simulated write failure');";
        file_put_contents($fault, str_replace('$installed[] = $relativePath;', '$installed[] = $relativePath;' . $injection, $source));
        [$code, $output, $error] = collection_run($fault, $webRoot);
        collection_assert($code === 1 && str_contains($error, 'rolled back'), 'Publication errors roll back for ' . $release);
        foreach ($originals as $path => $original) {
            collection_assert($original === null ? !file_exists($webRoot . '/' . $path) : file_get_contents($webRoot . '/' . $path) === $original, 'Rollback restores ' . $path);
        }
        [$code, $output, $error] = collection_run($root . '/scripts/apply-venue-collection.php', $webRoot);
        collection_assert($code === 0, 'Supported release installs successfully: ' . $release . ' ' . $error);
        foreach ($payload['files'] as $path => $entry) {
            collection_assert(hash_file('sha256', $webRoot . '/' . $path) === $entry['sha256'], 'Installed file verified: ' . $path);
        }
        foreach ($preserved as $path => $content) {
            collection_assert(file_get_contents($webRoot . '/' . $path) === $content, 'Owner content preserved: ' . $path);
        }
        $backups = glob(dirname($webRoot) . '/weddingza-venue-backup-*');
        collection_assert(count($backups) === 2 && (fileperms($backups[0]) & 0777) === 0700, 'Rollback and successful backups remain private');
        [$code, $output, $error] = collection_run($root . '/scripts/apply-venue-collection.php', $webRoot);
        collection_assert($code === 0 && str_contains($output, 'already installed') && count(glob(dirname($webRoot) . '/weddingza-venue-backup-*')) === 2, 'Rerun creates no duplicates or backup');
    }
} finally {
    collection_remove($fixture);
}
echo "Venue collection and updater QA complete.\n";
