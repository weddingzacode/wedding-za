<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/database.php';

const WZ_LIVE_PASS = 'PASS';
const WZ_LIVE_WARNING = 'WARNING';
const WZ_LIVE_BLOCKER = 'BLOCKER';

$results = [];

function wz_live_add(
    array &$results,
    string $status,
    string $label,
    string $message
): void {
    $results[] = [
        'status' => $status,
        'label' => $label,
        'message' => $message,
    ];
}

function wz_live_mask(string $value): string
{
    $length = strlen($value);

    if ($length <= 8) {
        return str_repeat('*', max(0, $length));
    }

    return substr($value, 0, 4)
        . str_repeat('*', max(4, $length - 8))
        . substr($value, -4);
}

function wz_live_table_exists(
    PDO $pdo,
    string $table
): bool {
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
         AND table_name = :table_name'
    );

    $statement->execute([
        'table_name' => $table,
    ]);

    return (int)$statement->fetchColumn() > 0;
}

$config = wz_config();
$appUrl = trim((string)($config['app_url'] ?? ''));
$database = $config['database'] ?? [];
$payments = $config['payments'] ?? [];
$seo = $config['seo'] ?? [];
$notifications = $config['notifications'] ?? [];
$operations = $config['operations'] ?? [];

$root = dirname(__DIR__);
$configPath = $root . '/config.local.php';
$uploadsPath = $root . '/uploads/media';
$storagePath = $root . '/storage';
$backupPath = $storagePath . '/backups';
$htaccessPath = $root . '/.htaccess';

wz_live_add(
    $results,
    version_compare(PHP_VERSION, '8.1.0', '>=')
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'PHP version',
    'Running PHP ' . PHP_VERSION . '; PHP 8.1+ is required.'
);

foreach (
    [
        'pdo' => 'PDO',
        'pdo_mysql' => 'PDO MySQL',
        'fileinfo' => 'Fileinfo',
        'mbstring' => 'Mbstring',
        'json' => 'JSON',
    ]
    as $extension => $label
) {
    wz_live_add(
        $results,
        extension_loaded($extension)
            ? WZ_LIVE_PASS
            : WZ_LIVE_BLOCKER,
        $label . ' extension',
        extension_loaded($extension)
            ? 'Loaded.'
            : 'Missing PHP extension: ' . $extension . '.'
    );
}

wz_live_add(
    $results,
    function_exists('curl_init')
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'cURL extension',
    function_exists('curl_init')
        ? 'Loaded.'
        : 'Required for Razorpay checkout. Enable the PHP cURL extension.'
);

wz_live_add(
    $results,
    is_file($configPath)
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'Private configuration',
    is_file($configPath)
        ? 'config.local.php is present.'
        : 'Create config.local.php from config.example.php before launch.'
);

$isHttpsUrl = $appUrl !== ''
    && str_starts_with(
        strtolower($appUrl),
        'https://'
    );

$isLocalUrl = $appUrl === ''
    || preg_match(
        '#https?://(localhost|127\.0\.0\.1)(?::\d+)?(?:/|$)#i',
        $appUrl
    );

$urlHost = strtolower((string)parse_url($appUrl, PHP_URL_HOST));
$isPlaceholderUrl = in_array(
    $urlHost,
    ['your-domain.com', 'example.com', 'your-domain.example', 'localhost', '::1', '[::1]'],
    true
);
$validProductionUrl = filter_var($appUrl, FILTER_VALIDATE_URL)
    && $isHttpsUrl
    && !$isLocalUrl
    && !$isPlaceholderUrl
    && parse_url($appUrl, PHP_URL_USER) === null
    && parse_url($appUrl, PHP_URL_PASS) === null;

wz_live_add(
    $results,
    $validProductionUrl
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'Production app URL',
    $validProductionUrl
        ? 'Configured as ' . $appUrl . '.'
        : 'Set app_url to the final HTTPS production domain.'
);

wz_live_add(
    $results,
    is_file($htaccessPath)
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'Apache security rules (server verification still required)',
    is_file($htaccessPath)
        ? '.htaccess is present. Confirm the server actually enforces it over HTTP.'
        : '.htaccess is missing.'
);

