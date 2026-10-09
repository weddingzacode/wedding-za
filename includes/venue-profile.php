<?php

$venuePhotos = wz_venue_photos($v);
$venueCapacity = wz_venue_capacity($v);
$venueRooms = (int)($v['rooms'] ?? 0);
$venueEvents = $v['events'] ?? [];
$venueAddress = trim((string)($v['address'] ?? ''));
$venueMapQuery = $venueAddress !== ''
    ? (string)$v['name'] . ', ' . $venueAddress
    : (string)$v['name'] . ', ' . (string)$v['city'];
?>

<link rel="stylesheet" href="assets/css/venues.css?v=2026-10-09-1">

<main class="venue-profile">
    <section class="venue-container venue-profile-opening">
        <nav class="venue-breadcrumbs" aria-label="Breadcrumb">
            <a href="venues.php">Venues</a>
            <span aria-hidden="true">/</span>
            <a href="venues.php?city=<?= urlencode((string)$v['city']) ?>"><?= h((string)$v['city']) ?></a>
            <span aria-hidden="true">/</span>
            <span><?= h((string)$v['name']) ?></span>
        </nav>
        <div class="venue-profile-title">
            <div>
                <span class="venue-kicker"><?= h((string)($v['venue_type'] ?? 'Celebration venue')) ?></span>
                <h1><?= h((string)$v['name']) ?></h1>
                <p class="venue-location"><?= h((string)($v['locality'] ?? '')) ?> · <?= h((string)$v['city']) ?></p>
            </div>
            <div class="venue-profile-actions">
                <button class="venue-button venue-button-outline heart-btn-static" type="button" data-shortlist="<?= h((string)$v['id']) ?>" aria-label="Save <?= h((string)$v['name']) ?>" aria-pressed="false">♡ Save to shortlist</button>
                <button class="venue-compare" type="button" data-compare-venue="<?= h((string)$v['id']) ?>" data-compare-name="<?= h((string)$v['name']) ?>" aria-label="Compare <?= h((string)$v['name']) ?>" aria-pressed="false">Compare <span aria-hidden="true">+</span>
                </button>
            </div>
        </div>
        <div class="venue-photo-mosaic" aria-label="Venue preview photos">
            <?php foreach (array_slice($venuePhotos, 0, 3) as $photoIndex => $photo): ?>
                <a href="<?= h((string)$photo['src']) ?>" class="venue-mosaic-photo" data-venue-photo="<?= $photoIndex ?>" aria-label="View <?= h((string)$photo['caption']) ?>" target="_blank" rel="noopener">
                    <img src="<?= h((string)$photo['src']) ?>" alt="<?= h((string)$photo['caption']) ?>" width="<?= (int)($photo['width'] ?? 1200) ?>" height="<?= (int)($photo['height'] ?? 900) ?>" <?= $photoIndex === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
                </a>
            <?php endforeach; ?>

            <a class="venue-gallery-open" href="#gallery" data-venue-photo="0">View all <?= count($venuePhotos) ?> photos <span aria-hidden="true">↗</span>
            </a>
        </div>
        <div class="venue-profile-summary">
            <div>
                <strong><?= $venueCapacity > 0 ? number_format($venueCapacity) : 'Ask venue' ?></strong>
                <span><?= $venueCapacity > 0 ? 'guests, up to' : 'guest capacity' ?></span>
            </div>
            <div>
                <strong><?= $venueRooms > 0 ? $venueRooms : 'Ask venue' ?></strong>
                <span><?= $venueRooms > 0 ? 'guest rooms' : 'room details' ?></span>
            </div>
            <div>
                <strong><?= h((string)($v['venue_type'] ?? 'Venue')) ?></strong>
                <span>the setting</span>
            </div>
            <div>
                <a href="#enquire">Request pricing <span aria-hidden="true">↗</span>
                </a>
                <span>for your date & plans</span>
            </div>
        </div>
    </section>

    <div class="venue-container venue-profile-layout">
        <div class="venue-profile-main">
            <nav class="venue-profile-tabs" aria-label="Venue details">
                <a href="#overview">Overview</a>
                <a href="#spaces">Event spaces</a>
                <?php if ($venueRooms > 0): ?>
                    <a href="#stay">Stay</a>
                <?php endif; ?>

                <a href="#gallery">Gallery</a>
                <a href="#location">Location</a>
                <?php if ($businessUserId > 0): ?>
                    <a href="#availability">Availability</a>
                <?php endif; ?>

                <a href="#venue-reviews">Reviews</a>
            </nav>
            <section class="venue-detail-section" id="overview" data-venue-reveal>
                <span class="venue-kicker">A little about the place</span>
                <h2>The setting for your story.</h2>
                <p><?= h((string)$v['about']) ?></p>
                <?php if ($venueEvents): ?>
                    <div class="venue-occasion-links" aria-label="Celebrations at this venue">
                        <?php foreach ($venueEvents as $venueEvent): ?>
                            <a href="venues.php?<?= h(http_build_query(['city' => (string)$v['city'], 'event' => (string)$venueEvent])) ?>#venue-results"><?= h((string)$venueEvent) ?></a>
                        <?php endforeach; ?>

                    </div>
                <?php endif; ?>

            </section>

            <section class="venue-detail-section" id="spaces" data-venue-reveal>
                <span class="venue-kicker">Room to celebrate</span>
                <h2>Find your event space.</h2>
                <?php if (!empty($v['event_spaces'])): ?>
                    <div class="venue-spaces-grid">
                        <?php foreach ($v['event_spaces'] as $space): ?>
                            <article class="venue-space-card">
                                <div>
                                    <span class="venue-space-type"><?= h((string)$space['type']) ?></span>
                                    <strong><?= !empty($space['capacity']) ? 'Up to ' . number_format((int)$space['capacity']) . ' guests' : 'Capacity on request' ?></strong>
                                </div>
                                <h3><?= h((string)$space['name']) ?></h3>
                                <p><?= h((string)$space['description']) ?></p>
                            </article>
                        <?php endforeach; ?>

                    </div>
                <?php else: ?>

                    <div class="venue-card-amenities">
                        <?php foreach ($v['spaces'] ?? $v['services'] ?? [] as $space): ?>
                            <span><?= h((string)$space) ?></span>
                        <?php endforeach; ?>

                    </div>
                    <p>Ask the venue about available spaces, capacity and the layout for your celebration.</p>
                <?php endif; ?>

                <p class="venue-detail-note">Confirm the capacity for your selected space, seating and event layout when you enquire.</p>
            </section>
            <?php if ($venueRooms > 0): ?>
                <section class="venue-detail-section" id="stay" data-venue-reveal>
                    <span class="venue-kicker">Make a stay of it</span>
                    <h2><?= $venueRooms ?> rooms. Everyone closer.</h2>
                    <p><?= h((string)($v['stay_description'] ?? 'Ask the venue about room types, availability and accommodation packages for your guests.')) ?></p>
                </section>
            <?php endif; ?>
            <?php if (!empty($v['amenities'])): ?>
                <section class="venue-detail-section" data-venue-reveal>
                    <span class="venue-kicker">The thoughtful extras</span>
                    <h2>Amenities & facilities.</h2>
                    <ul class="venue-amenities-list">
                        <?php foreach ($v['amenities'] as $amenity): ?>
                            <li>
                                <span aria-hidden="true">✓</span> <?= h((string)$amenity) ?>
                            </li>
                        <?php endforeach; ?>

                    </ul>
                </section>
            <?php endif; ?>

            <section class="venue-detail-section" id="gallery" data-venue-reveal>
                <div class="venue-detail-heading">
                    <div>
                        <span class="venue-kicker">A closer look</span>
                        <h2>Explore the gallery.</h2>
                    </div>
                    <span><?= count($venuePhotos) ?> photos</span>
                </div>
                <div class="venue-full-gallery">
                    <?php foreach ($venuePhotos as $photoIndex => $photo): ?>
                        <a href="<?= h((string)$photo['src']) ?>" data-venue-photo="<?= $photoIndex ?>" aria-label="View <?= h((string)$photo['caption']) ?>" target="_blank" rel="noopener">
                            <figure>
                                <img src="<?= h((string)$photo['src']) ?>" alt="<?= h((string)$photo['caption']) ?>" width="<?= (int)($photo['width'] ?? 1200) ?>" height="<?= (int)($photo['height'] ?? 900) ?>" loading="lazy" decoding="async">
                                <figcaption><?= h((string)$photo['caption']) ?></figcaption>
                            </figure>
                        </a>
                    <?php endforeach; ?>

                </div>
            </section>

            <section class="venue-detail-section" id="location" data-venue-reveal>
                <span class="venue-kicker">Getting here</span>
                <h2><?= h((string)$v['city']) ?>, with everyone in reach.</h2>
                <address><?= h($venueAddress !== '' ? $venueAddress : trim((string)($v['locality'] ?? '') . ', ' . (string)$v['city'], ', ')) ?></address>
                <?php if (!empty($v['location_notes'])): ?>
                    <dl class="venue-travel-details">
                        <?php foreach ($v['location_notes'] as $travel): ?>
                            <div>
                                <dt><?= h((string)$travel['label']) ?></dt>
                                <dd><?= h((string)$travel['value']) ?></dd>
                            </div>
                        <?php endforeach; ?>

                    </dl>
                <?php endif; ?>

                <a class="venue-button venue-button-outline" href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($venueMapQuery) ?>" target="_blank" rel="noopener">Open in Google Maps <span aria-hidden="true">↗</span>
                </a>
            </section>
            <?php if ($businessUserId > 0): ?>
                <section class="venue-detail-section" id="availability" data-venue-reveal>
                    <span class="venue-kicker">Dates to consider</span>
                    <h2>Upcoming calendar status.</h2>
                    <?php if ($availability): ?>
                        <dl class="venue-travel-details">
                            <?php foreach ($availability as $slot): ?>
                                <div>
                                    <dt><?= h(date('d M Y', strtotime((string)$slot['availability_date']))) ?></dt>
                                    <dd><?= h(ucfirst((string)$slot['status'])) ?></dd>
                                </div>
                            <?php endforeach; ?>

                        </dl>
                    <?php else: ?>

                        <p>Ask the venue to confirm availability for your preferred date.</p>
                    <?php endif; ?>

                </section>
            <?php endif; ?>

            <section class="venue-detail-section" id="venue-reviews" data-venue-reveal>
                <span class="venue-kicker">Guest experiences</span>
                <h2>Reviews & recommendations.</h2>
                <?php if ((int)($v['reviews'] ?? 0) > 0): ?>
                    <p>★ <?= h((string)$v['rating']) ?> · <?= (int)$v['reviews'] ?> reviews</p>
                <?php endif; ?>
                <?php foreach ($reviews as $review): ?>
                    <article class="venue-review-card">
                        <strong><?= str_repeat('★', (int)$review['rating']) ?> <?= h((string)$review['reviewer_name']) ?></strong>
                        <p><?= h((string)$review['body']) ?></p>
                        <?php if (!empty($review['business_reply'])): ?>
                            <p>
                                <strong>Venue reply:</strong> <?= h((string)$review['business_reply']) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$reviews): ?>
                    <p>No published guest reviews yet.</p>
                <?php endif; ?>
                <?php if ($businessUserId > 0 && wz_is_logged_in() && wz_role() === 'host'): ?>
                    <?php if ($reviewMessage !== ''): ?>
                        <p role="status"><?= h($reviewMessage) ?></p>
                    <?php endif; ?>

                    <form class="venue-review-form" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="review">
                        <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                        <label class="venue-field">
                            <span>Your rating</span>
                            <select name="rating" required>
                                <option value="">Choose a rating</option>
                                <?php for ($stars = 5; $stars >= 1; $stars--): ?>
                                    <option value="<?= $stars ?>"><?= $stars ?> stars</option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label class="venue-field">
                            <span>Review title</span>
                            <input name="title" maxlength="160">
                        </label>
                        <label class="venue-field">
                            <span>Your experience</span>
                            <textarea name="body" rows="4" required></textarea>
                        </label>
                        <label class="venue-field">
                            <span>Review photo (optional)</span>
                            <input type="file" name="review_photo" accept="image/jpeg,image/png,image/webp">
                        </label>
                        <button class="venue-button" type="submit">Submit review <span aria-hidden="true">↗</span>
                        </button>
                    </form>
                <?php elseif ($businessUserId > 0 && !wz_is_logged_in()): ?>
                    <a class="venue-text-link" href="login.php?role=host">Sign in to share a review <span aria-hidden="true">↗</span>
                    </a>
                <?php endif; ?>

            </section>
        </div>

        <aside class="venue-enquiry-card" id="enquire" aria-labelledby="venueEnquiryTitle">
            <span class="venue-kicker">Let’s make a plan</span>
            <h2 id="venueEnquiryTitle"><?= wz_money_number((string)$v['price']) > 0 ? h((string)$v['price']) : 'Your celebration,<br><em>your quote.</em>' ?></h2>
            <p>Ask about pricing, spaces and availability for your date.</p>
            <form class="venue-enquiry-form" data-async action="api/lead.php" method="post">
                <input type="hidden" name="type" value="vendor-enquiry">
                <input type="hidden" name="vendor" value="<?= h((string)$v['name']) ?>">
                <input type="hidden" name="category" value="Venues">
                <input type="hidden" name="city" value="<?= h((string)$v['city']) ?>">
                <input class="hp-field" name="company_website" tabindex="-1" autocomplete="off" aria-hidden="true">
                <label class="venue-field">
                    <span>Your name</span>
                    <input name="name" autocomplete="name" maxlength="120" required>
                </label>
                <label class="venue-field">
                    <span>Phone / WhatsApp</span>
                    <input type="tel" name="phone" autocomplete="tel" maxlength="80" required>
                </label>
                <label class="venue-field">
                    <span>Email (optional)</span>
                    <input type="email" name="email" autocomplete="email" maxlength="180">
                </label>
                <div class="venue-enquiry-row">
                    <label class="venue-field">
                        <span>Occasion</span>
                        <select name="topic" aria-label="Occasion" required>
                            <option value="">Choose occasion</option>
                            <?php foreach ($venueEvents as $venueEvent): ?>
                                <option value="<?= h((string)$venueEvent) ?>"><?= h((string)$venueEvent) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="venue-field">
                        <span>Event date</span>
                        <input type="date" name="event_date">
                    </label>
                </div>
                <label class="venue-field">
                    <span>Your plans</span>
                    <textarea name="message" rows="3" maxlength="1500" placeholder="Guest count, rooms, spaces and anything else on your mind…"></textarea>
                </label>
                <button class="venue-button venue-enquiry-submit" type="submit">Request pricing & availability <span aria-hidden="true">↗</span>
                </button>
                <div class="success-box" role="status" aria-live="polite">
                </div>
                <small class="venue-form-note">Wedding Za will receive your enquiry. Availability and rates are confirmed before a booking is made.</small>
            </form>
        </aside>
    </div>
    <section class="venue-container venue-profile-more">
        <span class="venue-kicker">Keep exploring</span>
        <h2>Find the place that feels right.</h2>
        <a class="venue-button venue-button-outline" href="venues.php?city=<?= urlencode((string)$v['city']) ?>#venue-results">More venues in <?= h((string)$v['city']) ?> <span aria-hidden="true">↗</span>
        </a>
    </section>
