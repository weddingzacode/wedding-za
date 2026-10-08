<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/crm.php';
require __DIR__ . '/includes/marketplace.php';
require_once __DIR__ . '/includes/cities.php';
require_once __DIR__ . '/includes/home-content.php';

$homeCustomer = null;
$homeRecommendations = [];
$homeUnread = 0;
$homeUpcomingBookings = 0;
$homePlan = null;

if (wz_is_logged_in()
    && wz_role() === 'host'
    && !empty(wz_user()['id'])
    && wz_database_ready()
) {
    $homeUserId = (int) wz_user()['id'];
    $homeCustomer = wz_crm_customer_profile($homeUserId) ?? [];
    $homeRecommendations = wz_marketplace_recommendations($homeUserId, 6);
    $homeUnread = wz_marketplace_unread_notification_count($homeUserId);
    $homePlan = wz_marketplace_venue_plan_purchase($homeUserId);
    $homeBookings = wz_crm_bookings_for_user($homeUserId, 'host');
    $homeUpcomingBookings = count(array_filter(
        $homeBookings,
        fn (array $booking): bool => !in_array(
            (string) ($booking['status'] ?? ''),
            ['completed', 'cancelled'],
            true
        )
    ));
}

$pageTitle = 'Celebrations, Reimagined';
$pageDescription = 'Find venues, vendors and ideas for weddings, birthdays, engagements and every celebration. Choose your city, shortlist your favourites and send an enquiry.';
$pageKey = 'home';
$vendors = wz_data('vendors');
$articles = wz_home_articles(wz_data('articles'));
$ideas = wz_home_ideas(wz_data('inspiration'));
$categories = wz_data('categories');
$events = wz_home_events(wz_data('event_types'));
$featuredCelebrations = [];

foreach (['aanya-veer', 'ria-roka', 'aurora-summit'] as $celebrationId) {
    $celebration = wz_wedding($celebrationId);

    if ($celebration !== null) {
        $featuredCelebrations[] = $celebration;
    }
}

$cityCatalogue = wz_city_catalogue();
$cities = wz_city_featured($cityCatalogue);

require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/css/cities.css?v=2026-10-07">
<link rel="stylesheet" href="assets/css/home.css?v=2026-10-08">
<noscript><style>.vision-preloader{display:none}</style></noscript>

