<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Run from the website folder. This helper can be downloaded outside it.
$root = getcwd();
$changes = [];
$installed = [];
$backupDirectory = '';

$edits = [
    'includes/bootstrap.php' => [
        'setting' => "date_default_timezone_set('Asia/Kolkata');",
        'before' => "declare(strict_types=1);\n",
        'after' => "declare(strict_types=1);\n"
            . "// Event dates and CRM schedules use Indian local time.\n"
            . "date_default_timezone_set('Asia/Kolkata');\n",
    ],
    'includes/database.php' => [
        'setting' => "\$pdo->exec(\"SET time_zone = '+05:30'\");",
        'before' => "        );\n    } catch (PDOException \$exception) {",
        'after' => "        );\n\n"
            . "        // Match PHP and local DATETIME fields without host time-zone tables.\n"
            . "        \$pdo->exec(\"SET time_zone = '+05:30'\");\n"
            . "    } catch (PDOException \$exception) {",
    ],
];

try {
    if (!$root || !function_exists('token_get_all')) {
        throw new RuntimeException('PHP tokenizer and a website folder are required.');
    }

    // Validate both edits before changing either file.
    foreach ($edits as $relativePath => $edit) {
        $path = $root . '/' . $relativePath;

        if (!is_file($path) || !is_writable($path) || !is_writable(dirname($path))) {
            throw new RuntimeException('Cannot update ' . $relativePath . '.');
        }

        $original = file_get_contents($path);

        if ($original === false) {
            throw new RuntimeException('Cannot read ' . $relativePath . '.');
        }

        if (str_contains($original, $edit['setting'])) {
            continue;
        }

        if (substr_count($original, $edit['before']) !== 1) {
            throw new RuntimeException('Unexpected code in ' . $relativePath . '; no edits applied.');
        }

        $updated = str_replace($edit['before'], $edit['after'], $original);
        token_get_all($updated, TOKEN_PARSE);
        $changes[$relativePath] = $updated;
    }

    if ($changes) {
        // Store original source files outside the website's public folder.
        $backupDirectory = dirname($root) . '/weddingza-time-backup-'
            . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3));

        if (!mkdir($backupDirectory, 0700)) {
            throw new RuntimeException('Cannot prepare the source backup.');
        }

        foreach ($changes as $relativePath => $updated) {
            $name = basename($relativePath);
            $path = $root . '/' . $relativePath;

            if (!copy($path, $backupDirectory . '/' . $name)
                || file_put_contents($backupDirectory . '/' . $name . '.new', $updated) !== strlen($updated)
                || !chmod($backupDirectory . '/' . $name . '.new', fileperms($path) & 0777)) {
                throw new RuntimeException('Cannot prepare ' . $relativePath . '.');
            }
        }

        foreach ($changes as $relativePath => $updated) {
            if (!rename($backupDirectory . '/' . basename($relativePath) . '.new', $root . '/' . $relativePath)) {
                throw new RuntimeException('Cannot install ' . $relativePath . '.');
            }

            $installed[] = $relativePath;
        }

        echo "India-time fix applied. Original files backed up outside public_html.\n";
    } else {
        echo "India-time fix is already applied.\n";
    }
} catch (Throwable $error) {
    foreach ($installed as $relativePath) {
        copy($backupDirectory . '/' . basename($relativePath), $root . '/' . $relativePath);
    }

    fwrite(STDERR, "Update stopped: " . $error->getMessage() . "\n");
    exit(1);
}

// Print clocks only; credentials are never displayed or rewritten.
require $root . '/includes/database.php';
$pdo = wz_db();

echo "PHP time: " . date('Y-m-d H:i:s T') . "\n";

if (!$pdo) {
    fwrite(STDERR, "Database connection unavailable; run the go-live checker.\n");
    exit(1);
}

echo "Database time: " . $pdo->query('SELECT NOW()')->fetchColumn() . "\n";
echo "Database timezone: " . $pdo->query('SELECT @@session.time_zone')->fetchColumn() . "\n";
