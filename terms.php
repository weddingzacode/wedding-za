<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/policies.php';

$pageTitle = 'Terms of Use';
$pageDescription = 'Terms for Wedding Za accounts, venue and vendor discovery, event planning and the One Wedding assistance service.';
$pageKey = 'terms';
$policy = wz_policy_details();
require __DIR__ . '/includes/header.php';
?>
<main>
    <?php wz_page_intro('POLICIES', 'Terms of use', 'Clear terms for discovering businesses, planning your event and requesting Wedding Za assistance.'); ?>
    <section class="section-sm">
        <article class="container legal-copy policy-copy">
            <?php wz_policy_navigation('terms'); ?>
            <div class="policy-summary">
                <p>Wedding Za helps you discover venues and vendors and organise celebrations. A paid assistance plan is a separate service from a venue or vendor booking. Read the scope, price and refund terms before paying.</p>
            </div>

            <h2 id="service">1. The service and these terms</h2>
            <p>These terms apply to weddingza.com and its account and planning workspaces. Wedding Za offers discovery, comparison, enquiries and planning tools for weddings and other celebrations, together with any paid assistance described at checkout. Use the site lawfully and review these terms when creating an account or requesting a service.</p>
            <?php if ($policy['operator_name'] !== ''): ?>
                <p>Wedding Za is operated by <?= h($policy['operator_name']) ?>. Our business and grievance contact details appear below.</p>
            <?php endif; ?>
            <p>The <a href="privacy.php">Privacy Policy</a> explains our handling of information. The <a href="cancellation.php">Cancellation &amp; Refund Policy</a> explains cancellation and refund eligibility. Any specific service scope or supplier contract disclosed and agreed before purchase also applies to that purchase, subject to applicable law.</p>

            <h2 id="accounts">2. Accounts and eligibility</h2>
            <p>You must be at least 18 and able to enter a binding contract to create an account or purchase a service. Give accurate contact and event details, keep them current and protect your login credentials. A person managing a venue or vendor account must have authority to represent that business. Tell us promptly about suspected misuse of your account.</p>
            <p>You are responsible for instructions and content you submit with authority. An enquiry or account registration by itself does not confirm a venue, supplier, event date or paid service.</p>

            <h2 id="marketplace">3. Venue and vendor information</h2>
            <p>Listings, pictures, indicative prices, capacity and availability help you explore options. These details may change and a listing does not guarantee suitability, availability or a booking. Confirm the current quote, inclusions, taxes, capacity, permissions, accessibility, payment schedule and cancellation terms directly with the relevant business before committing.</p>
            <p>Unless a separate agreement expressly says otherwise, a venue or vendor booking is a contract between you and that business. The business is responsible for its agreed supply and performance. Wedding Za remains responsible for its own services, statements and obligations under applicable law. Recommendations and assistance do not guarantee the independent supplier's performance.</p>

            <h2 id="assistance">4. One Wedding venue assistance</h2>
            <p>The currently listed One Wedding plan costs <strong>₹1,000 as a one-time payment</strong> for venue-search assistance for one wedding. Its displayed scope includes curated recommendations, shortlist support, venue enquiries, site-visit coordination and Wedding Za support. It is not an automatically renewing subscription.</p>
            <p>This fee pays for assistance. It is not a venue deposit, rental charge, catering payment or vendor fee. It does not guarantee a confirmed booking, a particular venue, a discount or a successful event. Venue availability, rates and site-visit arrangements must be confirmed with the venue. We will agree the practical requirements and next steps with you and ask for approval before any separately charged additional work.</p>
            <p>The amount shown as the total before payment is the amount charged for that purchase. Any change in price or additional charge must be disclosed and agreed before purchase; it will not be applied retrospectively to a completed purchase. Refunds follow our <a href="cancellation.php">Cancellation &amp; Refund Policy</a>, including a full refund before assistance starts and a review of undelivered work after it begins.</p>

            <h2 id="payments">5. Orders and payments</h2>
            <p>Online checkout is available only when the payment service is enabled. When available, Razorpay processes checkout and offers the payment methods shown there. Submit payment only through the displayed secure checkout or another arrangement expressly confirmed by Wedding Za. Never share an OTP, UPI PIN, CVV or banking password with our support team.</p>
            <p>A successful payment must be confirmed in our records. Keep the payment reference and contact support if money was deducted but your plan remains unpaid; do not pay repeatedly while the transaction is being checked. We will investigate duplicate or incorrect charges. A venue booking becomes confirmed only under that venue's agreed booking process, independently of payment for the assistance plan.</p>

            <h2 id="content">6. Content and acceptable use</h2>
            <p>Submit content that is accurate and that you have the right to use. Get permission before sharing another person's personal details, photographs or copyrighted work. Business representatives are responsible for the accuracy and rights in their listings and portfolios.</p>
            <p>You retain ownership of content you submit. You give Wedding Za permission to store, display and use it as needed to provide the requested service or publication. This does not transfer ownership or permit unrelated sale of your private event information. Wedding Za's own text, designs and branding may not be copied or used commercially without permission, except where law permits it.</p>
            <p>Do not impersonate others, post false or unlawful material, harass people, send spam, attempt unauthorised access, disrupt the service or extract personal information without authority. We may remove inappropriate content or restrict an account to protect people or the service, or meet a legal obligation. Where appropriate we will explain the reason and allow you to contact support for review. A restriction does not remove any refund or other right you have under law.</p>

            <h2 id="availability">7. Availability and responsibility</h2>
            <p>We use reasonable care in providing our services, but maintenance, connectivity issues and other events may interrupt access. Keep copies of important event decisions and contracts. Tell us if a tool or record appears incorrect so we can investigate.</p>
            <p>If circumstances outside our reasonable control affect assistance, we will discuss revised arrangements with you. We will not require you to accept a replacement service instead of a refund where a refund is due. These terms do not exclude responsibility that cannot lawfully be excluded, or limit remedies for fraud, misrepresentation, deficient service or other protected consumer rights.</p>

            <h2 id="ending">8. Account closure and changes</h2>
            <p>You can request account closure through support. Closing your account is separate from cancelling a venue, vendor booking or paid assistance, so tell us which service you want to cancel. Information may need to be retained as explained in our Privacy Policy.</p>
            <p>We may update these terms for future use or purchases and show the revised date. Material changes to an existing paid service require appropriate notice and any agreement required by law. A later policy update does not retrospectively reduce the refund rights agreed for an earlier purchase.</p>

            <h2 id="rights">9. Complaints and applicable law</h2>
            <p>Indian law applies subject to any mandatory protections that apply to you. Contact the grievance officer below to report a problem. Nothing requires you to give up a right to approach the National Consumer Helpline, a competent Consumer Commission, court or other authority. Your statutory remedies and applicable jurisdiction are preserved.</p>

            <?php wz_policy_contact(); ?>
        </article>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
