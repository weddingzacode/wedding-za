<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/vendors.php';
require_once __DIR__ . '/includes/venue-collection.php';

$vcId = wz_collection_get('id');
$vcVenue = null;
foreach (wz_collection_venues(wz_public_vendors()) as $vcCandidate) {
    if ((string)$vcCandidate['id'] === $vcId) {
        $vcVenue = $vcCandidate;
        break;
    }
}
if (!$vcVenue) {
    http_response_code(404);
    $pageTitle = 'Venue not found | Weddingza';
    $pageKey = 'venues';
    require __DIR__ . '/includes/header.php';
    ?>
    <link rel="stylesheet" href="assets/css/venue-collection.css?v=20261010">
    <main class="vc-page"><div class="vc-shell vc-empty"><h1>Venue not found.</h1><p>Explore the collection to find another setting.</p><a class="vc-btn" href="venues.php">Browse venues ↗</a></div></main>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}
if (empty($vcVenue['collection_detail'])) {
    header('Location: vendor.php?id=' . rawurlencode((string)$vcVenue['id']));
    exit;
}
$pageTitle = $vcVenue['name'] . ', Jaipur | Weddingza';
$pageDescription = $vcVenue['summary'];
$pageKey = 'venues';
require __DIR__ . '/includes/header.php';
$vcMapQuery = $vcVenue['name'] . ', ' . $vcVenue['locality'] . ', ' . $vcVenue['city'];
?>
<link rel="stylesheet" href="assets/css/venue-collection.css?v=20261010">
<main class="vc-page">
    <div class="vc-shell">
        <section class="vc-detail-heading">
            <nav class="vc-breadcrumb" aria-label="Breadcrumb"><a href="index.php">Home</a> / <a href="venues.php">Venues</a> / <?= h($vcVenue['name']) ?></nav>
            <p class="vc-eyebrow"><?= h($vcVenue['venue_type']) ?> · <?= h($vcVenue['city']) ?></p>
            <h1><?= h($vcVenue['name']) ?></h1>
            <p class="vc-location"><?= h($vcVenue['locality']) ?> · Jaipur, Rajasthan</p>
            <div class="vc-hero-actions"><a class="vc-btn" href="tel:+919785005549">Call Weddingza · 9785005549 ↗</a><a class="vc-link" href="#enquire">Ask about your celebration ↗</a></div>
        </section>
        <section class="vc-detail-hero" aria-label="Venue highlights">
            <img src="<?= h($vcVenue['image']) ?>" alt="<?= h(!empty($vcVenue['illustrated']) ? 'Illustrated cover for ' . $vcVenue['name'] : $vcVenue['name']) ?>" width="800" height="540" decoding="async" fetchpriority="high">
            <div class="vc-detail-hero-copy">
                <p class="vc-eyebrow"><?= h($vcVenue['eyebrow']) ?></p>
                <h2>A setting for<br><em>your special moments.</em></h2>
                <p class="vc-card-summary"><?= h($vcVenue['summary']) ?></p>
                <div class="vc-facts"><div><strong><?= h($vcVenue['capacity_label']) ?></strong><span>guest capacity</span></div><div><strong><?= (int)$vcVenue['rooms'] ?></strong><span>guest rooms</span></div><div><strong><?= count($vcVenue['spaces']) ?></strong><span>event spaces</span></div></div>
                <?php if (!empty($vcVenue['illustrated'])): ?><p class="vc-small">Illustrated venue cover. Ask Weddingza for venue photographs.</p><?php endif; ?>
            </div>
        </section>
        <div class="vc-detail-layout">
            <div>
                <nav class="vc-anchor-nav" aria-label="Venue sections"><a href="#about">About</a><a href="#spaces">Event spaces</a><a href="#amenities">Amenities</a><a href="#location">Location</a><a href="#enquire">Enquire</a></nav>
                <section class="vc-detail-section" id="about"><h2>About the venue</h2><p><?= h($vcVenue['about']) ?></p></section>
                <section class="vc-detail-section" id="spaces">
                    <h2>One celebration.<br><em>Many beautiful spaces.</em></h2>
                    <div class="vc-space-grid">
                        <?php foreach ($vcVenue['spaces'] as $space): ?>
                            <article class="vc-space"><h3><?= h($space['name']) ?></h3><strong><?= h($space['capacity']) ?></strong><p><?= h($space['description']) ?></p></article>
                        <?php endforeach; ?>
                    </div>
                    <p class="vc-small">Confirm capacity for your seating plan, décor and function with Weddingza.</p>
                </section>
                <section class="vc-detail-section" id="amenities">
                    <h2>Stay, celebrate & unwind.</h2>
                    <div class="vc-tags"><?php foreach ($vcVenue['amenities'] as $amenity): ?><span><?= h($amenity) ?></span><?php endforeach; ?></div>
                    <?php foreach ($vcVenue['sections'] as $section): ?>
                        <div class="vc-detail-section"><h3><?= h($section['title']) ?></h3><p><?= h($section['text']) ?></p></div>
                    <?php endforeach; ?>
                </section>
                <section class="vc-detail-section"><h2>Perfect for your plans.</h2><div class="vc-tags"><?php foreach ($vcVenue['events'] as $occasion): ?><span><?= h($occasion) ?></span><?php endforeach; ?></div>
                    <?php if (!empty($vcVenue['policies'])): ?><div class="vc-policy"><?php foreach ($vcVenue['policies'] as $key => $value): ?><div><strong><?= h($key) ?></strong><span><?= h($value) ?></span></div><?php endforeach; ?></div><?php endif; ?>
                </section>
                <section class="vc-detail-section" id="location"><h2>Find your way here.</h2><p><?= h($vcVenue['locality']) ?>, <?= h($vcVenue['city']) ?>, Rajasthan.</p><a class="vc-link" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($vcMapQuery) ?>" target="_blank" rel="noopener noreferrer">Search on Google Maps ↗</a></section>
            </div>
            <aside class="vc-enquiry" id="enquire" aria-label="Venue enquiry">
                <p class="vc-eyebrow">LET’S PLAN YOUR CELEBRATION</p><h2><?= h($vcVenue['price']) ?></h2>
                <?php if ($vcVenue['starting_price'] !== null): ?><p class="vc-small">Quoted for one function. Confirm dates and package inclusions with Weddingza.</p><?php else: ?><p class="vc-small">Get availability and a quote for your dates, guests and functions.</p><?php endif; ?>
                <a class="vc-btn vc-call" href="tel:+919785005549">Call 9785005549 ↗</a>
                <form class="form-stack" data-async action="api/lead.php" method="post">
                    <input type="hidden" name="type" value="vendor-enquiry">
                    <input type="hidden" name="vendor" value="<?= h($vcVenue['name']) ?>">
                    <input class="hp-field" name="company_website" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <label class="vc-field"><span>YOUR NAME</span><input name="name" autocomplete="name" maxlength="120" required></label>
                    <label class="vc-field"><span>PHONE / WHATSAPP</span><input name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="25" required></label>
                    <label class="vc-field"><span>YOUR OCCASION</span><select name="topic" required><option value="">Choose your celebration</option><?php foreach ($vcVenue['events'] as $occasion): ?><option><?= h($occasion) ?></option><?php endforeach; ?></select></label>
                    <label class="vc-field"><span>EVENT DATE</span><input name="event_date" type="date" min="<?= date('Y-m-d') ?>"></label>
                    <input type="hidden" name="city" value="<?= h($vcVenue['city']) ?>">
                    <label class="vc-field"><span>TELL US YOUR PLANS</span><textarea name="message" maxlength="2000" placeholder="Guest count, rooms, functions and budget…"></textarea></label>
                    <button class="vc-btn" type="submit">Request pricing & availability ↗</button>
                    <div class="success-box" role="status" aria-live="polite"></div>
                </form>
                <p class="vc-small">Weddingza will help you check availability and arrangements for this venue.</p>
            </aside>
        </div>
    </div>
</main>
<script defer src="assets/js/venue-collection.js?v=20261010"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
