<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h2 class="h5 mb-3">Form Buat Absen</h2>
        <form action="<?= htmlspecialchars(app_url('/attendance/create'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label">Tipe Kegiatan</label>
                <select name="event_type" class="form-select" required>
                    <option value="">Pilih tipe</option>
                    <option value="Rapat">Rapat</option>
                    <option value="Event">Event</option>
                </select>
            </div>

            <div class="col-12 col-md-8">
                <label class="form-label">Judul Kegiatan</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Tanggal Kegiatan</label>
                <input type="date" name="event_date" class="form-control" required>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Jam Kegiatan</label>
                <input type="time" name="event_time" class="form-control" required>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Lokasi</label>
                <input type="text" name="location" class="form-control" required>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3" required></textarea>
            </div>

            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-royal">Simpan Kegiatan</button>
            </div>
        </form>
    </div>
</div>
