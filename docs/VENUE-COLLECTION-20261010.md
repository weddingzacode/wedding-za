# Venue collection update — completed 15 October 2026

Adds the eight supplied Jaipur venues, detailed venue guides and a call button
for Weddingza on 9785005549 on every directory listing and venue profile. Saving and comparison cover both the existing and newly added venues. The directory uses
ivory and burgundy styling, responsive layouts, lightweight reveal animations that replay in both scroll directions,
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
original files outside the website folder, and installs only 22 runtime files.
Unexpected versions or local edits cause it to stop before installation.
Re-running an already installed release leaves files unchanged.

Existing `assets/data/site.json`, approved CRM records, configuration, login,
header, footer, uploaded media and owner records are preserved. The additions
are file-backed catalogue overlays rather than new venue CRM accounts.

The updater accepts both the first venue directory release and the 10 October collection release. It preserves owner edits by identity and retains their room counts, capacities, published prices, photos and reviews. Results paginate at 12 venues, city choices include the configured city catalogue, and phone buttons use `tel:+919785005549`. New enquiry forms send `category=Venues` so the CRM routes them as venue requests.

## Validation

- PHP and JavaScript syntax checks, supplied venue facts and legacy capacity parsing.
- `php scripts/qa-venue-collection.php`: payload hashes, both supported releases, owner preservation, local-edit refusal, rollback, private backups and repeat installation.
- `npx playwright test`: desktop/mobile filters, price units, pagination, every new profile, phone links, shortlisting, comparison, enquiry payloads, no-JavaScript browsing, narrow screens and replaying/reduced motion.
- `php scripts/qa-venue-enquiries.php`: all ten supplied venues are submitted to the real HTTP endpoint in the isolated CI database, and their venue category and event details are checked in the CRM.

The eight new covers remain clearly labelled illustrations; the original Rudra Vilas and Gopal Bagh photo galleries are retained. This source release is applied to GoDaddy by running its pinned updater; a GitHub commit alone does not deploy the live server.
