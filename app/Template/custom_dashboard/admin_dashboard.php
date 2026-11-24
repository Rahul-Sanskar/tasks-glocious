<?php
/**
 * Admin Analytics Dashboard
 *
 * Template variables (from CustomDashboardController::adminShow):
 *   $stats, $trend30, $trend7, $task_dist, $priority_dist,
 *   $employees, $project_stats, $workload,
 *   $date_filter, $custom_from, $custom_to
 */
echo $this->asset->css('assets/css/custom_dashboard.css');
echo $this->asset->js('assets/js/custom_dashboard.js');
?>

$pct_global = $stats['total_tasks'] > 0
    ? round($stats['completed'] / $stats['total_tasks'] * 100)
    : 0;

$data_url = $this->url->href('CustomDashboardController', 'adminData', [
    'date_filter' => $date_filter,
    'custom_from' => $custom_from,
    'custom_to'   => $custom_to,
]);
$page_url = $this->url->href('CustomDashboardController', 'adminShow');

$prio_labels = [-1 => t('Very Low'), 0 => t('Normal'), 1 => t('Low'), 2 => t('Medium'), 3 => t('High')];

$kdb_cards = [
    ['label' => t('Total Users'),     'value' => $stats['total_users'],    'icon' => 'users',          'class' => 'card-blue',   'data_key' => 'total_users'],
    ['label' => t('Active Users'),    'value' => $stats['active_users'],   'icon' => 'user',           'class' => 'card-teal',   'data_key' => 'active_users'],
    ['label' => t('Projects'),        'value' => $stats['total_projects'], 'icon' => 'folder',         'class' => 'card-blue',   'data_key' => 'total_projects'],
    ['label' => t('Total Tasks'),     'value' => $stats['total_tasks'],    'icon' => 'tasks',          'class' => 'card-grey',   'data_key' => 'total_tasks'],
    ['label' => t('Completed'),       'value' => $stats['completed'],      'icon' => 'check-circle',   'class' => 'card-green',  'data_key' => 'completed'],
    ['label' => t('In Progress'),     'value' => $stats['in_progress'],    'icon' => 'spinner',        'class' => 'card-teal',   'data_key' => 'in_progress'],
    ['label' => t('Pending'),         'value' => $stats['pending'],        'icon' => 'clock-o',        'class' => 'card-grey',   'data_key' => 'pending'],
    ['label' => t('Overdue'),         'value' => $stats['overdue'],        'icon' => 'exclamation-circle','class' => 'card-red', 'data_key' => 'overdue'],
];
?>

