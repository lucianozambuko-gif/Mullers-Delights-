<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';

$admin = require_admin();

if (is_post()) {
    $values = [];
    foreach (content_fields() as $fields) {
        foreach ($fields as $key => [$label, $type, $max]) {
            $values[$key] = match ($type) {
                'icon' => valid_icon(post($key, 40)),
                'textarea' => post_text($key, $max),
                default => post($key, $max),
            };
        }
    }

    // The page falls apart without these, so they can't be blanked out
    $required = ['hero_title' => 'The headline', 'about_heading' => 'The About heading', 'about_text_1' => 'The first About paragraph'];
    $missing = array_keys(array_filter($required, fn ($label, $key) => $values[$key] === '', ARRAY_FILTER_USE_BOTH));
    if ($missing) {
        flash('danger', $required[$missing[0]] . ' cannot be empty. Nothing was saved.');
    } else {
        save_settings($values);
        flash('success', 'Website content saved. The changes are live now.');
    }
    redirect('/admin/content.php');
}

$s = settings();

admin_start('Content', 'content', $admin);
?>
        <p class="text-muted">Edit the wording on the website. Changes go live as soon as you save. Phone numbers and deposits are under Settings.</p>

        <form method="post" action="/admin/content.php">
            <?= csrf_field() ?>
<?php foreach (content_fields() as $section => $fields): ?>
            <section class="admin-card mb-4">
                <h2 class="h4 mb-3"><?= e($section) ?></h2>
                <div class="row g-3">
<?php foreach ($fields as $key => [$label, $type, $max]): ?>
                    <div class="<?= $type === 'textarea' ? 'col-12' : ($type === 'icon' ? 'col-md-4' : 'col-md-6') ?>">
                        <label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label>
<?php if ($type === 'textarea'): ?>
                        <textarea class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" rows="<?= $max > 200 ? 4 : 2 ?>" maxlength="<?= (int) $max ?>"><?= e($s[$key]) ?></textarea>
<?php elseif ($type === 'icon'): ?>
                        <?= icon_select($key, $s[$key], $key) ?>
<?php else: ?>
                        <input type="text" class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($s[$key]) ?>" maxlength="<?= (int) $max ?>">
<?php endif; ?>
                    </div>
<?php endforeach; ?>
                </div>
            </section>
<?php endforeach; ?>
            <div class="save-bar">
                <button class="btn btn-primary px-5 py-3" type="submit">Save all content</button>
            </div>
        </form>
<?php
admin_end();
