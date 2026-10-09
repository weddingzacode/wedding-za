# City discovery

The homepage shows at most four configured cities. Its search and “View all” link open `cities.php`, an alphabetical directory with city-name search, a starting-letter filter and 12 results per page. Search works through normal GET forms, including when JavaScript is disabled. The existing brand styling is kept, with four cards per desktop row and two per mobile row on the homepage.

The real city list remains in `assets/data/site.json` under `cities`. Add names to that list as coverage expands; no layout changes are required for 50 or more cities. The directory count, search, features and sitemap read this list. This update does not add invented destinations, rewrite site data or add a city-management database table. The existing Admin → Website → Cities screen still displays the configured list.

City guides accept the canonical configured name, use a neutral illustration for an unmapped city, and show an enquiry link when no approved public vendors match. Other-city links are limited to six, followed by the full directory link. Unknown city names lead to the directory search.

## GoDaddy update

Download `scripts/apply-city-discovery.php` from the immutable release commit, then execute it from `~/public_html`. It carries verified source for six public files, refuses unknown local versions, backs up originals in a private folder outside the web root, stages changes before replacement and rolls back if replacement fails. It can be run again safely. Private configuration, public policy details, site data, account records and payment settings are outside its manifest.

Styles are loaded only by the three city-discovery pages, using a new versioned asset URL. Shared header/footer and existing cached styles are unchanged, so the preceding policy updater remains valid.

## Verification

- `php scripts/qa-cities.php`: 50-city pagination without missing or repeated cities, bounded features, query/letter handling, Unicode names, empty results and safe URLs.
- `php scripts/qa-city-update.php`: real baseline upgrade, source integrity, preservation of owner data, refusal of custom edits, private backups and idempotence.
- `tests/cities.spec.js`: desktop/mobile layout, search, clear/filter controls, selected guide navigation, escaped empty results and search without JavaScript. Screenshots are saved with browser QA artifacts.