<div class="kdb-wrapper" id="kdb-admin-dashboard"
     data-role="admin">

    <!-- Page header -->
    <div class="kdb-page-header">
        <div class="kdb-page-header-left">
            <h1 class="kdb-page-title">
                <i class="fa fa-line-chart"></i>
                <?= t('Admin Analytics Dashboard') ?>
            </h1>
            <p class="kdb-page-subtitle">
                <?= t('Organisation-wide performance overview') ?>
                &nbsp;&mdash;&nbsp;<?= t('Overall completion: %d%%', $pct_global) ?>
            </p>
        </div>
        <div class="kdb-page-header-right">
            <a href="<?= $this->url->href('CustomDashboardController', 'show') ?>" class="btn kdb-btn-outline">
                <i class="fa fa-bar-chart"></i> <?= t('My Dashboard') ?>
            </a>
        </div>
    </div>

    <!-- Date filter -->
    <?= $this->render('custom_dashboard/date_filter', [
        'date_filter' => $date_filter,
        'custom_from' => $custom_from,
        'custom_to'   => $custom_to,
        'data_url'    => $data_url,
        'page_url'    => $page_url,
    ]) ?>

    <!-- KPI cards -->
    <?= $this->render('custom_dashboard/stat_cards', ['kdb_cards' => $kdb_cards]) ?>

    <!-- Charts row -->
    <div class="kdb-charts-row">

        <!-- Task distribution donut -->
        <div class="kdb-panel kdb-panel-sm">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-pie-chart"></i> <?= t('Task Distribution') ?></h3>
            </div>
            <div class="kdb-panel-body kdb-chart-center">
                <div id="kdb-admin-donut" class="kdb-chart-donut"
                     data-completed="<?= $task_dist['completed'] ?>"
                     data-in-progress="<?= $task_dist['in_progress'] ?>"
                     data-pending="<?= $task_dist['pending'] ?>"
                     data-overdue="<?= $task_dist['overdue'] ?>">
                </div>
                <div class="kdb-donut-legend">
                    <span class="kdb-legend-item kdb-color-green"><i class="fa fa-circle"></i> <?= t('Done') ?> (<?= $task_dist['completed'] ?>)</span>
                    <span class="kdb-legend-item kdb-color-blue"><i class="fa fa-circle"></i> <?= t('In Progress') ?> (<?= $task_dist['in_progress'] ?>)</span>
                    <span class="kdb-legend-item kdb-color-grey"><i class="fa fa-circle"></i> <?= t('Pending') ?> (<?= $task_dist['pending'] ?>)</span>
                    <span class="kdb-legend-item kdb-color-red"><i class="fa fa-circle"></i> <?= t('Overdue') ?> (<?= $task_dist['overdue'] ?>)</span>
                </div>
            </div>
        </div>

        <!-- 30-day trend -->
        <div class="kdb-panel kdb-panel-lg">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-line-chart"></i> <?= t('30-Day Completion Trend') ?></h3>
            </div>
            <div class="kdb-panel-body">
                <div id="kdb-admin-trend30" class="kdb-chart-bar"
                     data-trend="<?= htmlspecialchars(json_encode($trend30), ENT_QUOTES) ?>">
                </div>
            </div>
        </div>

        <!-- Priority distribution -->
        <div class="kdb-panel kdb-panel-md">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-flag"></i> <?= t('Priority Distribution') ?></h3>
            </div>
            <div class="kdb-panel-body">
                <?php if (empty($priority_dist)): ?>
                    <p class="kdb-empty"><?= t('No data available.') ?></p>
                <?php else: ?>
                    <div id="kdb-priority-chart" class="kdb-chart-bar-h"
                         data-priorities="<?= htmlspecialchars(json_encode($priority_dist), ENT_QUOTES) ?>"
                         data-labels="<?= htmlspecialchars(json_encode($prio_labels), ENT_QUOTES) ?>">
                    </div>
                <?php endif ?>
            </div>
        </div>

    </div>

    <!-- Project monitoring table -->
    <div class="kdb-panel">
        <div class="kdb-panel-header kdb-header-collapsible" data-target="kdb-project-table">
            <h3><i class="fa fa-folder-open"></i> <?= t('Project Monitoring') ?></h3>
            <span class="kdb-toggle-icon"><i class="fa fa-chevron-down"></i></span>
        </div>
        <div class="kdb-panel-body" id="kdb-project-table">
            <?php if (empty($project_stats)): ?>
                <p class="kdb-empty"><?= t('No projects found.') ?></p>
            <?php else: ?>

                <div class="kdb-projects-chart-wrap">
                    <div id="kdb-projects-bar" class="kdb-chart-bar-h kdb-chart-tall"
                         data-projects="<?= htmlspecialchars(json_encode($project_stats), ENT_QUOTES) ?>">
                    </div>
                </div>

                <table class="kdb-table kdb-table-sortable" id="kdb-projects-tbl">
                    <thead>
                        <tr>
                            <th data-col="project_name"><?= t('Project') ?> <i class="fa fa-sort"></i></th>
                            <th data-col="total"><?= t('Total') ?> <i class="fa fa-sort"></i></th>
                            <th data-col="completed"><?= t('Done') ?> <i class="fa fa-sort"></i></th>
                            <th data-col="in_progress"><?= t('In Progress') ?> <i class="fa fa-sort"></i></th>
                            <th data-col="pending"><?= t('Pending') ?> <i class="fa fa-sort"></i></th>
                            <th data-col="overdue"><?= t('Overdue') ?> <i class="fa fa-sort"></i></th>
                            <th data-col="completion_pct"><?= t('Progress') ?> <i class="fa fa-sort"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($project_stats as $ps): ?>
                        <tr data-project-id="<?= (int) $ps['project_id'] ?>">
                            <td>
                                <a href="<?= $this->url->href('BoardViewController', 'show', ['project_id' => $ps['project_id']]) ?>">
                                    <?= $this->text->e($ps['project_name']) ?>
                                </a>
                            </td>
                            <td><?= (int) $ps['total'] ?></td>
                            <td><span class="kdb-num-green"><?= (int) $ps['completed'] ?></span></td>
                            <td><span class="kdb-num-blue"><?= (int) $ps['in_progress'] ?></span></td>
                            <td><?= (int) $ps['pending'] ?></td>
                            <td><span class="kdb-num-red"><?= (int) $ps['overdue'] ?></span></td>
                            <td>
                                <div class="kdb-progress-bar">
                                    <div class="kdb-progress-fill kdb-color-green-bg"
                                         style="width:<?= (int) $ps['completion_pct'] ?>%"></div>
                                </div>
                                <span class="kdb-pct-label"><?= (int) $ps['completion_pct'] ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
        </div>
    </div>

    <!-- Employee productivity table -->
    <div class="kdb-panel">
        <div class="kdb-panel-header kdb-header-collapsible" data-target="kdb-employee-table-wrap">
            <h3><i class="fa fa-users"></i> <?= t('Employee Productivity') ?></h3>
            <span class="kdb-toggle-icon"><i class="fa fa-chevron-down"></i></span>
        </div>
        <div class="kdb-panel-body" id="kdb-employee-table-wrap">
            <div class="kdb-table-toolbar">
                <input type="text" class="kdb-search-input" id="kdb-emp-search"
                       placeholder="<?= t('Search by name or username…') ?>">
                <div class="kdb-sort-buttons">
                    <span><?= t('Sort by:') ?></span>
                    <button class="kdb-sort-btn active" data-sort="completed" data-dir="desc"><?= t('Completed') ?></button>
                    <button class="kdb-sort-btn" data-sort="completion_pct" data-dir="desc"><?= t('% Done') ?></button>
                    <button class="kdb-sort-btn" data-sort="overdue" data-dir="desc"><?= t('Overdue') ?></button>
                </div>
            </div>
            <table class="kdb-table" id="kdb-emp-table">
                <thead>
                    <tr>
                        <th><?= t('Employee') ?></th>
                        <th><?= t('Assigned') ?></th>
                        <th><?= t('Completed') ?></th>
                        <th><?= t('In Progress') ?></th>
                        <th><?= t('Pending') ?></th>
                        <th><?= t('Overdue') ?></th>
                        <th><?= t('% Done') ?></th>
                        <th><?= t('Last Activity') ?></th>
                        <th><?= t('Details') ?></th>
                    </tr>
                </thead>
                <tbody id="kdb-emp-tbody">
                <?php foreach ($employees as $emp): ?>
                    <tr data-username="<?= $this->text->e(strtolower($emp['username'])) ?>"
                        data-name="<?= $this->text->e(strtolower($emp['name'] ?? '')) ?>"
                        data-completed="<?= (int) $emp['completed'] ?>"
                        data-completion_pct="<?= (int) $emp['completion_pct'] ?>"
                        data-overdue="<?= (int) $emp['overdue'] ?>">
                        <td>
                            <div class="kdb-emp-name">
                                <span class="kdb-avatar-initials">
                                    <?= strtoupper(mb_substr($emp['name'] ?: $emp['username'], 0, 2)) ?>
                                </span>
                                <div>
                                    <strong><?= $this->text->e($emp['name'] ?: $emp['username']) ?></strong><br>
                                    <small class="kdb-muted">@<?= $this->text->e($emp['username']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= (int) $emp['total_assigned'] ?></td>
                        <td><span class="kdb-num-green"><?= (int) $emp['completed'] ?></span></td>
                        <td><span class="kdb-num-blue"><?= (int) $emp['in_progress'] ?></span></td>
                        <td><?= (int) $emp['pending'] ?></td>
                        <td><span class="kdb-num-red"><?= (int) $emp['overdue'] ?></span></td>
                        <td>
                            <div class="kdb-progress-bar">
                                <div class="kdb-progress-fill kdb-color-green-bg"
                                     style="width:<?= (int) $emp['completion_pct'] ?>%"></div>
                            </div>
                            <span class="kdb-pct-label"><?= (int) $emp['completion_pct'] ?>%</span>
                        </td>
                        <td class="kdb-muted">
                            <?= !empty($emp['last_activity_ts'])
                                ? date('d M Y', $emp['last_activity_ts'])
                                : t('No activity') ?>
                        </td>
                        <td>
                            <a href="<?= $this->url->href('CustomDashboardController', 'employeeDetail', ['user_id' => $emp['user_id']]) ?>"
                               class="btn kdb-btn-sm">
                                <i class="fa fa-eye"></i> <?= t('View') ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Workload distribution -->
    <div class="kdb-panel">
        <div class="kdb-panel-header kdb-header-collapsible" data-target="kdb-workload-wrap">
            <h3><i class="fa fa-balance-scale"></i> <?= t('Workload Distribution') ?></h3>
            <span class="kdb-toggle-icon"><i class="fa fa-chevron-down"></i></span>
        </div>
        <div class="kdb-panel-body" id="kdb-workload-wrap">
            <?php if (empty($workload)): ?>
                <p class="kdb-empty"><?= t('No workload data.') ?></p>
            <?php else: ?>
                <div id="kdb-workload-chart" class="kdb-chart-bar-h kdb-chart-tall"
                     data-workload="<?= htmlspecialchars(json_encode($workload), ENT_QUOTES) ?>">
                </div>
                <table class="kdb-table" style="margin-top:16px">
                    <thead>
                        <tr>
                            <th><?= t('Employee') ?></th>
                            <th><?= t('Open Tasks') ?></th>
                            <th><?= t('Overdue') ?></th>
                            <th><?= t('Status') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($workload as $w): ?>
                        <tr>
                            <td><?= $this->text->e($w['name'] ?: $w['username']) ?></td>
                            <td><?= (int) $w['open_tasks'] ?></td>
                            <td><span class="<?= (int) $w['overdue_tasks'] > 0 ? 'kdb-num-red' : '' ?>">
                                <?= (int) $w['overdue_tasks'] ?>
                            </span></td>
                            <td>
                                <?php if ((int) $w['open_tasks'] === 0): ?>
                                    <span class="kdb-badge kdb-badge-idle"><?= t('No tasks') ?></span>
                                <?php elseif ((int) $w['overdue_tasks'] > 0): ?>
                                    <span class="kdb-badge kdb-badge-overdue"><?= t('Has overdue') ?></span>
                                <?php else: ?>
                                    <span class="kdb-badge kdb-badge-ok"><?= t('On track') ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
        </div>
    </div>

</div><!-- /kdb-wrapper -->
