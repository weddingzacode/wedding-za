<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/** The configured list is authoritative; featured cities never add coverage. */
function wz_city_catalogue(?array $names = null): array
{
    $edits = [
        'jaipur' => ['Palaces · courtyards · colour', 'photo-1767158597961-bd58dccc16bd'],
        'udaipur' => ['Lakes · light · old-world romance', 'photo-1770665567877-72ee8a7c9051'],
        'goa' => ['Salt air · sunset · after-parties', 'photo-1710952356679-1eff1cb5ba64'],
        'delhi ncr' => ['Grandeur · scale · everything close', 'photo-1587474260584-136574528ed5'],
        'mumbai' => ['City lights · sea views · celebrations', 'photo-1751608734207-1c68d53554b4'],
        'bengaluru' => ['Garden settings · contemporary celebrations', 'photo-1596176530529-78163a4f7af2'],
        'hyderabad' => ['Heritage · hospitality · grand occasions', 'photo-1578662996442-48f60103fc96'],
    ];
    $catalogue = [];
    foreach ($names ?? wz_data('cities') as $name) {
        if (!is_string($name)) {
            continue;
        }
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $key = mb_strtolower($name, 'UTF-8');
        if ($name === '' || isset($catalogue[$key])) {
            continue;
        }
        $edit = $edits[$key] ?? null;
        $catalogue[$key] = [
            'name' => $name,
            'description' => $edit[0] ?? 'Ideas and planning for your next celebration.',
            'image' => $edit
                ? 'https://images.unsplash.com/' . $edit[1] . '?auto=format&fit=crop&w=1200&q=85'
                : 'assets/images/image-fallback.svg',
            'image_alt' => $edit ? $name . ' event destination' : 'Wedding Za event planning illustration',
        ];
    }
    $catalogue = array_values($catalogue);
    usort($catalogue, fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
    return $catalogue;
}

function wz_city_featured(array $catalogue): array
{
    $featured = [];
    $priority = ['Jaipur', 'Udaipur', 'Goa', 'Delhi NCR'];
    foreach (array_merge($priority, array_column($catalogue, 'name')) as $name) {
        foreach ($catalogue as $city) {
            if (strcasecmp($city['name'], $name) === 0) {
                $featured[mb_strtolower($city['name'], 'UTF-8')] = $city;
                break;
            }
        }
        if (count($featured) === 4) {
            break;
        }
    }
    return array_values($featured);
}

function wz_city_directory(array $catalogue, string $query = '', string $letter = '', int $page = 1): array
{
    $query = mb_substr(trim(preg_replace('/\s+/u', ' ', $query) ?? ''), 0, 120, 'UTF-8');
    $letter = mb_strtoupper(trim($letter), 'UTF-8');
    $letters = array_unique(array_map(
        fn (array $city): string => mb_strtoupper(mb_substr($city['name'], 0, 1, 'UTF-8'), 'UTF-8'),
        $catalogue
    ));
    sort($letters, SORT_STRING);
    $matches = array_values(array_filter($catalogue, fn (array $city): bool =>
        ($query === '' || mb_stripos($city['name'], $query, 0, 'UTF-8') !== false)
        && ($letter === '' || mb_strtoupper(mb_substr($city['name'], 0, 1, 'UTF-8'), 'UTF-8') === $letter)
    ));
    $total = count($matches);
    $pageSize = 12;
    $pages = max(1, (int)ceil($total / $pageSize));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $pageSize;
    return [
        'items' => array_slice($matches, $offset, $pageSize),
        'total' => $total,
        'total_cities' => count($catalogue),
        'pages' => $pages,
        'page' => $page,
        'start' => $total ? $offset + 1 : 0,
        'end' => min($offset + $pageSize, $total),
        'query' => $query,
        'letter' => $letter,
        'letters' => array_values($letters),
    ];
}

function wz_city_directory_url(string $query = '', string $letter = '', int $page = 1): string
{
    $parameters = [];
    if ($query !== '') {
        $parameters['q'] = $query;
    }
    if ($letter !== '') {
        $parameters['letter'] = $letter;
    }
    if ($page > 1) {
        $parameters['page'] = $page;
    }
    return 'cities.php' . ($parameters ? '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986) : '') . '#city-results';
}
