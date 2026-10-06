<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';

$admin = require_admin();

if (is_post()) {
    $action = post('action', 30);

    if ($action === 'contact') {
        $values = [
            'site_name' => post('site_name', 80),
            'contact_name' => post('contact_name', 60),
            'phone_1' => post('phone_1', 20),
            'phone_2' => post('phone_2', 20),
            'whatsapp_number' => post('whatsapp_number', 20),
            'country_code' => preg_replace('/\D+/', '', post('country_code', 5)) ?? '',
            'currency_symbol' => post('currency_symbol', 4),
        ];
        $errors = [];
        if ($values['site_name'] === '' || $values['contact_name'] === '') {
            $errors[] = 'The business name and contact name cannot be empty.';
        }
        foreach (['phone_1' => 'Phone 1', 'phone_2' => 'Phone 2', 'whatsapp_number' => 'The WhatsApp number'] as $key => $label) {
            if ($values[$key] !== '' && !valid_phone($values[$key])) {
                $errors[] = $label . ' is not a valid phone number.';
            }
        }
        if ($values['country_code'] === '') {
            $errors[] = 'Please enter the country dialling code, for example 263.';
        }
        if ($values['currency_symbol'] === '') {
            $errors[] = 'Please enter a currency symbol.';
        }
        if ($errors) {
            flash('danger', implode(' ', $errors) . ' Nothing was saved.');
        } else {
            save_settings($values);
            flash('success', 'Contact details saved.');
        }
    } elseif ($action === 'deposits') {
        $amount = str_replace(',', '.', post('deposit_amount', 12));
        if (!is_numeric($amount) || (float) $amount < 0 || (float) $amount > 100000) {
            flash('danger', 'Please enter a valid deposit amount.');
        } elseif (isset($_POST['deposits_enabled']) && (float) $amount <= 0) {
            flash('danger', 'To switch deposits on, the amount must be more than zero.');
        } else {
            save_settings([
                'deposits_enabled' => isset($_POST['deposits_enabled']) ? '1' : '0',
                'deposit_amount' => number_format((float) $amount, 2, '.', ''),
            ]);
            flash('success', 'Deposit settings saved.');
        }
    } elseif ($action === 'password') {
        $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $new = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirm = is_string($_POST['new_password_confirm'] ?? null) ? $_POST['new_password_confirm'] : '';
        $hash = (string) scalar('SELECT password_hash FROM admins WHERE id = ?', [$admin['id']]);
        if (!password_verify($current, $hash)) {
            flash('danger', 'Your current password is not correct.');
        } elseif (($problem = password_problem($new)) !== null) {
            flash('danger', $problem);
        } elseif ($new !== $confirm) {
            flash('danger', 'The two new passwords do not match.');
        } else {
            q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
        }
    } elseif ($action === 'add_admin') {
        $name = post('name', 80);
        $email = strtolower(post('email', 190));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (mb_strlen($name) < 2 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            flash('danger', 'Please enter a name and a valid email address.');
        } elseif (($problem = password_problem($password)) !== null) {
            flash('danger', $problem);
        } elseif (scalar('SELECT COUNT(*) FROM admins WHERE email = ?', [$email])) {
            flash('danger', 'An admin with that email already exists.');
        } else {
            q('INSERT INTO admins (name, email, password_hash, created_at) VALUES (?, ?, ?, ?)', [$name, $email, password_hash($password, PASSWORD_DEFAULT), now_utc()]);
            flash('success', 'Admin added. Give them their password privately and ask them to change it after logging in.');
        }
    } elseif ($action === 'delete_admin') {
        $id = (int) post('id', 10);
        if ($id === (int) $admin['id']) {
            flash('danger', 'You cannot remove your own account.');
        } else {
            q('DELETE FROM admins WHERE id = ?', [$id]);
            flash('success', 'Admin removed.');
        }
    }

    redirect('/admin/settings.php');
}

$s = settings();
$admins = all('SELECT id, name, email, created_at FROM admins ORDER BY id');

