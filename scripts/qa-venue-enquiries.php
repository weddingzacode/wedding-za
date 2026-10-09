<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/crm.php';

$baseUrl = rtrim((string)($argv[1] ?? 'http://127.0.0.1:8088'), '/');
$config = wz_config();

if (!in_array(parse_url($baseUrl, PHP_URL_HOST), ['127.0.0.1', 'localhost', '::1'], true)
    || ($config['operations']['health_token'] ?? '') !== 'ci-health-token'
    || rtrim((string)($config['app_url'] ?? ''), '/') !== $baseUrl
) {
    throw new RuntimeException('Venue enquiry QA requires the isolated localhost CI configuration.');
}

$pdo = wz_db();

if (!$pdo) {
    throw new RuntimeException('The CI database is required for venue enquiry QA.');
}

$email = 'venue-qa-' . bin2hex(random_bytes(8)) . '@example.com';
$venueNames = ['Hotel Rudra Vilas', 'The Gopal Bagh & Resort'];

try {
    foreach ($venueNames as $venueName) {
        if (wz_crm_find_business_by_name($venueName)) {
            throw new RuntimeException('Use a fresh CI database without registered accounts for the supplied hotels.');
        }

        $fields = [
            'type' => 'vendor-enquiry',
            'name' => 'Venue HTTP QA',
            'phone' => '9000000000',
            'email' => $email,
            'vendor' => $venueName,
            'category' => 'Venues',
            'city' => 'Jaipur',
            'topic' => 'Wedding',
            'event_date' => '2027-02-15',
            'message' => 'QA only: 300 guests and 40 rooms.',
            'company_website' => '',
        ];
        $request = curl_init($baseUrl . '/api/lead.php');
        curl_setopt_array($request, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($request);
        $status = (int)curl_getinfo($request, CURLINFO_HTTP_CODE);
        curl_close($request);
        $result = json_decode(is_string($response) ? $response : '', true);

        if ($status !== 200 || empty($result['ok']) || ($result['storage'] ?? '') !== 'database') {
            throw new RuntimeException('The venue enquiry did not reach database storage: ' . $venueName);
        }

        $find = $pdo->prepare(
            'SELECT l.*, e.id AS enquiry_id, e.business_type AS enquiry_type,
                    e.subject AS enquiry_subject, e.event_type AS enquiry_event,
                    e.event_date AS enquiry_date
             FROM leads l
             LEFT JOIN crm_enquiries e ON e.source_lead_id = l.id
             WHERE l.email = :email AND l.vendor = :vendor
             ORDER BY l.id DESC
             LIMIT 1'
        );
        $find->execute(['email' => $email, 'vendor' => $venueName]);
        $stored = $find->fetch();

        if (!$stored || empty($stored['enquiry_id']) || $stored['enquiry_type'] !== 'venue'
            || $stored['enquiry_subject'] !== 'Wedding · ' . $venueName
            || $stored['enquiry_event'] !== 'Wedding' || $stored['enquiry_date'] !== $fields['event_date']
            || $stored['city'] !== 'Jaipur' || $stored['message'] !== $fields['message']
        ) {
            throw new RuntimeException('The stored venue or event details do not match: ' . $venueName);
        }

        echo 'PASS — HTTP enquiry reaches the lead inbox and venue CRM: ' . $venueName . PHP_EOL;
    }
} finally {
    $findTestRows = $pdo->prepare('SELECT e.id FROM crm_enquiries e JOIN leads l ON l.id = e.source_lead_id WHERE l.email = :email');
    $findTestRows->execute(['email' => $email]);

    foreach ($findTestRows->fetchAll(PDO::FETCH_COLUMN) as $enquiryId) {
        $deleteAudit = $pdo->prepare("DELETE FROM audit_log WHERE entity_type = 'crm_enquiry' AND entity_id = :id");
        $deleteAudit->execute(['id' => $enquiryId]);
        $deleteEnquiry = $pdo->prepare('DELETE FROM crm_enquiries WHERE id = :id');
        $deleteEnquiry->execute(['id' => $enquiryId]);
    }

    $deleteLeads = $pdo->prepare('DELETE FROM leads WHERE email = :email');
    $deleteLeads->execute(['email' => $email]);
}

echo "Venue HTTP enquiry QA complete; temporary records removed.\n";
