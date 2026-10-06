<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/layout.php';

if (!is_post()) {
    redirect('/#booking');
}

/** Replies as JSON to the page's fetch() call, or as a plain page when JavaScript is off. */
function booking_fail(array $errors, string $message, int $status = 422): never
{
    http_response_code($status);
    if (wants_json()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => $message, 'errors' => $errors]);
        exit;
    }
    simple_page_start('Booking not sent');
    echo '<div class="mx-auto simple-card"><h1 class="mb-3">We could not send your booking</h1><p>' . e($message) . '</p>';
    if ($errors) {
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . e($error) . '</li>';
        }
        echo '</ul>';
    }
    echo '<a class="btn btn-primary py-3 px-5 mt-3" href="/#booking">Back to the booking form</a></div>';
    simple_page_end();
    exit;
}

function booking_done(string $url): never
{
    if (wants_json()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'redirect' => $url]);
        exit;
    }
    redirect($url);
}

// Bots that fill the hidden field get a normal-looking response and nothing is saved
if (post('website') !== '') {
    booking_done('/');
}

$ip = client_ip();
if (!rate_limit_ok('booking', $ip, 5, 3600)) {
    booking_fail([], 'You have sent several bookings in a short time. Please wait a while, or contact us on WhatsApp instead.', 429);
}

$name = post('name', 80);
$phone = post('phone', 20);
$eventType = post('event_type', 120);
$packageId = post('package_id', 10);
$eventDate = post('event_date', 10);
$guests = post('guests', 6);
$venue = post('venue', 120);
$details = post_text('details', 600);

$errors = [];
if (mb_strlen($name) < 2) {
    $errors['name'] = 'Please enter your name.';
}
if (!valid_phone($phone)) {
    $errors['phone'] = 'Please enter a valid phone number.';
}
if (!in_array($eventType, booking_event_options(), true)) {
    $errors['event_type'] = 'Please choose an event type.';
}

$date = DateTimeImmutable::createFromFormat('!Y-m-d', $eventDate);
if ($date === false || $date->format('Y-m-d') !== $eventDate) {
    $errors['event_date'] = 'Please choose a valid event date.';
} elseif ($eventDate < date('Y-m-d')) {
    $errors['event_date'] = 'Please choose a date from today onwards.';
} elseif ($eventDate > date('Y-m-d', strtotime('+3 years'))) {
    $errors['event_date'] = 'Please choose a date within the next three years.';
}

if (!ctype_digit($guests) || (int) $guests < 1 || (int) $guests > 5000) {
    $errors['guests'] = 'Please enter the number of guests (1 to 5000).';
}
if (($_POST['consent'] ?? '') !== '1') {
    $errors['consent'] = 'Please tick this box so we can handle your booking.';
}

// The price is always taken from the database, never from the form
$packageLabel = '';
$packagePrice = null;
if ($packageId !== '') {
    $package = ctype_digit($packageId)
        ? one('SELECT p.*, g.name AS group_name FROM packages p JOIN package_groups g ON g.id = p.group_id WHERE p.id = ? AND p.is_active = 1 AND g.is_active = 1', [(int) $packageId])
        : null;
    if ($package === null) {
        $errors['package_id'] = 'That package is no longer available. Please choose another.';
    } else {
        $packageLabel = package_label(['name' => $package['group_name']], $package);
        $packagePrice = $package['price'];
    }
}

if ($errors) {
    booking_fail($errors, 'Please check the highlighted details and try again.');
}

// Unambiguous characters only, so a reference is easy to read out over the phone
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
do {
    $reference = 'MD-';
    for ($i = 0; $i < 6; $i++) {
        $reference .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
} while (scalar('SELECT COUNT(*) FROM bookings WHERE reference = ?', [$reference]));

$token = bin2hex(random_bytes(24));
$now = now_utc();

q('INSERT INTO bookings (reference, token, name, phone, event_type, package_label, package_price, event_date, guests, venue, details, consent_at, created_at, updated_at)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [$reference, $token, $name, $phone, $eventType, $packageLabel, $packagePrice, $eventDate, (int) $guests, $venue, $details, $now, $now, $now]);

rate_limit_hit('booking', $ip);

booking_done('/booking-status.php?t=' . $token);
