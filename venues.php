<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/vendors.php';
require_once __DIR__ . '/includes/cities.php';
require_once __DIR__ . '/includes/venue-directory.php';

$pageTitle = 'Wedding & Celebration Venues';
$pageDescription = 'Explore wedding lawns, banquet halls, heritage hotels and resorts. Compare guest capacity, rooms and venue photos, then request pricing for your celebration.';
$pageKey = 'venues';
$pageImage = wz_app_url('assets/images/venues/gopal-bagh-resort-banquet.webp');

$filters = [
    'q' => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120, 'UTF-8'),
    'city' => trim((string)($_GET['city'] ?? '')),
    'type' => trim((string)($_GET['type'] ?? '')),
    'event' => trim((string)($_GET['event'] ?? '')),
    'guests' => max(0, (int)($_GET['guests'] ?? 0)),
    'rooms' => max(0, (int)($_GET['rooms'] ?? 0)),
    'max_price' => max(0, (int)($_GET['max_price'] ?? 0)),
    'rating' => max(0, min(5, (float)($_GET['rating'] ?? 0))),
];
$sort = in_array($_GET['sort'] ?? '', ['featured', 'rating', 'price', 'capacity'], true)
    ? (string)$_GET['sort']
    : 'featured';
$city = $filters['city'];
$allVenues = array_values(array_filter(
    wz_public_vendors(),
    fn (array $business): bool => ($business['business_type'] ?? '') === 'venue'
        || ($business['category'] ?? '') === 'Venues'
));
$venues = array_values(array_filter(
    $allVenues,
    fn (array $venue): bool => wz_venue_matches($venue, $filters)
));

usort($venues, function (array $left, array $right) use ($sort): int {
    if ($sort === 'price') {
        $leftPrice = wz_money_number((string)($left['price'] ?? ''));
        $rightPrice = wz_money_number((string)($right['price'] ?? ''));

        // An unpublished quote must not appear as a free venue.
        return ($leftPrice ?: PHP_INT_MAX) <=> ($rightPrice ?: PHP_INT_MAX);
    }

    return match ($sort) {
        'rating' => (float)($right['rating'] ?? 0) <=> (float)($left['rating'] ?? 0),
        'capacity' => wz_venue_capacity($right) <=> wz_venue_capacity($left),
        default => ((int)($right['venue_priority'] ?? 0) <=> (int)($left['venue_priority'] ?? 0))
            ?: ((int)!empty($right['featured']) <=> (int)!empty($left['featured'])),
    };
});

$venueTypes = array_values(array_unique(array_filter(array_map(
    fn (array $venue): string => trim((string)($venue['venue_type'] ?? '')),
    $allVenues
))));
sort($venueTypes);
$cityOptions = array_column(wz_city_catalogue(), 'name');

foreach ($allVenues as $listedVenue) {
    $listedCity = trim((string)($listedVenue['city'] ?? ''));

    if ($listedCity !== '' && !in_array($listedCity, $cityOptions, true)) {
        $cityOptions[] = $listedCity;
    }
}

sort($cityOptions, SORT_NATURAL | SORT_FLAG_CASE);
$total = count($venues);
$pageSize = 12;
$pages = max(1, (int)ceil($total / $pageSize));
$page = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$venues = array_slice($venues, ($page - 1) * $pageSize, $pageSize);
$hasFilters = (bool)array_filter($filters);
$advancedOpen = $filters['type'] !== '' || $filters['event'] !== ''
    || $filters['rooms'] > 0 || $filters['max_price'] > 0 || $filters['rating'] > 0;

require __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="assets/css/venues.css?v=2026-10-09-1">

