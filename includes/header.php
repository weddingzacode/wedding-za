<?php

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Wedding Za';
$pageDescription = $pageDescription
    ?? 'Discover venues, vendors, ideas and planning tools for every kind of celebration across India.';
$pageKey = $pageKey ?? '';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$canonicalUrl = wz_app_url(ltrim($requestUri, '/'));

$seoConfig = wz_config()['seo'] ?? [];
$analyticsConfig = wz_config()['analytics'] ?? [];

$pageImage = $pageImage
    ?? ($seoConfig['default_og_image'] ?? '');

$structuredData = $structuredData ?? [];

$baseStructuredData = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => (string)($seoConfig['organization_name'] ?? 'Wedding Za'),
        'url' => wz_app_url(''),
        'email' => (string)($seoConfig['contact_email'] ?? ''),
        'telephone' => (string)($seoConfig['contact_phone'] ?? ''),
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Wedding Za',
        'url' => wz_app_url(''),
    ],
];

$structuredData = array_merge(
    $baseStructuredData,
    is_array($structuredData)
        ? $structuredData
        : []
);

$analyticsId = trim(
    (string)($analyticsConfig['measurement_id'] ?? '')
);

$accountUrl = 'login.php?role=host';
$accountLabel = 'Log in';

if (wz_is_logged_in()) {
    $accountUrl = match (wz_role()) {
        'vendor' => 'crm/vendor/index.php',
        'venue' => 'crm/venue/index.php',
        'admin' => 'admin/index.php',
        default => 'crm/customer/index.php',
    };

    $accountLabel = match (wz_role()) {
        'vendor' => 'Vendor CRM',
        'venue' => 'Venue CRM',
        'admin' => 'Admin',
        default => 'My CRM',
    };
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <?php if (!empty($useBaseHref)): ?>
        <base href="<?= h(rtrim(wz_app_url(''), '/') . '/') ?>">
    <?php endif; ?>

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1,viewport-fit=cover"
    >

    <meta
        name="theme-color"
        content="#17120f"
    >

    <meta
        name="apple-mobile-web-app-capable"
        content="yes"
    >

    <meta
        name="apple-mobile-web-app-status-bar-style"
        content="black-translucent"
    >

    <link
        rel="manifest"
        href="manifest.webmanifest"
    >

    <meta
        name="description"
        content="<?= h($pageDescription) ?>"
    >

    <link
        rel="canonical"
        href="<?= h($canonicalUrl) ?>"
    >

    <meta
        property="og:title"
        content="<?= h($pageTitle) ?> · Wedding Za"
    >

    <meta
        property="og:description"
        content="<?= h($pageDescription) ?>"
    >

    <meta
        property="og:type"
        content="website"
    >

    <meta
        property="og:url"
        content="<?= h($canonicalUrl) ?>"
    >

    <?php if ($pageImage !== ''): ?>
        <meta
            property="og:image"
            content="<?= h($pageImage) ?>"
        >
    <?php endif; ?>

    <?php foreach ($structuredData as $schema): ?>
        <script type="application/ld+json"><?= json_encode(
            array_filter(
                $schema,
                fn ($value): bool => $value !== ''
            ),
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        ) ?></script>
    <?php endforeach; ?>

    <?php if ($analyticsId !== ''): ?>
        <script
            async
            src="https://www.googletagmanager.com/gtag/js?id=<?= h($analyticsId) ?>"
        ></script>

        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', <?= json_encode($analyticsId) ?>);
        </script>
    <?php endif; ?>

    <title><?= h($pageTitle) ?> · Wedding Za</title>

    <link
        rel="icon"
        href="assets/images/favicon.svg"
        type="image/svg+xml"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/app.css?v=4.0.0"
    >

    <link
        rel="stylesheet"
        href="assets/css/vision.css?v=4.0.4"
    >

    <link
        rel="stylesheet"
        href="assets/css/marketplace.css?v=1.0.0"
    >

    <link
        rel="stylesheet"
        href="assets/css/final-polish.css?v=1.0.0"
    >

    <link rel="stylesheet" href="assets/css/header.css?v=2026-10-08">

    <noscript>
        <style>
            .vision-preloader { display: none; }
            #siteHeader .vision-nav { display: flex; flex-wrap: wrap; height: auto; padding: 12px; gap: 8px 18px; }
            #siteHeader .vision-nav-actions { margin-left: auto; }
            #siteHeader .vision-menu-btn { display: none; }
            #siteHeader .vision-nav-links { display: flex; order: 3; width: 100%; justify-content: flex-start; gap: 18px; overflow-x: auto; }
            #siteHeader .vision-nav-links a { padding: 8px 0; }
        </style>
    </noscript>

    <?php if (in_array($pageKey, ['privacy', 'terms', 'cancellation'], true)): ?>
        <link rel="stylesheet" href="assets/css/policies.css?v=2026-10-07">
    <?php endif; ?>
</head>

<body
    data-page="<?= h($pageKey) ?>"
    class="vision-site"
>
    <div
        class="page-progress"
        id="pageProgress"
    ></div>

    <div
        class="page-wipe"
        id="pageWipe"
        aria-hidden="true"
    >
        <span>WZ</span>
    </div>

    <div
        class="preloader vision-preloader"
        id="preloader"
        aria-hidden="true"
    >
        <div class="vision-preloader-inner">
            <span class="vision-preloader-kicker">
                WEDDING ZA
            </span>

            <strong>
                For every reason
                <br>
                <em>to celebrate.</em>
            </strong>

            <i></i>
        </div>
    </div>

    <header
        class="vision-header"
        id="siteHeader"
    >
        <div class="container vision-nav">
            <a
                class="vision-brand"
                href="index.php"
                aria-label="Wedding Za home"
            >
                <img class="wz-brand-mark" src="assets/images/weddingza-mark.svg" width="44" height="44" alt="" aria-hidden="true">
                <b>Wedding Za</b>
            </a>

            <nav
                class="vision-nav-links"
                aria-label="Primary navigation"
            >
                <a<?= wz_active('venues.php') ?> href="venues.php">
                    Venues
                </a>

                <a<?= wz_active('vendors.php') ?> href="vendors.php">
                    Vendors
                </a>

                <a<?= wz_active('inspiration.php') ?> href="inspiration.php">
                    Ideas
                </a>

                <a<?= wz_active('real-weddings.php') ?> href="real-weddings.php">
                    Celebrations
                </a>

                <a<?= wz_active('blog.php') ?> href="blog.php">
                    Journal
                </a>

                <a<?= wz_active('contact.php') ?> href="contact.php">
                    Contact
                </a>
            </nav>

            <div class="vision-nav-actions">
                <a
                    class="vision-heart vision-search"
                    href="search.php"
                    aria-label="Search Wedding Za"
                >
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 4.5 4.5"/></svg>
                </a>

                <a
                    class="vision-discover"
                    href="planner.php"
                >
                    Plan an event
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18 18 6M6 6h12v12"/></svg>
                </a>

                <a
                    class="vision-heart vision-shortlist"
                    href="shortlist.php"
                    aria-label="Open shortlist"
                >
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.1 4.9a5.3 5.3 0 0 0-7.5 0l-.6.6-.6-.6a5.3 5.3 0 0 0-7.5 7.5L12 20.5l8.1-8.1a5.3 5.3 0 0 0 0-7.5Z"/></svg>
                    <b id="shortlistCount" aria-label="Saved items">0</b>
                </a>

                <?php if (!wz_is_logged_in()): ?>
                    <div
                        class="vision-auth-cta"
                        aria-label="Account access"
                    >
                        <a
                            class="vision-auth-login"
                            href="login.php?role=host"
                        >
                            Log in
                        </a>

                        <a
                            class="vision-auth-signup"
                            href="register.php?role=host"
                        >
                            Sign up
                            <span>↗</span>
                        </a>
                    </div>
                <?php else: ?>
                    <a
                        class="vision-auth-account"
                        href="<?= h($accountUrl) ?>"
                    >
                        <?= h($accountLabel) ?>
                        <span>↗</span>
                    </a>
                <?php endif; ?>

                <button
                    class="vision-menu-btn"
                    id="menuToggle"
                    type="button"
                    aria-expanded="false"
                    aria-controls="mobileMenu"
                    aria-label="Open menu"
                >
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <div
        class="vision-menu"
        id="mobileMenu"
        aria-hidden="true"
        inert
    >
        <div
            class="vision-menu-bg"
            aria-hidden="true"
        ></div>

        <div class="container vision-menu-grid">
            <div class="vision-menu-main">
                <small>EXPLORE</small>

                <a href="venues.php">
                    <span aria-hidden="true">01</span>
                    Venues
                </a>

                <a href="vendors.php">
                    <span aria-hidden="true">02</span>
                    Vendors
                </a>

                <a href="inspiration.php">
                    <span aria-hidden="true">03</span>
                    Ideas & inspiration
                </a>

                <a href="real-weddings.php">
                    <span aria-hidden="true">04</span>
                    Real celebrations
                </a>

                <a href="blog.php">
                    <span aria-hidden="true">05</span>
                    The journal
                </a>

                <a href="contact.php">
                    <span aria-hidden="true">06</span>
                    Contact
                </a>
            </div>

            <aside class="vision-menu-side">
                <div class="vision-menu-cities">
                    <small>POPULAR CITIES</small>

                    <a href="city.php?city=Jaipur">
                        Jaipur
                    </a>

                    <a href="city.php?city=Udaipur">
                        Udaipur
                    </a>

                    <a href="city.php?city=Goa">
                        Goa
                    </a>

                    <a href="city.php?city=Delhi%20NCR">
                        Delhi NCR
                    </a>
                </div>

                <div class="vision-menu-account-nav">
                    <small>YOUR SPACE</small>

                    <a href="search.php">
                        Search
                    </a>

                    <a href="planner.php">
                        Planning studio
                    </a>

                    <a href="shortlist.php">
                        Shortlist
                    </a>

                    <a href="invites.php">
                        E-invites
                    </a>

                    <?php if (!wz_is_logged_in()): ?>
                        <div class="vision-menu-auth">
                            <a class="vision-menu-login" href="login.php?role=host">Log in</a>
                            <a class="vision-menu-signup" href="register.php?role=host">Sign up <span aria-hidden="true">↗</span></a>
                        </div>

                        <a href="login.php?role=host">
                            Customer CRM
                        </a>

                        <a href="login.php?role=vendor">
                            Vendor CRM
                        </a>

                        <a href="login.php?role=venue">
                            Venue CRM
                        </a>
                    <?php else: ?>
                        <a class="vision-menu-account" href="<?= h($accountUrl) ?>">
                            <?= h($accountLabel) ?> <span aria-hidden="true">↗</span>
                        </a>
                    <?php endif; ?>

                    <a href="register.php?role=vendor">
                        Join as vendor ↗
                    </a>

                    <a href="register.php?role=venue">
                        Join as venue ↗
                    </a>
                </div>
            </aside>
        </div>
    </div>

    <div
        class="discovery-panel"
        id="discoveryPanel"
        aria-hidden="true"
    >
        <button
            class="discovery-backdrop"
            type="button"
            data-discovery-close
            aria-label="Close vendor finder"
        ></button>

        <div
            class="discovery-dialog"
            role="dialog"
            aria-modal="true"
            aria-label="Find event vendors"
        >
            <div class="discovery-top">
                <span>BUILD YOUR EVENT TEAM</span>

                <button
                    type="button"
                    data-discovery-close
                >
                    Close ×
                </button>
            </div>

            <form
                action="vendors.php"
                method="get"
            >
                <label>
                    <span>
                        01 / What are you celebrating?
                    </span>

                    <select name="event">
                        <option value="">
                            Any celebration
                        </option>

                        <?php foreach (wz_data('event_types') as $event): ?>
                            <option>
                                <?= h($event['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>
                        02 / What do you need?
                    </span>

                    <select name="category">
                        <option value="">
                            All vendor categories
                        </option>

                        <?php foreach (wz_data('categories') as $category): ?>
                            <option>
                                <?= h($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>
                        03 / Where?
                    </span>

                    <select name="city">
                        <option value="">
                            All cities
                        </option>

                        <?php foreach (wz_data('cities') as $city): ?>
                            <option>
                                <?= h($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <button type="submit">
                    Build my shortlist
                    <span>↗</span>
                </button>
            </form>

            <div class="discovery-shortcuts">
                <span>Popular:</span>

                <a href="event.php?type=Wedding">
                    Wedding
                </a>

                <a href="event.php?type=Birthday">
                    Birthday
                </a>

                <a href="event.php?type=Corporate">
                    Corporate
                </a>

                <a href="city.php?city=Jaipur">
                    Jaipur
                </a>
            </div>
        </div>
    </div>
