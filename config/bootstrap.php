<?php

declare(strict_types=1);

use app\helpers\SessionHelper;
use flight\database\PdoWrapper;

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        static $basePath = null;
        if ($basePath !== null) {
            return $basePath;
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = str_replace('\\', '/', dirname($scriptName));

        if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
            $basePath = '/';
            return $basePath;
        }

        $basePath = '/' . trim($basePath, '/');
        return $basePath;
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = '/'): string
    {
        $normalizedPath = '/' . ltrim($path, '/');
        $basePath = app_base_path();

        if ($basePath === '/') {
            return $normalizedPath;
        }

        return $basePath . $normalizedPath;
    }
}

if (!function_exists('app_asset_url')) {
    function app_asset_url(string $path = ''): string
    {
        return app_url('/public/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('app_request_scheme')) {
    function app_request_scheme(): string
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return 'https';
        }

        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if ($forwardedProto !== '') {
            $firstProto = trim(explode(',', $forwardedProto)[0]);
            if ($firstProto === 'https' || $firstProto === 'http') {
                return $firstProto;
            }
        }

        $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');
        return $serverPort === '443' ? 'https' : 'http';
    }
}

if (!function_exists('app_request_host')) {
    function app_request_host(): string
    {
        $forwardedHost = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
        if ($forwardedHost !== '') {
            return trim(explode(',', $forwardedHost)[0]);
        }

        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== '') {
            return $host;
        }

        $serverName = trim((string) ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '80');
        if (($serverPort === '80' && app_request_scheme() === 'http') || ($serverPort === '443' && app_request_scheme() === 'https')) {
            return $serverName;
        }

        return $serverName . ':' . $serverPort;
    }
}

if (!function_exists('app_url_absolute')) {
    function app_url_absolute(string $path = '/'): string
    {
        $configuredBaseUrl = 'https://rsuthalia.id/absensi-rapat-event';
        $configuredBaseUrl = rtrim(trim($configuredBaseUrl), '/');

        if ($configuredBaseUrl !== '') {
            return $configuredBaseUrl . '/' . ltrim($path, '/');
        }

        return app_request_scheme() . '://' . app_request_host() . app_url($path);
    }
}

SessionHelper::start();
date_default_timezone_set('Asia/Jakarta');
Flight::set('flight.base_url', app_base_path());

$config = require __DIR__ . '/database.php';

$dsn = sprintf(
    '%s:host=%s;port=%s;dbname=%s;charset=%s',
    $config['driver'],
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

Flight::register('db', PdoWrapper::class, [
    $dsn,
    $config['username'],
    $config['password'],
    $options,
]);
