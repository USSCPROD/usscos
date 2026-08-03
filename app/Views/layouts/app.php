<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($title ?? 'Dashboard') ?> — <?= e(config('app.name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=<?= time() ?>">
</head>
<body class="app-body">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-layout">

    <aside class="sidebar" id="sidebar">
        <?= view('partials.sidebar') ?>
    </aside>

    <div class="app-main">

        <header class="topbar">
            <?= view('partials.topbar', ['title' => $title ?? '']) ?>
        </header>

        <main class="app-content">

            <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
                <div class="alert alert--success" data-auto-dismiss="5000"><?= e($flash) ?></div>
            <?php endif; ?>

            <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
                <div class="alert alert--error"><?= e($flash) ?></div>
            <?php endif; ?>

            <?= $content ?>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (isset($scripts)): ?>
    <?= $scripts ?>
<?php endif; ?>

</body>
</html>
