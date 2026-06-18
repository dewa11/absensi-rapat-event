<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php
$summary = is_array($summary ?? null) ? $summary : [];
$upcomingEvents = is_array($upcomingEvents ?? null) ? $upcomingEvents : [];
$latestSubmissions = is_array($latestSubmissions ?? null) ? $latestSubmissions : [];
?>

<div class="dashboard-hero mb-4">
    <div>
        <h2 class="h5 mb-1">Selamat Datang di Dashboard Admin</h2>
        <p class="mb-0 text-muted">Ringkasan 7 hari ke depan dan aktivitas absensi terbaru.</p>
    </div>
    <img src="<?= htmlspecialchars(app_asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo Dashboard" class="dashboard-logo">
</div>

<div class="row g-3 dashboard-kpi-grid">
    <div class="col-12 col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1">Total Kegiatan</p>
                <h3 class="mb-0"><?= (int) ($summary['events_total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1">Total Data Absen</p>
                <h3 class="mb-0"><?= (int) ($summary['attendances_total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1">Absen Hari Ini</p>
                <h3 class="mb-0"><?= (int) ($summary['today_total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1">Kegiatan 7 Hari ke Depan</p>
                <h3 class="mb-0"><?= (int) ($summary['upcoming_7d_total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1">Rata-rata Peserta / Kegiatan</p>
                <h3 class="mb-0"><?= htmlspecialchars(number_format((float) ($summary['avg_attendees_per_event'] ?? 0), 1, ',', '.'), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm dashboard-widget h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Agenda Mendatang (7 Hari)</h3>
                    <a href="<?= htmlspecialchars(app_url('/attendance'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>

                <?php if (empty($upcomingEvents)): ?>
                    <p class="text-muted mb-0">Tidak ada kegiatan dalam 7 hari ke depan.</p>
                <?php else: ?>
                    <div class="dashboard-list">
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="dashboard-list-item">
                                <div>
                                    <a href="<?= htmlspecialchars(app_url('/attendance/detail/' . (int) $event['id']), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none fw-semibold d-block">
                                        <?= htmlspecialchars((string) $event['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <small class="text-muted">
                                        <?= htmlspecialchars((string) $event['event_date'], ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string) $event['event_time'], ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                    <small class="text-muted d-block"><?= htmlspecialchars((string) $event['location'], ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge text-bg-light border"><?= (int) ($event['attendee_total'] ?? 0) ?> peserta</span>
                                    <small class="d-block text-muted mt-1"><?= htmlspecialchars((string) $event['event_type'], ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm dashboard-widget h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Absensi Terbaru</h3>
                    <a href="<?= htmlspecialchars(app_url('/attendance'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">Buka Data Absen</a>
                </div>

                <?php if (empty($latestSubmissions)): ?>
                    <p class="text-muted mb-0">Belum ada data absensi yang masuk.</p>
                <?php else: ?>
                    <div class="dashboard-list">
                        <?php foreach ($latestSubmissions as $submission): ?>
                            <?php $isLate = ((int) ($submission['is_late'] ?? 0)) === 1; ?>
                            <div class="dashboard-list-item">
                                <div>
                                    <strong class="d-block"><?= htmlspecialchars((string) $submission['participant_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-muted d-block">
                                        NIP: <?= htmlspecialchars((string) ($submission['nip'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <?= htmlspecialchars((string) ($submission['unit_name'] ?: '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                    <a href="<?= htmlspecialchars(app_url('/attendance/detail/' . (int) ($submission['event_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>" class="small text-decoration-none">
                                        <?= htmlspecialchars((string) ($submission['event_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block mb-1"><?= htmlspecialchars((string) ($submission['present_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php if ($isLate): ?>
                                        <span class="badge text-bg-danger">Terlambat</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">Tepat Waktu</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
