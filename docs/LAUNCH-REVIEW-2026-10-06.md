# Weddingza launch review — 6 October 2026

**Decision: ready for upload and hosting verification; public launch is not yet approved.**

The user confirmed that the new website has not been uploaded to GoDaddy.
This review covers the PHP source in `weddingzacode/wedding-za`, based on
commit `88a60e605ec55f5b7dca2ffefd70bf1eec294f2f`, with the changes in this release.
It does not certify an installed GoDaddy website or the old Vercel frontend.

## Fixes included

- Login fails safely when the database is unavailable. Passwordless demo access
  requires explicit opt-in, a loopback hostname/client, and no configured database.
  Old demo sessions are rejected when those conditions no longer hold.
- Session pages send `Cache-Control: no-store, private`.
- The Razorpay webhook returns HTTP 503 when a captured payment cannot be
  persisted, allowing delivery retries instead of silently acknowledging loss.
- Repeated payment verification and webhook-first delivery no longer return a
  false missing-order error. An active plan retains its status on replay.
- The launch checker requires cURL, a non-placeholder HTTPS URL, all schema
  tables, an active administrator, disabled demo access and finalized policy pages.
  Its success message describes configuration checks, not a complete live audit.
- GSAP, ScrollTrigger and Lenis are bundled locally with upstream notices.
  The animation libraries no longer depend on a CDN at runtime.
- Contact copy and GoDaddy setup instructions were updated. CI includes security
  regression checks and the required PHP extensions.

## Validation completed

| Check | Result |
| --- | --- |
| PHP source syntax | Passed |
| Application and bundled JavaScript syntax / site JSON | Passed |
| Fresh database schema import | Passed on MariaDB 10.11.14 |
| Existing database upgrade against the fresh schema | Passed |
| Customer/vendor CRM integration script | Passed |
| Venue CRM integration script | Passed |
| Admin CRM integration script | Passed |
| Existing HTTP route/health smoke suite | Passed |
| Desktop/mobile Chromium suite | 36 passed, 0 failed, 0 skipped |
| Local-only demo access and disabled-session cases | 6 passed |
| Launch-checker regression cases | 4 passed |
| HTTP payment security and outage checks | Passed with synthetic local signatures |

Payment checks covered authentication, CSRF, invalid signatures, webhook-first
delivery, repeated callbacks, active-plan preservation, database-outage login,
and retryable webhook failure. They used temporary local test data, which was
removed. No real payment was made. MySQL 8 verification and remote GitHub CI
results are not claimed by this local MariaDB run.

The first browser pass had two animation dependency failures. After bundling
the libraries, all 36 checks passed. The main mobile routes were checked at
320, 375, 390 and 768 px. Browser tests do not certify the real production
photography, remote font delivery, or every authenticated CRM layout.

## Required before public launch

1. Replace the draft privacy, terms, and cancellation/refund policy copy with
   client-approved business content. Review real listings, prices, images and
   contact details. Sample editorial/inventory data remains in the package.
2. Configure the final domain, database, administrator, health token and
   permissions on GoDaddy. The archive intentionally excludes private credentials.
3. Verify valid SSL, HTTP-to-HTTPS routing, Apache security enforcement and
   forbidden access to private configuration, database and storage files.
4. Test Razorpay Test Mode, automatic capture, cancellation, webhook delivery
   and the final Live Mode configuration. Complete a controlled real payment.
5. Configure/test external email or push only if the business enables those
   channels; otherwise notifications remain in-app.
6. Test all four portals, signup, enquiries, quotes, bookings, media uploads,
   member card, mobile navigation and animations on the installed test domain.
7. Retain the old full backup and confirm rollback before the domain cutover.

Use `GODADDY-START-HERE.txt` and `docs/GO-LIVE.md` for the upload sequence.
Do not interpret the package filename or passing local tests as public launch approval.
