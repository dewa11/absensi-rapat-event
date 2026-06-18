<?php
$activeMenu = (string) ($activeMenu ?? '');
?>
<aside class="sidebar">
    <div class="brand-wrap">
        <img src="<?= htmlspecialchars(app_asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo Aplikasi" class="brand-logo">
        <div>
            <p class="brand-title">RSUTI Rapat</p>
            <small class="brand-subtitle">Panel Admin</small>
        </div>
    </div>

    <nav class="menu-nav">
        <a href="<?= htmlspecialchars(app_url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="menu-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(app_url('/attendance/create'), ENT_QUOTES, 'UTF-8') ?>" class="menu-item <?= $activeMenu === 'create' ? 'active' : '' ?>">Buat Absen</a>
        <a href="<?= htmlspecialchars(app_url('/attendance'), ENT_QUOTES, 'UTF-8') ?>" class="menu-item <?= $activeMenu === 'data' ? 'active' : '' ?>">Data Absen</a>
        <a href="<?= htmlspecialchars(app_url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="menu-item danger">Logout</a>
    </nav>
</aside>