<main class="vision-home home-refreshed">
    <section class="vision-hero celebration-hero" id="home" aria-labelledby="homeHeading">
        <div class="vision-hero-bg">
            <img
                src="assets/images/home-decor.webp"
                alt=""
                aria-hidden="true"
                fetchpriority="high"
                decoding="async"
                width="1200"
                height="1800"
            >
        </div>
        <div class="vision-hero-vignette"></div>

        <div class="container vision-hero-stage">
            <div class="home-hero-content">
                <h1 id="homeHeading">
                    <span class="hero-line">Whatever the occasion.</span>
                    <span class="hero-line hero-line-italic">
                        Make it <em>unforgettable.</em>
                    </span>
                </h1>

                <p class="vision-hero-copy">
                    Find venues, creators and event teams for every celebration.
                    Start with your city. Find your perfect fit.
                </p>

                <form
                    class="hero-plan-dock"
                    id="heroPlanDock"
                    action="vendors.php"
                    method="get"
                    role="search"
                    aria-label="Find venues and vendors"
                >
                    <label for="heroCity">
                        <span>City</span>
                        <select name="city" id="heroCity" aria-label="City">
                            <option value="">All cities</option>
                            <?php foreach ($cityCatalogue as $cityOption): ?>
                                <option value="<?= h($cityOption['name']) ?>">
                                    <?= h($cityOption['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label for="heroEvent">
                        <span>Occasion</span>
                        <select name="event" id="heroEvent" aria-label="Occasion">
                            <option value="">Any celebration</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= h($event['name']) ?>">
                                    <?= h($event['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label for="heroCategory">
                        <span>Looking for</span>
                        <select name="category" id="heroCategory" aria-label="Looking for">
                            <option value="">All services</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= h($category['name']) ?>">
                                    <?= h($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <button type="submit">
                        Find venues &amp; vendors
                        <span aria-hidden="true">↗</span>
                    </button>
                </form>

                <div class="vision-hero-actions">
                    <a href="real-weddings.php" class="vision-secondary">
                        Explore real celebrations <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>

            <figure class="home-hero-portrait">
                <img
                    src="assets/images/home-engagement.webp"
                    alt="An Indian couple celebrating their engagement"
                    decoding="async"
                    width="720"
                    height="1080"
                >
                <figcaption>Made for moments that matter.</figcaption>
            </figure>
        </div>
    </section>

    <?php if($homeCustomer!==null): ?>
        <section class="marketplace-home-personal">
            <div class="container">
                <div class="marketplace-home-personal-card">
                    <div class="marketplace-home-personal-main">
                        <span>YOUR WEDDING ZA</span>
                        <h2>
                            Continue planning,
                            <?=h(explode(' ',(string)(wz_user()['name']??'there'))[0])?>.
                        </h2>

                        <p>
                            <?=!empty($homeCustomer['event_date'])
                                ?'Your main event is set for '.h(date('d M Y',strtotime((string)$homeCustomer['event_date']))).'.'
                                :'Add your wedding date and requirements to make Wedding Za recommendations more precise.'?>
                        </p>

                        <div class="marketplace-home-personal-actions">
                            <a
                                class="pill-btn wine"
                                href="<?=h(wz_app_url('crm/customer/'))?>"
                            >
                                Open my CRM ↗
                            </a>

                            <a
                                class="pill-btn outline"
                                href="<?=h(wz_app_url('crm/customer/recommendations.php'))?>"
                            >
                                <?=count($homeRecommendations)?> recommendations
                            </a>
                        </div>
                    </div>

                    <div class="marketplace-home-personal-stat">
                        <span>Upcoming bookings</span>
                        <strong><?=h((string)$homeUpcomingBookings)?></strong>
                    </div>

                    <div class="marketplace-home-personal-stat">
                        <span>Notifications</span>
                        <strong><?=h((string)$homeUnread)?></strong>
                    </div>

                    <div class="marketplace-home-personal-stat">
                        <span>Venue Assist</span>
                        <strong style="font-size:28px;">
                            <?=h(
                                !empty($homePlan['status'])
                                    ?ucfirst((string)$homePlan['status'])
                                    :'Not started'
                            )?>
                        </strong>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="home-steps section-bleed" id="how-it-works" aria-labelledby="homeStepsHeading">
        <div class="container">
            <div class="home-steps-head">
                <div>
                    <span class="home-kicker">A SIMPLE WAY TO PLAN</span>
                    <h2 id="homeStepsHeading">From the first idea to <em>the right team.</em></h2>
                </div>
                <p>Three simple steps to move your celebration forward.</p>
            </div>

            <ol class="home-step-grid">
                <li>
                    <span class="home-step-number" aria-hidden="true">01</span>
                    <div>
                        <h3>Choose your city</h3>
                        <p>Explore places and people where you want to celebrate.</p>
                        <a href="cities.php">Explore cities <span aria-hidden="true">↗</span></a>
                    </div>
                </li>
                <li>
                    <span class="home-step-number" aria-hidden="true">02</span>
                    <div>
                        <h3>Build a shortlist</h3>
                        <p>Save your favourites and compare the details that matter to you.</p>
                        <a href="vendors.php">Browse venues &amp; vendors <span aria-hidden="true">↗</span></a>
                    </div>
                </li>
                <li>
                    <span class="home-step-number" aria-hidden="true">03</span>
                    <div>
                        <h3>Send an enquiry</h3>
                        <p>Ask your shortlisted businesses about dates, prices and availability.</p>
                        <a href="shortlist.php">Open your shortlist <span aria-hidden="true">↗</span></a>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <section class="vision-experience section-bleed" id="visionExperience" aria-labelledby="homeEventsHeading">
        <div class="container vision-section-head light">
            <div>
                <small>START WITH THE OCCASION</small>
                <h2 id="homeEventsHeading">What are we <em>celebrating?</em></h2>
            </div>
            <p>From intimate gatherings to grand occasions, find a team that fits your event.</p>
        </div>

        <div class="vision-category-track" id="visionCategoryTrack">
            <?php foreach ($events as $index => $event): ?>
                <a class="vision-category-panel" href="event.php?type=<?= rawurlencode($event['name']) ?>">
                    <div class="vision-category-image">
                        <img
                            src="<?= h($event['image']) ?>"
                            alt="<?= h($event['name']) ?> celebration inspiration"
                            loading="lazy"
                            decoding="async"
                            width="720"
                            height="1080"
                        >
                    </div>
                    <div class="vision-category-number" aria-hidden="true">
                        <?= sprintf('%02d', $index + 1) ?>
                    </div>
                    <div class="vision-category-copy">
                        <h3><?= h($event['name']) ?></h3>
                        <p><?= h($event['sub']) ?></p>
                        <b>Explore this occasion <span aria-hidden="true">↗</span></b>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="vision-cities city-discovery section-bleed" id="cities" aria-labelledby="homeCityHeading">
        <div class="container vision-section-head">
            <div>
                <small>PLACE CHANGES EVERYTHING</small>
                <h2 id="homeCityHeading">Choose the city. <em>Then build the mood.</em></h2>
            </div>
            <p>Explore city guides and discover places to celebrate.</p>
        </div>

        <div class="container city-home-tools">
            <form action="cities.php#city-results" method="get" class="city-search" role="search" aria-label="Search cities">
                <div class="city-search-field">
                    <label for="homeCitySearch">Where are you celebrating?</label>
                    <input type="search" id="homeCitySearch" name="q" placeholder="Search your city…" maxlength="120">
                </div>
                <button type="submit" class="city-action">
                    Find a city <span aria-hidden="true">↗</span>
                </button>
            </form>
            <a class="city-action city-view-all" href="cities.php">
                View all <?= count($cityCatalogue) ?> cities <span aria-hidden="true">↗</span>
            </a>
        </div>

        <div class="vision-city-rail-wrap">
            <div class="vision-city-rail" id="visionCityRail">
                <?php foreach ($cities as $index => $cityCard): ?>
                    <a class="vision-city-card" href="city.php?city=<?= rawurlencode($cityCard['name']) ?>">
                        <figure>
                            <img
                                src="<?= h(wz_home_photo($cityCard['image'])) ?>"
                                alt="<?= h($cityCard['image_alt']) ?>"
                                loading="lazy"
                                decoding="async"
                                width="600"
                                height="400"
                            >
                        </figure>
                        <div>
                            <span aria-hidden="true"><?= sprintf('%02d', $index + 1) ?></span>
                            <h3><?= h($cityCard['name']) ?></h3>
                            <p><?= h($cityCard['description']) ?></p>
                            <b>Open city guide <span aria-hidden="true">↗</span></b>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="vision-vendors section-bleed" aria-labelledby="homeVendorsHeading">
        <div class="container vision-section-head light">
            <div>
                <small>THE WEDDING ZA EDIT</small>
                <h2 id="homeVendorsHeading">Teams worth <em>meeting first.</em></h2>
            </div>
            <p>Browse profiles, compare starting prices and save the people you would like to meet.</p>
        </div>

        <div class="container vision-featured-vendors">
            <?php foreach (array_slice($vendors, 0, 4) as $vendor): ?>
                <?php
                $vendor['image'] = wz_home_photo((string) ($vendor['image'] ?? ''));
                wz_vendor_card($vendor);
                ?>
            <?php endforeach; ?>
        </div>

        <div class="container vision-section-action">
            <a href="vendors.php">Browse all venues &amp; vendors <span aria-hidden="true">↗</span></a>
        </div>
    </section>

    <section class="vision-real section-bleed" aria-labelledby="homeStoriesHeading">
        <div class="container vision-section-head light">
            <div>
                <small>REAL CELEBRATIONS</small>
                <h2 id="homeStoriesHeading">See how a function <em>becomes a feeling.</em></h2>
            </div>
            <p>Explore the details, ideas and atmosphere behind each celebration.</p>
        </div>

        <div class="container vision-real-stage">
            <?php foreach ($featuredCelebrations as $celebration): ?>
                <article class="vision-real-panel">
                    <a href="wedding-story.php?id=<?= rawurlencode($celebration['id']) ?>">
                        <img
                            src="<?= h(wz_home_photo($celebration['image'])) ?>"
                            alt="<?= h($celebration['couple']) ?> celebration"
                            loading="lazy"
                            decoding="async"
                            width="720"
                            height="1080"
                        >
                        <div class="vision-real-overlay">
                            <span>
                                <?= h($celebration['event_type'] ?? 'Celebration') ?>
                                · <?= h($celebration['city']) ?>
                            </span>
                            <h3><?= h($celebration['couple']) ?></h3>
                            <p><?= h($celebration['title']) ?></p>
                            <b>View the story <span aria-hidden="true">↗</span></b>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="container vision-section-action">
            <a href="real-weddings.php">View all celebrations <span aria-hidden="true">↗</span></a>
        </div>
    </section>

    <section class="vision-ideas section-bleed" aria-labelledby="homeIdeasHeading">
        <div class="container vision-section-head light">
            <div>
                <small>THE VISUAL EDIT</small>
                <h2 id="homeIdeasHeading">Save what <em>inspires you.</em></h2>
            </div>
            <p>Ideas for decor, style, food and the details that make it yours.</p>
        </div>

        <div class="container vision-idea-collage">
            <?php foreach (array_slice($ideas, 0, 7) as $index => $idea): ?>
                <a class="vision-idea vision-idea-<?= $index + 1 ?>" href="inspiration.php">
                    <div class="home-idea-image">
                        <img
                            src="<?= h($idea['image']) ?>"
                            alt="<?= h($idea['title']) ?>"
                            loading="lazy"
                            decoding="async"
                            width="720"
                            height="1080"
                        >
                    </div>
                    <div class="home-idea-copy">
                        <span><?= h($idea['category']) ?></span>
                        <h3><?= h($idea['title']) ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="container vision-section-action dark">
            <a href="inspiration.php">Explore more ideas <span aria-hidden="true">↗</span></a>
        </div>
    </section>

    <section class="vision-concierge section-bleed" aria-labelledby="homeHelpHeading">
        <div class="vision-concierge-image">
            <img
                src="assets/images/home-decor.webp"
                alt="A celebration table with flowers and hanging lanterns"
                loading="lazy"
                decoding="async"
                width="1200"
                height="1800"
            >
        </div>
        <div class="container vision-concierge-grid">
            <div>
                <span>PLANNING, MADE CLEARER</span>
                <h2 id="homeHelpHeading">A place for <em>every detail.</em></h2>
            </div>
            <div>
                <p>Keep your event brief, budget and ideas together in the planning studio. Need a hand getting started? Our contact form is one click away.</p>
                <div class="home-help-actions">
                    <a href="planner.php">Open planning studio <span aria-hidden="true">↗</span></a>
                    <a href="contact.php">Contact us <span aria-hidden="true">↗</span></a>
                </div>
            </div>
        </div>
    </section>

    <section class="vision-journal section-bleed" aria-labelledby="homeJournalHeading">
        <div class="container vision-section-head">
            <div>
                <small>THE JOURNAL</small>
                <h2 id="homeJournalHeading">Read before <em>you decide.</em></h2>
            </div>
            <p>Practical guides to venues, budgets and a better guest experience.</p>
        </div>

        <div class="container vision-journal-grid">
            <?php foreach (array_slice($articles, 0, 3) as $article): ?>
                <?php
                $article['image'] = wz_home_photo((string) ($article['image'] ?? ''));
                wz_article_card($article);
                ?>
            <?php endforeach; ?>
        </div>

        <div class="container vision-section-action">
            <a href="blog.php">Read all guides <span aria-hidden="true">↗</span></a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
