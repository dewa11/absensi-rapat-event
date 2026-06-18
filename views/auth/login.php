<section class="container py-5">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card login-card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="<?= htmlspecialchars(app_asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="login-logo mb-3">
                        <h2 class="h4 mb-1">Login Admin</h2>
                        <p class="text-muted mb-0">Sistem Absensi Rapat & Event</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success" role="alert"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form action="<?= htmlspecialchars(app_url('/login'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <label class="form-label">Captcha 4 Digit</label>
                        <div class="captcha-wrap mb-3">
                            <img id="captcha-image" data-captcha-url="<?= htmlspecialchars(app_url('/captcha'), ENT_QUOTES, 'UTF-8') ?>" src="<?= htmlspecialchars(app_url('/captcha'), ENT_QUOTES, 'UTF-8') ?>?ts=<?= time() ?>" alt="Captcha" class="captcha-img">
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

                        <button type="submit" class="btn btn-royal w-100 d-inline-flex align-items-center justify-content-center gap-2" aria-label="Login" title="Login">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false">
                                <path d="M10.854 8.354a.5.5 0 0 0 0-.708L7.672 4.464a.5.5 0 1 0-.708.708L9.293 7.5H2.5a.5.5 0 0 0 0 1h6.793l-2.329 2.328a.5.5 0 0 0 .708.708l3.182-3.182z"/>
                                <path d="M13.5 13a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1 0-1h5.5V3.5H7a.5.5 0 0 1 0-1h6a.5.5 0 0 1 .5.5V13z"/>
                            </svg>
                            <span>Login</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