$protectedFiles = [
    $root . '/storage/.htaccess' => 'Storage protection',
    $root . '/storage/backups/.htaccess' => 'Backup protection',
    $root . '/uploads/media/.htaccess' => 'Upload execution protection',
    $root . '/database/.htaccess' => 'Database-file protection',
];

foreach ($protectedFiles as $path => $label) {
    wz_live_add(
        $results,
        is_file($path)
            ? WZ_LIVE_PASS
            : WZ_LIVE_BLOCKER,
        $label,
        is_file($path)
            ? basename(dirname($path))
                . '/.htaccess is present.'
            : 'Missing protection file: '
                . str_replace($root . '/', '', $path)
                . '.'
    );
}

$displayErrors = strtolower(
    trim((string)ini_get('display_errors'))
);

$errorsHidden = in_array(
    $displayErrors,
    ['', '0', 'off', 'false'],
    true
);

wz_live_add(
    $results,
    $errorsHidden
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'PHP error display',
    $errorsHidden
        ? 'display_errors is disabled.'
        : 'Disable display_errors in production so PHP warnings are not exposed to visitors.'
);

$exposePhp = strtolower(
    trim((string)ini_get('expose_php'))
);

wz_live_add(
    $results,
    in_array(
        $exposePhp,
        ['', '0', 'off', 'false'],
        true
    )
        ? WZ_LIVE_PASS
        : WZ_LIVE_WARNING,
    'PHP version exposure',
    in_array(
        $exposePhp,
        ['', '0', 'off', 'false'],
        true
    )
        ? 'expose_php is disabled.'
        : 'Consider disabling expose_php in production.'
);

$healthToken = trim(
    (string)($operations['health_token'] ?? '')
);

wz_live_add(
    $results,
    empty($operations['allow_demo_login']) ? WZ_LIVE_PASS : WZ_LIVE_BLOCKER,
    'Demo account access',
    empty($operations['allow_demo_login'])
        ? 'Disabled.'
        : 'Set operations.allow_demo_login to false before launch.'
);

wz_live_add(
    $results,
    strlen($healthToken) >= 32
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'Health-check token',
    strlen($healthToken) >= 32
        ? 'Strong health token configured.'
        : 'Set a random health_token of at least 32 characters.'
);

$databaseConfigured =
    !empty($database['host'])
    && !empty($database['name'])
    && !empty($database['user']);

wz_live_add(
    $results,
    $databaseConfigured
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'Database configuration',
    $databaseConfigured
        ? 'Database credentials are configured.'
        : 'Production database configuration is incomplete.'
);

$pdo = wz_db();

wz_live_add(
    $results,
    $pdo instanceof PDO
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'Database connection',
    $pdo instanceof PDO
        ? 'MySQL connection succeeded.'
        : 'Could not connect to MySQL.'
);

