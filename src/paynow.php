<?php
declare(strict_types=1);

/**
 * Paynow (paynow.co.zw) hosted-checkout integration.
 *
 * Flow: we POST the transaction to Paynow, they return a browser URL to send
 * the customer to and a poll URL. Paynow then POSTs the outcome to our result
 * URL and sends the customer back to our return URL. Every message is signed
 * with an uppercase SHA-512 of the field values followed by the integration key.
 */

function paynow_base_url(): string
{
    return rtrim(env('PAYNOW_BASE_URL', 'https://www.paynow.co.zw'), '/');
}

function paynow_hash(array $fields): string
{
    $string = '';
    foreach ($fields as $key => $value) {
        if (strtolower((string) $key) !== 'hash') {
            $string .= $value;
        }
    }
    return strtoupper(hash('sha512', $string . env('PAYNOW_INTEGRATION_KEY', '')));
}

/** Parses a Paynow url-encoded message and checks its signature. */
function paynow_parse(string $body): array
{
    parse_str($body, $raw);
    $fields = [];
    foreach ($raw as $key => $value) {
        if (is_string($value)) {
            $fields[strtolower((string) $key)] = $value;
        }
    }
    return $fields;
}

function paynow_signature_ok(array $fields): bool
{
    return isset($fields['hash']) && hash_equals(paynow_hash($fields), strtoupper($fields['hash']));
}

function paynow_post(string $url, array $fields): string
{
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: MullersDelights/1.0\r\n",
        'content' => http_build_query($fields),
        'timeout' => 25,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    if ($body === false || !preg_match('#\s2\d\d(\s|$)#', $statusLine)) {
        error_log('[paynow] request failed: ' . ($statusLine !== '' ? $statusLine : 'no response'));
        throw new RuntimeException('The payment service could not be reached. Please try again in a moment.');
    }
    return $body;
}

/** Only ever call poll URLs on the Paynow host we initiated with. */
function paynow_poll_url_ok(string $url): bool
{
    $expected = parse_url(paynow_base_url());
    $actual = parse_url($url);
    return $actual !== false
        && ($actual['scheme'] ?? '') === ($expected['scheme'] ?? 'https')
        && strtolower($actual['host'] ?? '') === strtolower($expected['host'] ?? '');
}

/** Maps Paynow's status text to our payment status. */
function paynow_map_status(string $gatewayStatus): string
{
    return match (strtolower(trim($gatewayStatus))) {
        'paid', 'awaiting delivery', 'delivered' => 'paid',
        'cancelled' => 'cancelled',
        'failed', 'disputed', 'refunded' => 'failed',
        default => 'pending',
    };
}

/**
 * Creates a payment for the booking's deposit and returns the Paynow URL to
 * redirect the customer to.
 */
