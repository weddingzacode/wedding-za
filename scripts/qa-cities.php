<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/cities.php';

function wz_city_qa_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo 'PASS — ' . $message . PHP_EOL;
}

$sampleNames = [];
foreach (range(1, 50) as $i) {
    $sampleNames[] = sprintf('Test City %02d', $i);
}
$catalogue = wz_city_catalogue(array_reverse($sampleNames));
wz_city_qa_assert(count($catalogue) === 50, 'All 50 configured cities remain available');
wz_city_qa_assert(count(wz_city_featured($catalogue)) === 4, 'Homepage is capped at four cards even with 50 cities');
$visited = [];
foreach (range(1, 5) as $page) {
    $result = wz_city_directory($catalogue, '', '', $page);
    wz_city_qa_assert(count($result['items']) === ($page === 5 ? 2 : 12) && $result['pages'] === 5, 'Directory page ' . $page . ' has the expected size');
    $visited = array_merge($visited, array_column($result['items'], 'name'));
}
wz_city_qa_assert($visited === $sampleNames, 'Pagination visits every city once in alphabetical order');
$result = wz_city_directory($catalogue, '  test city 0  ', 't', 99);
wz_city_qa_assert($result['total'] === 9 && $result['page'] === 1 && $result['query'] === 'test city 0', 'Search normalizes spaces, ignores case and clamps page numbers');
wz_city_qa_assert(wz_city_directory($catalogue, '', '', -3)['page'] === 1, 'Negative page numbers return the first page');
wz_city_qa_assert(wz_city_directory($catalogue, '', '', PHP_INT_MAX)['page'] === 5, 'Out-of-range page numbers return the last page');
wz_city_qa_assert(wz_city_directory($catalogue, 'not listed')['total'] === 0, 'A missing city returns an empty result');
wz_city_qa_assert(wz_city_directory($catalogue, '', 'A')['total'] === 0, 'The letter filter is respected');
$small = wz_city_catalogue([' Jaipur ', 'jaipur', '', null, ['city' => 'invalid'], 'Delhi   NCR', 'Ålesund', 'A&B / City']);
wz_city_qa_assert(count($small) === 4, 'Duplicates, whitespace and invalid entries are handled');
wz_city_qa_assert(wz_city_directory($small, 'åle', 'å')['total'] === 1, 'Unicode search and initials work');
$onlyNew = wz_city_featured(wz_city_catalogue(['A&B / City']));
wz_city_qa_assert(count($onlyNew) === 1 && $onlyNew[0]['name'] === 'A&B / City', 'Featured cities come only from the configured list');
wz_city_qa_assert($onlyNew[0]['image'] === 'assets/images/image-fallback.svg', 'A new city uses a neutral illustration instead of Jaipur imagery');
wz_city_qa_assert(str_contains(wz_city_directory_url('A&B / City', 'A', 2), 'q=A%26B%20%2F%20City&letter=A&page=2'), 'Pagination preserves and safely encodes search filters');
wz_city_qa_assert(wz_city_directory([])['total'] === 0 && wz_city_featured([]) === [], 'An empty configured list is safe');
echo "City discovery QA complete.\n";
