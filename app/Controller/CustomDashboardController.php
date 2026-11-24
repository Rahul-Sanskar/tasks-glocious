<?php

namespace Kanboard\Controller;

use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Core\Controller\PageNotFoundException;
use Kanboard\Core\Security\Role;

/**
 * Custom Dashboard Controller
 *
 * Routes:
 *   GET  /analytics-dashboard          → show()       user dashboard (own user_id)
 *   GET  /analytics-dashboard/admin    → adminShow()  admin org dashboard
 *   GET  /analytics-dashboard/employee/:user_id → employeeDetail()
 *   GET  /analytics-dashboard/data/user         → userData()   JSON
 *   GET  /analytics-dashboard/data/admin        → adminData()  JSON
 *   GET  /analytics-dashboard/data/employee/:user_id → employeeData() JSON
 *
 * @package Kanboard\Controller
 */
class CustomDashboardController extends BaseController
{
    // -------------------------------------------------------------------------
    // Helper: resolve date filter from request
    // -------------------------------------------------------------------------
    private function getDateRange()
    {
        $filter      = $this->request->getStringParam('date_filter', 'last30');
        $custom_from = (int) $this->request->getStringParam('custom_from', 0);
        $custom_to   = (int) $this->request->getStringParam('custom_to', 0);

        // Parse custom date strings (YYYY-MM-DD) to timestamps if supplied as strings
        $custom_from_str = $this->request->getStringParam('custom_from_str', '');
        $custom_to_str   = $this->request->getStringParam('custom_to_str', '');
        if ($custom_from === 0 && $custom_from_str !== '') {
            $custom_from = (int) strtotime($custom_from_str . ' 00:00:00');
        }
        if ($custom_to === 0 && $custom_to_str !== '') {
            $custom_to = (int) strtotime($custom_to_str . ' 23:59:59');
        }

        return [
            'filter'      => $filter,
            'dateRange'   => $this->customDashboardModel->parseDateFilter($filter, $custom_from, $custom_to),
            'custom_from' => $custom_from,
            'custom_to'   => $custom_to,
        ];
    }

    // -------------------------------------------------------------------------
    // USER DASHBOARD
    // -------------------------------------------------------------------------

    /**
     * User analytics dashboard — only visible to the logged-in user themselves.
     */
    public function show()
    {
        $current_user_id = $this->userSession->getId();
        $user            = $this->userModel->getById($current_user_id);

        if (empty($user)) {
            throw new PageNotFoundException();
        }

        $dateInfo  = $this->getDateRange();
        $stats     = $this->customDashboardModel->getUserStats($current_user_id);
        $trend30   = $this->customDashboardModel->getUserCompletionTrend($current_user_id, 30);
        $trend7    = $this->customDashboardModel->getUserCompletionTrend($current_user_id, 7);
        $projects  = $this->customDashboardModel->getUserProjectDistribution($current_user_id);
        $overdue   = $this->customDashboardModel->getUserOverdueTasks($current_user_id);
        $open      = $this->customDashboardModel->getUserAssignedTasks($current_user_id, $dateInfo['dateRange']);
        $completed = $this->customDashboardModel->getUserCompletedTasks($current_user_id, $dateInfo['dateRange']);
        $activity  = $this->customDashboardModel->getUserActivity($current_user_id);

        $this->response->html($this->helper->layout->app('custom_dashboard/user_dashboard', [
            'title'       => t('My Analytics Dashboard'),
            'user'        => $user,
            'stats'       => $stats,
            'trend30'     => $trend30,
            'trend7'      => $trend7,
            'projects'    => $projects,
            'overdue'     => $overdue,
            'open_tasks'  => $open,
            'completed_tasks' => $completed,
            'activity'    => $activity,
            'date_filter' => $dateInfo['filter'],
            'custom_from' => $dateInfo['custom_from'],
            'custom_to'   => $dateInfo['custom_to'],
        ]));
    }

    // -------------------------------------------------------------------------
    // ADMIN DASHBOARD
    // -------------------------------------------------------------------------