function paynow_start_deposit(array $booking): string
{
    if (!deposits_available()) {
        throw new RuntimeException('Online deposits are not available right now.');
    }
    $amount = number_format((float) setting('deposit_amount'), 2, '.', '');
    $attempt = (int) scalar('SELECT COUNT(*) FROM payments WHERE booking_id = ?', [$booking['id']]) + 1;
    $reference = $booking['reference'] . '-D' . $attempt;

    $fields = [
        'id' => env('PAYNOW_INTEGRATION_ID'),
        'reference' => $reference,
        'amount' => $amount,
        'additionalinfo' => 'Booking deposit ' . $booking['reference'],
        'returnurl' => app_url() . '/paynow-return.php?t=' . $booking['token'],
        'resulturl' => app_url() . '/paynow-result.php',
        // Paynow test mode only accepts the merchant's own login email here
        'authemail' => env('PAYNOW_AUTH_EMAIL', ''),
        'status' => 'Message',
    ];
    $fields['hash'] = paynow_hash($fields);

    $now = now_utc();
    q('INSERT INTO payments (booking_id, reference, amount, currency, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$booking['id'], $reference, $amount, 'USD', 'pending', $now, $now]);

    $response = paynow_parse(paynow_post(paynow_base_url() . '/interface/initiatetransaction', $fields));
    $status = strtolower($response['status'] ?? '');

    if ($status !== 'ok') {
        error_log('[paynow] initiate rejected for ' . $reference . ': ' . ($response['error'] ?? 'unknown error'));
        q("UPDATE payments SET status = 'failed', gateway_status = ?, updated_at = ? WHERE reference = ?",
            [mb_substr('Error: ' . ($response['error'] ?? 'unknown'), 0, 40), now_utc(), $reference]);
        throw new RuntimeException('The payment could not be started. Please try again or contact us on WhatsApp.');
    }
    if (!paynow_signature_ok($response) || empty($response['browserurl']) || empty($response['pollurl']) || !paynow_poll_url_ok($response['pollurl'])) {
        error_log('[paynow] initiate response failed verification for ' . $reference);
        q("UPDATE payments SET status = 'failed', gateway_status = 'Unverified response', updated_at = ? WHERE reference = ?", [now_utc(), $reference]);
        throw new RuntimeException('The payment could not be started safely. Please try again or contact us on WhatsApp.');
    }

    q('UPDATE payments SET poll_url = ?, gateway_status = ?, updated_at = ? WHERE reference = ?',
        [$response['pollurl'], 'Created', now_utc(), $reference]);
    q("UPDATE bookings SET payment_status = 'pending', updated_at = ? WHERE id = ? AND payment_status <> 'paid'", [now_utc(), $booking['id']]);

    return $response['browserurl'];
}

/**
 * Applies a verified Paynow status message (from the result callback or a poll)
 * to the matching payment and its booking. Returns false if it was rejected.
 */
function paynow_apply_status(array $fields): bool
{
    if (!paynow_signature_ok($fields) || empty($fields['reference'])) {
        error_log('[paynow] status message rejected: bad signature or missing reference');
        return false;
    }
    $payment = one('SELECT * FROM payments WHERE reference = ?', [$fields['reference']]);
    if ($payment === null) {
        error_log('[paynow] status message for unknown reference ' . $fields['reference']);
        return false;
    }

    $gatewayStatus = mb_substr($fields['status'] ?? '', 0, 40);
    $status = paynow_map_status($gatewayStatus);
    // A "paid" message for less than we asked for is not a paid deposit
    if ($status === 'paid' && (float) ($fields['amount'] ?? 0) + 0.001 < (float) $payment['amount']) {
        error_log('[paynow] amount mismatch on ' . $payment['reference']);
        $status = 'failed';
        $gatewayStatus = 'Amount mismatch';
    }
    // Never downgrade a payment that is already settled
    if ($payment['status'] === 'paid') {
        return true;
    }

    q('UPDATE payments SET status = ?, gateway_status = ?, paynow_reference = ?, updated_at = ? WHERE id = ?',
        [$status, $gatewayStatus, mb_substr($fields['paynowreference'] ?? $payment['paynow_reference'], 0, 60), now_utc(), $payment['id']]);
    q("UPDATE bookings SET payment_status = ?, updated_at = ? WHERE id = ? AND payment_status <> 'paid'", [$status, now_utc(), $payment['booking_id']]);
    return true;
}

/** Asks Paynow for the latest state of a payment. */
function paynow_poll(array $payment): bool
{
    if ($payment['poll_url'] === '' || !paynow_poll_url_ok($payment['poll_url'])) {
        return false;
    }
    try {
        $fields = paynow_parse(paynow_post($payment['poll_url'], []));
    } catch (RuntimeException $e) {
        return false;
    }
    // Paynow echoes the reference; make sure the answer is about this payment
    if (($fields['reference'] ?? '') !== $payment['reference']) {
        return false;
    }
    return paynow_apply_status($fields);
}
