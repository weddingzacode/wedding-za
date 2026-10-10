# Venue collection update — 10 October 2026

Adds the eight supplied Jaipur venues, detailed venue guides and a call button
for Weddingza on 9785005549 on every directory listing. The directory uses
ivory and burgundy styling, responsive layouts, lightweight reveal animations,
expandable space summaries, and filters for names, neighbourhoods, cities,
guest capacities, rooms, occasions, types, ratings and published prices.

Hari Van and JISAA show the supplied ₹11.50 lakh per-function price. Unknown
prices display Request pricing. Price filters compare the selected unit only.
The new venues use clearly marked illustrated covers until photos are supplied.

## GoDaddy deployment

Run from the current website folder, `~/public_html`. Download
`scripts/apply-venue-collection.php` from this release to a file outside the
website folder, then run it with PHP. The updater contains its own payload.
No Git checkout, ZIP upload, database migration or external PHP dependency is
needed on the server.

The updater validates file hashes and PHP syntax, stages changes, backs up
original files outside the website folder, and installs only 15 runtime files.
Unexpected versions or local edits cause it to stop before installation.
Re-running an already installed release leaves files unchanged.

Existing `assets/data/site.json`, approved CRM records, configuration, login,
header, footer, uploaded media and owner records are preserved. The additions
are file-backed catalogue overlays rather than new venue CRM accounts.

## Validation

PHP 8.3 and JavaScript syntax passed. Rendering against the latest release
catalogue showed 13 listings with 13 call buttons. All eight detailed guides
rendered. Search, filters, price units and empty results were checked.

Installer checks passed for exact output hashes, catalogue/configuration/media
preservation, repeat installation and refusal of unexpected local edits.

Browser/device visual checks and live CRM enquiry delivery must be tested after
deployment. This commit by itself does not change the GoDaddy live website.
