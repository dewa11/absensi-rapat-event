<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php
        $filters = is_array($filters ?? null) ? $filters : [];
        $fromDate = (string) ($filters['from_date'] ?? '');
        $toDate = (string) ($filters['to_date'] ?? '');
        $eventType = (string) ($filters['event_type'] ?? '');
        $keyword = (string) ($filters['keyword'] ?? '');
        ?>
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3 gap-3">
            <div>
                <h2 class="h5 mb-1">Data Kegiatan</h2>
                <p class="text-muted mb-0">Default menampilkan kegiatan 7 hari terakhir (termasuk hari ini).</p>
            </div>
            <form action="<?= htmlspecialchars(app_url('/attendance'), ENT_QUOTES, 'UTF-8') ?>" method="get" class="attendance-filter-form">
                <div>
                    <label for="from_date" class="form-label mb-1 small">Dari Tanggal</label>
                    <input type="date" id="from_date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div>
                    <label for="to_date" class="form-label mb-1 small">Sampai Tanggal</label>
                    <input type="date" id="to_date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div>
                    <label for="event_type" class="form-label mb-1 small">Tipe</label>
                    <select id="event_type" name="event_type" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="Rapat" <?= $eventType === 'Rapat' ? 'selected' : '' ?>>Rapat</option>
                        <option value="Event" <?= $eventType === 'Event' ? 'selected' : '' ?>>Event</option>
                    </select>
                </div>
                <div>
                    <label for="q" class="form-label mb-1 small">Cari Teks</label>
                    <input type="text" id="q" name="q" class="form-control form-control-sm" placeholder="Judul/lokasi/catatan" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="attendance-filter-actions">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Cari</button>
                    <a href="<?= htmlspecialchars(app_url('/attendance'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php
        $exportQuery = http_build_query([
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'event_type' => $eventType,
            'q'          => $keyword,
        ]);
        $csvUrl  = htmlspecialchars(app_url('/attendance/export/csv') . '?' . $exportQuery, ENT_QUOTES, 'UTF-8');
        $pdfUrl  = htmlspecialchars(app_url('/attendance/export/pdf') . '?' . $exportQuery, ENT_QUOTES, 'UTF-8');
        ?>
        <div class="d-flex gap-2 mb-3">
            <a href="<?= $csvUrl ?>" class="btn btn-sm btn-outline-success" title="Unduh data sebagai CSV (dapat dibuka di Excel)">
                &#8595; Ekspor CSV
            </a>
            <a href="<?= $pdfUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Buka halaman cetak untuk simpan sebagai PDF">
                &#8599; Ekspor PDF
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>No</th>
                    <th>Tipe</th>
                    <th>Judul</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Lokasi</th>
                    <th>Peserta</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada data kegiatan pada filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $idx => $row): ?>
                        <tr>
                            <td><?= (int) $idx + 1 ?></td>
                            <td><?= htmlspecialchars((string) $row['event_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="<?= htmlspecialchars(app_url('/attendance/detail/' . (int) $row['id']), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars((string) $row['event_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row['event_time'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row['location'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge text-bg-light border"><?= (int) ($row['attendee_total'] ?? 0) ?> peserta</span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="<?= htmlspecialchars(app_url('/attendance/detail/' . (int) $row['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                    <form action="<?= htmlspecialchars(app_url('/attendance/detail/' . (int) $row['id'] . '/generate-link'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                                        <button type="submit" class="btn btn-sm <?= !empty($row['attendance_token']) ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                            <?= !empty($row['attendance_token']) ? 'Regenerate Link' : 'Generate Link' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
