<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/policies.php';

$pageTitle = 'Privacy Policy';
$pageDescription = 'How Wedding Za collects, uses and protects account, event, enquiry and payment information, and how to contact us about your data.';
$pageKey = 'privacy';
$policy = wz_policy_details();
$analyticsEnabled = trim((string)(wz_config()['analytics']['measurement_id'] ?? '')) !== '';
require __DIR__ . '/includes/header.php';
?>
<main>
    <?php wz_page_intro('POLICIES', 'Privacy policy', 'Your information should help you plan your celebration. Here is what we collect, how we use it and the choices you have.'); ?>
    <section class="section-sm">
        <article class="container legal-copy policy-copy">
            <?php wz_policy_navigation('privacy'); ?>
            <div class="policy-summary">
                <p>We use your details to run your account, support your event plans and respond to enquiries. An enquiry may be shared with the venue or vendor you ask to contact. We do not sell your personal information.</p>
            </div>

            <h2 id="scope">1. Who this policy covers</h2>
            <p>This policy applies to weddingza.com and the Wedding Za customer, venue, vendor and administrator workspaces. It covers visitors, account holders, business representatives and people who contact us. It does not cover an independent venue or vendor's own website, booking contract or handling of information after you share it with them.</p>
            <?php if ($policy['operator_name'] !== ''): ?>
                <p>The service is operated by <?= h($policy['operator_name']) ?>. Our business and grievance contact details appear below.</p>
            <?php endif; ?>

            <h2 id="information">2. Information we collect</h2>
            <ul>
                <li><strong>Account and profile details:</strong> your name, email address, phone number, account role, city and profile information. Passwords are stored as hashes rather than readable passwords.</li>
                <li><strong>Event and planning details:</strong> event type and date, guest count, budget, requirements, notes, shortlists, saved ideas and planning activity you provide or save.</li>
                <li><strong>Enquiries and service records:</strong> messages, contact requests, venue or vendor enquiries, appointments, quotes, bookings, invoices, support conversations and payment or refund records associated with your use of the service.</li>
                <li><strong>Business content:</strong> business names, categories, locations, prices, availability, descriptions, portfolios and other material submitted by venue or vendor representatives.</li>
                <li><strong>Technical information:</strong> IP addresses, login and security activity, device or browser information available in server logs, and cookies or browser storage needed for the site to work.</li>
                <li><strong>Payment information:</strong> when online payments are available, order and payment references, amounts, currency and payment status. Payment details entered into Razorpay checkout are handled by Razorpay and the relevant financial providers. Wedding Za does not store your full card number, CVV, UPI PIN or banking password.</li>
            </ul>
            <p>Please provide information you are entitled to share. Avoid putting identity documents, health information, financial credentials or other unnecessary sensitive details in planning notes or enquiry messages. If you provide details about another person, make sure you have their authority to do so.</p>

            <h2 id="use">3. How we use information</h2>
            <p>We use information to create and secure accounts; save and display your plans; help find suitable venues and vendors; deliver requested assistance; route and follow up enquiries; manage bookings and payments; answer support requests; prevent abuse or fraud; and maintain, troubleshoot and improve the service. We also keep records needed for accounting, disputes and applicable legal obligations.</p>
            <p>We may contact you about an enquiry, payment, appointment or service you requested. In-app notifications are available in your workspace. Email or push delivery may also be used when those services are enabled. Optional promotional communications are separate from messages needed to deliver a service; you can ask us to stop promotional messages.</p>

            <h2 id="sharing">4. Who can receive information</h2>
            <ul>
                <li><strong>Requested venues and vendors:</strong> relevant contact details, event requirements and messages when you make an enquiry or ask us to coordinate with them. Information is not sent to every listed business simply because you create an account.</li>
                <li><strong>Authorised staff and service providers:</strong> people supporting your enquiry or service, and providers of hosting, storage, domain services, payments and, when enabled, communication or analytics services. They receive information needed for their role.</li>
                <li><strong>Public visitors:</strong> business profiles, portfolios, reviews or celebration content that you submit for publication may become public. Your private customer profile and planning notes are not automatically published as a public listing.</li>
                <li><strong>Authorities and professional advisers:</strong> where disclosure is required by law or reasonably necessary to address fraud, safety concerns, legal claims or protect lawful rights.</li>
            </ul>
            <p>Independent venues and vendors are responsible for the information they collect and their own privacy practices. Service providers may process information outside India; any such processing remains subject to applicable requirements. We do not promise that all third-party processing takes place in India.</p>

            <h2 id="storage">5. Cookies, browser storage and external content</h2>
            <p>We use an essential session cookie to keep you signed in and support account security. Some planning tools save preferences, shortlists or progress in your browser. The app may cache public assets to help it load. Clearing cookies or site storage can sign you out or remove information saved only on that device; it does not delete information already saved to your account or our server.</p>
            <?php if ($analyticsEnabled): ?>
                <p>Google Analytics is currently enabled on this website to help us understand visits and use of public pages. Google may receive identifiers and device or usage information. You can manage cookies through your browser and review <a href="https://policies.google.com/privacy" rel="noopener noreferrer">Google's privacy information</a> and its <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener noreferrer">Analytics opt-out tool</a>.</p>
            <?php else: ?>
                <p>Google Analytics tracking is not currently enabled on this website. If that changes, we will update this notice and apply any consent requirements that apply.</p>
            <?php endif; ?>
            <p>Some pages load fonts from Google or pictures from external image providers such as Unsplash. Your browser contacts those providers, which may receive your IP address and request information. Their privacy policies apply to their processing. Links to other websites do not make their practices part of Wedding Za's service.</p>

            <h2 id="retention">6. Storage, retention and security</h2>
            <p>Account and service information is kept in our server databases or operational storage, with backups. We retain information while it is needed to provide the service, maintain your account, resolve a complaint, prevent abuse or meet record-keeping requirements. Different records may need different retention periods. Information no longer needed is deleted or de-identified; copies in backups may remain until those backups are replaced and are not used for normal day-to-day access.</p>
            <p>We use HTTPS, password hashing, access restrictions and other reasonable safeguards. No internet service can guarantee complete security. Keep your account password private, use a unique password, and contact us promptly if you suspect unauthorised access.</p>

            <h2 id="choices">7. Your choices and requests</h2>
            <p>You can update supported profile fields in your account and manage available notification preferences. For access, correction, a copy of information, account closure, deletion or a privacy complaint, email <a href="mailto:<?= h($policy['support_email']) ?>"><?= h($policy['support_email']) ?></a> from your account email address. These requests are handled by our team; we may ask for proportionate information to verify your identity.</p>
            <p>You can withdraw permission for optional uses by contacting us. Withdrawing information needed to fulfil a service may affect what we can provide, and closing an account does not automatically cancel a booking or payment. We will explain any information we must retain for legal, accounting, security or dispute purposes and any applicable limits on a request. Nothing in this policy takes away rights available under applicable law.</p>

            <h2 id="children">8. Children</h2>
            <p>Wedding Za accounts and paid services are intended for adults aged 18 or over. Children should not create accounts or make purchases. If an event involves children, share only information necessary for the event and with appropriate authority. A parent or guardian who believes a child has provided personal information without authority can contact us to request a review.</p>

            <h2 id="changes">9. Changes to this policy</h2>
            <p>We may update this policy when our services or applicable requirements change. The date shown above identifies the current version. We will provide additional notice or seek permission where required for a material change in how information is used.</p>

            <?php wz_policy_contact(); ?>
        </article>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
