<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/policies.php';

$pageTitle = 'Cancellation & Refund Policy';
$pageDescription = 'Cancellation and refund rules for Wedding Za venue assistance, including full refunds before assistance begins and how to request help.';
$pageKey = 'cancellation';
$policy = wz_policy_details();
require __DIR__ . '/includes/header.php';
?>
<main>
    <?php wz_page_intro('POLICIES', 'Cancellation &amp; refunds', 'Know what happens if your plans change, work has already started or a payment needs to be checked.'); ?>
    <section class="section-sm">
        <article class="container legal-copy policy-copy">
            <?php wz_policy_navigation('cancellation'); ?>
            <div class="policy-summary">
                <p><strong>One Wedding assistance: full refund before work starts.</strong> Once assistance has started, we review the undelivered portion of the service. Payments made directly to a venue or vendor follow that business's agreed terms and applicable law.</p>
            </div>

            <h2 id="scope">1. What this policy covers</h2>
            <p>This policy covers fees paid to Wedding Za for its own services. The currently listed One Wedding venue-assistance plan is a one-time <strong>₹1,000</strong> service. It is separate from any venue deposit, rental, catering or vendor charge. The rules below apply when a payment has been successfully received; they do not mean that online checkout is available before the payment service is enabled.</p>
            <p>Any additional paid service must have its scope, price and cancellation terms disclosed before you purchase it. A later policy change will not retrospectively reduce the terms agreed for an earlier purchase.</p>

            <h2 id="before">2. Cancelling before assistance starts</h2>
            <p>If you cancel before Wedding Za has started the actual assistance, you are entitled to a full refund of the assistance fee paid. We do not deduct a cancellation penalty or payment-gateway charge from this full refund.</p>
            <p>Creating an account, submitting requirements, logging in or paying alone does not count as the start of assistance. Work starts when we begin an actual service for your request, such as sending personalised venue recommendations, contacting a venue on your instructions or coordinating a site visit. Our response to a cancellation request will identify any work already carried out.</p>

            <h2 id="after">3. Cancelling after assistance starts</h2>
            <p>If you cancel after assistance has begun, we review what was agreed, what was actually delivered and what remains undelivered. Any retained amount must relate fairly to work already delivered; it is not an automatic forfeiture of the entire fee. We will explain the work and calculation in writing and refund the amount attributable to the undelivered portion.</p>
            <p>If you believe the service was deficient or did not match what was agreed, tell us so we can investigate and offer an appropriate remedy. This review does not limit any refund, compensation or other remedy available under applicable consumer law.</p>

            <h2 id="our-cancellation">4. If Wedding Za cannot provide the service</h2>
            <p>If we cancel or cannot provide the agreed assistance, we will offer a full refund of the fee for that assistance. An alternative arrangement is offered only if you choose to accept it. We do not impose a separate cancellation penalty on you.</p>
            <p>If a venue becomes unavailable, we may help look for alternatives within the agreed assistance scope. That does not replace the venue's obligations for any booking or payment it accepted.</p>

            <h2 id="payment-issues">5. Duplicate, failed or unconfirmed payments</h2>
            <p>Contact us if you were charged more than once for the same service, charged an incorrect amount, or debited without a successful payment confirmation. We will check the transaction with the payment provider and refund any confirmed duplicate or excess amount. A failed payment may be reversed by your bank or payment provider; its timing depends on the transaction and payment method.</p>
            <p>Do not retry repeatedly while a deducted payment is being investigated. Send the payment reference and relevant details, without your full card details, passwords, OTPs or UPI PIN.</p>

            <h2 id="request">6. How to request cancellation or a refund</h2>
            <ol>
                <li>Email <a href="mailto:<?= h($policy['support_email']) ?>?subject=Cancellation%20or%20refund%20request"><?= h($policy['support_email']) ?></a> from the email address used for your account or purchase.</li>
                <li>Include your name, the service, payment date and amount, order or payment reference, and the reason for the request. Tell us if you want to cancel remaining assistance.</li>
                <li>We will verify the payment and work status, explain the decision and amount, and send a refund reference when a refund is submitted. A request or a status change in your workspace alone is not confirmation that funds have been returned.</li>
            </ol>
            <p>We acknowledge consumer complaints within 48 hours and address them within one month of receipt. If a bank or provider needs further information, we will explain what is needed and keep you informed. You can escalate a disputed decision to the grievance officer below.</p>

            <h2 id="timing">7. Refund method and timing</h2>
            <p>Approved refunds are submitted without undue delay through the original payment provider and normally returned to the original payment method. For Razorpay normal refunds, the credit generally takes <strong>7–10 business days after the refund is submitted</strong>, depending on the payment method and bank. This is a provider estimate, not a guaranteed bank credit date.</p>
            <p>If the refund has not appeared after the expected period, contact us with the refund reference so we can help trace it. You may also raise a payment dispute with your bank or provider under its rules. We will not ask for your payment password or OTP to process a refund.</p>

            <h2 id="suppliers">8. Venue and vendor bookings</h2>
            <p>A payment made directly to an independent venue or vendor is governed by the contract you agreed with that business and applicable law. Ask that business about its deposit, cancellation deadlines and refund process before paying. Wedding Za's assistance refund does not automatically cancel or refund a supplier booking.</p>
            <p>If you need help contacting the business, send us the booking reference and details. Responsibility for Wedding Za's own acts and any statutory consumer rights is not excluded by this distinction.</p>

            <h2 id="rights">9. Your consumer rights</h2>
            <p>This policy does not override rights available under applicable law. You may approach the <a href="https://consumerhelpline.gov.in/" rel="noopener noreferrer">National Consumer Helpline</a>, a competent Consumer Commission, court or other authority if a complaint remains unresolved. Please keep your payment receipt, correspondence and any supplier agreement.</p>

            <?php wz_policy_contact(); ?>
        </article>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
