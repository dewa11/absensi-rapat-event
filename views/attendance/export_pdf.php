<?php
$filters = is_array($filters ?? null) ? $filters : [];
$fromDate = (string) ($filters['from_date'] ?? '');
$toDate = (string) ($filters['to_date'] ?? '');
$eventType = (string) ($filters['event_type'] ?? '');
$keyword = (string) ($filters['keyword'] ?? '');
$groupedEvents = is_array($groupedEvents ?? null) ? $groupedEvents : [];

$filterParts = [];
if ($fromDate !== '' && $toDate !== '') {
    $filterParts[] = 'Tanggal: ' . htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8') . ' s.d. ' . htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8');
}
if ($eventType !== '') {
    $filterParts[] = 'Tipe: ' . htmlspecialchars($eventType, ENT_QUOTES, 'UTF-8');
}
if ($keyword !== '') {
    $filterParts[] = 'Kata kunci: "' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '"';
}
?>
<div class="d-flex justify-content-between align-items-start mb-3 no-print">
    <div>
        <h4 class="mb-0">Ekspor Data Kegiatan &amp; Absensi</h4>
        <?php if ($filterParts !== []): ?>
            <small class="text-muted"><?= implode(' &bull; ', $filterParts) ?></small>
        <?php endif; ?>
    </div>
    <button class="btn btn-sm btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<div class="mb-4 d-print-block">
    <h5 class="mb-0">Ekspor Data Kegiatan &amp; Absensi</h5>
    <?php if ($filterParts !== []): ?>
        <p class="text-muted small mb-0"><?= implode(' &bull; ', $filterParts) ?></p>
    <?php endif; ?>
    <p class="text-muted small mb-0">Dicetak pada: <?= date('d/m/Y H:i') ?></p>
</div>

<?php if (empty($groupedEvents)): ?>
    <div class="alert alert-info">Tidak ada data kegiatan untuk filter yang dipilih.</div>
<?php else: ?>
    <?php $eventIndex = 0; ?>
    <?php foreach ($groupedEvents as $eventData): ?>
        <?php
        $info = is_array($eventData['info']) ? $eventData['info'] : [];
        $attendees = is_array($eventData['attendees']) ? $eventData['attendees'] : [];
        $eventIndex++;
        ?>
        <div class="event-section <?= $eventIndex > 1 ? 'mt-4' : '' ?>">
            <div class="card border mb-2">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <span class="badge text-bg-secondary me-1"><?= htmlspecialchars((string) ($info['event_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= htmlspecialchars((string) ($info['event_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <span class="badge text-bg-light border"><?= count($attendees) ?> peserta hadir</span>
                    </div>
                    <div class="text-muted small mt-1">
                        <?= htmlspecialchars((string) ($info['event_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        &bull; <?= htmlspecialchars((string) ($info['event_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        &bull; <?= htmlspecialchars((string) ($info['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($info['event_notes'])): ?>
                            &bull; <em><?= htmlspecialchars((string) $info['event_notes'], ENT_QUOTES, 'UTF-8') ?></em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (empty($attendees)): ?>
                <p class="text-muted fst-italic ps-2 mb-0 small">(Belum ada peserta yang tercatat)</p>
            <?php else: ?>
                <table class="table table-bordered table-sm table-striped align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:2.5rem">No</th>
                        <th>Nama Peserta</th>
                        <th>NIP</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Waktu Hadir</th>
                        <th style="width:5rem">Terlambat</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($attendees as $idx => $att): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars((string) ($att['participant_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($att['nip'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($att['unit_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($att['attendance_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($att['present_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-center">
                                <?php if ((int) ($att['is_late'] ?? 0) === 1): ?>
                                    <span class="badge text-bg-warning">Ya</span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">Tidak</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
