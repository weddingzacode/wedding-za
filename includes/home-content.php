<?php

declare(strict_types=1);

/** Keep the existing catalogue editable; only replace known editorial photos. */
function wz_home_photo(string $source): string
{
    if (parse_url(trim($source), PHP_URL_HOST) !== 'images.unsplash.com') {
        return $source;
    }

    $photos = [
        '/photo-1744805624952-dab790f6b3bd' => 'decor',
        '/photo-1769500810743-5e5dd4fd5848' => 'wedding',
        '/photo-1776078171101-0776fd3a953f' => 'engagement',
        '/photo-1695277789188-ec9ef58d3bc3' => 'corporate',
        '/photo-1771992226261-c1efb190ed34' => 'style',
        '/photo-1781077127473-343904bb5856' => 'portrait',
        '/photo-1767158597961-bd58dccc16bd' => 'jaipur',
        '/photo-1770665567877-72ee8a7c9051' => 'udaipur',
        '/photo-1710952356679-1eff1cb5ba64' => 'goa',
        '/photo-1587474260584-136574528ed5' => 'delhi',
        '/photo-1770387688476-d7072fb2ca2e' => 'photography',
        '/photo-1770387688486-397ff1afdb2c' => 'films',
    ];

    $name = $photos[parse_url(trim($source), PHP_URL_PATH)] ?? null;

    return $name === null ? $source : 'assets/images/home-' . $name . '.webp';
}

function wz_home_events(array $events): array
{
    $corrections = [
        'birthday' => ['1770387688486-397ff1afdb2c', 'birthday'],
        'anniversary' => ['1744805624952-dab790f6b3bd', 'dinner'],
        'baby-shower' => ['1732259495388-af40b972c311', 'baby-shower'],
        'festive' => ['1770346279037-89853a3e8c60', 'festive'],
        'private-party' => ['1744805624952-dab790f6b3bd', 'dinner'],
    ];

    foreach ($events as &$event) {
        $source = (string) ($event['image'] ?? '');
        $correction = $corrections[$event['id'] ?? ''] ?? null;

        if ($correction !== null
            && parse_url(trim($source), PHP_URL_HOST) === 'images.unsplash.com'
            && parse_url(trim($source), PHP_URL_PATH) === '/photo-' . $correction[0]
        ) {
            $event['image'] = 'assets/images/home-' . $correction[1] . '.webp';
        } else {
            $event['image'] = wz_home_photo($source);
        }
    }
    unset($event);

    return $events;
}

function wz_home_ideas(array $ideas): array
{
    $corrections = [
        2 => [
            'source' => '1770346279037-89853a3e8c60',
            'photo' => 'dinner',
            'previous_title' => 'Candlelight, carnations and low tables',
            'title' => 'Candlelight and flowers for an intimate dinner',
        ],
        4 => [
            'source' => '1587271636175-90d58cdad458',
            'photo' => 'jaipur',
            'previous_title' => 'Palace courtyards at golden hour',
            'title' => 'Heritage architecture for a royal backdrop',
        ],
        5 => [
            'source' => '1732259495388-af40b972c311',
            'photo' => 'desserts',
        ],
        6 => [
            'source' => '1610030469983-98e550d6193c',
            'photo' => 'baby-shower',
            'previous_title' => 'Soft florals and joyful family tables',
            'title' => 'Soft balloons and a joyful baby shower',
        ],
        7 => [
            'source' => '1771992230505-97e0c3d38213',
            'photo' => 'corporate',
        ],
    ];

    foreach ($ideas as &$idea) {
        $source = (string) ($idea['image'] ?? '');
        $correction = $corrections[(int) ($idea['id'] ?? 0)] ?? null;

        if ($correction !== null
            && parse_url(trim($source), PHP_URL_HOST) === 'images.unsplash.com'
            && parse_url(trim($source), PHP_URL_PATH) === '/photo-' . $correction['source']
        ) {
            $idea['image'] = 'assets/images/home-' . $correction['photo'] . '.webp';

            if (isset($correction['previous_title'])
                && ($idea['title'] ?? '') === $correction['previous_title']
            ) {
                $idea['title'] = $correction['title'];
            }
        } else {
            $idea['image'] = wz_home_photo($source);
        }
    }
    unset($idea);

    return $ideas;
}

function wz_home_articles(array $articles): array
{
    $corrections = [
        'jaipur-venues' => [
            'source' => '1587271636175-90d58cdad458',
            'title' => 'How to shortlist a Jaipur event venue without wasting weekends',
            'photo' => 'jaipur',
        ],
        'makeup-trial' => [
            'source' => '1781077126479-437220427c93',
            'title' => 'The guest-flow details that make private events feel effortless',
            'photo' => 'dinner',
        ],
        'guest-experience' => [
            'source' => '1769500810743-5e5dd4fd5848',
            'title' => 'Build a corporate event run-of-show that actually holds',
            'photo' => 'corporate',
        ],
    ];

    foreach ($articles as &$article) {
        $source = (string) ($article['image'] ?? '');
        $correction = $corrections[$article['id'] ?? ''] ?? null;

        if ($correction !== null
            && ($article['title'] ?? '') === $correction['title']
            && parse_url(trim($source), PHP_URL_HOST) === 'images.unsplash.com'
            && parse_url(trim($source), PHP_URL_PATH) === '/photo-' . $correction['source']
        ) {
            $article['image'] = 'assets/images/home-' . $correction['photo'] . '.webp';
        } else {
            $article['image'] = wz_home_photo($source);
        }
    }
    unset($article);

    return $articles;
}
