<?php

declare(strict_types=1);

namespace app\controllers;

use app\helpers\CaptchaHelper;
use app\helpers\RateLimitHelper;
use app\helpers\SessionHelper;
use app\models\AttendanceModel;
use flight\Engine;

class AttendanceController extends BaseController
{
    private const ALLOWED_EVENT_TYPES = ['Rapat', 'Event'];
    private const PUBLIC_CAPTCHA_KEY = 'public_attendance_captcha_code';
    private const PUBLIC_RATE_LIMIT_ATTEMPTS = 3;
    private const PUBLIC_RATE_LIMIT_WINDOW_SECONDS = 60;
    private const PUBLIC_TOKEN_PATTERN = '/^[a-f0-9]{32}$/';
    private const SELFIE_OUTPUT_SIZE = 500;
    private const SELFIE_JPEG_QUALITY = 85;

    private AttendanceModel $attendanceModel;

    public function __construct(Engine $app, AttendanceModel $attendanceModel)
    {
        parent::__construct($app);
        $this->attendanceModel = $attendanceModel;
    }

    public function createForm(): void
    {
        $this->render('attendance/create', [
            'title' => 'Buat Absen',
            'activeMenu' => 'create',
            'error' => SessionHelper::flash('error'),
            'success' => SessionHelper::flash('success'),
        ]);
    }

    public function store(): void
    {
        $request = $this->app->request();

        $payload = [
            'event_type' => trim((string) ($request->data->event_type ?? '')),
            'title' => trim((string) ($request->data->title ?? '')),
            'event_date' => trim((string) ($request->data->event_date ?? '')),
            'event_time' => trim((string) ($request->data->event_time ?? '')),
            'location' => trim((string) ($request->data->location ?? '')),
            'notes' => trim((string) ($request->data->notes ?? '')),
        ];

        foreach (['event_type', 'title', 'event_date', 'event_time', 'location', 'notes'] as $field) {
            if ($payload[$field] === '') {
                SessionHelper::flash('error', 'Semua field wajib diisi.');
                $this->app->redirect('/attendance/create');
                return;
            }
        }

        if (!in_array($payload['event_type'], ['Rapat', 'Event'], true)) {
            SessionHelper::flash('error', 'Tipe kegiatan harus Rapat atau Event.');
            $this->app->redirect('/attendance/create');
            return;
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $payload['event_time'])) {
            SessionHelper::flash('error', 'Jam kegiatan harus berformat HH:MM.');
            $this->app->redirect('/attendance/create');
            return;
        }

        $admin = SessionHelper::get('admin_user', []);
        $adminId = (int) ($admin['id'] ?? 0);

