<?php
declare(strict_types=1);

/** Icons the admin can choose from (Font Awesome 5 free, solid). */
function icon_choices(): array
{
    return [
        'fa-utensils' => 'Knife and fork',
        'fa-concierge-bell' => 'Serving bell',
        'fa-coffee' => 'Coffee cup',
        'fa-mug-hot' => 'Hot mug',
        'fa-hamburger' => 'Burger',
        'fa-drumstick-bite' => 'Drumstick',
        'fa-fish' => 'Fish',
        'fa-carrot' => 'Carrot',
        'fa-seedling' => 'Seedling',
        'fa-ice-cream' => 'Dessert',
        'fa-glass-cheers' => 'Glasses',
        'fa-birthday-cake' => 'Birthday cake',
        'fa-graduation-cap' => 'Graduation cap',
        'fa-ring' => 'Ring',
        'fa-handshake' => 'Handshake',
        'fa-blender' => 'Blender',
        'fa-briefcase' => 'Briefcase',
        'fa-church' => 'Church',
        'fa-users' => 'People',
        'fa-home' => 'Home',
        'fa-gift' => 'Gift',
        'fa-music' => 'Music',
        'fa-award' => 'Award',
        'fa-user-tie' => 'Professional',
        'fa-heart' => 'Heart',
        'fa-star' => 'Star',
        'fa-clock' => 'Clock',
        'fa-truck' => 'Delivery',
    ];
}

function valid_icon(string $icon): string
{
    return array_key_exists($icon, icon_choices()) ? $icon : 'fa-utensils';
}

/**
 * Every editable piece of site text: key => [label, type, max length, default].
 * Grouped by the section headings shown on the admin Content page.
 */
function content_fields(): array
{
    return [
        'Hero' => [
            'hero_tagline' => ['Tagline (script text above the headline)', 'text', 80, 'Good Food Brings People Together'],
            'hero_title' => ['Headline (a new line starts a new row)', 'textarea', 120, "Delicious Meals,\nMemorable Moments"],
            'hero_text' => ['Introduction', 'textarea', 400, "Muller's Delights Catering Services brings Chef Muller's cooking to your home or venue, from a family breakfast to a wedding, marooro or business event. Fresh ingredients, quality food and professional service, every time."],
        ],
        'Promise cards' => [
            'promise_1_icon' => ['Card 1 icon', 'icon', 40, 'fa-seedling'],
            'promise_1_title' => ['Card 1 title', 'text', 40, 'Fresh Ingredients'],
            'promise_1_text' => ['Card 1 text', 'textarea', 160, 'Every dish starts with fresh produce, prepared on the day for your guests.'],
            'promise_2_icon' => ['Card 2 icon', 'icon', 40, 'fa-award'],
            'promise_2_title' => ['Card 2 title', 'text', 40, 'Quality Food'],
            'promise_2_text' => ['Card 2 text', 'textarea', 160, 'Generous, well-seasoned meals cooked and presented with care.'],
            'promise_3_icon' => ['Card 3 icon', 'icon', 40, 'fa-user-tie'],
            'promise_3_title' => ['Card 3 title', 'text', 40, 'Professional Service'],
            'promise_3_text' => ['Card 3 text', 'textarea', 160, 'Chef Muller handles the kitchen so you can spend the day with your guests.'],
            'promise_4_icon' => ['Card 4 icon', 'icon', 40, 'fa-heart'],
            'promise_4_title' => ['Card 4 title', 'text', 40, 'Your Satisfaction'],
            'promise_4_text' => ['Card 4 text', 'textarea', 160, 'Your satisfaction is our priority, from the first call to the last plate.'],
        ],
        'About' => [
            'about_heading' => ['Heading', 'text', 80, "Welcome to Muller's Delights"],
            'about_text_1' => ['First paragraph', 'textarea', 500, "Muller's Delights is a catering service led by Chef Muller. We cook for the occasions that matter to you, whether that is a household breakfast, a kitchen party or a full wedding spread."],
            'about_text_2' => ['Second paragraph (optional)', 'textarea', 500, 'We believe good food brings people together. Tell us about your event and your guests, and we will take care of the meal.'],
            'stat_1_value' => ['Highlight 1 figure (leave empty to hide)', 'text', 8, '10'],
            'stat_1_top' => ['Highlight 1 small line', 'text', 24, 'Guests per'],
            'stat_1_bottom' => ['Highlight 1 bold line', 'text', 24, 'Household'],
            'stat_2_value' => ['Highlight 2 figure (leave empty to hide)', 'text', 8, '$20'],
            'stat_2_top' => ['Highlight 2 small line', 'text', 24, 'Meals'],
            'stat_2_bottom' => ['Highlight 2 bold line', 'text', 24, 'Start From'],
        ],
        'Packages section' => [
            'packages_heading' => ['Heading', 'text', 80, 'Main Course Meals'],
            'packages_intro' => ['Line under the heading', 'textarea', 200, 'With your own ingredients, for 10 guests per household, the meals cost:'],
            'packages_footnote' => ['Small print under the packages', 'text', 160, 'Tap a meal to start your booking. Prices are in US dollars.'],
        ],
        'Events section' => [
            'events_heading' => ['Heading', 'text', 80, 'Catering for Every Occasion'],
        ],
        'Booking section' => [
            'booking_heading' => ['Heading', 'text', 80, 'Let Us Make Your Event Special!'],
        ],
        'Footer and search engines' => [
            'footer_tagline' => ['Footer tagline', 'text', 80, 'Delicious Meals · Memorable Moments'],
            'meta_description' => ['Description shown in Google results', 'textarea', 200, "Muller's Delights Catering Services. Chef Muller caters weddings, marooro, graduation, birthday and kitchen parties and business events."],
        ],
    ];
}

