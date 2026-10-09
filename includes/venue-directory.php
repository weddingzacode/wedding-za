<?php

declare(strict_types=1);

function wz_venue_capacity(array $venue): int
{
    $capacity = (int)($venue['capacity_max'] ?? 0);

    if ($capacity > 0) {
        return $capacity;
    }

    preg_match_all('/[\d,]+/', (string)($venue['capacity'] ?? ''), $matches);
    $values = array_map(
        fn (string $number): int => (int)str_replace(',', '', $number),
        $matches[0]
    );

    return $values ? max($values) : 0;
}

function wz_venue_photos(array $venue): array
{
    $photos = [];
    $captions = [];

    foreach ($venue['photos'] ?? [] as $photo) {
        if (is_array($photo) && !empty($photo['src'])) {
            $captions[(string)$photo['src']] = $photo;
        }
    }

    $sources = array_merge(
        [(string)($venue['image'] ?? '')],
        $venue['images'] ?? []
    );

    foreach (array_unique($sources) as $source) {
        $source = trim((string)$source);

        if ($source === '') {
            continue;
        }

        $photos[] = $captions[$source] ?? [
            'src' => $source,
            'caption' => (string)$venue['name'] . ' · venue view',
        ];
    }

    return $photos;
}

function wz_venue_matches(array $venue, array $filters): bool
{
    if ($filters['city'] !== ''
        && strcasecmp((string)($venue['city'] ?? ''), $filters['city']) !== 0
    ) {
        return false;
    }

    $searchText = implode(' ', [
        (string)($venue['name'] ?? ''),
        (string)($venue['city'] ?? ''),
        (string)($venue['locality'] ?? ''),
        (string)($venue['address'] ?? ''),
    ]);

    if ($filters['q'] !== ''
        && mb_stripos($searchText, $filters['q'], 0, 'UTF-8') === false
    ) {
        return false;
    }

    if ($filters['type'] !== ''
        && strcasecmp((string)($venue['venue_type'] ?? ''), $filters['type']) !== 0
    ) {
        return false;
    }

    if ($filters['event'] !== ''
        && !in_array($filters['event'], $venue['events'] ?? [], true)
    ) {
        return false;
    }

    if ($filters['guests'] > 0 && wz_venue_capacity($venue) < $filters['guests']) {
        return false;
    }

    if ($filters['rooms'] > 0 && (int)($venue['rooms'] ?? 0) < $filters['rooms']) {
        return false;
    }

    $knownPrice = wz_money_number((string)($venue['price'] ?? ''));

    if ($filters['max_price'] > 0
        && ($knownPrice === 0 || $knownPrice > $filters['max_price'])
    ) {
        return false;
    }

    return $filters['rating'] <= 0
        || (float)($venue['rating'] ?? 0) >= $filters['rating'];
}

function wz_venue_url(array $venue): string
{
    return 'vendor.php?id=' . urlencode((string)$venue['id']);
}

function wz_venue_listing_card(array $venue): void
{
    $url = wz_venue_url($venue);
    $photos = wz_venue_photos($venue);
    $capacity = wz_venue_capacity($venue);
    $rooms = (int)($venue['rooms'] ?? 0);
    $reviewCount = (int)($venue['reviews'] ?? 0);
    ?>

        <article class="venue-listing-card" data-vendor-id="<?= h((string)$venue['id']) ?>" data-venue-reveal>
        <a class="venue-listing-image" href="<?= h($url) ?>" aria-label="Open <?= h((string)$venue['name']) ?>">
            <img
                src="<?= h((string)$venue['image']) ?>"
                alt="<?= h((string)($photos[0]['caption'] ?? $venue['name'])) ?>"
                loading="lazy"
                decoding="async"
                width="720"
                height="540"
            >
            <span class="venue-photo-count"><?= count($photos) ?> photos</span>
            <span class="venue-image-link">Take a look <span aria-hidden="true">↗</span></span>
        </a>
        <div class="venue-listing-copy">
            <div class="venue-listing-topline">
                <span class="venue-kicker"><?= h((string)($venue['venue_type'] ?? 'Celebration venue')) ?></span>
                <button
                        class="venue-save heart-btn"
                        type="button"
                        data-shortlist="<?= h((string)$venue['id']) ?>"
                        data-business-user-id="<?= h((string)($venue['database_user_id'] ?? 0)) ?>"
                        data-business-type="venue"
                        aria-label="Save <?= h((string)$venue['name']) ?>"
                        aria-pressed="false"
                    >♡</button>
            </div>
            <h2>
                <a href="<?= h($url) ?>"><?= h((string)$venue['name']) ?></a>
            </h2>
            <p class="venue-location"><?= h((string)($venue['locality'] ?? '')) ?> · <?= h((string)$venue['city']) ?></p>
            <?php if ($reviewCount > 0): ?>
                <p class="venue-review-summary">★ <?= h((string)$venue['rating']) ?> <span>· <?= $reviewCount ?> reviews</span>
                </p>
            <?php endif; ?>

            <div class="venue-card-facts">
                <div>
                    <strong><?= $capacity > 0 ? number_format($capacity) : 'Ask venue' ?></strong>
                    <span><?= $capacity > 0 ? 'guests, up to' : 'guest capacity' ?></span>
                </div>
                <div>
                    <strong><?= $rooms > 0 ? $rooms : 'Ask venue' ?></strong>
                    <span><?= $rooms > 0 ? 'guest rooms' : 'room details' ?></span>
                </div>
                <div>
                    <strong><?= count($photos) ?></strong>
                    <span>gallery views</span>
                </div>
            </div>
            <div class="venue-card-amenities">
                <?php foreach (array_slice($venue['amenities'] ?? $venue['services'] ?? [], 0, 3) as $amenity): ?>
                    <span><?= h((string)$amenity) ?></span>
                <?php endforeach; ?>

            </div>
            <div class="venue-listing-actions">
                <div class="venue-price-note">
                    <small><?= wz_money_number((string)$venue['price']) > 0 ? 'Starting from' : 'Your event, your quote' ?></small>
                    <strong><?= h((string)$venue['price']) ?></strong>
                </div>
                <div class="venue-card-buttons">
                    <button
                            class="venue-compare"
                            type="button"
                            data-compare-venue="<?= h((string)$venue['id']) ?>"
                            data-compare-name="<?= h((string)$venue['name']) ?>"
                            aria-label="Compare <?= h((string)$venue['name']) ?>"
                            aria-pressed="false"
                        >Compare <span aria-hidden="true">+</span>
                    </button>
                    <a class="venue-button" href="<?= h($url) ?>#enquire">Enquire <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>
        </div>
    </article>

<?php
}
