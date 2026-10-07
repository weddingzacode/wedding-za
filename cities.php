<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/cities.php';

$query = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';
$letter = is_string($_GET['letter'] ?? null) ? $_GET['letter'] : '';
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
$directory = wz_city_directory(wz_city_catalogue(), $query, $letter, $page);
$pageTitle = 'Explore all cities';
$pageDescription = 'Find your celebration destination. Search Wedding Za city guides and explore local venues, vendors and planning ideas.';
$pageKey = 'cities';
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/css/cities.css?v=2026-10-07">
<noscript><style>.vision-preloader{display:none}</style></noscript>
<main class="city-directory">
    <section class="container city-directory-intro">
        <a class="city-back-link" href="index.php#cities">← Back to Wedding Za</a>
        <div class="city-directory-heading">
            <div>
                <span class="eyebrow">THE WEDDING ZA CITY EDIT</span>
                <h1>Find your <em>somewhere.</em></h1>
                <p>Start with a place. Discover its city guide, then shape a celebration around you.</p>
            </div>
            <div class="city-directory-total">
                <strong><?= $directory['total_cities'] ?></strong>
                <span><?= $directory['total_cities'] === 1 ? 'CITY TO EXPLORE' : 'CITIES TO EXPLORE' ?></span>
            </div>
        </div>
        <form action="cities.php#city-results" method="get" class="city-search city-directory-search" role="search" aria-label="Find a city">
            <div class="city-search-field">
                <label for="directoryCitySearch">Search by city name</label>
                <input type="search" id="directoryCitySearch" name="q" value="<?= h($directory['query']) ?>" placeholder="Try Jaipur, Mumbai or your city…" maxlength="120">
            </div>
            <div class="city-search-letter">
                <label for="directoryCityLetter">Starts with</label>
                <select id="directoryCityLetter" name="letter">
                    <option value="">All letters</option>
                    <?php foreach ($directory['letters'] as $initial): ?>
                        <option value="<?= h($initial) ?>" <?= $directory['letter'] === $initial ? 'selected' : '' ?>><?= h($initial) ?></option>
                    <?php endforeach; ?>
                    <?php if ($directory['letter'] !== '' && !in_array($directory['letter'], $directory['letters'], true)): ?>
                        <option value="<?= h($directory['letter']) ?>" selected><?= h($directory['letter']) ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="city-action">Find a city <span aria-hidden="true">↗</span></button>
        </form>
    </section>

    <section class="container city-directory-results" id="city-results" aria-labelledby="cityResultsHeading">
        <div class="city-directory-results-head">
            <div>
                <h2 id="cityResultsHeading"><?= $directory['query'] !== '' || $directory['letter'] !== '' ? 'Your city search' : 'All cities' ?></h2>
                <p class="city-result-count">
                    <?php if ($directory['total']): ?>
                        Showing <?= $directory['start'] ?>–<?= $directory['end'] ?> of <?= $directory['total'] ?> <?= $directory['total'] === 1 ? 'city' : 'cities' ?> · A–Z
                    <?php else: ?>
                        No cities found<?= $directory['query'] !== '' ? ' for “' . h($directory['query']) . '”' : '' ?>.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($directory['query'] !== '' || $directory['letter'] !== ''): ?>
                <a class="city-clear-link" href="cities.php#city-results">Clear filters <span aria-hidden="true">↗</span></a>
            <?php endif; ?>
        </div>

        <?php if ($directory['items']): ?>
            <div class="city-directory-grid">
                <?php foreach ($directory['items'] as $city): ?>
                    <a class="city-directory-card" href="city.php?city=<?= rawurlencode($city['name']) ?>">
                        <span class="city-directory-initial" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($city['name'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                        <h3><?= h($city['name']) ?></h3>
                        <p><?= h($city['description']) ?></p>
                        <span class="city-directory-card-link">Open city guide <span aria-hidden="true">↗</span></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="city-directory-empty">
                <h3>Let’s find another place.</h3>
                <p>Try a shorter city name or clear the letter filter. If your city is not listed yet, tell us where you’re planning.</p>
                <div>
                    <a class="city-action" href="cities.php#city-results">Explore all cities <span aria-hidden="true">↗</span></a>
                    <a class="city-clear-link" href="contact.php">Ask about your city <span aria-hidden="true">↗</span></a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($directory['pages'] > 1): ?>
            <nav class="city-pagination" aria-label="City directory pages">
                <?php if ($directory['page'] > 1): ?>
                    <a href="<?= h(wz_city_directory_url($directory['query'], $directory['letter'], $directory['page'] - 1)) ?>" rel="prev">← Previous</a>
                <?php endif; ?>
                <span>Page <?= $directory['page'] ?> of <?= $directory['pages'] ?></span>
                <?php if ($directory['page'] < $directory['pages']): ?>
                    <a href="<?= h(wz_city_directory_url($directory['query'], $directory['letter'], $directory['page'] + 1)) ?>" rel="next">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
