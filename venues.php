<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/vendors.php';
require_once __DIR__ . '/includes/venue-collection.php';

$pageTitle = 'Venues for Every Celebration | Weddingza';
$pageDescription = 'Explore Jaipur hotels, resorts, palaces, banquet halls and lawns. Compare rooms and guest capacity, discover event spaces and call Weddingza on 9785005549.';
$pageKey = 'venues';
$vcAllVenues = wz_collection_venues(wz_public_vendors());
$vcQuery = substr(wz_collection_get('q'), 0, 120);
$vcCity = wz_collection_get('city');
$vcType = wz_collection_get('type');
$vcEvent = wz_collection_get('event');
$vcGuests = max(0, (int)wz_collection_get('guests'));
$vcRooms = max(0, (int)wz_collection_get('rooms'));
$vcMaxPrice = max(0, (int)wz_collection_get('max_price'));
$vcPriceUnit = wz_collection_get('price_unit', 'function');
if (!in_array($vcPriceUnit, ['function', 'plate'], true)) {
    $vcPriceUnit = 'function';
}
$vcRating = max(0, min(5, (float)wz_collection_get('rating')));
$vcSort = wz_collection_get('sort', 'featured');
$cities = $types = $events = [];
foreach ($vcAllVenues as $venue) {
    $cities[] = (string)($venue['city'] ?? '');
    $types[] = (string)($venue['venue_type'] ?? 'Celebration venue');
    $events = array_merge($events, $venue['events'] ?? []);
}
$vcOptions = function (array $values, string $first): array {
    $values = array_values(array_unique(array_filter($values)));
    sort($values);
    return ['' => $first] + array_combine($values, $values);
};
$vcVenues = array_values(array_filter($vcAllVenues, function (array $venue) use ($vcQuery, $vcCity, $vcType, $vcEvent, $vcGuests, $vcRooms, $vcMaxPrice, $vcPriceUnit, $vcRating): bool {
    $search = implode(' ', [$venue['name'] ?? '', $venue['city'] ?? '', $venue['locality'] ?? '', $venue['about'] ?? '']);
    if ($vcQuery !== '' && stripos($search, $vcQuery) === false) return false;
    if ($vcCity !== '' && strcasecmp((string)($venue['city'] ?? ''), $vcCity) !== 0) return false;
    if ($vcType !== '' && strcasecmp((string)($venue['venue_type'] ?? 'Celebration venue'), $vcType) !== 0) return false;
    if ($vcEvent !== '' && !in_array($vcEvent, $venue['events'] ?? [], true)) return false;
    if ($vcGuests > 0 && (int)($venue['capacity_max'] ?? 0) < $vcGuests) return false;
    if ($vcRooms > 0 && (int)($venue['rooms'] ?? 0) < $vcRooms) return false;
    if ($vcRating > 0 && (float)($venue['rating'] ?? 0) < $vcRating) return false;
    [$amount, $unit] = wz_collection_price($venue);
    if ($vcMaxPrice > 0 && ($amount === null || $unit !== $vcPriceUnit || $amount > $vcMaxPrice)) return false;
    return true;
}));
usort($vcVenues, function (array $a, array $b) use ($vcSort, $vcPriceUnit): int {
    if ($vcSort === 'capacity') return (int)($b['capacity_max'] ?? 0) <=> (int)($a['capacity_max'] ?? 0);
    if ($vcSort === 'rooms') return (int)($b['rooms'] ?? 0) <=> (int)($a['rooms'] ?? 0);
    if ($vcSort === 'rating') return (float)($b['rating'] ?? 0) <=> (float)($a['rating'] ?? 0);
    if ($vcSort === 'price') {
        [$ap, $au] = wz_collection_price($a);
        [$bp, $bu] = wz_collection_price($b);
        return ($au === $vcPriceUnit && $ap !== null ? $ap : PHP_INT_MAX)
            <=> ($bu === $vcPriceUnit && $bp !== null ? $bp : PHP_INT_MAX);
    }
    return (int)($a['collection_order'] ?? 1000) <=> (int)($b['collection_order'] ?? 1000);
});
$vcHasFilters = $vcQuery !== '' || $vcCity !== '' || $vcType !== '' || $vcEvent !== '' || $vcGuests || $vcRooms || $vcMaxPrice || $vcRating;
$vcHero = '';
foreach ($vcAllVenues as $venue) {
    if (($venue['id'] ?? '') === 'the-gopal-bagh-resort-jaipur') $vcHero = (string)($venue['image'] ?? '');
}
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/css/venue-collection.css?v=20261010">
<main class="vc-page">
    <section class="vc-hero">
        <div class="vc-shell vc-hero-grid">
            <div class="vc-hero-copy">
                <p class="vc-eyebrow"><span></span> THE WEDDINGZA VENUE COLLECTION</p>
                <h1>Some places make<br><em>every moment</em><br>feel extraordinary.</h1>
                <p class="vc-lead">A palace for your wedding. A garden for your Mehendi. A beautiful space for whatever you’re celebrating.</p>
                <div class="vc-hero-actions">
                    <a class="vc-btn" href="#venue-results">Find your setting <span aria-hidden="true">↗</span></a>
                    <a class="vc-link" href="tel:+919785005549">Talk to Weddingza <span aria-hidden="true">↗</span></a>
                </div>
                <div class="vc-hero-note"><span aria-hidden="true">✦</span> Jaipur favourites. Thoughtful spaces. Celebrations of every size.</div>
            </div>
            <div class="vc-hero-art">
                <?php if ($vcHero !== ''): ?>
                    <img class="vc-hero-photo" src="<?= h($vcHero) ?>" alt="The Gopal Bagh & Resort, Jaipur" width="900" height="1100" fetchpriority="high" decoding="async">
                    <span class="vc-art-caption">THE GOPAL BAGH & RESORT · JAIPUR</span>
                <?php else: ?>
                    <div class="vc-arch-scene" aria-hidden="true"><div class="vc-arch"><span>W</span></div></div>
                    <span class="vc-art-caption">BEAUTIFUL BEGINNINGS · JAIPUR</span>
                <?php endif; ?>
                <div class="vc-art-note"><small>THE RIGHT SPACE FOR</small><strong>Your kind of<br><em>celebration.</em></strong><span>Indoor · Outdoor · Stay & celebrate</span></div>
            </div>
        </div>
    </section>

    <section class="vc-shell vc-search" aria-label="Find a venue">
        <form method="get" action="venues.php#venue-results" id="vc-search-form">
            <div class="vc-search-main">
                <label class="vc-field vc-query"><span>VENUE OR NEIGHBOURHOOD</span><input name="q" type="search" maxlength="120" value="<?= h($vcQuery) ?>" placeholder="Try Jagatpura, Hari Van…"></label>
                <?php wz_collection_select('city', 'CITY', $vcOptions($cities, 'All cities'), $vcCity); ?>
                <label class="vc-field"><span>YOUR GUEST LIST</span><input name="guests" type="number" min="0" step="1" value="<?= $vcGuests ?: '' ?>" placeholder="Number of guests"></label>
                <button class="vc-btn" type="submit">Explore venues <span aria-hidden="true">↗</span></button>
            </div>
            <details class="vc-filter-details" <?= $vcType || $vcEvent || $vcRooms || $vcMaxPrice || $vcRating ? 'open' : '' ?>>
                <summary>Refine your search <span aria-hidden="true">+</span></summary>
                <div class="vc-filter-grid">
                    <?php wz_collection_select('type', 'VENUE TYPE', $vcOptions($types, 'All spaces'), $vcType); ?>
                    <?php wz_collection_select('event', 'OCCASION', $vcOptions($events, 'Any celebration'), $vcEvent); ?>
                    <label class="vc-field"><span>MINIMUM ROOMS</span><input name="rooms" type="number" min="0" step="1" value="<?= $vcRooms ?: '' ?>" placeholder="Rooms for your guests"></label>
                    <label class="vc-field"><span>MAXIMUM PUBLISHED PRICE (₹)</span><input name="max_price" type="number" min="0" step="1" value="<?= $vcMaxPrice ?: '' ?>" placeholder="e.g. 1200000"></label>
                    <?php wz_collection_select('price_unit', 'PRICE BASIS', ['function' => 'Per function / package', 'plate' => 'Per plate'], $vcPriceUnit); ?>
                    <?php wz_collection_select('rating', 'MINIMUM RATING', ['' => 'Any rating', '4.5' => '4.5+', '4' => '4+', '3.5' => '3.5+'], $vcRating ? (string)$vcRating : ''); ?>
                </div>
                <p class="vc-small">Price filters use published rates on your selected basis. Guest capacities depend on the event layout.</p>
                <button class="vc-btn vc-btn-small" type="submit">Apply filters ↗</button>
            </details>
            <input type="hidden" name="sort" value="<?= h($vcSort) ?>">
        </form>
    </section>

    <section class="vc-shell vc-results" id="venue-results">
        <div class="vc-results-head">
            <div><p class="vc-eyebrow">FIND A PLACE THAT FEELS LIKE YOU</p><h2><?= count($vcVenues) ?> venues.<br><em>Endless possibilities.</em></h2></div>
            <form method="get" action="venues.php#venue-results" class="vc-sort">
                <?php foreach (['q','city','type','event','guests','rooms','max_price','price_unit','rating'] as $key): ?>
                    <?php if (wz_collection_get($key) !== ''): ?><input type="hidden" name="<?= h($key) ?>" value="<?= h(wz_collection_get($key)) ?>"><?php endif; ?>
                <?php endforeach; ?>
                <?php wz_collection_select('sort', 'SORT BY', ['featured' => 'Weddingza collection', 'capacity' => 'Largest guest capacity', 'rooms' => 'Most guest rooms', 'price' => 'Lowest price on selected basis', 'rating' => 'Highest rated'], $vcSort); ?>
                <button class="vc-link" type="submit">Apply ↗</button>
            </form>
        </div>
        <?php if ($vcHasFilters): ?><div class="vc-active-filters"><span>Showing your filtered collection</span><a href="venues.php#venue-results">Clear all filters ×</a></div><?php endif; ?>
        <div class="vc-grid">
            <?php foreach ($vcVenues as $index => $venue): ?>
                <?php
                $url = wz_collection_detail_url($venue);
                $amenities = $venue['amenities'] ?? $venue['services'] ?? [];
                $capacity = $venue['capacity_label'] ?? (!empty($venue['capacity_max']) ? 'Up to ' . number_format((int)$venue['capacity_max']) : 'Ask venue');
                $image = $venue['image'] ?? 'assets/images/image-fallback.svg';
                ?>
                <article class="vc-card" data-vc-reveal>
                    <a class="vc-card-media <?= !empty($venue['illustrated']) ? 'vc-illustrated' : '' ?>" href="<?= h($url) ?>" aria-label="Explore <?= h($venue['name']) ?>">
                        <img src="<?= h($image) ?>" alt="<?= h(!empty($venue['illustrated']) ? 'Illustrated cover for ' . $venue['name'] : $venue['name']) ?>" width="800" height="540" loading="lazy" decoding="async">
                        <span class="vc-media-type"><?= h($venue['venue_type'] ?? 'Celebration venue') ?></span>
                        <?php if (!empty($venue['illustrated'])): ?><span class="vc-media-caption">ILLUSTRATED VENUE COVER</span><?php endif; ?>
                        <span class="vc-media-open" aria-hidden="true">↗</span>
                    </a>
                    <div class="vc-card-body">
                        <p class="vc-location"><span aria-hidden="true">⌖</span> <?= h($venue['locality'] ?? '') ?> · <?= h($venue['city'] ?? '') ?></p>
                        <h3><a href="<?= h($url) ?>"><?= h($venue['name']) ?></a></h3>
                        <p class="vc-card-summary"><?= h($venue['summary'] ?? substr((string)($venue['about'] ?? ''), 0, 170)) ?></p>
                        <div class="vc-facts"><div><strong><?= h($capacity) ?></strong><span>guest capacity</span></div><div><strong><?= !empty($venue['rooms']) ? number_format((int)$venue['rooms']) : 'Enquire' ?></strong><span>guest rooms</span></div><div><strong><?= !empty($venue['collection_detail']) ? count($venue['spaces'] ?? []) : 'Explore' ?></strong><span>event spaces</span></div></div>
                        <div class="vc-tags"><?php foreach (array_slice($amenities, 0, 3) as $amenity): ?><span><?= h((string)$amenity) ?></span><?php endforeach; ?></div>
                        <?php if (!empty($venue['collection_detail'])): ?>
                            <details class="vc-card-details"><summary>Spaces & venue highlights <span aria-hidden="true">+</span></summary><ul><?php foreach ($venue['spaces'] as $space): ?><li><strong><?= h($space['name']) ?></strong><span><?= h($space['capacity']) ?></span></li><?php endforeach; ?></ul><a class="vc-link" href="<?= h($url) ?>">Read the full venue guide ↗</a></details>
                        <?php endif; ?>
                        <?php if ((int)($venue['reviews'] ?? 0) > 0): ?><p class="vc-small">★ <?= h((string)($venue['rating'] ?? '')) ?> · <?= (int)$venue['reviews'] ?> reviews</p><?php endif; ?>
                        <div class="vc-card-bottom"><div><small><?= !empty($venue['starting_price']) ? 'QUOTED PACKAGE' : 'PLAN YOUR CELEBRATION' ?></small><strong><?= h($venue['price'] ?? 'Request pricing') ?></strong></div><a class="vc-link" href="<?= h($url) ?>">View venue ↗</a></div>
                        <div class="vc-card-actions"><a class="vc-btn vc-call" href="tel:+919785005549" aria-label="Call Weddingza on 9785005549 about <?= h($venue['name']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3 5 4c-2 5 4 13 10 15l4-3-3-4-3 2-4-4 2-3Z"/></svg>Call 9785005549</a><a class="vc-btn vc-btn-outline" href="<?= h($url) ?>#enquire">Enquire <span aria-hidden="true">↗</span></a></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$vcVenues): ?><div class="vc-empty"><p class="vc-eyebrow">LET’S FIND ANOTHER POSSIBILITY</p><h2>No venues match these filters.</h2><p>Try a different guest count, neighbourhood or budget.</p><a class="vc-btn" href="venues.php#venue-results">Reset filters ↗</a></div><?php endif; ?>
        <div class="vc-concierge" data-vc-reveal><div><p class="vc-eyebrow">A LITTLE HELP GOES A LONG WAY</p><h2>Your celebration.<br><em>Our local know-how.</em></h2><p>Tell us your dates, guest list and the kind of space you love. Weddingza can help you explore your options.</p></div><a class="vc-btn vc-btn-light" href="tel:+919785005549">Call Weddingza · 9785005549 ↗</a></div>
    </section>
</main>
<script defer src="assets/js/venue-collection.js?v=20261010"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
