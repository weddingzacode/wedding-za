# Wedding Za policy release — 7 October 2026

The Privacy, Terms and Cancellation & Refund pages now describe the working
account, enquiry, planning and assistance service. Refund terms reflect the
owner's chosen rule: a full refund before assistance starts; after it starts,
refund the undelivered portion with a written explanation of work and calculation.
The ₹1,000 One Wedding fee is separate from independent supplier bookings.

## Install on the existing GoDaddy deployment

Download `scripts/apply-policy-pages.php` from the immutable release commit to a
file outside `public_html`, then run it from `~/public_html`. It includes the
checked page payloads; it does not need Git, Composer or a new ZIP upload.

The installer asks for actual public business details before changing website
files: legal operator name, full headquarters and branch postal addresses,
support phone/email, and the grievance officer's name, designation and email.
For a sole proprietor, provide the proprietor's actual legal name and trading
name; a brand alone must not be assumed to identify a registered legal entity.
The existing verified mailbox `info@weddingza.com` is the default support and
grievance email. The owner must monitor the selected mailbox, phone and grievance
process; the pages promise complaint acknowledgement within 48 hours and redress
within one month.

Public details are saved in `includes/policy-details.local.php`, excluded from
Git. Database passwords, existing accounts, health tokens and payment keys are
neither displayed nor rewritten. An existing complete details file is preserved.
Run the same installer with `--update-details` to update public details later.

All payload hashes, PHP syntax and expected destination versions are checked
before writes. Unknown versions, local edits and unsafe paths stop the update.
Original files are backed up under a unique folder outside `public_html`.
Files are staged there before replacement; a failed installation attempts to
restore every replaced file and reports any restoration failure.

After installation, open all three pages, check the public business details and
run `php scripts/go-live-check.php`. The checker separately blocks missing
business/grievance identity; absence of old draft phrases is not proof of legal
approval. No operator identity or postal address has been invented in this release.

## Application behaviour and operational follow-through

- Public and checkout links expose the same policies. Unavailable checkout now
  gives customer-facing help instead of configuration instructions.
- The privacy page discloses server records, local browser storage, essential
  sessions, requested supplier sharing, external fonts/images and conditional
  Google Analytics. It does not claim that an analytics consent banner exists.
- Privacy requests, cancellation review and refund approval are handled by the
  support team. A CRM refund status alone does not move money. Approved refunds
  must actually be submitted through the payment provider and reconciled.
- No automatic data-deletion schedule, India-only processing guarantee, venue
  booking guarantee or already-enabled live payments is claimed.
- Payment keys remain disabled/unconfigured until separately set up. These
  policy changes do not enable payment acceptance.

## Sources checked for this release

- [Consumer Protection (E-Commerce) Rules, 2020 — official court-hosted notified rules](https://thc.nic.in/Central%20Governmental%20Rules/Consumer%20Protection%20(E-Commerce)%20Rules,%202020.pdf): operator/address/contact disclosure, named grievance officer,
  48-hour acknowledgement, one-month redress and consumer protections.
- [Government explanation of the notified rules](https://www.pib.gov.in/PressReleasePage.aspx?PRID=1641559): scope includes online services and marketplace transactions.
- [Razorpay normal refunds](https://razorpay.com/docs/payments/refunds/normal/):
  original payment method and current 7–10 business-day estimate, dependent on
  bank and payment method. The merchant's original transaction fee is not used
  as a deduction from the promised full customer refund.
- [MeitY Digital Personal Data Protection Rules 2025](https://www.meity.gov.in/documents/act-and-policies/digital-personal-data-protection-rules-2025-gDOxUjMtQWa): phased commencement; the pages do not claim every provision is already in force.
- [Government announcement of the 2026 e-commerce amendment](https://www.pib.gov.in/newsite/erelcontent.aspx?lang=2&reg=48&relid=294532): stated effective date 1 January 2027; it is not treated as already effective for this October 2026 release.

Re-check applicable requirements when services or payment arrangements change.
The code checker verifies presence/configuration; it cannot verify the truth of
business details or certify legal compliance.
