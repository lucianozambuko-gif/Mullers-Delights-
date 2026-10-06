<?php
declare(strict_types=1);

/** Shared <head> for every public and admin page. */
function html_head(string $title, string $description = '', array $extraCss = []): void
{
    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= e($title) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
<?php if ($description !== ''): ?>
    <meta content="<?= e($description) ?>" name="description">
<?php endif; ?>
    <meta content="#5B0E1B" name="theme-color">

    <!-- Favicon -->
    <link href="/img/favicon.png" rel="icon" type="image/png">
    <link href="/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,500&family=Great+Vibes&family=Heebo:wght@400;500&family=Jost:wght@500;600&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="/lib/animate/animate.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="/css/style.css" rel="stylesheet">
<?php foreach ($extraCss as $href): ?>
    <link href="<?= e($href) ?>" rel="stylesheet">
<?php endforeach; ?>
</head>
<?php
}

/** Slim header for the secondary public pages (booking status, privacy). */
function simple_page_start(string $title): void
{
    send_security_headers(true);
    html_head($title . ' - ' . setting('site_name'));
    ?>

<body>
    <div class="container-xxl position-relative bg-white p-0 simple-page">
        <nav class="navbar navbar-dark bg-dark simple-nav px-4 px-lg-5 py-2">
            <a href="/" class="navbar-brand p-0">
                <img src="/img/logo-horizontal.png" alt="<?= e(setting('site_name')) ?>" width="640" height="182">
            </a>
            <a href="/" class="btn btn-outline-light btn-sm py-2 px-3">Back to site</a>
        </nav>
        <main class="container py-5">
<?php
}

function simple_page_end(): void
{
    ?>
        </main>
        <div class="container-fluid bg-dark text-light footer">
            <div class="container">
                <div class="copyright text-center border-0">
                    &copy; <?= date('Y') ?> <?= e(setting('site_name')) ?>. All Rights Reserved.
                    <a class="ms-2" href="/privacy.php">Privacy</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<?php
}