if ($pdo instanceof PDO) {
    // The fresh schema is the source of truth for all required CRM tables.
    $schema = file_get_contents($root . '/database/schema.sql') ?: '';
    preg_match_all('/CREATE TABLE\s+([a-z_]+)/i', $schema, $tableMatches);
    $requiredTables = $tableMatches[1];

    $missingTables = [];

    foreach ($requiredTables as $table) {
        if (!wz_live_table_exists($pdo, $table)) {
            $missingTables[] = $table;
        }
    }

    wz_live_add(
        $results,
        $requiredTables && !$missingTables
            ? WZ_LIVE_PASS
            : WZ_LIVE_BLOCKER,
        'Database migrations',
        $requiredTables && !$missingTables
            ? 'Required live tables are present.'
            : 'Required schema missing or incomplete. Missing tables: '
                . implode(', ', $missingTables)
                . '. Run scripts/upgrade-existing-database.php.'
    );

    $adminCount = wz_live_table_exists($pdo, 'users')
        ? (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin" AND status = "active"')->fetchColumn()
        : 0;
    wz_live_add(
        $results,
        $adminCount > 0 ? WZ_LIVE_PASS : WZ_LIVE_BLOCKER,
        'Administrator account',
        $adminCount > 0
            ? 'An active administrator account exists.'
            : 'Create an administrator with scripts/create-admin.php before launch.'
    );
}

foreach (
    [
        $uploadsPath => 'Uploads directory',
        $storagePath => 'Storage directory',
    ]
    as $path => $label
) {
    $parent = is_dir($path)
        ? $path
        : dirname($path);

    wz_live_add(
        $results,
        is_dir($parent)
        && is_writable($parent)
            ? WZ_LIVE_PASS
            : WZ_LIVE_BLOCKER,
        $label,
        is_dir($parent)
        && is_writable($parent)
            ? 'Writable by PHP.'
            : 'PHP cannot write to ' . $parent . '.'
    );
}

$backupParent = is_dir($backupPath)
    ? $backupPath
    : dirname($backupPath);

wz_live_add(
    $results,
    is_dir($backupParent)
    && is_writable($backupParent)
        ? WZ_LIVE_PASS
        : WZ_LIVE_WARNING,
    'Backup directory',
    is_dir($backupParent)
    && is_writable($backupParent)
        ? 'Database backup location is writable.'
        : 'Backup directory is not writable; configure backups before launch.'
);

$razorpayKey = trim(
    (string)($payments['razorpay_key_id'] ?? '')
);

$razorpaySecret = trim(
    (string)($payments['razorpay_key_secret'] ?? '')
);

$razorpayWebhook = trim(
    (string)($payments['razorpay_webhook_secret'] ?? '')
);

$razorpayLive =
    str_starts_with($razorpayKey, 'rzp_live_')
    && $razorpaySecret !== ''
    && $razorpayWebhook !== '';

wz_live_add(
    $results,
    $razorpayLive
        ? WZ_LIVE_PASS
        : WZ_LIVE_BLOCKER,
    'Razorpay live mode',
    $razorpayLive
        ? 'Live Razorpay credentials are configured (' . wz_live_mask($razorpayKey) . ').'
        : 'Add rzp_live_* key, key secret and webhook secret before accepting real payments.'
);

$contactEmail = trim(
    (string)($seo['contact_email'] ?? '')
);

$contactPhone = trim(
    (string)($seo['contact_phone'] ?? '')
);

$defaultOgImage = trim(
    (string)($seo['default_og_image'] ?? '')
);

wz_live_add(
    $results,
    filter_var(
        $contactEmail,
        FILTER_VALIDATE_EMAIL
    )
        ? WZ_LIVE_PASS
        : WZ_LIVE_WARNING,
    'Public contact email',
    filter_var(
        $contactEmail,
        FILTER_VALIDATE_EMAIL
    )
        ? 'Configured as ' . $contactEmail . '.'
        : 'Set seo.contact_email for production.'
);

wz_live_add(
    $results,
    $contactPhone !== ''
        ? WZ_LIVE_PASS
        : WZ_LIVE_WARNING,
    'Public contact phone',
    $contactPhone !== ''
        ? 'Configured.'
        : 'Set seo.contact_phone if customer support will use a phone number.'
);

wz_live_add(
    $results,
    $defaultOgImage !== ''
        ? WZ_LIVE_PASS
        : WZ_LIVE_WARNING,
    'Default social-share image',
    $defaultOgImage !== ''
        ? 'Configured.'
        : 'Set seo.default_og_image for consistent WhatsApp/social previews.'
);

$notificationWebhook = trim(
    (string)($notifications['delivery_webhook_url'] ?? '')
);

$emailEnabled = !empty(
    $notifications['email_enabled']
);

$pushEnabled = !empty(
    $notifications['push_enabled']
);

if ($emailEnabled || $pushEnabled) {
    $notificationReady =
        $notificationWebhook !== ''
        && filter_var(
            $notificationWebhook,
            FILTER_VALIDATE_URL
        )
        && str_starts_with(
            strtolower($notificationWebhook),
            'https://'
        );

    wz_live_add(
        $results,
        $notificationReady
            ? WZ_LIVE_PASS
            : WZ_LIVE_BLOCKER,
        'Email / push delivery',
        $notificationReady
            ? 'Production notification webhook is configured.'
            : 'Email/push is enabled but no valid HTTPS notification webhook is configured.'
    );
} else {
    wz_live_add(
        $results,
        WZ_LIVE_WARNING,
        'Email / push delivery',
        'External delivery is disabled. In-app CRM notifications will still work.'
    );
}

wz_live_add(
    $results,
    is_file($root . '/assets/images/image-fallback.svg')
        ? WZ_LIVE_PASS
        : WZ_LIVE_WARNING,
    'Image fallback',
    is_file($root . '/assets/images/image-fallback.svg')
        ? 'Graceful image fallback is present.'
        : 'Image fallback asset is missing.'
);

$draftPolicyPages = [];
foreach (['privacy.php', 'terms.php', 'cancellation.php'] as $policyPage) {
    $policyText = is_file($root . '/' . $policyPage)
        ? file_get_contents($root . '/' . $policyPage)
        : false;
    if (
        $policyText === false
        || preg_match('/development\/demo website|demo package|placeholder policy/i', $policyText)
    ) {
        $draftPolicyPages[] = $policyPage;
    }
}
wz_live_add(
    $results,
    !$draftPolicyPages ? WZ_LIVE_PASS : WZ_LIVE_BLOCKER,
    'Published business policies',
    !$draftPolicyPages
        ? 'Policy pages exist and no known draft markers remain. Confirm client approval separately.'
        : 'Replace draft policy copy with client-approved content in: ' . implode(', ', $draftPolicyPages) . '.'
);

$missingBusinessDetails = ['policy business-details helper'];
if (is_file($root . '/includes/policies.php')) {
    require_once $root . '/includes/policies.php';
    $missingBusinessDetails = wz_policy_missing_details(wz_policy_details());
}
wz_live_add(
    $results,
    !$missingBusinessDetails ? WZ_LIVE_PASS : WZ_LIVE_BLOCKER,
    'Business and grievance details',
    !$missingBusinessDetails
        ? 'Operator, address, support and named grievance contact are configured. Verify their accuracy and monitor the contact channels.'
        : 'Add actual public business details: ' . implode(', ', $missingBusinessDetails) . '.'
);

$blockers = array_values(
    array_filter(
        $results,
        fn (array $item): bool =>
            $item['status'] === WZ_LIVE_BLOCKER
    )
);

$warnings = array_values(
    array_filter(
        $results,
        fn (array $item): bool =>
            $item['status'] === WZ_LIVE_WARNING
    )
);

$passes = array_values(
    array_filter(
        $results,
        fn (array $item): bool =>
            $item['status'] === WZ_LIVE_PASS
    )
);

fwrite(
    STDOUT,
    "Wedding Za — Go-Live Checker\n"
    . str_repeat('=', 56)
    . "\n\n"
);

foreach ($results as $item) {
    $symbol = match ($item['status']) {
        WZ_LIVE_PASS => '[PASS]   ',
        WZ_LIVE_WARNING => '[WARN]   ',
        default => '[BLOCK]  ',
    };

    fwrite(
        STDOUT,
        $symbol
        . $item['label']
        . "\n         "
        . $item['message']
        . "\n\n"
    );
}

fwrite(
    STDOUT,
    str_repeat('-', 56)
    . "\n"
);

fwrite(
    STDOUT,
    'Pass: ' . count($passes)
    . ' | Warnings: ' . count($warnings)
    . ' | Blockers: ' . count($blockers)
    . "\n"
);

if ($blockers) {
    fwrite(
        STDOUT,
        "\nNOT READY FOR LIVE\n"
        . "Resolve every BLOCKER above, then run this command again.\n"
    );

    exit(1);
}

fwrite(
    STDOUT,
    "\nSERVER CONFIGURATION CHECK PASSED\n"
    . "Complete real-domain login, upload, SSL, payment and webhook tests before launch.\n"
);

exit(0);
