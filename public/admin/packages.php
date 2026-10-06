<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';

$admin = require_admin();

/** Reads and validates the shared package fields; returns [values, error]. */
function package_input(): array
{
    $name = post('name', 120);
    $price = str_replace(',', '.', post('price', 12));
    if ($name === '') {
        return [null, 'Please enter a name for the meal.'];
    }
    if (!is_numeric($price) || (float) $price < 0 || (float) $price > 100000) {
        return [null, 'Please enter a valid price, for example 20 or 20.50.'];
    }
    return [[$name, number_format((float) $price, 2, '.', ''), valid_icon(post('icon', 40)), (int) post('sort_order', 6), isset($_POST['is_active']) ? 1 : 0], null];
}

if (is_post()) {
    $action = post('action', 30);
    $id = (int) post('id', 10);

    if ($action === 'add_group' || $action === 'save_group') {
        $name = post('name', 120);
        if ($name === '') {
            flash('danger', 'Please enter a name for the group.');
        } elseif ($action === 'add_group') {
            q('INSERT INTO package_groups (name, note, sort_order, is_active) VALUES (?, ?, ?, 1)', [$name, post('note', 255), (int) post('sort_order', 6)]);
            flash('success', 'Group added. Now add its meals.');
        } else {
            q('UPDATE package_groups SET name = ?, note = ?, sort_order = ?, is_active = ? WHERE id = ?',
                [$name, post('note', 255), (int) post('sort_order', 6), isset($_POST['is_active']) ? 1 : 0, $id]);
            flash('success', 'Group saved.');
        }
    } elseif ($action === 'delete_group') {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            q('DELETE FROM packages WHERE group_id = ?', [$id]);
            q('DELETE FROM package_groups WHERE id = ?', [$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        flash('success', 'Group and its meals deleted.');
    } elseif ($action === 'add_package' || $action === 'save_package') {
        [$values, $error] = package_input();
        $groupId = (int) post('group_id', 10);
        if ($error !== null) {
            flash('danger', $error);
        } elseif ($action === 'add_package') {
            if (!scalar('SELECT COUNT(*) FROM package_groups WHERE id = ?', [$groupId])) {
                flash('danger', 'That group no longer exists.');
            } else {
                q('INSERT INTO packages (group_id, name, price, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)', array_merge([$groupId], $values));
                flash('success', 'Meal added.');
            }
        } else {
            q('UPDATE packages SET name = ?, price = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?', array_merge($values, [$id]));
            flash('success', 'Meal saved.');
        }
    } elseif ($action === 'delete_package') {
        q('DELETE FROM packages WHERE id = ?', [$id]);
        flash('success', 'Meal deleted.');
    }

    redirect('/admin/packages.php');
}

$groups = all('SELECT * FROM package_groups ORDER BY sort_order, id');
$packages = all('SELECT * FROM packages ORDER BY sort_order, id');

admin_start('Packages', 'packages', $admin);
?>
        <p class="text-muted">Meals are shown on the website grouped under a heading, lowest "Order" number first. Untick "Shown" to hide something without deleting it. Existing bookings keep the name and price they were made with.</p>

<?php foreach ($groups as $group): ?>
<?php $items = array_filter($packages, fn ($package) => (int) $package['group_id'] === (int) $group['id']); ?>
        <section class="admin-card mb-4">
            <form method="post" action="/admin/packages.php" class="row g-2 align-items-end group-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_group">
                <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
                <div class="col-md-4">
                    <label class="form-label" for="g<?= (int) $group['id'] ?>name">Group heading</label>
                    <input type="text" class="form-control" id="g<?= (int) $group['id'] ?>name" name="name" value="<?= e($group['name']) ?>" maxlength="120" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="g<?= (int) $group['id'] ?>note">Note under the meals (optional)</label>
                    <input type="text" class="form-control" id="g<?= (int) $group['id'] ?>note" name="note" value="<?= e($group['note']) ?>" maxlength="255">
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label" for="g<?= (int) $group['id'] ?>sort">Order</label>
                    <input type="number" class="form-control" id="g<?= (int) $group['id'] ?>sort" name="sort_order" value="<?= (int) $group['sort_order'] ?>" min="0" max="9999">
                </div>
                <div class="col-4 col-md-1">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="g<?= (int) $group['id'] ?>active" name="is_active" value="1"<?= $group['is_active'] ? ' checked' : '' ?>>
                        <label class="form-check-label" for="g<?= (int) $group['id'] ?>active">Shown</label>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Save group</button>
                </div>
            </form>

            <hr>

<?php foreach ($items as $item): ?>
            <form method="post" action="/admin/packages.php" class="row g-2 align-items-end item-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <div class="col-md-3">
                    <label class="form-label" for="p<?= (int) $item['id'] ?>name">Meal</label>
                    <input type="text" class="form-control" id="p<?= (int) $item['id'] ?>name" name="name" value="<?= e($item['name']) ?>" maxlength="120" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="p<?= (int) $item['id'] ?>price">Price (<?= e(setting('currency_symbol')) ?>)</label>
                    <input type="number" class="form-control" id="p<?= (int) $item['id'] ?>price" name="price" value="<?= e($item['price']) ?>" min="0" max="100000" step="0.01" inputmode="decimal" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="p<?= (int) $item['id'] ?>icon">Icon</label>
                    <?= icon_select('icon', $item['icon'], 'p' . (int) $item['id'] . 'icon') ?>
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label" for="p<?= (int) $item['id'] ?>sort">Order</label>
                    <input type="number" class="form-control" id="p<?= (int) $item['id'] ?>sort" name="sort_order" value="<?= (int) $item['sort_order'] ?>" min="0" max="9999">
                </div>
                <div class="col-8 col-md-1">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="p<?= (int) $item['id'] ?>active" name="is_active" value="1"<?= $item['is_active'] ? ' checked' : '' ?>>
                        <label class="form-check-label" for="p<?= (int) $item['id'] ?>active">Shown</label>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary flex-fill" type="submit" name="action" value="save_package">Save</button>
                    <button class="btn btn-outline-danger" type="submit" name="action" value="delete_package" data-confirm="Delete the meal &quot;<?= e($item['name']) ?>&quot;?">Delete</button>
                </div>
            </form>
<?php endforeach; ?>

            <form method="post" action="/admin/packages.php" class="row g-2 align-items-end item-form add-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_package">
                <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                <input type="hidden" name="is_active" value="1">
                <div class="col-md-3">
                    <label class="form-label" for="n<?= (int) $group['id'] ?>name">New meal</label>
                    <input type="text" class="form-control" id="n<?= (int) $group['id'] ?>name" name="name" maxlength="120" placeholder="e.g. Brunch" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="n<?= (int) $group['id'] ?>price">Price (<?= e(setting('currency_symbol')) ?>)</label>
                    <input type="number" class="form-control" id="n<?= (int) $group['id'] ?>price" name="price" min="0" max="100000" step="0.01" inputmode="decimal" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="n<?= (int) $group['id'] ?>icon">Icon</label>
                    <?= icon_select('icon', 'fa-utensils', 'n' . (int) $group['id'] . 'icon') ?>
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label" for="n<?= (int) $group['id'] ?>sort">Order</label>
                    <input type="number" class="form-control" id="n<?= (int) $group['id'] ?>sort" name="sort_order" value="<?= count($items) * 10 + 10 ?>" min="0" max="9999">
                </div>
                <div class="col-8 col-md-4">
                    <button class="btn btn-outline-dark w-100" type="submit"><i class="fa fa-plus me-2"></i>Add meal</button>
                </div>
            </form>

            <form method="post" action="/admin/packages.php" class="mt-3 text-end" data-confirm="Delete the group &quot;<?= e($group['name']) ?>&quot; and all its meals?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_group">
                <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
                <button class="btn btn-link text-danger p-0" type="submit">Delete this whole group</button>
            </form>
        </section>
<?php endforeach; ?>

        <section class="admin-card">
            <h2 class="h4 mb-3">Add a group</h2>
            <form method="post" action="/admin/packages.php" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_group">
                <div class="col-md-4">
                    <label class="form-label" for="newGroupName">Group heading</label>
                    <input type="text" class="form-control" id="newGroupName" name="name" maxlength="120" placeholder="e.g. Buffet Packages" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="newGroupNote">Note under the meals (optional)</label>
                    <input type="text" class="form-control" id="newGroupNote" name="note" maxlength="255">
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label" for="newGroupSort">Order</label>
                    <input type="number" class="form-control" id="newGroupSort" name="sort_order" value="<?= count($groups) * 10 + 10 ?>" min="0" max="9999">
                </div>
                <div class="col-8 col-md-3">
                    <button class="btn btn-outline-dark w-100" type="submit"><i class="fa fa-plus me-2"></i>Add group</button>
                </div>
            </form>
        </section>
<?php
admin_end();