        try {
            $this->attendanceModel->createAttendance($payload, $adminId);
            SessionHelper::flash('success', 'Data kegiatan berhasil disimpan.');
            $this->app->redirect('/attendance');
        } catch (\Throwable $e) {
            SessionHelper::flash('error', 'Gagal menyimpan data kegiatan. Pastikan database sudah siap.');
            $this->app->redirect('/attendance/create');
        }
    }

    public function index(): void
    {
        $request = $this->app->request();
        $fromDate = trim((string) ($request->query->from_date ?? ''));
        $toDate = trim((string) ($request->query->to_date ?? ''));
        $eventType = trim((string) ($request->query->event_type ?? ''));
        $keyword = trim((string) ($request->query->q ?? ''));

        if (!$this->isValidDate($fromDate) && $fromDate !== '') {
            $fromDate = '';
        }

        if (!$this->isValidDate($toDate) && $toDate !== '') {
            $toDate = '';
        }

        if ($fromDate === '' && $toDate === '') {
            $toDate = date('Y-m-d');
            $fromDate = date('Y-m-d', strtotime('-6 days'));
        } elseif ($fromDate === '' && $toDate !== '') {
            $fromDate = $toDate;
        } elseif ($fromDate !== '' && $toDate === '') {
            $toDate = $fromDate;
        }

        if (strtotime($fromDate) > strtotime($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        if (!in_array($eventType, self::ALLOWED_EVENT_TYPES, true)) {
            $eventType = '';
        }

        $filters = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'event_type' => $eventType,
            'keyword' => $keyword,
        ];

        $this->render('attendance/index', [
            'title' => 'Data Kegiatan',
            'activeMenu' => 'data',
            'filters' => $filters,
            'rows' => $this->attendanceModel->listAttendances($filters),
            'success' => SessionHelper::flash('success'),
            'error' => SessionHelper::flash('error'),
        ]);
    }

    public function detail(string $id): void
    {
        $eventId = (int) $id;
        if ($eventId <= 0) {
            SessionHelper::flash('error', 'Data kegiatan tidak ditemukan.');
            $this->app->redirect('/attendance');
            return;
        }

        $event = $this->attendanceModel->getEventById($eventId);
        if ($event === null) {
            SessionHelper::flash('error', 'Data kegiatan tidak ditemukan.');
            $this->app->redirect('/attendance');
            return;
        }

        $publicLink = '';
        if (!empty($event['attendance_token'])) {
            $publicLink = app_url_absolute('/f/' . (string) $event['attendance_token']);
        }

        $this->render('attendance/detail', [
            'title' => 'Detail Kegiatan',
            'activeMenu' => 'data',
            'event' => $event,
            'attendees' => $this->attendanceModel->getEventAttendances($eventId),
            'publicLink' => $publicLink,
            'success' => SessionHelper::flash('success'),
            'error' => SessionHelper::flash('error'),
        ]);
    }

    public function generateLink(string $id): void
    {
        $eventId = (int) $id;
        if ($eventId <= 0 || !$this->attendanceModel->eventExists($eventId)) {
            SessionHelper::flash('error', 'Data kegiatan tidak ditemukan.');
            $this->app->redirect('/attendance');
            return;
        }

        try {
            $token = bin2hex(random_bytes(16));
            if (!$this->isValidPublicToken($token)) {
                throw new \RuntimeException('Generated token is invalid.');
            }

            $this->attendanceModel->saveEventToken($eventId, $token);
            SessionHelper::flash('success', 'Link absensi berhasil dibuat.');
        } catch (\Throwable $e) {
            SessionHelper::flash('error', 'Gagal membuat link absensi. Silakan coba lagi.');
        }

        $this->app->redirect('/attendance/detail/' . $eventId);
    }

    public function publicForm(string $token): void
    {
        $cleanToken = trim($token);
        if (!$this->isValidPublicToken($cleanToken)) {
            $this->render('attendance/public', [
                'title' => 'Form Absensi Tidak Ditemukan',
                'event' => null,
                'error' => 'Link absensi tidak valid atau sudah tidak aktif.',
                'success' => null,
            ], 'layouts/auth');
            return;
        }

        $event = $this->attendanceModel->getEventByToken($cleanToken);
        if ($event === null) {
            $this->render('attendance/public', [
                'title' => 'Form Absensi Tidak Ditemukan',
                'event' => null,
                'error' => 'Link absensi tidak valid atau sudah tidak aktif.',
                'success' => null,
            ], 'layouts/auth');
            return;
        }

        $this->render('attendance/public', [
            'title' => 'Form Absensi Peserta',
            'event' => $event,
            'error' => SessionHelper::flash('public_error'),
            'success' => SessionHelper::flash('public_success'),
        ], 'layouts/auth');
    }

    public function submitPublicForm(string $token): void
    {
        $cleanToken = trim($token);
        if (!$this->isValidPublicToken($cleanToken)) {
            SessionHelper::flash('public_error', 'Link absensi tidak valid atau sudah tidak aktif.');
            $this->app->redirect('/');
            return;
        }

        $event = $this->attendanceModel->getEventByToken($cleanToken);
        if ($event === null) {
            SessionHelper::flash('public_error', 'Link absensi tidak valid atau sudah tidak aktif.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        $request = $this->app->request();
        $rateKey = $this->buildPublicRateLimitKey((string) $cleanToken, (string) ($request->ip ?? 'unknown'));
        if (!RateLimitHelper::allow($rateKey, self::PUBLIC_RATE_LIMIT_ATTEMPTS, self::PUBLIC_RATE_LIMIT_WINDOW_SECONDS)) {
            $retryAfter = RateLimitHelper::retryAfterSeconds($rateKey, self::PUBLIC_RATE_LIMIT_WINDOW_SECONDS);
            $waitFor = $retryAfter > 0 ? $retryAfter : self::PUBLIC_RATE_LIMIT_WINDOW_SECONDS;
            SessionHelper::flash('public_error', 'Terlalu banyak percobaan. Coba lagi dalam ' . $waitFor . ' detik.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        $name = trim((string) ($request->data->participant_name ?? ''));
        $nip = trim((string) ($request->data->nip ?? ''));
        $unit = trim((string) ($request->data->unit_name ?? ''));
        $selfieData = trim((string) ($request->data->selfie_data ?? ''));
        $captcha = trim((string) ($request->data->captcha ?? ''));

        if ($name === '') {
            SessionHelper::flash('public_error', 'Nama wajib diisi.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        if ($nip === '') {
            SessionHelper::flash('public_error', 'NIP wajib diisi.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        if (!preg_match('/^\d+$/', $nip)) {
            SessionHelper::flash('public_error', 'NIP harus berupa angka saja.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        if ($unit === '') {
            SessionHelper::flash('public_error', 'Unit wajib diisi.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        if ($selfieData === '') {
            SessionHelper::flash('public_error', 'Selfie wajib diambil dari kamera.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        if ($captcha === '') {
            SessionHelper::flash('public_error', 'Captcha wajib diisi.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        if (!CaptchaHelper::validate($captcha, self::PUBLIC_CAPTCHA_KEY)) {
            SessionHelper::flash('public_error', 'Captcha tidak valid. Silakan coba lagi.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        $tmpPath = null;
        try {
            $tmpPath = $this->createTempSelfieFileFromDataUrl($selfieData);

            $size = is_file($tmpPath) ? (int) filesize($tmpPath) : 0;
            if ($size <= 0 || $size > 5 * 1024 * 1024) {
                throw new \RuntimeException('Ukuran foto maksimal 5MB.');
            }

            if (!$this->isAllowedSelfieMime($tmpPath)) {
                throw new \RuntimeException('Format foto hanya JPG, PNG, atau WEBP.');
            }
        } catch (\Throwable $e) {
            if ($tmpPath !== null && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
            SessionHelper::flash('public_error', $e->getMessage() !== '' ? $e->getMessage() : 'Foto selfie tidak valid.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        $uploadDirRelative = 'public/uploads/selfies';
        $uploadDirAbsolute = dirname(__DIR__, 2) . '/' . $uploadDirRelative;
        if (!is_dir($uploadDirAbsolute) && !mkdir($uploadDirAbsolute, 0775, true) && !is_dir($uploadDirAbsolute)) {
            SessionHelper::flash('public_error', 'Folder upload tidak tersedia.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        $filename = sprintf('event-%d-%s.jpg', (int) $event['id'], str_replace('.', '', uniqid('', true)));
        $targetAbsolute = $uploadDirAbsolute . '/' . $filename;

        try {
            $this->storeSelfieImage($tmpPath, $targetAbsolute);
        } catch (\Throwable $e) {
            if (is_file($targetAbsolute)) {
                @unlink($targetAbsolute);
            }
            if ($tmpPath !== null && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
            error_log('[attendance:selfie] ' . $e->getMessage());
            SessionHelper::flash('public_error', 'Gagal menyimpan foto selfie.');
            $this->app->redirect('/f/' . rawurlencode($cleanToken));
            return;
        }

        if ($tmpPath !== null && is_file($tmpPath)) {
            @unlink($tmpPath);
        }

        try {
            $this->attendanceModel->createPublicAttendance(
                (int) $event['id'],
                $name,
                $nip,
                $unit,
                $uploadDirRelative . '/' . $filename
            );
            SessionHelper::flash('public_success', 'Absen berhasil dikirim. Terima kasih.');
        } catch (\Throwable $e) {
            SessionHelper::flash('public_error', 'Gagal menyimpan data absen. Silakan coba lagi.');
        }

        $this->app->redirect('/f/' . rawurlencode($cleanToken));
    }

    private function isValidPublicToken(string $token): bool
    {
        return preg_match(self::PUBLIC_TOKEN_PATTERN, $token) === 1;
    }

    private function detectMimeType(string $filePath): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $filePath);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    return $detected;
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $detected = mime_content_type($filePath);
            if (is_string($detected) && $detected !== '') {
                return $detected;
            }
        }

        return '';
    }

    private function isAllowedSelfieMime(string $filePath): bool
    {
        $mime = $this->detectMimeType($filePath);
        return in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    private function loadImageResource(string $filePath, string $mime)
    {
        if ($mime === 'image/jpeg') {
            return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($filePath) : false;
        }

        if ($mime === 'image/png') {
            return function_exists('imagecreatefrompng') ? @imagecreatefrompng($filePath) : false;
        }

        if ($mime === 'image/webp') {
            return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : false;
        }

        return false;
    }

    private function storeSelfieImage(string $sourcePath, string $targetPath): void
    {
        $mime = $this->detectMimeType($sourcePath);
        if ($mime === 'image/jpeg') {
            if (!@rename($sourcePath, $targetPath) && !@copy($sourcePath, $targetPath)) {
                throw new \RuntimeException('Failed to move selfie image.');
            }

            return;
        }

        $this->convertSelfieToJpegSquare(
            $sourcePath,
            $targetPath,
            self::SELFIE_OUTPUT_SIZE,
            self::SELFIE_JPEG_QUALITY
        );
    }

    private function convertSelfieToJpegSquare(string $sourcePath, string $targetPath, int $size, int $quality): void
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled') || !function_exists('imagejpeg')) {
            throw new \RuntimeException('GD extension is required.');
        }

        $mime = $this->detectMimeType($sourcePath);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \RuntimeException('Unsupported selfie format.');
        }

        $sourceImage = $this->loadImageResource($sourcePath, $mime);
        if ($sourceImage === false) {
            throw new \RuntimeException('Failed to read selfie image.');
        }

        $srcWidth = imagesx($sourceImage);
        $srcHeight = imagesy($sourceImage);
        if ($srcWidth <= 0 || $srcHeight <= 0) {
            imagedestroy($sourceImage);
            throw new \RuntimeException('Invalid selfie dimensions.');
        }

        $cropSize = min($srcWidth, $srcHeight);
        $srcX = (int) floor(($srcWidth - $cropSize) / 2);
        $srcY = (int) floor(($srcHeight - $cropSize) / 2);

        $targetImage = imagecreatetruecolor($size, $size);
        if ($targetImage === false) {
            imagedestroy($sourceImage);
            throw new \RuntimeException('Failed to process selfie image.');
        }

        $white = imagecolorallocate($targetImage, 255, 255, 255);
        imagefilledrectangle($targetImage, 0, 0, $size, $size, $white);

        if (!imagecopyresampled($targetImage, $sourceImage, 0, 0, $srcX, $srcY, $size, $size, $cropSize, $cropSize)) {
            imagedestroy($sourceImage);
            imagedestroy($targetImage);
            throw new \RuntimeException('Failed to resize selfie image.');
        }

        if (!imagejpeg($targetImage, $targetPath, $quality)) {
            imagedestroy($sourceImage);
            imagedestroy($targetImage);
            throw new \RuntimeException('Failed to write selfie image.');
        }

        imagedestroy($sourceImage);
        imagedestroy($targetImage);
    }

    private function buildPublicRateLimitKey(string $token, string $ip): string
    {
        $normalizedIp = trim($ip) === '' ? 'unknown' : trim($ip);
        return 'public_submit:' . hash('sha256', $token . '|' . $normalizedIp);
    }

    private function createTempSelfieFileFromDataUrl(string $dataUrl): string
    {
        if (!preg_match('/^data:(image\/(?:jpeg|png|webp));base64,([a-zA-Z0-9+\/=\r\n]+)$/', $dataUrl, $matches)) {
            throw new \RuntimeException('Format selfie tidak valid.');
        }

        $binary = base64_decode(str_replace(["\r", "\n"], '', $matches[2]), true);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('Data selfie tidak valid.');
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Ukuran foto maksimal 5MB.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'selfie_');
        if ($tempPath === false) {
            throw new \RuntimeException('Tidak dapat menyiapkan file selfie sementara.');
        }

        if (file_put_contents($tempPath, $binary) === false) {
            @unlink($tempPath);
            throw new \RuntimeException('Tidak dapat menyimpan data selfie sementara.');
        }

        return $tempPath;
    }

    public function publicCaptcha(string $token): void
    {
        $cleanToken = trim($token);
        if (!$this->isValidPublicToken($cleanToken)) {
            $this->app->response()->status(404);
            $this->app->response()->write('Captcha tidak tersedia.');
            return;
        }

        $event = $this->attendanceModel->getEventByToken($cleanToken);
        if ($event === null) {
            $this->app->response()->status(404);
            $this->app->response()->write('Captcha tidak tersedia.');
            return;
        }

        $code = CaptchaHelper::generateCode(self::PUBLIC_CAPTCHA_KEY);
        $image = CaptchaHelper::renderImage($code);

        $this->app->response()->header('Content-Type', $image['mime']);
        $this->app->response()->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->app->response()->write($image['content']);
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
