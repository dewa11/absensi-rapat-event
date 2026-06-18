<?php

declare(strict_types=1);

namespace app\controllers;

use app\helpers\SessionHelper;
use app\models\AttendanceModel;
use flight\Engine;

class DashboardController extends BaseController
{
    private AttendanceModel $attendanceModel;

    public function __construct(Engine $app, AttendanceModel $attendanceModel)
    {
        parent::__construct($app);
        $this->attendanceModel = $attendanceModel;
    }

    public function index(): void
    {
        $this->render('dashboard/index', [
            'title' => 'Dashboard Admin',
            'activeMenu' => 'dashboard',
            'summary' => $this->attendanceModel->getDashboardSummary(),
            'upcomingEvents' => $this->attendanceModel->getDashboardUpcomingEvents(),
            'latestSubmissions' => $this->attendanceModel->getDashboardLatestSubmissions(),
            'success' => SessionHelper::flash('success'),
        ]);
    }
}
