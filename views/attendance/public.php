<?php
$event = is_array($event ?? null) ? $event : null;
?>

<section class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <div class="card login-card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="<?= htmlspecialchars(app_asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="login-logo mb-3">
                        <h2 class="h4 mb-1">Form Absensi Peserta</h2>
                        <p class="text-muted mb-0">Isi form ini tanpa login, lalu klik Kirim Absen.</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success" role="alert"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <?php if ($event !== null): ?>
                        <div class="public-event-summary mb-4">
                            <p class="mb-1"><strong><?= htmlspecialchars((string) $event['title'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                            <small class="text-muted d-block">Tipe: <?= htmlspecialchars((string) $event['event_type'], ENT_QUOTES, 'UTF-8') ?></small>
                            <small class="text-muted d-block">Tanggal: <?= htmlspecialchars((string) $event['event_date'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $event['event_time'], ENT_QUOTES, 'UTF-8') ?></small>
                            <small class="text-muted d-block">Lokasi: <?= htmlspecialchars((string) $event['location'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>

                        <form action="<?= htmlspecialchars(app_url('/f/' . (string) $event['attendance_token']), ENT_QUOTES, 'UTF-8') ?>" method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="participant_name" class="form-label">Nama</label>
                                <input type="text" class="form-control" id="participant_name" name="participant_name" required>
                            </div>

                            <div class="mb-3">
                                <label for="nip" class="form-label">NIP</label>
                                <input type="text" class="form-control" id="nip" name="nip" inputmode="numeric" pattern="[0-9]+" placeholder="Angka saja" required>
                                <small class="text-muted">Gunakan angka saja, tanpa huruf atau simbol.</small>
                            </div>

                            <div class="mb-3">
                                <label for="unit_name" class="form-label">Unit</label>
                                <input type="text" class="form-control" id="unit_name" name="unit_name" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Selfie</label>
                                <div class="border rounded-3 p-3 bg-body-tertiary">
                                    <video id="selfie-video" class="w-100 rounded-2 bg-black" autoplay muted playsinline></video>
                                    <img id="selfie-preview" class="w-100 rounded-2 d-none mt-3" alt="Pratinjau selfie yang diambil">
                                    <canvas id="selfie-canvas" class="d-none"></canvas>
                                    <input type="hidden" id="selfie_data" name="selfie_data" value="">
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <button type="button" class="btn btn-outline-primary" id="capture-selfie">Ambil Foto</button>
                                        <button type="button" class="btn btn-outline-secondary d-none" id="retake-selfie">Ulangi Foto</button>
                                    </div>
                                    <small class="text-muted d-block mt-2">Selfie diambil langsung dari kamera, bukan unggahan file.</small>
                                    <small class="text-muted d-block" id="selfie-status">Menyiapkan kamera...</small>
                                </div>
                            </div>

                            <label class="form-label">Captcha 4 Digit</label>
                            <div class="captcha-wrap mb-3">
                                <img id="captcha-image" data-captcha-url="<?= htmlspecialchars(app_url('/f/' . (string) $event['attendance_token'] . '/captcha'), ENT_QUOTES, 'UTF-8') ?>" src="<?= htmlspecialchars(app_url('/f/' . (string) $event['attendance_token'] . '/captcha'), ENT_QUOTES, 'UTF-8') ?>?ts=<?= time() ?>" alt="Captcha" class="captcha-img">
                                <button type="button" class="btn btn-outline-primary btn-icon-only" id="refresh-captcha" aria-label="Muat ulang captcha" title="Muat ulang captcha">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path d="M8 3a5 5 0 1 0 4.546 2.916.5.5 0 1 1 .908-.418A6 6 0 1 1 8 2v1z"/>
                                        <path d="M8 0a.5.5 0 0 1 .5.5V3h2.5a.5.5 0 0 1 .354.854l-3 3a.5.5 0 0 1-.708 0l-3-3A.5.5 0 0 1 5 3h2.5V.5A.5.5 0 0 1 8 0z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="mb-4">
                                <input type="text" class="form-control" id="captcha" name="captcha" maxlength="4" pattern="[0-9]{4}" placeholder="Masukkan 4 digit" required>
                            </div>

                            <button type="submit" class="btn btn-royal w-100">Kirim Absen</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
