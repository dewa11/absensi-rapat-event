<?php

declare(strict_types=1);

namespace app\models;

use flight\database\PdoWrapper;

class AttendanceModel
{
    private const PUBLIC_TOKEN_PATTERN = '/^[a-f0-9]{32}$/';

    private PdoWrapper $db;
    private bool $publicAttendanceSchemaChecked = false;

    public function __construct(PdoWrapper $db)
    {
        $this->db = $db;
        $this->ensurePublicAttendanceSchema();
    }

    public function createAttendance(array $payload, int $adminId): void
    {
        $this->db->runQuery(
            'INSERT INTO events (event_type, title, event_date, event_time, location, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $payload['event_type'],
                $payload['title'],
                $payload['event_date'],
                $payload['event_time'],
                $payload['location'],
                $payload['notes'],
                $adminId,
            ]
        );
    }

    public function getDashboardSummary(): array
    {
        $events = (int) $this->db->runQuery('SELECT COUNT(*) AS total FROM events')->fetch()['total'];
        $attendances = (int) $this->db->runQuery('SELECT COUNT(*) AS total FROM attendances')->fetch()['total'];
        $today = (int) $this->db->runQuery('SELECT COUNT(*) AS total FROM attendances WHERE DATE(created_at) = CURDATE()')->fetch()['total'];
        $upcomingSevenDays = (int) $this->db->runQuery(
            'SELECT COUNT(*) AS total
             FROM events
             WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)'
        )->fetch()['total'];
        $completedEvents = (int) $this->db->runQuery(
            'SELECT COUNT(*) AS total
             FROM events
             WHERE event_date < CURDATE()'
        )->fetch()['total'];
        $avgAttendees = (float) $this->db->runQuery(
            'SELECT COALESCE(AVG(attendee_total), 0) AS avg_total
             FROM (
                SELECT e.id, COUNT(a.id) AS attendee_total
                FROM events e
                LEFT JOIN attendances a ON a.event_id = e.id
                GROUP BY e.id
             ) AS event_attendance'
        )->fetch()['avg_total'];

        return [
            'events_total' => $events,
            'attendances_total' => $attendances,
            'today_total' => $today,
            'upcoming_7d_total' => $upcomingSevenDays,
            'completed_events_total' => $completedEvents,
            'avg_attendees_per_event' => round($avgAttendees, 1),
        ];
    }

    public function getDashboardUpcomingEvents(int $limit = 5): array
    {
        $safeLimit = max(1, min($limit, 10));
        $stmt = $this->db->runQuery(
            "SELECT e.id, e.event_type, e.title, e.event_date, e.event_time, e.location,
                    COUNT(a.id) AS attendee_total
             FROM events e
             LEFT JOIN attendances a ON a.event_id = e.id
             WHERE e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             GROUP BY e.id, e.event_type, e.title, e.event_date, e.event_time, e.location
             ORDER BY e.event_date ASC, e.event_time ASC
             LIMIT {$safeLimit}"
        );

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function getDashboardLatestSubmissions(int $limit = 5): array
    {
        $safeLimit = max(1, min($limit, 10));
        $stmt = $this->db->runQuery(
            "SELECT a.id, a.participant_name, a.nip, a.unit_name, a.present_at,
                    e.id AS event_id, e.title AS event_title, e.event_date, e.event_time,
                    CASE WHEN a.present_at > TIMESTAMP(e.event_date, e.event_time) THEN 1 ELSE 0 END AS is_late
             FROM attendances a
             INNER JOIN events e ON e.id = a.event_id
             ORDER BY a.present_at DESC, a.created_at DESC
             LIMIT {$safeLimit}"
        );

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function listAttendances(array $filters): array
    {
        $sql = 'SELECT e.id, e.event_type, e.title, e.event_date, e.event_time, e.location, e.notes,
                       e.attendance_token, e.token_generated_at, e.token_is_active, e.created_at,
                       COUNT(a.id) AS attendee_total
                FROM events e
                LEFT JOIN attendances a ON a.event_id = e.id';

        $params = [
            $filters['from_date'],
            $filters['to_date'],
        ];
        $where = ['e.event_date BETWEEN ? AND ?'];

        if ($filters['event_type'] !== '') {
            $where[] = 'e.event_type = ?';
            $params[] = $filters['event_type'];
        }

        if ($filters['keyword'] !== '') {
            $where[] = '(e.title LIKE ? OR e.location LIKE ? OR e.notes LIKE ?)';
            $like = '%' . $filters['keyword'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' GROUP BY e.id, e.event_type, e.title, e.event_date, e.event_time, e.location, e.notes,
                           e.attendance_token, e.token_generated_at, e.token_is_active, e.created_at';
        $sql .= ' ORDER BY e.event_date DESC, e.event_time DESC, e.created_at DESC';

        $stmt = $this->db->runQuery($sql, $params);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    public function getEventById(int $eventId): ?array
    {
        $stmt = $this->db->runQuery(
            'SELECT e.id, e.event_type, e.title, e.event_date, e.event_time, e.location, e.notes,
                    e.attendance_token, e.token_generated_at, e.token_is_active, e.created_at,
                    COUNT(a.id) AS attendee_total,
                    SUM(CASE WHEN a.present_at > TIMESTAMP(e.event_date, e.event_time) THEN 1 ELSE 0 END) AS late_total
             FROM events e
             LEFT JOIN attendances a ON a.event_id = e.id
             WHERE e.id = ?
             GROUP BY e.id, e.event_type, e.title, e.event_date, e.event_time, e.location, e.notes,
                      e.attendance_token, e.token_generated_at, e.token_is_active, e.created_at',
            [$eventId]
        );

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function getEventByToken(string $token): ?array
    {
        if (!preg_match(self::PUBLIC_TOKEN_PATTERN, $token)) {
            return null;
        }

        $stmt = $this->db->runQuery(
            'SELECT id, event_type, title, event_date, event_time, location, notes, attendance_token, token_is_active
             FROM events
             WHERE attendance_token = ? AND token_is_active = 1
             LIMIT 1',
            [$token]
        );

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function getEventAttendances(int $eventId): array
    {
        $stmt = $this->db->runQuery(
            'SELECT a.id, a.participant_name, a.nip, a.unit_name, a.selfie_path, a.attendance_status, a.present_at, a.notes,
                    CASE WHEN a.present_at > TIMESTAMP(e.event_date, e.event_time) THEN 1 ELSE 0 END AS is_late
             FROM attendances a
             INNER JOIN events e ON e.id = a.event_id
             WHERE a.event_id = ?
             ORDER BY a.present_at DESC, a.created_at DESC',
            [$eventId]
        );

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function eventExists(int $eventId): bool
    {
        $stmt = $this->db->runQuery('SELECT id FROM events WHERE id = ? LIMIT 1', [$eventId]);
        $row = $stmt->fetch();

        return is_array($row);
    }

    public function saveEventToken(int $eventId, string $token): void
    {
        $this->db->runQuery(
            'UPDATE events SET attendance_token = ?, token_generated_at = NOW(), token_is_active = 1 WHERE id = ?',
            [$token, $eventId]
        );
    }

    public function createPublicAttendance(int $eventId, string $name, string $nip, ?string $unit, string $selfiePath): void
    {
        $this->db->runQuery(
            'INSERT INTO attendances (event_id, participant_name, nip, unit_name, selfie_path, attendance_status, present_at, notes)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)',
            [$eventId, $name, $nip, $unit, $selfiePath, 'Hadir', null]
        );
    }

    public function getExportData(array $filters): array
    {
        $sql = 'SELECT e.id AS event_id, e.event_type, e.title AS event_title,
                       e.event_date, e.event_time, e.location, e.notes AS event_notes,
                       a.participant_name, a.nip, a.unit_name, a.attendance_status, a.present_at,
                       CASE WHEN a.present_at > TIMESTAMP(e.event_date, e.event_time) THEN 1 ELSE 0 END AS is_late
                FROM events e
                LEFT JOIN attendances a ON a.event_id = e.id';

        $params = [
            $filters['from_date'],
            $filters['to_date'],
        ];
        $where = ['e.event_date BETWEEN ? AND ?'];

        if ($filters['event_type'] !== '') {
            $where[] = 'e.event_type = ?';
            $params[] = $filters['event_type'];
        }

        if ($filters['keyword'] !== '') {
            $where[] = '(e.title LIKE ? OR e.location LIKE ? OR e.notes LIKE ?)';
            $like = '%' . $filters['keyword'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY e.event_date DESC, e.event_time DESC, e.id DESC, a.present_at ASC';

        $stmt = $this->db->runQuery($sql, $params);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    private function ensurePublicAttendanceSchema(): void
    {
        if ($this->publicAttendanceSchemaChecked) {
            return;
        }

        $this->publicAttendanceSchemaChecked = true;

        $missingColumns = [];
        foreach (['nip', 'selfie_path'] as $column) {
            if (!$this->columnExists('attendances', $column)) {
                if ($column === 'nip') {
                    $missingColumns[] = 'ADD COLUMN nip VARCHAR(32) NOT NULL AFTER participant_name';
                } elseif ($column === 'selfie_path') {
                    $missingColumns[] = 'ADD COLUMN selfie_path VARCHAR(255) NULL AFTER unit_name';
                }
            }
        }

        if ($missingColumns === []) {
            return;
        }

        $this->db->runQuery('ALTER TABLE attendances ' . implode(', ', $missingColumns));
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $stmt = $this->db->runQuery(
            'SELECT COUNT(*) AS total
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND column_name = ?',
            [$tableName, $columnName]
        );

        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0) > 0;
    }
}
