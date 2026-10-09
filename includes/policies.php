<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

function wz_policy_details(): array
{
    $config = wz_config();
    $seo = $config['seo'] ?? [];
    $details = [
        'operator_name' => '',
        'postal_address' => '',
        'support_email' => 'info@weddingza.com',
        'support_phone' => trim((string)($seo['contact_phone'] ?? '')),
        'grievance_officer_name' => '',
        'grievance_officer_designation' => '',
        'grievance_email' => 'info@weddingza.com',
        'effective_date' => '2026-10-07',
    ];

    if (filter_var($seo['contact_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $details['support_email'] = trim((string)$seo['contact_email']);
    }

    // Only public business details are stored here; no account or payment secrets.
    $localPath = WZ_ROOT . '/includes/policy-details.local.php';
    if (is_file($localPath)) {
        $local = require $localPath;
        if (is_array($local)) {
            $details = array_replace($details, array_intersect_key($local, $details));
        }
    }

    foreach ($details as $key => $value) {
        $details[$key] = is_string($value) ? trim($value) : '';
    }

    return $details;
}

function wz_policy_missing_details(array $details): array
{
    $missing = [];
    foreach ([
        'operator_name' => 'legal business/operator name',
        'postal_address' => 'full business postal address',
        'support_phone' => 'customer support phone number',
        'grievance_officer_name' => 'grievance officer name',
        'grievance_officer_designation' => 'grievance officer designation',
    ] as $key => $label) {
        if (!is_string($details[$key] ?? null) || trim($details[$key]) === '') {
            $missing[] = $label;
        }
    }
    foreach (['support_email' => 'support email', 'grievance_email' => 'grievance email'] as $key => $label) {
        if (!filter_var($details[$key] ?? '', FILTER_VALIDATE_EMAIL)) {
            $missing[] = $label;
        }
    }
    return $missing;
}

function wz_policy_navigation(string $current): void
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', wz_policy_details()['effective_date']);
    ?>
    <div class="policy-meta">
        <p>Effective date: <time><?= h($date ? $date->format('j F Y') : '7 October 2026') ?></time></p>
        <nav aria-label="Policy pages">
            <?php foreach (['privacy' => ['privacy.php', 'Privacy'], 'terms' => ['terms.php', 'Terms'], 'cancellation' => ['cancellation.php', 'Cancellation & refunds']] as $key => [$path, $label]): ?>
                <a href="<?= h($path) ?>"<?= $key === $current ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <?php
}

function wz_policy_contact(): void
{
    $details = wz_policy_details();
    ?>
    <section class="policy-contact" aria-labelledby="policy-contact-title">
        <h2 id="policy-contact-title">Business details &amp; grievance contact</h2>
        <dl>
            <?php foreach (['operator_name' => 'Legal operator', 'postal_address' => 'Business address', 'grievance_officer_name' => 'Grievance officer', 'grievance_officer_designation' => 'Designation', 'support_phone' => 'Support and grievance phone'] as $key => $label): ?>
                <?php if ($details[$key] !== ''): ?>
                    <div><dt><?= h($label) ?></dt><dd><?= nl2br(h($details[$key])) ?></dd></div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div><dt>Website</dt><dd><a href="<?= h(wz_app_url('')) ?>">weddingza.com</a></dd></div>
            <div><dt>Customer support &amp; privacy</dt><dd><a href="mailto:<?= h($details['support_email']) ?>"><?= h($details['support_email']) ?></a></dd></div>
            <div><dt>Grievance email</dt><dd><a href="mailto:<?= h($details['grievance_email']) ?>"><?= h($details['grievance_email']) ?></a></dd></div>
        </dl>
        <p>Consumer complaints are acknowledged within 48 hours and addressed within one month of receipt. Include your account email and any enquiry, booking or payment reference so we can investigate.</p>
    </section>
    <?php
}
