<?php

namespace Kanboard\Model;

use Kanboard\Core\Base;

/**
 * Custom Dashboard Model
 *
 * Provides aggregated statistics for the role-based analytics dashboard.
 * Uses existing Kanboard tables without schema modifications.
 *
 * PicoDb Database::execute() only supports positional ? parameters.
 * Date grouping (by day) is done in PHP to remain cross-DB compatible.
 *
 * Task status: tasks.is_active = 1 (open), 0 (closed)
 * Overdue:     open + date_due > 0 + date_due <= now()
 * In-progress: open tasks in columns whose title contains 'progress'
 *
 * @package Kanboard\Model
 */
class CustomDashboardModel extends Base
{
    // -------------------------------------------------------------------------
    // Helper: date filter
    // -------------------------------------------------------------------------

    /**
     * Parse a date_filter string into ['from'=>ts, 'to'=>ts].
     * Returns [0, 0] for 'all' (no restriction).
     */
    public function parseDateFilter($filter, $custom_from = 0, $custom_to = 0)
    {
        $today = mktime(0, 0, 0, (int) date('n'), (int) date('j'), (int) date('Y'));
        $eod   = $today + 86399;

        switch ($filter) {
            case 'today':
                return ['from' => $today, 'to' => $eod];
            case 'last7':
                return ['from' => $today - 6 * 86400, 'to' => $eod];
            case 'last30':
                return ['from' => $today - 29 * 86400, 'to' => $eod];
            case 'this_month':
                $from = mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));
                $to   = mktime(23, 59, 59, (int) date('n'), (int) date('t'), (int) date('Y'));
                return ['from' => $from, 'to' => $to];
            case 'prev_month':
                $year  = (int) date('Y');
                $month = (int) date('n') - 1;
                if ($month < 1) { $month = 12; $year--; }
                $days  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                return [
                    'from' => mktime(0, 0, 0, $month, 1, $year),
                    'to'   => mktime(23, 59, 59, $month, $days, $year),
                ];
            case 'custom':
                $from = $custom_from > 0 ? (int) $custom_from : ($today - 29 * 86400);
                $to   = $custom_to   > 0 ? (int) $custom_to   : $eod;
                return ['from' => $from, 'to' => max($from, $to)];
            default:
                return ['from' => 0, 'to' => 0];
        }
    }

    // =========================================================================
    // USER DASHBOARD
    // =========================================================================

    /**
     * Core stats for one user.
     */
    public function getUserStats($user_id)
    {
        $now       = time();
        $today_end = mktime(23, 59, 59, (int) date('n'), (int) date('j'), (int) date('Y'));

        // ISO 8601 day-of-week: 1=Mon … 7=Sun
        $dow      = (int) date('N');
        $week_end = $today_end + (7 - $dow) * 86400;

        $rq = $this->db->execute("
            SELECT
                COUNT(*) AS total_assigned,
                SUM(CASE WHEN t.is_active = 0 THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN t.is_active = 1 THEN 1 ELSE 0 END) AS open_tasks,
                SUM(CASE WHEN t.is_active = 1 AND t.date_due > 0 AND t.date_due <= ? THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN t.is_active = 1 AND t.date_due > 0 AND t.date_due >= ? AND t.date_due <= ? THEN 1 ELSE 0 END) AS due_today,
                SUM(CASE WHEN t.is_active = 1 AND t.date_due > 0 AND t.date_due <= ? THEN 1 ELSE 0 END) AS due_this_week
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            WHERE t.owner_id = ? AND p.is_active = 1
        ", [$now, $today_end - 86399, $today_end, $week_end, $user_id]);

        $result = ($rq !== false) ? $rq->fetch(\PDO::FETCH_ASSOC) : [];

        $ip = $this->db->execute("
            SELECT COUNT(*) AS cnt
            FROM tasks t
            INNER JOIN columns  c ON c.id  = t.column_id
            INNER JOIN projects p ON p.id  = t.project_id
            WHERE t.owner_id = ? AND t.is_active = 1 AND p.is_active = 1
              AND LOWER(c.title) LIKE '%progress%'
        ", [$user_id]);

        $in_progress = ($ip !== false) ? (int) $ip->fetchColumn() : 0;
        $open_tasks  = (int) ($result['open_tasks'] ?? 0);
        $pending     = max(0, $open_tasks - $in_progress);

        return [
            'total_assigned' => (int) ($result['total_assigned'] ?? 0),
            'completed'      => (int) ($result['completed']      ?? 0),
            'open'           => $open_tasks,
            'in_progress'    => $in_progress,
            'pending'        => $pending,
            'overdue'        => (int) ($result['overdue']        ?? 0),
            'due_today'      => (int) ($result['due_today']      ?? 0),
            'due_this_week'  => (int) ($result['due_this_week']  ?? 0),
        ];
    }

    /**
     * Tasks completed by the user per day over the last N days.
     * Date grouping happens in PHP for cross-DB compatibility.
     */
    public function getUserCompletionTrend($user_id, $days = 30)
    {
        $from = time() - ($days - 1) * 86400;
        $from = mktime(0, 0, 0, (int) date('n', $from), (int) date('j', $from), (int) date('Y', $from));

        $rq = $this->db->execute("
            SELECT date_completed
            FROM tasks
            WHERE owner_id = ? AND is_active = 0 AND date_completed >= ?
        ", [$user_id, $from]);

        $data = [];
        if ($rq !== false) {
            foreach ($rq->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $day = date('Y-m-d', (int) $row['date_completed']);
                $data[$day] = ($data[$day] ?? 0) + 1;
            }
        }

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $label    = date('Y-m-d', $from + $i * 86400);
            $result[] = ['day' => $label, 'count' => $data[$label] ?? 0];
        }

        return $result;
    }

    /**
     * Open tasks assigned to the user, ordered by due date (nulls last).
     */
    public function getUserAssignedTasks($user_id, array $dateRange = [], $limit = 20)
    {
        $params = [$user_id];
        $extra  = '';

        if (!empty($dateRange['from'])) {
            $extra   .= ' AND t.date_creation >= ?';
            $params[] = (int) $dateRange['from'];
        }
        if (!empty($dateRange['to'])) {
            $extra   .= ' AND t.date_creation <= ?';
            $params[] = (int) $dateRange['to'];
        }

        $params[] = (int) $limit;

        $rq = $this->db->execute("
            SELECT t.id, t.title, t.date_due, t.date_creation, t.is_active,
                   t.priority, t.color_id, t.owner_id,
                   c.title AS column_name,
                   p.name  AS project_name,
                   p.id    AS project_id
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            INNER JOIN columns  c ON c.id = t.column_id
            WHERE t.owner_id = ? AND t.is_active = 1 AND p.is_active = 1
            $extra
            ORDER BY
                CASE WHEN t.date_due = 0 OR t.date_due IS NULL THEN 1 ELSE 0 END,
                t.date_due ASC
            LIMIT ?
        ", $params);

        return ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * Recently completed tasks for the user.
     */
    public function getUserCompletedTasks($user_id, array $dateRange = [], $limit = 20)
    {
        $params = [$user_id];
        $extra  = '';

        if (!empty($dateRange['from'])) {
            $extra   .= ' AND t.date_completed >= ?';
            $params[] = (int) $dateRange['from'];
        }
        if (!empty($dateRange['to'])) {
            $extra   .= ' AND t.date_completed <= ?';
            $params[] = (int) $dateRange['to'];
        }

        $params[] = (int) $limit;

        $rq = $this->db->execute("
            SELECT t.id, t.title, t.date_due, t.date_completed, t.date_creation,
                   t.priority, t.color_id,
                   c.title AS column_name,
                   p.name  AS project_name,
                   p.id    AS project_id
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            INNER JOIN columns  c ON c.id = t.column_id
            WHERE t.owner_id = ? AND t.is_active = 0
            $extra
            ORDER BY t.date_completed DESC
            LIMIT ?
        ", $params);

        return ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * Overdue tasks for the user.
     */
    public function getUserOverdueTasks($user_id, $limit = 20)
    {
        $rq = $this->db->execute("
            SELECT t.id, t.title, t.date_due, t.priority, t.color_id,
                   c.title AS column_name,
                   p.name  AS project_name,
                   p.id    AS project_id
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            INNER JOIN columns  c ON c.id = t.column_id
            WHERE t.owner_id = ? AND t.is_active = 1
              AND t.date_due > 0 AND t.date_due <= ?
              AND p.is_active = 1
            ORDER BY t.date_due ASC
            LIMIT ?
        ", [$user_id, time(), $limit]);

        return ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * Recent activity events for the user (as creator).
     */
    public function getUserActivity($user_id, $limit = 30)
    {
        $rq = $this->db->execute("
            SELECT pa.event_name, pa.date_creation, pa.data,
                   t.title AS task_title, t.id AS task_id,
                   p.name  AS project_name, p.id AS project_id
            FROM project_activities pa
            INNER JOIN tasks    t ON t.id = pa.task_id
            INNER JOIN projects p ON p.id = pa.project_id
            WHERE pa.creator_id = ?
            ORDER BY pa.date_creation DESC
            LIMIT ?
        ", [$user_id, $limit]);

        return ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * Per-project task distribution for the current user.
     */
    public function getUserProjectDistribution($user_id)
    {
        $rq = $this->db->execute("
            SELECT p.name AS project_name, p.id AS project_id,
                   SUM(CASE WHEN t.is_active = 1 THEN 1 ELSE 0 END) AS open_tasks,
                   SUM(CASE WHEN t.is_active = 0 THEN 1 ELSE 0 END) AS closed_tasks,
                   COUNT(*) AS total
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            WHERE t.owner_id = ? AND p.is_active = 1
            GROUP BY p.id, p.name
            ORDER BY total DESC
        ", [$user_id]);

        return ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    // =========================================================================
    // ADMIN DASHBOARD
    // =========================================================================

    /**
     * Organisation-wide KPI counts.
     */
    public function getAdminOverallStats()
    {
        $now = time();

        $rqU = $this->db->execute("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_users
            FROM users
            WHERE role != 'app-public'
        ", []);
        $userRow = ($rqU !== false) ? $rqU->fetch(\PDO::FETCH_ASSOC) : [];

        $rqP = $this->db->execute("SELECT COUNT(*) AS total FROM projects WHERE is_active = 1", []);
        $projectRow = ($rqP !== false) ? $rqP->fetch(\PDO::FETCH_ASSOC) : [];

        $rqT = $this->db->execute("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN t.is_active = 0 THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN t.is_active = 1 THEN 1 ELSE 0 END) AS open_tasks,
                SUM(CASE WHEN t.is_active = 1 AND t.date_due > 0 AND t.date_due <= ? THEN 1 ELSE 0 END) AS overdue
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            WHERE p.is_active = 1
        ", [$now]);
        $taskRow = ($rqT !== false) ? $rqT->fetch(\PDO::FETCH_ASSOC) : [];

        $rqI = $this->db->execute("
            SELECT COUNT(*) AS cnt
            FROM tasks t
            INNER JOIN columns  c ON c.id = t.column_id
            INNER JOIN projects p ON p.id = t.project_id
            WHERE t.is_active = 1 AND p.is_active = 1
              AND LOWER(c.title) LIKE '%progress%'
        ", []);
        $in_progress = ($rqI !== false) ? (int) $rqI->fetchColumn() : 0;
        $pending     = max(0, (int) ($taskRow['open_tasks'] ?? 0) - $in_progress);

        return [
            'total_users'    => (int) ($userRow['total']        ?? 0),
            'active_users'   => (int) ($userRow['active_users'] ?? 0),
            'total_projects' => (int) ($projectRow['total']     ?? 0),
            'total_tasks'    => (int) ($taskRow['total']        ?? 0),
            'completed'      => (int) ($taskRow['completed']    ?? 0),
            'in_progress'    => $in_progress,
            'pending'        => $pending,
            'overdue'        => (int) ($taskRow['overdue']      ?? 0),
        ];
    }

    /**
     * Tasks completed per day over the last N days (all users).
     * Grouping done in PHP for DB portability.
     */
    public function getAdminCompletionTrend($days = 30)
    {
        $from = time() - ($days - 1) * 86400;
        $from = mktime(0, 0, 0, (int) date('n', $from), (int) date('j', $from), (int) date('Y', $from));

        $rq = $this->db->execute("
            SELECT date_completed
            FROM tasks
            WHERE is_active = 0 AND date_completed >= ?
        ", [$from]);

        $data = [];
        if ($rq !== false) {
            foreach ($rq->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $day = date('Y-m-d', (int) $row['date_completed']);
                $data[$day] = ($data[$day] ?? 0) + 1;
            }
        }

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $label    = date('Y-m-d', $from + $i * 86400);
            $result[] = ['day' => $label, 'count' => $data[$label] ?? 0];
        }

        return $result;
    }

    /**
     * Task status distribution (for donut chart).
     */
    public function getAdminTaskDistribution()
    {
        $stats = $this->getAdminOverallStats();
        return [
            'completed'   => $stats['completed'],
            'in_progress' => $stats['in_progress'],
            'pending'     => $stats['pending'],
            'overdue'     => $stats['overdue'],
        ];
    }

    /**
     * Task priority distribution across all active projects.
     */
    public function getAdminPriorityDistribution()
    {
        $rq = $this->db->execute("
            SELECT t.priority, COUNT(*) AS cnt
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            WHERE p.is_active = 1
            GROUP BY t.priority
            ORDER BY t.priority DESC
        ", []);

        return ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * Per-project task summary.
     */
    public function getProjectStats(array $dateRange = [])
    {
        $now    = time();
        $params = [$now];
        $extra  = '';

        if (!empty($dateRange['from'])) {
            $extra   .= ' AND t.date_creation >= ?';
            $params[] = (int) $dateRange['from'];
        }
        if (!empty($dateRange['to'])) {
            $extra   .= ' AND t.date_creation <= ?';
            $params[] = (int) $dateRange['to'];
        }

        $rq = $this->db->execute("
            SELECT p.id AS project_id, p.name AS project_name,
                   COUNT(t.id) AS total,
                   SUM(CASE WHEN t.is_active = 0 THEN 1 ELSE 0 END) AS completed,
                   SUM(CASE WHEN t.is_active = 1 THEN 1 ELSE 0 END) AS open_tasks,
                   SUM(CASE WHEN t.is_active = 1 AND t.date_due > 0 AND t.date_due <= ? THEN 1 ELSE 0 END) AS overdue
            FROM projects p
            LEFT JOIN tasks t ON t.project_id = p.id
            WHERE p.is_active = 1
            $extra
            GROUP BY p.id, p.name
            ORDER BY total DESC
        ", $params);

        $data = ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];

        foreach ($data as &$row) {
            $rqI = $this->db->execute("
                SELECT COUNT(*) AS cnt
                FROM tasks t
                INNER JOIN columns c ON c.id = t.column_id
                WHERE t.project_id = ? AND t.is_active = 1
                  AND LOWER(c.title) LIKE '%progress%'
            ", [(int) $row['project_id']]);

            $row['in_progress']     = ($rqI !== false) ? (int) $rqI->fetchColumn() : 0;
            $row['pending']         = max(0, (int) $row['open_tasks'] - $row['in_progress']);
            $row['completion_pct']  = (int) $row['total'] > 0
                ? round((int) $row['completed'] / (int) $row['total'] * 100)
                : 0;
        }
        unset($row);

        return $data;
    }

    /**
     * Per-employee productivity summary.
     */
    public function getEmployeeStats(array $dateRange = [])
    {
        $now    = time();
        $params = [$now];
        $taskWhere  = '';

        if (!empty($dateRange['from'])) {
            $taskWhere .= ' AND t.date_creation >= ?';
            $params[]   = (int) $dateRange['from'];
        }
        if (!empty($dateRange['to'])) {
            $taskWhere .= ' AND t.date_creation <= ?';
            $params[]   = (int) $dateRange['to'];
        }

        $rq = $this->db->execute("
            SELECT u.id AS user_id, u.username, u.name, u.email,
                   COUNT(t.id) AS total_assigned,
                   SUM(CASE WHEN t.is_active = 0 THEN 1 ELSE 0 END) AS completed,
                   SUM(CASE WHEN t.is_active = 1 THEN 1 ELSE 0 END) AS open_tasks,
                   SUM(CASE WHEN t.is_active = 1 AND t.date_due > 0 AND t.date_due <= ? THEN 1 ELSE 0 END) AS overdue,
                   MAX(pa.date_creation) AS last_activity_ts
            FROM users u
            LEFT JOIN tasks t ON t.owner_id = u.id
              AND t.project_id IN (SELECT id FROM projects WHERE is_active = 1)
              $taskWhere
            LEFT JOIN project_activities pa ON pa.creator_id = u.id
            WHERE u.is_active = 1
              AND u.role IN ('app-user', 'app-manager', 'app-admin')
            GROUP BY u.id, u.username, u.name, u.email
            ORDER BY completed DESC
        ", $params);

        $data = ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];

        foreach ($data as &$row) {
            $rqI = $this->db->execute("
                SELECT COUNT(*) AS cnt
                FROM tasks t
                INNER JOIN columns c ON c.id = t.column_id
                WHERE t.owner_id = ? AND t.is_active = 1
                  AND LOWER(c.title) LIKE '%progress%'
            ", [(int) $row['user_id']]);

            $row['in_progress']    = ($rqI !== false) ? (int) $rqI->fetchColumn() : 0;
            $row['pending']        = max(0, (int) $row['open_tasks'] - $row['in_progress']);
            $total                 = (int) $row['total_assigned'];
            $row['completion_pct'] = $total > 0
                ? round((int) $row['completed'] / $total * 100)
                : 0;
        }
        unset($row);

        return $data;
    }

    /**
     * Full stats bundle for a single employee (admin detail view).
     */
    public function getSingleEmployeeStats($target_user_id, array $dateRange = [])
    {
        $stats     = $this->getUserStats($target_user_id);
        $trend30   = $this->getUserCompletionTrend($target_user_id, 30);
        $trend7    = $this->getUserCompletionTrend($target_user_id, 7);
        $projects  = $this->getUserProjectDistribution($target_user_id);
        $activity  = $this->getUserActivity($target_user_id, 30);
        $overdue   = $this->getUserOverdueTasks($target_user_id, 20);
        $open      = $this->getUserAssignedTasks($target_user_id, $dateRange, 50);
        $completed = $this->getUserCompletedTasks($target_user_id, $dateRange, 20);

        return compact('stats', 'trend30', 'trend7', 'projects', 'activity', 'overdue', 'open', 'completed');
    }

    /**
     * Workload distribution: open task count per active user.
     */
    public function getWorkloadDistribution()
    {
        $rq = $this->db->execute("
            SELECT u.id AS user_id, u.username, u.name,
                   COUNT(t.id) AS open_tasks,
                   SUM(CASE WHEN t.date_due > 0 AND t.date_due <= ? THEN 1 ELSE 0 END) AS overdue_tasks
            FROM users u
            LEFT JOIN tasks t ON t.owner_id = u.id AND t.is_active = 1
              AND t.project_id IN (SELECT id FROM projects WHERE is_active = 1)
            WHERE u.is_active = 1
              AND u.role IN ('app-user', 'app-manager', 'app-admin')
            GROUP BY u.id, u.username, u.name
            ORDER BY open_tasks DESC
        ", [time()]);

        return ($rq !== false) ? $rq->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * Fetch a user's public (non-sensitive) profile record.
     */
    public function getUserPublicProfile($user_id)
    {
        $rq = $this->db->execute("
            SELECT id, username, name, email, role, is_active, avatar_path
            FROM users WHERE id = ?
        ", [(int) $user_id]);

        return ($rq !== false) ? $rq->fetch(\PDO::FETCH_ASSOC) : null;
    }
}
