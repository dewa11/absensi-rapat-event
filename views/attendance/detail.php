<?php
$event = is_array($event ?? null) ? $event : null;
$attendees = is_array($attendees ?? null) ? $attendees : [];
$publicLink = (string) ($publicLink ?? '');
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($event === null): ?>
    <div class="alert alert-warning" role="alert">Data kegiatan tidak ditemukan.</div>
<?php else: ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="<?= htmlspecialchars(app_url('/attendance'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Kembali ke Data Absen</a>
        <div class="d-flex flex-wrap gap-2">
            <form action="<?= htmlspecialchars(app_url('/attendance/detail/' . (int) $event['id'] . '/generate-link'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                <button type="submit" class="btn btn-sm <?= $publicLink !== '' ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                    <?= $publicLink !== '' ? 'Regenerate Link & QR' : 'Generate Link & QR' ?>
                </button>
            </form>
            <form action="<?= htmlspecialchars(app_url('/attendance/detail/' . (int) $event['id'] . '/delete'), ENT_QUOTES, 'UTF-8') ?>" method="post" onsubmit="return confirm('Hapus kegiatan ini beserta semua data absensinya? Tindakan ini tidak dapat dibatalkan.')">
                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus Kegiatan</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <h2 class="h5 mb-3"><?= htmlspecialchars((string) $event['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <small class="text-muted d-block">Tipe</small>
                    <strong><?= htmlspecialchars((string) $event['event_type'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="col-12 col-md-3">
                    <small class="text-muted d-block">Tanggal</small>
                    <strong><?= htmlspecialchars((string) $event['event_date'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="col-12 col-md-3">
                    <small class="text-muted d-block">Jam</small>
                    <strong><?= htmlspecialchars((string) $event['event_time'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="col-12 col-md-3">
                    <small class="text-muted d-block">Lokasi</small>
                    <strong><?= htmlspecialchars((string) $event['location'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="col-12">
                    <small class="text-muted d-block">Catatan</small>
                    <span><?= nl2br(htmlspecialchars((string) $event['notes'], ENT_QUOTES, 'UTF-8')) ?></span>
                </div>
            </div>

            <hr>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-light border">Total peserta: <?= (int) ($event['attendee_total'] ?? 0) ?></span>
                <span class="badge text-bg-danger">Terlambat: <?= (int) ($event['late_total'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <h3 class="h6 mb-3">Link & QR Absensi</h3>
            <?php if ($publicLink === ''): ?>
                <p class="text-muted mb-0">Belum ada link. Klik tombol Generate Link & QR untuk membuat link absensi publik.</p>
            <?php else: ?>
                <div class="attendance-share-wrap">
                    <div>
                        <label class="form-label mb-1">Link Absensi Publik</label>
                        <input type="text" class="form-control" readonly value="<?= htmlspecialchars($publicLink, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= rawurlencode($publicLink) ?>" alt="QR Code Absensi" class="img-fluid" loading="lazy">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h3 class="h6 mb-3">Daftar Kehadiran</h3>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Unit</th>
                        <th>Selfie</th>
                        <th>Waktu Hadir</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($attendees)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada peserta yang mengisi form.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendees as $idx => $attendee): ?>
                            <?php
                            $isLate = ((int) ($attendee['is_late'] ?? 0)) === 1;
                            $selfiePath = trim((string) ($attendee['selfie_path'] ?? ''));
                            ?>
                            <tr>
                                <td><?= (int) $idx + 1 ?></td>
                                <td><?= htmlspecialchars((string) $attendee['participant_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($attendee['nip'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($attendee['unit_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($selfiePath !== ''): ?>
                                        <a href="<?= htmlspecialchars(app_url('/' . ltrim($selfiePath, '/')), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info">Lihat Selfie</a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?= $isLate ? 'text-danger fw-semibold' : '' ?>">
                                    <?= htmlspecialchars((string) $attendee['present_at'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <?php if ($isLate): ?>
                                        <span class="badge text-bg-danger">Terlambat</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">Tepat Waktu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="<?= htmlspecialchars(app_url('/attendance/detail/' . (int) $event['id'] . '/attendee/' . (int) $attendee['id'] . '/delete'), ENT_QUOTES, 'UTF-8') ?>" method="post" onsubmit="return confirm('Hapus data absensi <?= htmlspecialchars((string) $attendee['participant_name'], ENT_JS, 'UTF-8') ?>? Tindakan ini tidak dapat dibatalkan.')">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