<main class="venue-directory">
    <section class="venue-directory-hero">
        <div class="venue-container venue-hero-layout">
            <div class="venue-hero-copy" data-venue-reveal>
                <span class="venue-kicker">The venue collection</span>
                <h1>A setting for<br>
                    <em>every celebration.</em>
                </h1>
                <p>Heritage stays, open lawns and beautiful banquets. Find a place that fits your guests, your plans and you.</p>
                <a class="venue-text-link" href="#venue-results">Explore the collection <span aria-hidden="true">↓</span>
                </a>
            </div>
            <figure class="venue-hero-image" data-venue-reveal>
                <img src="assets/images/venues/gopal-bagh-resort-banquet.webp" alt="Grand banquet celebration setting at The Gopal Bagh & Resort in Jaipur" width="1080" height="789" fetchpriority="high" decoding="async">
                <figcaption>
                    <span>In the collection</span>
                    <strong>The Gopal Bagh & Resort</strong>
                    <small>Mansarovar · Jaipur</small></figcaption>
            </figure>
        </div>
    </section>

    <section class="venue-container venue-search-section" aria-label="Venue search">
        <form id="venueFilterForm" class="venue-search-form" method="get" action="venues.php#venue-results">
            <div class="venue-search-primary">
                <label class="venue-field venue-search-query">
                    <span>Venue or neighbourhood</span>
                    <input type="search" name="q" value="<?= h($filters['q']) ?>" placeholder="Try Rudra Vilas or Mansarovar" maxlength="120">
                </label>
                <label class="venue-field">
                    <span>City</span>
                    <select name="city" aria-label="City">
                        <option value="">All cities</option>
                        <?php foreach ($cityOptions as $cityOption): ?>
                            <option value="<?= h($cityOption) ?>" <?= $filters['city'] === $cityOption ? 'selected' : '' ?>><?= h($cityOption) ?></option>
                        <?php endforeach; ?>

                    </select>
                </label>
                <label class="venue-field">
                    <span>Guests</span>
                    <input type="number" name="guests" min="0" step="1" value="<?= $filters['guests'] ?: '' ?>" placeholder="How many guests?">
                </label>
                <button class="venue-button venue-find-button" type="submit">Find venues <span aria-hidden="true">↗</span>
                </button>
            </div>
            <details class="venue-advanced-filters" <?= $advancedOpen ? 'open' : '' ?>>
                <summary>Capacity & more filters <span aria-hidden="true">+</span>
                </summary>
                <div class="venue-search-secondary">
                    <label class="venue-field">
                        <span>Venue type</span>
                        <select name="type" aria-label="Venue type">
                            <option value="">All venue types</option>
                            <?php foreach ($venueTypes as $venueType): ?>
                                <option value="<?= h($venueType) ?>" <?= $filters['type'] === $venueType ? 'selected' : '' ?>><?= h($venueType) ?></option>
                            <?php endforeach; ?>

                        </select>
                    </label>
                    <label class="venue-field">
                        <span>Occasion</span>
                        <select name="event" aria-label="Occasion">
                            <option value="">Any occasion</option>
                            <?php foreach (wz_data('event_types') as $occasion): ?>
                                <option value="<?= h((string)$occasion['name']) ?>" <?= $filters['event'] === $occasion['name'] ? 'selected' : '' ?>><?= h((string)$occasion['name']) ?></option>
                            <?php endforeach; ?>

                        </select>
                    </label>
                    <label class="venue-field">
                        <span>Rooms</span>
                        <input type="number" name="rooms" min="0" step="1" value="<?= $filters['rooms'] ?: '' ?>" placeholder="Minimum rooms">
                    </label>
                    <label class="venue-field">
                        <span>Max starting price</span>
                        <input type="number" name="max_price" min="0" step="1" value="<?= $filters['max_price'] ?: '' ?>" placeholder="Published rates only">
                    </label>
                    <label class="venue-field">
                        <span>Min rating</span>
                        <select name="rating" aria-label="Min rating">
                            <option value="">Any rating</option>
                            <?php foreach ([4.5, 4, 3.5, 3] as $minimumRating): ?>
                                <option value="<?= $minimumRating ?>" <?= $filters['rating'] === (float)$minimumRating ? 'selected' : '' ?>><?= $minimumRating ?>+</option>
                            <?php endforeach; ?>

                        </select>
                    </label>
                </div>
                <p class="venue-filter-note">Capacity depends on your event layout. Price filters include venues with published starting rates.</p>
            </details>
            <input type="hidden" name="sort" value="<?= h($sort) ?>">
            <?php if ($hasFilters): ?>
                <a class="venue-clear-filters" href="venues.php#venue-results">Clear all filters <span aria-hidden="true">×</span>
                </a>
            <?php endif; ?>

        </form>
    </section>

    <section class="venue-container venue-results-section" id="venue-results">
        <div class="venue-results-head">
            <div>
                <span class="venue-kicker"><?= $hasFilters ? 'Your venue matches' : 'Places to come together' ?></span>
                <h2><?= $total ?> venue<?= $total === 1 ? '' : 's' ?><?= $city !== '' ? ' in ' . h($city) : ' to explore' ?></h2>
            </div>
            <form class="venue-sort-form" method="get" action="venues.php#venue-results">
                <?php foreach ($filters as $filterName => $filterValue): ?>
                    <?php if ($filterValue !== '' && $filterValue !== 0 && $filterValue !== 0.0): ?>
                        <input type="hidden" name="<?= h($filterName) ?>" value="<?= h((string)$filterValue) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>

                <label class="venue-field">
                    <span>Sort results</span>
                    <select name="sort" aria-label="Sort results" data-venue-sort>
                        <?php foreach (['featured' => 'Wedding Za collection', 'rating' => 'Highest rated', 'price' => 'Lowest published price', 'capacity' => 'Largest capacity'] as $sortValue => $sortLabel): ?>
                            <option value="<?= h($sortValue) ?>" <?= $sort === $sortValue ? 'selected' : '' ?>><?= h($sortLabel) ?></option>
                        <?php endforeach; ?>

                    </select>
                </label>
                <button class="venue-sort-apply" type="submit">Apply sort</button>
            </form>
        </div>
        <div class="venue-results-list">
            <?php foreach ($venues as $venue): ?>
                <?php wz_venue_listing_card($venue); ?>
            <?php endforeach; ?>

        </div>
        <?php if (!$venues): ?>
            <div class="venue-empty-state">
                <span class="venue-kicker">A different starting point</span>
                <h3>No exact venue match yet.</h3>
                <p>Try a nearby city or adjust your guest count, rooms or other filters.</p>
                <a class="venue-button" href="venues.php#venue-results">See all venues <span aria-hidden="true">↗</span>
                </a>
            </div>
        <?php endif; ?>
        <?php if ($pages > 1): ?>
            <nav class="venue-pagination" aria-label="Venue results pages">
                <?php for ($pageNumber = 1; $pageNumber <= $pages; $pageNumber++): ?>
                    <a href="venues.php?<?= h(http_build_query(array_merge($filters, ['sort' => $sort, 'page' => $pageNumber]))) ?>#venue-results" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                <?php endfor; ?>

            </nav>
        <?php endif; ?>

        <div class="venue-help-strip">
            <div>
                <span class="venue-kicker">A little help choosing</span>
                <h2>Start with a conversation.</h2>
                <p>Tell us your date, guest count and what you have in mind.</p>
            </div>
            <a class="venue-button venue-button-outline" href="contact.php">Talk to Wedding Za <span aria-hidden="true">↗</span>
            </a>
        </div>
    </section>
</main>

<script defer src="assets/js/venues.js?v=2026-10-09-1">
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
