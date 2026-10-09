<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/components.php';
require_once dirname(__DIR__) . '/includes/venue-directory.php';

function wz_venue_qa_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }

    echo 'PASS — ' . $message . PHP_EOL;
}

$venues = array_column(wz_data('vendors'), null, 'id');
$rudra = $venues['hotel-rudra-vilas-jaipur'];
$gopal = $venues['the-gopal-bagh-resort-jaipur'];
$filters = [
    'q' => '',
    'city' => '',
    'type' => '',
    'event' => '',
    'guests' => 0,
    'rooms' => 0,
    'max_price' => 0,
    'rating' => 0,
];

wz_venue_qa_assert(wz_venue_capacity($rudra) === 700 && $rudra['rooms'] === 45, 'Rudra Vilas retains 700 lawn guests and 45 rooms');
wz_venue_qa_assert(wz_venue_capacity($gopal) === 800 && $gopal['rooms'] === 63, 'Gopal Bagh retains the supplied 800 hall capacity and 63 rooms');
wz_venue_qa_assert(wz_venue_capacity(['capacity' => '120–1,000 guests']) === 1000, 'Existing formatted capacity ranges remain searchable');
wz_venue_qa_assert(wz_venue_capacity(['capacity' => 'Ask venue']) === 0, 'An unpublished capacity remains unknown');
wz_venue_qa_assert(wz_venue_matches($rudra, array_replace($filters, ['q' => 'RUDRA', 'city' => 'jaipur', 'guests' => 700])), 'Name, city and guest filters combine without case sensitivity');
wz_venue_qa_assert(!wz_venue_matches($rudra, array_replace($filters, ['guests' => 701])), 'An event above Rudra capacity is excluded');
wz_venue_qa_assert(wz_venue_matches($gopal, array_replace($filters, ['q' => 'Patrakar', 'rooms' => 60])), 'Neighbourhood and guest room filters work together');
wz_venue_qa_assert(!wz_venue_matches($rudra, array_replace($filters, ['rooms' => 60])), 'An accommodation minimum is respected');
wz_venue_qa_assert(!wz_venue_matches($gopal, array_replace($filters, ['city' => 'Goa'])), 'Other cities do not match Jaipur properties');
wz_venue_qa_assert(!wz_venue_matches($gopal, array_replace($filters, ['type' => 'Heritage hotel'])), 'Venue type is respected');
wz_venue_qa_assert(!wz_venue_matches($gopal, array_replace($filters, ['event' => 'Not supplied'])), 'Only supplied occasions match');

foreach ([$rudra, $gopal] as $venue) {
    wz_venue_qa_assert(wz_money_number($venue['price']) === 0 && $venue['price'] === 'Request pricing', 'No invented rate: ' . $venue['name']);
    wz_venue_qa_assert(!wz_venue_matches($venue, array_replace($filters, ['max_price' => 1000000])), 'An unknown quote is excluded from a price filter: ' . $venue['name']);
    wz_venue_qa_assert($venue['rating'] === 0 && $venue['reviews'] === 0 && !$venue['verified'], 'No fabricated rating, reviews or verification: ' . $venue['name']);
    $photos = wz_venue_photos($venue);
    wz_venue_qa_assert(count($photos) === 8, 'Eight distinct gallery images: ' . $venue['name']);

    foreach ($photos as $photo) {
        $image = getimagesize(dirname(__DIR__) . '/' . $photo['src']);
        wz_venue_qa_assert($image !== false && $image[0] === $photo['width'] && $image[1] === $photo['height'], 'Photo dimensions match: ' . $photo['src']);
    }
}

wz_venue_qa_assert(array_column($rudra['event_spaces'], 'capacity') === [700, 450, 150, null], 'Rudra lawn, both banquets and unspecified rooftop capacity are retained');
wz_venue_qa_assert(str_contains($gopal['event_spaces'][0]['description'], 'individual hall or a combined layout'), 'Gopal hall capacity qualification remains visible');
echo "Venue collection QA complete.\n";
