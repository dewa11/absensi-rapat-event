<?php

declare(strict_types=1);

use app\controllers\AttendanceController;
use app\controllers\AuthController;
use app\controllers\DashboardController;
use app\controllers\ExportController;
use app\helpers\SessionHelper;
use app\middleware\AuthMiddleware;
use app\models\AdminModel;
use app\models\AttendanceModel;

$getApp = static fn() => Flight::app();
$getDb = static fn() => Flight::db();

$authController = static fn(?AdminModel $adminModel = null) => new AuthController($getApp(), $adminModel);
$dashboardController = static fn() => new DashboardController($getApp(), new AttendanceModel($getDb()));
$attendanceController = static fn() => new AttendanceController($getApp(), new AttendanceModel($getDb()));
$exportController = static fn() => new ExportController($getApp(), new AttendanceModel($getDb()));

Flight::route('GET /', static function () {
    if (SessionHelper::isLoggedIn()) {
        Flight::redirect('/dashboard');
        return;
    }

    Flight::redirect('/login');
});

Flight::route('GET /login', static function () use ($authController) {
    $authController()->showLogin();
});

Flight::route('POST /login', static function () use ($authController) {
    $authController(new AdminModel(Flight::db()))->login();
});

Flight::route('GET /captcha', static function () use ($authController) {
    $authController()->captcha();
});

Flight::route('GET /logout', static function () use ($authController) {
    $authController()->logout();
});

Flight::route('GET /dashboard', static function () use ($dashboardController) {
    AuthMiddleware::handle();
    $dashboardController()->index();
});

Flight::route('GET /attendance/create', static function () use ($attendanceController) {
    AuthMiddleware::handle();
    $attendanceController()->createForm();
});

Flight::route('POST /attendance/create', static function () use ($attendanceController) {
    AuthMiddleware::handle();
    $attendanceController()->store();
});

Flight::route('GET /attendance', static function () use ($attendanceController) {
    AuthMiddleware::handle();
    $attendanceController()->index();
});

Flight::route('GET /attendance/export/csv', static function () use ($exportController) {
    AuthMiddleware::handle();
    $exportController()->exportCsv();
});

Flight::route('GET /attendance/export/pdf', static function () use ($exportController) {
    AuthMiddleware::handle();
    $exportController()->exportPdf();
});

Flight::route('GET /attendance/detail/@id', static function ($id) use ($attendanceController) {
    AuthMiddleware::handle();
    $attendanceController()->detail((string) $id);
});

Flight::route('POST /attendance/detail/@id/generate-link', static function ($id) use ($attendanceController) {
    AuthMiddleware::handle();
    $attendanceController()->generateLink((string) $id);
});

Flight::route('POST /attendance/detail/@id/attendee/@attendee_id/delete', static function ($id, $attendee_id) use ($attendanceController) {
    AuthMiddleware::handle();
    $attendanceController()->deleteAttendee((string) $id, (string) $attendee_id);
});

Flight::route('POST /attendance/detail/@id/delete', static function ($id) use ($attendanceController) {
    AuthMiddleware::handle();
    $attendanceController()->deleteEvent((string) $id);
});

Flight::route('GET /f/@token', static function ($token) use ($attendanceController) {
    $attendanceController()->publicForm((string) $token);
});

Flight::route('GET /f/@token/captcha', static function ($token) use ($attendanceController) {
    $attendanceController()->publicCaptcha((string) $token);
});

Flight::route('POST /f/@token', static function ($token) use ($attendanceController) {
    $attendanceController()->submitPublicForm((string) $token);
});