/** Settings edited on the admin Settings page rather than the Content page. */
function system_setting_defaults(): array
{
    return [
        'site_name' => "Muller's Delights Catering Services",
        'contact_name' => 'Chef Muller',
        'phone_1' => '0782 359 378',
        'phone_2' => '0718 521 403',
        'whatsapp_number' => '0782 359 378',
        'country_code' => '263',
        'currency_symbol' => '$',
        'deposits_enabled' => '0',
        'deposit_amount' => '10.00',
    ];
}

function setting_defaults(): array
{
    $defaults = system_setting_defaults();
    foreach (content_fields() as $fields) {
        foreach ($fields as $key => $field) {
            $defaults[$key] = $field[3];
        }
    }
    return $defaults;
}

function settings(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = setting_defaults();
        foreach (all('SELECT name, value FROM settings') as $row) {
            $cache[$row['name']] = $row['value'];
        }
    }
    return $cache;
}

function setting(string $key): string
{
    return settings()[$key] ?? '';
}

function save_settings(array $values): void
{
    foreach ($values as $key => $value) {
        $exists = scalar('SELECT COUNT(*) FROM settings WHERE name = ?', [$key]);
        if ($exists) {
            q('UPDATE settings SET value = ? WHERE name = ?', [$value, $key]);
        } else {
            q('INSERT INTO settings (name, value) VALUES (?, ?)', [$key, $value]);
        }
    }
    settings(true);
}

function valid_phone(string $phone): bool
{
    return (bool) preg_match('/^\+?[0-9 \-]{7,20}$/', $phone);
}

/** Digits-only international number: "0782 359 378" becomes "263782359378". */
function phone_intl(string $phone): string
{
    $hasPlus = str_starts_with(trim($phone), '+');
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if ($hasPlus) {
        return $digits;
    }
    if (str_starts_with($digits, '00')) {
        return substr($digits, 2);
    }
    if (str_starts_with($digits, '0')) {
        return setting('country_code') . substr($digits, 1);
    }
    return $digits;
}

function tel_link(string $phone): string
{
    return 'tel:+' . phone_intl($phone);
}

function wa_link(string $phone, string $message = ''): string
{
    $url = 'https://wa.me/' . phone_intl($phone);
    return $message === '' ? $url : $url . '?text=' . rawurlencode($message);
}

function money(float|string|null $amount): string
{
    if ($amount === null || $amount === '') {
        return '';
    }
    $amount = (float) $amount;
    $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;
    return setting('currency_symbol') . number_format($amount, $decimals);
}

function booking_statuses(): array
{
    return ['new' => 'New', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
}

function payment_statuses(): array
{
    return ['none' => 'No deposit', 'pending' => 'Deposit pending', 'paid' => 'Deposit paid', 'cancelled' => 'Payment cancelled', 'failed' => 'Payment failed'];
}

/** Event options offered on the booking form: the admin's event types plus two catch-alls. */
function booking_event_options(): array
{
    $names = array_column(all('SELECT name FROM event_types WHERE is_active = 1 ORDER BY sort_order, id'), 'name');
    foreach (['Household Meal', 'Other'] as $extra) {
        if (!in_array($extra, $names, true)) {
            $names[] = $extra;
        }
    }
    return $names;
}

/** Active package groups, each with its active packages under 'items'. */
function active_package_groups(): array
{
    $groups = all('SELECT * FROM package_groups WHERE is_active = 1 ORDER BY sort_order, id');
    $items = all('SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order, id');
    foreach ($groups as &$group) {
        $group['items'] = array_values(array_filter($items, fn ($item) => (int) $item['group_id'] === (int) $group['id']));
    }
    unset($group);
    return array_values(array_filter($groups, fn ($group) => $group['items'] !== []));
}

/** Label stored on a booking so later price or name edits don't rewrite history. */
function package_label(array $group, array $package): string
{
    return trim($group['name'] . ': ' . $package['name']);
}

function deposits_available(): bool
{
    return setting('deposits_enabled') === '1' && (float) setting('deposit_amount') > 0 && paynow_configured();
}

function paynow_configured(): bool
{
    return env('PAYNOW_INTEGRATION_ID') !== null && env('PAYNOW_INTEGRATION_KEY') !== null;
}

function booking_whatsapp_message(array $booking): string
{
    $lines = [
        "Hello " . setting('contact_name') . ", I'd like to make a booking.",
        '',
        'Reference: ' . $booking['reference'],
        'Name: ' . $booking['name'],
        'Phone: ' . $booking['phone'],
        'Event: ' . $booking['event_type'],
        'Date: ' . date('l, j F Y', strtotime($booking['event_date'])),
        'Guests: ' . $booking['guests'],
    ];
    if ($booking['package_label'] !== '') {
        $lines[] = 'Package: ' . $booking['package_label'] . ($booking['package_price'] !== null ? ' (' . money($booking['package_price']) . ')' : '');
    }
    if ($booking['venue'] !== '') {
        $lines[] = 'Venue: ' . $booking['venue'];
    }
    if ((string) $booking['details'] !== '') {
        array_push($lines, '', 'Details:', (string) $booking['details']);
    }
    return implode("\n", $lines);
}
