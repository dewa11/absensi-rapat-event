<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\AttendanceModel;
use flight\Engine;

class ExportController extends BaseController
{
    private const ALLOWED_EVENT_TYPES = ['Rapat', 'Event'];

    private AttendanceModel $attendanceModel;

    public function __construct(Engine $app, AttendanceModel $attendanceModel)
    {
        parent::__construct($app);
        $this->attendanceModel = $attendanceModel;
    }

    public function exportCsv(): void
    {
        $filters = $this->parseFilters();
        $rows = $this->attendanceModel->getExportData($filters);

        $filename = 'export-kegiatan-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            return;
        }

        // UTF-8 BOM for proper Excel rendering
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'No',
            'Tipe Kegiatan',
            'Judul Kegiatan',
            'Tanggal',
            'Waktu',
            'Lokasi',
            'Catatan Kegiatan',
            'Nama Peserta',
            'NIP',
            'Unit',
            'Status Kehadiran',
            'Waktu Hadir',
            'Terlambat',
        ]);

        $rowNum = 1;
        foreach ($rows as $row) {
            $hasAttendee = !empty($row['participant_name']);
            fputcsv($out, [
                $rowNum++,
                (string) ($row['event_type'] ?? ''),
                (string) ($row['event_title'] ?? ''),
                (string) ($row['event_date'] ?? ''),
                (string) ($row['event_time'] ?? ''),
                (string) ($row['location'] ?? ''),
                (string) ($row['event_notes'] ?? ''),
                $hasAttendee ? (string) ($row['participant_name'] ?? '') : '',
                $hasAttendee ? (string) ($row['nip'] ?? '') : '',
                $hasAttendee ? (string) ($row['unit_name'] ?? '') : '',
                $hasAttendee ? (string) ($row['attendance_status'] ?? '') : '',
                $hasAttendee ? (string) ($row['present_at'] ?? '') : '',
                $hasAttendee ? ((int) ($row['is_late'] ?? 0) === 1 ? 'Ya' : 'Tidak') : '',
            ]);
        }

        fclose($out);
        exit;
    }

    public function exportPdf(): void
    {
        $filters = $this->parseFilters();
        $rows = $this->attendanceModel->getExportData($filters);

        // Group rows by event_id
        $groupedEvents = [];
        foreach ($rows as $row) {
            $eventId = (int) ($row['event_id'] ?? 0);
            if (!isset($groupedEvents[$eventId])) {
                $groupedEvents[$eventId] = [
                    'info' => [
                        'event_id'    => $eventId,
                        'event_type'  => (string) ($row['event_type'] ?? ''),
                        'event_title' => (string) ($row['event_title'] ?? ''),
                        'event_date'  => (string) ($row['event_date'] ?? ''),
                        'event_time'  => (string) ($row['event_time'] ?? ''),
                        'location'    => (string) ($row['location'] ?? ''),
                        'event_notes' => (string) ($row['event_notes'] ?? ''),
                    ],
                    'attendees' => [],
                ];
            }

            if (!empty($row['participant_name'])) {
                $groupedEvents[$eventId]['attendees'][] = [
                    'participant_name'  => (string) ($row['participant_name'] ?? ''),
                    'nip'              => (string) ($row['nip'] ?? ''),
                    'unit_name'        => (string) ($row['unit_name'] ?? ''),
                    'attendance_status' => (string) ($row['attendance_status'] ?? ''),
                    'present_at'       => (string) ($row['present_at'] ?? ''),
                    'is_late'          => (int) ($row['is_late'] ?? 0),
                ];
            }
        }

        $this->render('attendance/export_pdf', [
            'title'         => 'Ekspor Data Kegiatan',
            'groupedEvents' => $groupedEvents,
            'filters'       => $filters,
        ], 'layouts/print');
    }

    private function parseFilters(): array
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

        return [
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'event_type' => $eventType,
            'keyword'    => $keyword,
        ];
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
