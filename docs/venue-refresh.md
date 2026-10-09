# Venue collection refresh

The venue directory now shows property photos, guest capacity, room count, enquiry links, saving and comparison. Search combines venue name or neighbourhood, city, guests, venue type, occasion, rooms, published starting price and rating. Results paginate at 12 venues; configured cities and published CRM venues remain available.

Venue profiles include a keyboard-accessible gallery, event spaces, accommodation, facilities, location and an enquiry form using the existing lead endpoint. Photos remain directly accessible without JavaScript. Scroll reveals replay in both directions and respect reduced motion.

## Supplied properties

| Property | Rooms | Event spaces | Location |
| --- | ---: | --- | --- |
| Hotel Rudra Vilas | 45 | Lawn 700; banquet 1: 450; banquet 2: 150; rooftop capacity on request | SFS Choraha, opposite ICG College, Mansarovar, Jaipur; airport 4 km; main railway station 12 km |
| The Gopal Bagh & Resort | 63 | Two halls advertised up to 800, with individual versus combined layout to be confirmed; lawn 700; poolside capacity on request | Patrakar Colony, Mansarovar, Jaipur |

The 16 WebP images come from the user's supplied archive, eight per property. No photographs from other property folders are used. Captions describe the supplied views without asserting independent verification; some supplied imagery may be architectural visualization. The original archive remains unchanged.

Rates, review counts, independent verification, contact numbers and precise map coordinates were not supplied. Both new records use **Request pricing**, zero published reviews and no verification badge. Google Maps opens an address search. The enquiry confirmation states that Wedding Za receives the request; it does not claim automatic delivery to a hotel or confirmed availability.

## cPanel update

Download `scripts/apply-venue-refresh.php` from the approved, pinned release commit outside `public_html`, then run it with `~/public_html` as the current directory. The single file embeds all 35 runtime files and both new records.

The updater accepts the no-pause homepage release and the subsequent audited homepage release. It includes the header, footer and shared script dependencies needed to upgrade either directly. Unknown runtime edits stop publication. Missing photo folders are created only after preflight.

The updater merges the two additions into `assets/data/site.json`. It preserves existing IDs or names, other venue records, cities and owner settings. It does not alter the database, private configuration, policies, uploads or customer storage. A rerun does not duplicate records or rewrite an unchanged catalogue.

Original files are backed up in a private `weddingza-venue-backup-*` directory outside the website root. Hash checks, a lock, symlink refusal, staging and rollback protect publication. After success, hard-refresh `/venues.php` and both new profile pages. A service-worker version bump refreshes cached assets.

## Validation

- `php scripts/qa-venues.php`: supplied capacities, price and rating semantics, combined filters and all photo dimensions.
- `php scripts/qa-venue-update.php`: payload hashes, both supported releases, owner preservation, malformed data, local edits, symlinks, rollback, private backups and idempotence.
- `npx playwright test tests/venues.spec.js`: desktop and mobile filters, galleries, saving, comparison, enquiry payloads, narrow screens, no-JavaScript rendering and repeated/reduced motion.
- The MySQL HTTP smoke suite submits both new venue enquiries to the real lead endpoint and verifies their stored venue and event fields. Browser enquiry testing separately intercepts the endpoint to validate form behavior without customer data.
