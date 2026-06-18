<?php

declare(strict_types=1);

namespace app\controllers;

use app\helpers\CaptchaHelper;
use app\helpers\SessionHelper;
use app\models\AdminModel;
use flight\Engine;

class AuthController extends BaseController
{
    private ?AdminModel $adminModel;

    public function __construct(Engine $app, ?AdminModel $adminModel = null)
    {
        parent::__construct($app);
        $this->adminModel = $adminModel;
    }

    public function showLogin(): void
    {
        if (SessionHelper::isLoggedIn()) {
            $this->app->redirect('/dashboard');
            return;
        }

        $this->render('auth/login', [
            'title' => 'Login Admin',
            'error' => SessionHelper::flash('error'),
            'success' => SessionHelper::flash('success'),
        ], 'layouts/auth');
    }

    public function login(): void
    {
        $request = $this->app->request();
        $username = trim((string) ($request->data->username ?? ''));
        $password = (string) ($request->data->password ?? '');
        $captcha = trim((string) ($request->data->captcha ?? ''));

        if ($username === '' || $password === '' || $captcha === '') {
            SessionHelper::flash('error', 'Username, password, dan captcha wajib diisi.');
            $this->app->redirect('/login');
            return;
        }

        if (!CaptchaHelper::validate($captcha)) {
            SessionHelper::flash('error', 'Captcha tidak valid. Silakan coba lagi.');
            $this->app->redirect('/login');
            return;
        }

        $adminModel = $this->adminModel;
        if ($adminModel === null) {
            SessionHelper::flash('error', 'Layanan login belum siap. Periksa koneksi database.');
            $this->app->redirect('/login');
            return;
        }

        $admin = $adminModel->verifyCredentials($username, $password);
        if ($admin === null) {
            SessionHelper::flash('error', 'Username atau password salah.');
            $this->app->redirect('/login');
            return;
        }

        SessionHelper::regenerateId();
        SessionHelper::set('admin_user', $admin);
        SessionHelper::flash('success', 'Login berhasil. Selamat datang, ' . $admin['full_name'] . '.');
        $this->app->redirect('/dashboard');
    }

    public function captcha(): void
    {
        $code = CaptchaHelper::generateCode();
        $image = CaptchaHelper::renderImage($code);

        $this->app->response()->header('Content-Type', $image['mime']);
        $this->app->response()->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->app->response()->write($image['content']);
    }

    public function logout(): void
    {
        SessionHelper::destroy();
        SessionHelper::flash('success', 'Anda telah logout.');
        $this->app->redirect('/login');
    }
}
