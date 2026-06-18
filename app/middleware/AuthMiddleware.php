<?php

declare(strict_types=1);

namespace app\middleware;

use app\helpers\SessionHelper;
use Flight;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (SessionHelper::isLoggedIn()) {
            return;
        }

        SessionHelper::flash('error', 'Silakan login terlebih dahulu.');
        Flight::redirect('/login');
        exit;
    }
}