</main>

<dialog class="venue-lightbox" id="venuePhotoDialog" aria-label="Venue photo gallery" data-lenis-prevent>
    <div class="venue-lightbox-top">
        <strong><?= h((string)$v['name']) ?></strong>
        <button type="button" data-venue-gallery-close aria-label="Close gallery" autofocus>Close <span aria-hidden="true">×</span>
        </button>
    </div>
    <figure>
        <img data-venue-gallery-image alt="">
        <figcaption data-venue-gallery-caption></figcaption>
    </figure>
    <div class="venue-lightbox-controls">
        <button type="button" data-venue-gallery-prev aria-label="Previous photo">← Previous</button>
        <span data-venue-gallery-count aria-live="polite"></span>
        <button type="button" data-venue-gallery-next aria-label="Next photo">Next →</button>
    </div>
    <div class="venue-lightbox-thumbs">
        <?php foreach ($venuePhotos as $photoIndex => $photo): ?>
            <button type="button" data-venue-gallery-index="<?= $photoIndex ?>" data-src="<?= h((string)$photo['src']) ?>" data-caption="<?= h((string)$photo['caption']) ?>" aria-label="Show <?= h((string)$photo['caption']) ?>">
                <img src="<?= h((string)$photo['src']) ?>" alt="" loading="lazy">
            </button>
        <?php endforeach; ?>

    </div>
</dialog>
<script defer src="assets/js/venues.js?v=2026-10-09-1"></script>
