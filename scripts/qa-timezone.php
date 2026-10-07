<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/database.php';

function wz_timezone_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = wz_db();

if (!$pdo) {
    fwrite(STDERR, "Time-zone QA requires MySQL.\n");
    exit(1);
}

wz_timezone_assert(
    date_default_timezone_get() === 'Asia/Kolkata',
    'PHP must use Indian local time.'
);

$clock = $pdo->query(
    "SELECT @@session.time_zone AS time_zone,
            NOW() AS database_time,
            TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) AS utc_offset"
)->fetch();

wz_timezone_assert(
    $clock['time_zone'] === '+05:30' && (int)$clock['utc_offset'] === 19800,
    'The database connection must use the Indian UTC offset.'
);

wz_timezone_assert(
    abs(strtotime($clock['database_time']) - time()) <= 5,
    'PHP and database clocks must represent the same instant.'
);

// Temporary rows exercise automatic timestamps and CRM reminder comparisons.
$pdo->exec(
    'CREATE TEMPORARY TABLE wz_qa_timezone (
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        follow_up_at DATETIME NOT NULL
    )'
);

try {
    $insert = $pdo->prepare(
        'INSERT INTO wz_qa_timezone (follow_up_at) VALUES (?)'
    );
    $insert->execute([date('Y-m-d H:i:s', time() - 60)]);

    $record = $pdo->query(
        'SELECT created_at, follow_up_at <= NOW() AS reminder_due
         FROM wz_qa_timezone'
    )->fetch();

    wz_timezone_assert(
        abs(strtotime($record['created_at']) - time()) <= 5,
        'Automatic DATETIME timestamps must match PHP time.'
    );

    wz_timezone_assert(
        (int)$record['reminder_due'] === 1,
        'A follow-up entered in Indian time must become due at the right time.'
    );
} finally {
    $pdo->exec('DROP TEMPORARY TABLE wz_qa_timezone');
}

echo "Time-zone QA passed: clocks, new timestamps and due reminders agree.\n";
