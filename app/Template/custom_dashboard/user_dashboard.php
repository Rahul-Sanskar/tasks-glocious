<?php
/**
 * User Analytics Dashboard
 *
 * Template variables (from CustomDashboardController::show):
 *   $user, $stats, $trend30, $trend7, $projects,
 *   $overdue, $open_tasks, $completed_tasks, $activity,
 *   $date_filter, $custom_from, $custom_to
 */
echo $this->asset->css('assets/css/custom_dashboard.css');
echo $this->asset->js('assets/js/custom_dashboard.js');
?>

$pct = $stats['total_assigned'] > 0
    ? round($stats['completed'] / $stats['total_assigned'] * 100)
    : 0;

$data_url = $this->url->href('CustomDashboardController', 'userData', [
    'date_filter' => $date_filter,
    'custom_from' => $custom_from,
    'custom_to'   => $custom_to,
]);
$page_url = $this->url->href('CustomDashboardController', 'show');

$kdb_cards = [
    ['label' => t('Total Assigned'),   'value' => $stats['total_assigned'], 'icon' => 'tasks',       'class' => 'card-blue',   'data_key' => 'total_assigned'],
    ['label' => t('Completed'),        'value' => $stats['completed'],      'icon' => 'check-circle', 'class' => 'card-green',  'data_key' => 'completed'],
    ['label' => t('In Progress'),      'value' => $stats['in_progress'],    'icon' => 'spinner',      'class' => 'card-teal',   'data_key' => 'in_progress'],
    ['label' => t('Pending'),          'value' => $stats['pending'],        'icon' => 'clock-o',      'class' => 'card-grey',   'data_key' => 'pending'],
    ['label' => t('Overdue'),          'value' => $stats['overdue'],        'icon' => 'exclamation-circle', 'class' => 'card-red',  'data_key' => 'overdue'],
    ['label' => t('Due Today'),        'value' => $stats['due_today'],      'icon' => 'calendar',     'class' => 'card-orange', 'data_key' => 'due_today'],
    ['label' => t('Due This Week'),    'value' => $stats['due_this_week'],  'icon' => 'calendar-check-o', 'class' => 'card-blue', 'data_key' => 'due_this_week'],
];
?>

