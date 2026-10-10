<?php

declare(strict_types=1);

/* This overlay reads the current catalogue without replacing site.json or CRM data. */
function wz_collection_venues(array $existing): array
{
    $path = __DIR__ . '/../assets/data/venue-collection.json';
    $additions = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $venues = [];

    foreach ($existing as $venue) {
        if (($venue['category'] ?? '') === 'Venues' || ($venue['business_type'] ?? '') === 'venue') {
            $venues[] = $venue;
        }
    }

    foreach ($additions as $addition) {
        $match = null;
        $needle = strtolower(preg_replace('/[^a-z0-9]/i', '', $addition['name']));

        foreach ($venues as $index => $venue) {
            $name = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)($venue['name'] ?? '')));
            if (($venue['id'] ?? '') === $addition['id'] || $name === $needle) {
                $match = $index;
                break;
            }
        }

        $original = $match !== null ? $venues[$match] : [];
        $record = array_replace($original, $addition);
        $record['id'] = $original['id'] ?? $addition['id'];
        $record['category'] = 'Venues';
        $record['business_type'] = 'venue';
        $record['collection_detail'] = true;
        $record['collection_order'] = array_search($addition, $additions, true);
        $record['image'] = $addition['image'] ?: ($original['image'] ?? '');
        $record['illustrated'] = $record['image'] === '';
        if ($record['illustrated']) {
            $record['image'] = 'assets/images/venues/collection-' . $addition['id'] . '.svg';
        }
        $record['price'] = $addition['starting_price'] !== null
            ? '₹11.50 lakh / function'
            : 'Request pricing';
        if ($match !== null) {
            $venues[$match] = $record;
        } else {
            $venues[] = $record;
        }
    }

    return array_values($venues);
}

function wz_collection_detail_url(array $venue): string
{
    $page = !empty($venue['collection_detail']) ? 'venue-detail.php' : 'vendor.php';
    return $page . '?id=' . rawurlencode((string)$venue['id']);
}

/* Price comparisons stay within one unit: a function package is not a plate rate. */
function wz_collection_price(array $venue): array
{
    if (array_key_exists('starting_price', $venue)) {
        return [$venue['starting_price'], $venue['price_unit'] ?? 'function'];
    }
    $label = (string)($venue['price'] ?? '');
    if (!preg_match('/([0-9][0-9,]*(?:\.[0-9]+)?)/', $label, $matches)) {
        return [null, null];
    }
    $amount = (float)str_replace(',', '', $matches[1]);
    if (preg_match('/lakh|\blac\b|\d\s*L\b/i', $label)) {
        $amount *= 100000;
    }
    $unit = stripos($label, 'plate') !== false ? 'plate'
        : (preg_match('/function|event|package/i', $label) ? 'function' : 'unknown');
    return [(int)$amount, $unit];
}

function wz_collection_get(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_scalar($value) ? trim((string)$value) : $default;
}

function wz_collection_select(string $name, string $label, array $options, string $current): void
{
    ?>
    <label class="vc-field">
        <span><?= h($label) ?></span>
        <select name="<?= h($name) ?>">
            <?php foreach ($options as $value => $text): ?>
                <option value="<?= h((string)$value) ?>" <?= (string)$value === $current ? 'selected' : '' ?>><?= h((string)$text) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php
}
