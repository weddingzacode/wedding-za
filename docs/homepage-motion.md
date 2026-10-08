# Homepage motion — 8 October 2026

The homepage has one animation controller in `assets/js/home-motion.js` and
one stylesheet in `assets/css/home-motion.css`. Shared navigation and animation
on other pages remain in `vision.js`. The old homepage animation and smooth
scroll entry points return early when the refreshed homepage is present, so
they cannot compete with this controller.

The opening headline reveals by word while the hero image settles into place.
Section headings and photographs reveal when they enter the viewport. Step
numbers and lines animate together. A slim line shows reading progress. On
devices with a mouse, cards tilt slightly toward the pointer, photographs zoom
on hover, and the hero and planning-help photos move gently while scrolling.
Links move their arrows and primary actions have a brief highlight on hover.
The desktop hero portrait and light texture move slowly while the hero is in
view. Background tabs and offscreen hero sections pause these repeating effects.

The second animation pass adds two opening panels over each discovery photo,
an image crop that opens into the full photograph, and lines with a turning
diamond between sections. Section labels and descriptions enter together.
The hero photo has a thin outline that draws once. On mouse devices, a soft
highlight follows the pointer across cards and primary buttons move at most
three pixels toward it. These effects reuse the controller and existing photo
timelines, and the one-off reveals finish in about a second.
Completed photo panels are hidden and do not replay on resize or resume.
Phone section descriptions reset the old two-column placement so titles and
introductions occupy the full width without overlap.

The search form remains reachable throughout the opening animation. Captions
stay within their cards and all headings retain their original text and
emphasis. Word spans are created with text nodes, without injecting HTML.
The existing locally bundled GSAP, ScrollTrigger and Lenis are reused; no new
external script is needed.

## Visitor preferences

The fixed Pause animations button stops all homepage effects, restores visible
content and destroys smooth scrolling. Resume animations starts them again.
Only an on/off preference is stored in browser storage. If storage is blocked,
the control still works for that visit.

System reduced-motion settings take priority, including changes during the
visit. Decorative motion and the control are hidden while reduced motion is
requested. Touch devices use native scrolling and do not receive pointer tilt.
When JavaScript or animation libraries are unavailable, the normal homepage
content and native search form remain usable.

## Deployment and checks

The backed-up homepage installer accepts the deployed header release, the
earlier homepage update and the first animation update. It includes the two
animation assets, updates
script query versions and advances the service-worker cache. The installer QA
verifies all three upgrade routes, rejection of unknown edits, preservation of owner
content, private backups and repeated runs.

Browser checks cover motion, pause/resume persistence, live reduced-motion
changes, search, shortlists, caption fit and responsive layouts on desktop and
phones. Photo panels must clear the images without intercepting links; pointer
effects must reset and pause cleanly. The rest of the navigation and catalogue
suite runs in release CI.
