<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/layout.php';

$site = setting('site_name');
$contact = setting('contact_name');
$phones = array_values(array_filter([setting('phone_1'), setting('phone_2')], fn ($phone) => $phone !== ''));

simple_page_start('Privacy Notice');
?>
            <div class="mx-auto simple-card legal">
                <h1 class="mb-4">Privacy Notice</h1>
                <p>This notice explains what <?= e($site) ?> does with the details you give us through this website.</p>

                <h4>What we collect</h4>
                <p>When you request a booking we collect your name, phone number, the type and date of your event, the number of guests, and anything you choose to add about the venue or your requirements. We do not ask for an email address or create an account for you.</p>

                <h4>Why we collect it</h4>
                <p>We use these details only to respond to your booking request, to plan and deliver the catering, and to keep a record of bookings and deposits. We do not use them for marketing and we do not sell or share them with advertisers.</p>

                <h4>WhatsApp</h4>
                <p>After you submit the form you can send your booking to us on WhatsApp. That message is sent by you, from your own WhatsApp account, and is handled under WhatsApp's own terms and privacy policy.</p>

                <h4>Deposits and payments</h4>
                <p>If you choose to pay a deposit online, the payment is handled by Paynow. Your card or mobile money details are entered on Paynow's pages and are never seen or stored by this website. We keep only the amount, the payment reference and whether it succeeded.</p>

                <h4>Who can see your details</h4>
                <p>Your booking is visible to <?= e($contact) ?> and anyone <?= e($contact) ?> authorises to manage bookings. It is stored with our website hosting and database providers, who process it on our behalf. Your booking page can be opened by anyone who has its link, so share that link only with people you trust.</p>

                <h4>How long we keep it</h4>
                <p>We keep booking records for as long as they are needed to deliver your event and for our business records afterwards. You can ask us to delete your booking at any time.</p>

                <h4>Cookies</h4>
                <p>The public pages of this website do not set tracking or advertising cookies. A sign-in cookie is used only for staff who manage the site.</p>

                <h4>Your choices</h4>
                <p>You can ask to see, correct or delete the details we hold about you. Contact <?= e($contact) ?><?= $phones ? ' on ' . e(implode(' or ', $phones)) : '' ?> and we will help.</p>
            </div>
<?php
simple_page_end();