admin_start('Settings', 'settings', $admin);
?>
        <div class="row g-4">
            <div class="col-lg-6">
                <form method="post" action="/admin/settings.php" class="admin-card h-100">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="contact">
                    <h2 class="h4 mb-3">Business and contact details</h2>
                    <div class="mb-3">
                        <label class="form-label" for="site_name">Business name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?= e($s['site_name']) ?>" maxlength="80" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="contact_name">Contact name (shown as "Call or WhatsApp ...")</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?= e($s['contact_name']) ?>" maxlength="60" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="phone_1">Phone 1</label>
                            <input type="tel" class="form-control" id="phone_1" name="phone_1" value="<?= e($s['phone_1']) ?>" maxlength="20">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="phone_2">Phone 2 (optional)</label>
                            <input type="tel" class="form-control" id="phone_2" name="phone_2" value="<?= e($s['phone_2']) ?>" maxlength="20">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="whatsapp_number">WhatsApp number</label>
                        <input type="tel" class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?= e($s['whatsapp_number']) ?>" maxlength="20" aria-describedby="waHelp">
                        <div class="form-text" id="waHelp">Bookings and the green WhatsApp button go to this number. Leave empty to hide WhatsApp.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="country_code">Country dialling code</label>
                            <input type="text" class="form-control" id="country_code" name="country_code" value="<?= e($s['country_code']) ?>" maxlength="5" inputmode="numeric" aria-describedby="ccHelp" required>
                            <div class="form-text" id="ccHelp">263 for Zimbabwe. Added to numbers that start with 0.</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="currency_symbol">Currency symbol</label>
                            <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?= e($s['currency_symbol']) ?>" maxlength="4" required>
                        </div>
                    </div>
                    <button class="btn btn-primary px-4" type="submit">Save details</button>
                </form>
            </div>

            <div class="col-lg-6">
                <form method="post" action="/admin/settings.php" class="admin-card mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="deposits">
                    <h2 class="h4 mb-3">Online deposits</h2>
<?php if (paynow_configured()): ?>
                    <p class="text-success"><i class="fa fa-check-circle me-2"></i>Paynow is connected.</p>
<?php else: ?>
                    <div class="alert alert-warning" role="status">Paynow is not connected yet. The Paynow integration ID and key must be added on the server before customers can pay. Until then no pay button is shown, whatever is set here.</div>
<?php endif; ?>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="deposits_enabled" name="deposits_enabled" value="1"<?= $s['deposits_enabled'] === '1' ? ' checked' : '' ?>>
                        <label class="form-check-label" for="deposits_enabled">Let customers pay a deposit after booking</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="deposit_amount">Deposit amount (<?= e($s['currency_symbol']) ?>)</label>
                        <input type="number" class="form-control" id="deposit_amount" name="deposit_amount" value="<?= e($s['deposit_amount']) ?>" min="0" max="100000" step="0.01" inputmode="decimal" required>
                        <div class="form-text">The same fixed amount is asked for on every booking. Paying is optional for the customer.</div>
                    </div>
                    <button class="btn btn-primary px-4" type="submit">Save deposits</button>
                </form>

                <form method="post" action="/admin/settings.php" class="admin-card">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <h2 class="h4 mb-3">Change your password</h2>
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password">New password (10 characters or more)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="10" autocomplete="new-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password_confirm">Repeat new password</label>
                        <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" minlength="10" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-primary px-4" type="submit">Change password</button>
                </form>
            </div>

            <div class="col-12">
                <div class="admin-card">
                    <h2 class="h4 mb-3">People who can log in</h2>
<?php foreach ($admins as $row): ?>
                    <div class="list-row">
                        <span><strong><?= e($row['name']) ?></strong><?= (int) $row['id'] === (int) $admin['id'] ? ' <span class="badge bg-secondary">You</span>' : '' ?><br><small class="text-muted"><?= e($row['email']) ?></small></span>
<?php if ((int) $row['id'] !== (int) $admin['id']): ?>
                        <form method="post" action="/admin/settings.php" class="m-0" data-confirm="Remove <?= e($row['name']) ?> as an admin?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_admin">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Remove</button>
                        </form>
<?php endif; ?>
                    </div>
<?php endforeach; ?>
                    <form method="post" action="/admin/settings.php" class="row g-2 align-items-end mt-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_admin">
                        <div class="col-md-3">
                            <label class="form-label" for="adminName">Name</label>
                            <input type="text" class="form-control" id="adminName" name="name" maxlength="80" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="adminEmail">Email</label>
                            <input type="email" class="form-control" id="adminEmail" name="email" maxlength="190" autocomplete="off" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="adminPassword">Starting password</label>
                            <input type="password" class="form-control" id="adminPassword" name="password" minlength="10" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-dark w-100" type="submit">Add admin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
<?php
admin_end();