    /**
     * Admin organisation-wide analytics dashboard.
     * Requires APP_ADMIN role.
     */
    public function adminShow()
    {
        if (! $this->userSession->isAdmin()) {
            throw new AccessForbiddenException(t('Access Forbidden'));
        }

        $dateInfo    = $this->getDateRange();
        $stats       = $this->customDashboardModel->getAdminOverallStats();
        $trend30     = $this->customDashboardModel->getAdminCompletionTrend(30);
        $trend7      = $this->customDashboardModel->getAdminCompletionTrend(7);
        $taskDist    = $this->customDashboardModel->getAdminTaskDistribution();
        $priorityDist = $this->customDashboardModel->getAdminPriorityDistribution();
        $employees   = $this->customDashboardModel->getEmployeeStats($dateInfo['dateRange']);
        $projectStats = $this->customDashboardModel->getProjectStats($dateInfo['dateRange']);
        $workload    = $this->customDashboardModel->getWorkloadDistribution();

        $this->response->html($this->helper->layout->app('custom_dashboard/admin_dashboard', [
            'title'         => t('Admin Analytics Dashboard'),
            'stats'         => $stats,
            'trend30'       => $trend30,
            'trend7'        => $trend7,
            'task_dist'     => $taskDist,
            'priority_dist' => $priorityDist,
            'employees'     => $employees,
            'project_stats' => $projectStats,
            'workload'      => $workload,
            'date_filter'   => $dateInfo['filter'],
            'custom_from'   => $dateInfo['custom_from'],
            'custom_to'     => $dateInfo['custom_to'],
        ]));
    }

    // -------------------------------------------------------------------------
    // EMPLOYEE DETAIL (admin only)
    // -------------------------------------------------------------------------

    /**
     * Individual employee performance detail view.
     * Only admins may access this; they can inspect any user.
     */
    public function employeeDetail()
    {
        if (! $this->userSession->isAdmin()) {
            throw new AccessForbiddenException(t('Access Forbidden'));
        }

        $target_user_id = $this->request->getIntegerParam('user_id');
        $target_user    = $this->customDashboardModel->getUserPublicProfile($target_user_id);

        if (empty($target_user)) {
            throw new PageNotFoundException();
        }

        $dateInfo = $this->getDateRange();
        $data     = $this->customDashboardModel->getSingleEmployeeStats($target_user_id, $dateInfo['dateRange']);

        $this->response->html($this->helper->layout->app('custom_dashboard/employee_detail', [
            'title'       => t('Employee Performance: %s', $target_user['name'] ?: $target_user['username']),
            'target_user' => $target_user,
            'stats'       => $data['stats'],
            'trend30'     => $data['trend30'],
            'trend7'      => $data['trend7'],
            'projects'    => $data['projects'],
            'activity'    => $data['activity'],
            'overdue'     => $data['overdue'],
            'open_tasks'  => $data['open'],
            'completed_tasks' => $data['completed'],
            'date_filter' => $dateInfo['filter'],
            'custom_from' => $dateInfo['custom_from'],
            'custom_to'   => $dateInfo['custom_to'],
        ]));
    }

    // -------------------------------------------------------------------------
    // JSON DATA ENDPOINTS (for AJAX refresh on date-filter change)
    // -------------------------------------------------------------------------

    /**
     * JSON: user dashboard data for the current user.
     */
    public function userData()
    {
        $current_user_id = $this->userSession->getId();
        $dateInfo        = $this->getDateRange();

        $payload = [
            'stats'    => $this->customDashboardModel->getUserStats($current_user_id),
            'trend30'  => $this->customDashboardModel->getUserCompletionTrend($current_user_id, 30),
            'trend7'   => $this->customDashboardModel->getUserCompletionTrend($current_user_id, 7),
            'projects' => $this->customDashboardModel->getUserProjectDistribution($current_user_id),
        ];

        $this->response->json($payload);
    }

    /**
     * JSON: admin dashboard aggregate data.
     */
    public function adminData()
    {
        if (! $this->userSession->isAdmin()) {
            $this->response->json(['error' => 'Forbidden'], 403);
            return;
        }

        $dateInfo = $this->getDateRange();

        $payload = [
            'stats'        => $this->customDashboardModel->getAdminOverallStats(),
            'trend30'      => $this->customDashboardModel->getAdminCompletionTrend(30),
            'trend7'       => $this->customDashboardModel->getAdminCompletionTrend(7),
            'task_dist'    => $this->customDashboardModel->getAdminTaskDistribution(),
            'project_stats'=> $this->customDashboardModel->getProjectStats($dateInfo['dateRange']),
            'employees'    => $this->customDashboardModel->getEmployeeStats($dateInfo['dateRange']),
            'workload'     => $this->customDashboardModel->getWorkloadDistribution(),
        ];

        $this->response->json($payload);
    }

    /**
     * JSON: single employee data (admin only).
     */
    public function employeeData()
    {
        if (! $this->userSession->isAdmin()) {
            $this->response->json(['error' => 'Forbidden'], 403);
            return;
        }

        $target_user_id = $this->request->getIntegerParam('user_id');
        $dateInfo       = $this->getDateRange();
        $data           = $this->customDashboardModel->getSingleEmployeeStats($target_user_id, $dateInfo['dateRange']);

        $this->response->json($data);
    }
}
