<?php

declare(strict_types=1);

namespace app\helpers;

class RateLimitHelper
{
    public static function allow(string $key, int $limit, int $windowSeconds): bool
    {
        SessionHelper::start();

        $now = time();
        $records = self::cleanup(self::getRecords($key), $now, $windowSeconds);

        if (count($records) >= $limit) {
            self::setRecords($key, $records);
            return false;
        }

        $records[] = $now;
        self::setRecords($key, $records);

        return true;
    }

    public static function retryAfterSeconds(string $key, int $windowSeconds): int
    {
        SessionHelper::start();

        $now = time();
        $records = self::cleanup(self::getRecords($key), $now, $windowSeconds);
        self::setRecords($key, $records);

        if (empty($records)) {
            return 0;
        }

        $oldest = (int) $records[0];
        $retryAfter = ($oldest + $windowSeconds) - $now;

        return $retryAfter > 0 ? $retryAfter : 0;
    }

    private static function getRecords(string $key): array
    {
        $bucket = SessionHelper::get('_rate_limit', []);
        $records = $bucket[$key] ?? [];

        return is_array($records) ? array_values(array_filter($records, 'is_numeric')) : [];
    }

    private static function setRecords(string $key, array $records): void
    {
        $bucket = SessionHelper::get('_rate_limit', []);
        if (!is_array($bucket)) {
            $bucket = [];
        }

        $bucket[$key] = array_values($records);
        SessionHelper::set('_rate_limit', $bucket);
    }

    private static function cleanup(array $records, int $now, int $windowSeconds): array
    {
        return array_values(array_filter($records, static function ($timestamp) use ($now, $windowSeconds) {
            return ((int) $timestamp) > ($now - $windowSeconds);
        }));
    }
}