<div class="kdb-wrapper" id="kdb-user-dashboard"
     data-role="user"
     data-user-id="<?= (int) $user['id'] ?>">

    <!-- Page header -->
    <div class="kdb-page-header">
        <div class="kdb-page-header-left">
            <h1 class="kdb-page-title">
                <i class="fa fa-bar-chart"></i>
                <?= t('My Analytics Dashboard') ?>
            </h1>
            <p class="kdb-page-subtitle">
                <?= $this->text->e($user['name'] ?: $user['username']) ?>
                &nbsp;&mdash;&nbsp;<?= t('Completion rate: %d%%', $pct) ?>
            </p>
        </div>
        <div class="kdb-page-header-right">
            <a href="<?= $this->url->href('DashboardController', 'show', ['user_id' => $user['id']]) ?>"
               class="btn kdb-btn-outline">
                <i class="fa fa-tachometer"></i> <?= t('Classic Dashboard') ?>
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
        <!-- Completion donut -->
        <div class="kdb-panel kdb-panel-sm">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-pie-chart"></i> <?= t('Task Completion') ?></h3>
            </div>
            <div class="kdb-panel-body kdb-chart-center">
                <div id="kdb-user-donut" class="kdb-chart-donut"
                     data-completed="<?= $stats['completed'] ?>"
                     data-in-progress="<?= $stats['in_progress'] ?>"
                     data-pending="<?= $stats['pending'] ?>"
                     data-overdue="<?= $stats['overdue'] ?>">
                </div>
                <div class="kdb-donut-legend">
                    <span class="kdb-legend-item kdb-color-green"><i class="fa fa-circle"></i> <?= t('Done') ?> (<?= $stats['completed'] ?>)</span>
                    <span class="kdb-legend-item kdb-color-blue"><i class="fa fa-circle"></i> <?= t('In Progress') ?> (<?= $stats['in_progress'] ?>)</span>
                    <span class="kdb-legend-item kdb-color-grey"><i class="fa fa-circle"></i> <?= t('Pending') ?> (<?= $stats['pending'] ?>)</span>
                    <span class="kdb-legend-item kdb-color-red"><i class="fa fa-circle"></i> <?= t('Overdue') ?> (<?= $stats['overdue'] ?>)</span>
                </div>
            </div>
        </div>

        <!-- 30-day trend -->
        <div class="kdb-panel kdb-panel-lg">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-line-chart"></i> <?= t('30-Day Completion Trend') ?></h3>
            </div>
            <div class="kdb-panel-body">
                <div id="kdb-user-trend30" class="kdb-chart-bar"
                     data-trend="<?= htmlspecialchars(json_encode($trend30), ENT_QUOTES) ?>">
                </div>
            </div>
        </div>

        <!-- 7-day trend -->
        <div class="kdb-panel kdb-panel-md">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-bar-chart"></i> <?= t('Weekly Trend') ?></h3>
            </div>
            <div class="kdb-panel-body">
                <div id="kdb-user-trend7" class="kdb-chart-bar"
                     data-trend="<?= htmlspecialchars(json_encode($trend7), ENT_QUOTES) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Per-project distribution -->
    <?php if (!empty($projects)): ?>
    <div class="kdb-panel">
        <div class="kdb-panel-header">
            <h3><i class="fa fa-folder-open"></i> <?= t('My Projects') ?></h3>
        </div>
        <div class="kdb-panel-body">
            <div id="kdb-user-projects-chart" class="kdb-chart-bar-h"
                 data-projects="<?= htmlspecialchars(json_encode($projects), ENT_QUOTES) ?>">
            </div>
            <table class="kdb-table" style="margin-top:16px">
                <thead>
                    <tr>
                        <th><?= t('Project') ?></th>
                        <th><?= t('Open') ?></th>
                        <th><?= t('Completed') ?></th>
                        <th><?= t('Total') ?></th>
                        <th><?= t('Progress') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $p): ?>
                    <tr>
                        <td>
                            <a href="<?= $this->url->href('BoardViewController', 'show', ['project_id' => $p['project_id']]) ?>">
                                <?= $this->text->e($p['project_name']) ?>
                            </a>
                        </td>
                        <td><?= (int) $p['open_tasks'] ?></td>
                        <td><?= (int) $p['closed_tasks'] ?></td>
                        <td><?= (int) $p['total'] ?></td>
                        <td>
                            <?php $pp = $p['total'] > 0 ? round($p['closed_tasks'] / $p['total'] * 100) : 0; ?>
                            <div class="kdb-progress-bar">
                                <div class="kdb-progress-fill kdb-color-green-bg" style="width:<?= $pp ?>%"></div>
                            </div>
                            <span class="kdb-pct-label"><?= $pp ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>

    <!-- Main content: tasks + activity -->
    <div class="kdb-two-col">

        <!-- Left column: task lists -->
        <div class="kdb-col-main">

            <!-- Overdue tasks -->
            <?php if (!empty($overdue)): ?>
            <div class="kdb-panel kdb-panel-alert">
                <div class="kdb-panel-header kdb-header-red">
                    <h3><i class="fa fa-exclamation-circle"></i> <?= t('Overdue Tasks') ?> (<?= count($overdue) ?>)</h3>
                </div>
                <div class="kdb-panel-body kdb-task-list" id="kdb-overdue-tasks">
                    <?php foreach ($overdue as $t): ?>
                        <?= $this->render('custom_dashboard/task_row', ['kdb_task' => $t]) ?>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif ?>

            <!-- Open / assigned tasks -->
            <div class="kdb-panel">
                <div class="kdb-panel-header">
                    <h3><i class="fa fa-list-ul"></i>
                        <?= t('My Open Tasks') ?>
                        <span class="kdb-count-badge"><?= count($open_tasks) ?></span>
                    </h3>
                </div>
                <div class="kdb-panel-body kdb-task-list" id="kdb-open-tasks">
                    <?php if (empty($open_tasks)): ?>
                        <p class="kdb-empty"><?= t('No open tasks assigned to you.') ?></p>
                    <?php else: ?>
                        <?php foreach ($open_tasks as $t): ?>
                            <?= $this->render('custom_dashboard/task_row', ['kdb_task' => $t]) ?>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>

            <!-- Recently completed tasks -->
            <div class="kdb-panel">
                <div class="kdb-panel-header">
                    <h3><i class="fa fa-check-circle"></i>
                        <?= t('Recently Completed') ?>
                        <span class="kdb-count-badge kdb-badge-green"><?= count($completed_tasks) ?></span>
                    </h3>
                </div>
                <div class="kdb-panel-body kdb-task-list" id="kdb-completed-tasks">
                    <?php if (empty($completed_tasks)): ?>
                        <p class="kdb-empty"><?= t('No completed tasks in this period.') ?></p>
                    <?php else: ?>
                        <?php foreach ($completed_tasks as $t): ?>
                            <?= $this->render('custom_dashboard/task_row', ['kdb_task' => $t]) ?>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>

        </div>

        <!-- Right column: activity timeline -->
        <div class="kdb-col-side">
            <div class="kdb-panel kdb-panel-full-height">
                <div class="kdb-panel-header">
                    <h3><i class="fa fa-history"></i> <?= t('My Recent Activity') ?></h3>
                </div>
                <div class="kdb-panel-body kdb-activity-feed" id="kdb-activity-feed">
                    <?php if (empty($activity)): ?>
                        <p class="kdb-empty"><?= t('No recent activity.') ?></p>
                    <?php else: ?>
                        <?php foreach ($activity as $event): ?>
                            <?= $this->render('custom_dashboard/activity_item', ['kdb_event' => $event]) ?>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>
        </div>

    </div><!-- /kdb-two-col -->

</div><!-- /kdb-wrapper -->
