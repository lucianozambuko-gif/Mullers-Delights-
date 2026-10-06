<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';

$admin = require_admin();

if (is_post()) {
    $action = post('action', 30);
    $id = (int) post('id', 10);

    if ($action === 'add' || $action === 'save') {
        $name = post('name', 120);
        $values = [$name, post('description', 255), valid_icon(post('icon', 40)), (int) post('sort_order', 6), isset($_POST['is_active']) ? 1 : 0];
        if ($name === '') {
            flash('danger', 'Please enter a name for the event type.');
        } elseif ($action === 'add') {
            q('INSERT INTO event_types (name, description, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?)', $values);
            flash('success', 'Event type added.');
        } else {
            q('UPDATE event_types SET name = ?, description = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?', array_merge($values, [$id]));
            flash('success', 'Event type saved.');
        }
    } elseif ($action === 'delete') {
        q('DELETE FROM event_types WHERE id = ?', [$id]);
        flash('success', 'Event type deleted.');
    }

    redirect('/admin/events.php');
}

$events = all('SELECT * FROM event_types ORDER BY sort_order, id');

admin_start('Events', 'events', $admin);
?>
        <p class="text-muted">These are the occasions listed under "What We Offer" and offered in the booking form. Lowest "Order" number shows first. Untick "Shown" to hide one without deleting it.</p>

<?php foreach ($events as $event): ?>
        <form method="post" action="/admin/events.php" class="admin-card mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="e<?= (int) $event['id'] ?>name">Event type</label>
                    <input type="text" class="form-control" id="e<?= (int) $event['id'] ?>name" name="name" value="<?= e($event['name']) ?>" maxlength="120" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="e<?= (int) $event['id'] ?>desc">Short description</label>
                    <input type="text" class="form-control" id="e<?= (int) $event['id'] ?>desc" name="description" value="<?= e($event['description']) ?>" maxlength="255">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="e<?= (int) $event['id'] ?>icon">Icon</label>
                    <?= icon_select('icon', $event['icon'], 'e' . (int) $event['id'] . 'icon') ?>
                </div>
                <div class="col-3 col-md-1">
                    <label class="form-label" for="e<?= (int) $event['id'] ?>sort">Order</label>
                    <input type="number" class="form-control" id="e<?= (int) $event['id'] ?>sort" name="sort_order" value="<?= (int) $event['sort_order'] ?>" min="0" max="9999">
                </div>
                <div class="col-3 col-md-1">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="e<?= (int) $event['id'] ?>active" name="is_active" value="1"<?= $event['is_active'] ? ' checked' : '' ?>>
                        <label class="form-check-label" for="e<?= (int) $event['id'] ?>active">Shown</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary px-4" type="submit" name="action" value="save">Save</button>
                <button class="btn btn-outline-danger" type="submit" name="action" value="delete" data-confirm="Delete the event type &quot;<?= e($event['name']) ?>&quot;?">Delete</button>
            </div>
        </form>
<?php endforeach; ?>

        <form method="post" action="/admin/events.php" class="admin-card">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="is_active" value="1">
            <h2 class="h4 mb-3">Add an event type</h2>
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="newName">Event type</label>
                    <input type="text" class="form-control" id="newName" name="name" maxlength="120" placeholder="e.g. Baby Shower" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="newDesc">Short description</label>
                    <input type="text" class="form-control" id="newDesc" name="description" maxlength="255">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="newIcon">Icon</label>
                    <?= icon_select('icon', 'fa-glass-cheers', 'newIcon') ?>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="newSort">Order</label>
                    <input type="number" class="form-control" id="newSort" name="sort_order" value="<?= count($events) * 10 + 10 ?>" min="0" max="9999">
                </div>
            </div>
            <button class="btn btn-outline-dark mt-3" type="submit"><i class="fa fa-plus me-2"></i>Add event type</button>
        </form>
<?php
admin_end();
