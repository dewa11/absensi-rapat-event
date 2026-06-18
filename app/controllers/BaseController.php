<?php

declare(strict_types=1);

namespace app\controllers;

use app\helpers\SessionHelper;
use flight\Engine;

abstract class BaseController
{
    protected Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    protected function render(string $view, array $data = [], string $layout = 'layouts/admin'): void
    {
        $content = $this->app->view()->fetch($view, $data);
        $baseData = [
            'title' => $data['title'] ?? 'Aplikasi Absensi',
            'content' => $content,
            'adminUser' => SessionHelper::get('admin_user'),
        ];

        $this->app->render($layout, array_merge($baseData, $data));
    }
}
