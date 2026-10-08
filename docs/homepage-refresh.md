# Homepage refresh — 8 October 2026

The first section has a smaller headline, a short explanation and an always
visible city / occasion / service search. Submitting it opens `vendors.php`
with the actual selected filters, including when JavaScript is disabled.
The separate engagement photograph is hidden below 1101px to leave room for
the search. The existing shared logo, Contact link and event planning link stay.

Three customer steps explain city discovery, shortlisting and enquiries.
Occasion cards, four featured cities, four vendor profiles, three celebration
stories, seven inspiration cards and three journal guides use compact grids.
Celebration stories no longer occupy separate sticky full-screen scenes.
Planning help links to the existing planning studio and contact page.

Descriptions, dark-section headings and footer links have larger text or
stronger contrast. Phone layouts keep search visible and use smaller card
grids. Coordinated heading reveals, photo movement, card interactions and a
scroll progress line add motion. Visitors can pause effects; their preference
persists, and reduced-motion settings keep content visible. See
`homepage-motion.md` for the animation behavior.

## Content and assets

Birthday, baby shower, festive, dinner, dessert and stage images match their
card subjects. Journal imagery matches its guide topic. `home-content.php`
replaces only known legacy editorial photo URLs and known mismatched titles.
Custom uploads, new photo URLs and edited owner text are preserved; the
installer never writes `assets/data/site.json`.

Seventeen local WebP images total under 900 KB. Below-the-fold photographs
remain lazy-loaded. Instrument Serif and Manrope Latin fonts are served locally
for the homepage so the layout does not depend on third-party font delivery.
The original font files and their SIL Open Font License notices are included.
Photo sources are recorded in `home-photo-credits.md`.

## GoDaddy update

Run the pinned `scripts/apply-homepage-refresh.php` from `~/public_html` after
the header and city releases. It embeds 30 runtime files and the exact deployed
baselines, including the earlier homepage release. It validates every
destination and payload, then backs up originals
privately outside the web root, stages all replacements and publishes them by
rename. Unknown edits, unsafe paths or damaged payloads stop before publication.
An interrupted publication attempts to restore the original files. Repeating
the completed installation is safe.

The payload contains no database, private configuration, policies, uploaded
customer media, payment settings or mail/DNS settings. Cache version and script
query versions change so returning visitors receive the new homepage assets.

## Verification

`php scripts/qa-homepage-update.php` checks embedded hashes, nonempty photos,
upgrade compatibility, private backups, repeated runs, rejection of local
edits, symlinks and corrupt payloads, and preservation of owner content.
`tests/home.spec.js` checks search filters, JavaScript-free searching, working
shortlists, occasion routes, phone/desktop fit from 320px to 1600px, compact
stories, descriptive font sizes, heading contrast and reduced motion. The
`tests/home-motion.spec.js` checks scroll progress, photo reveals, desktop
pointer tilt, persistent pause/resume, working search while paused, and live
changes to the reduced-motion preference. The
browser suite also retains the shared navigation and 50-city discovery checks.

Earlier header, city and policy installers are immutable release snapshots.
Their QA validates and exercises those snapshots. Homepage installer QA checks
the current homepage source, its deployed header baseline and the earlier
homepage release.
