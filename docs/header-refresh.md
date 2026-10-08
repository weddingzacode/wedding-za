# Header refresh — 8 October 2026

The shared header uses an ivory navigation bar, a linked-ring celebration mark,
clear icons, a Contact link and a plum “Plan an event” link to `planner.php`.
The homepage no longer displays the platform/year eyebrow or the vertical
Scroll / Discover caption. Existing hero photos, content and animations remain.

Below 1281px, primary navigation moves into the menu. On phones, account links
move there too, leaving the logo, event planning and menu controls in the header.
The menu supports Escape, focus return, keyboard containment and background
scroll locking. Contact and planning links also work without JavaScript.

## GoDaddy update

Run the pinned `scripts/apply-header-refresh.php` from `~/public_html`. It embeds
eight files, verifies installed versions, backs up originals privately outside
the web root and stages all replacements before publishing. Unknown local edits
stop the update before any website file changes. Repeating it is safe.

The homepage has two verified upgrade variants: the original city layout and
the compact city discovery release. Only the two requested labels are removed
from either variant, so this header update never changes city behavior.

Private configuration, database records, uploads, policy text, public business
details and payment configuration are not in the update payload.

New CSS and script query versions, plus a new service worker cache version,
prevent returning visitors from mixing old navigation assets with new markup.

## Checks

`php scripts/qa-header-update.php` verifies payload integrity, both upgrade
variants, private backups, repeated runs, refusal of local edits and symlinks,
and preservation of owner data. Playwright checks desktop and phone links,
keyboard navigation, JavaScript-free links and header fit from 320px to 1600px.

The header, city and policy installers remain immutable release snapshots.
Their QA verifies snapshot hashes and exercises those snapshots. The homepage
release checks current homepage source and its deployed header baseline.
