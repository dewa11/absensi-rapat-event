<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string) ($title ?? 'Dashboard'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(app_asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_asset_url('css/app.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="admin-page">
<div class="app-shell">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <section class="app-content">
        <header class="topbar">
            <div>
                <h1 class="h4 mb-0"><?= htmlspecialchars((string) ($title ?? 'Dashboard'), ENT_QUOTES, 'UTF-8') ?></h1>
                <small class="text-muted">Sistem Absensi Rapat & Event</small>
            </div>
            <div class="topbar-user">
                <span class="badge rounded-pill text-bg-light">Admin: <?= htmlspecialchars((string) (($adminUser['full_name'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <main class="content-panel">
            <?= (string) ($content ?? '') ?>
        </main>

        <footer class="app-watermark" role="contentinfo">
            <span>Made by RVL</span>
        </footer>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars(app_asset_url('js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
