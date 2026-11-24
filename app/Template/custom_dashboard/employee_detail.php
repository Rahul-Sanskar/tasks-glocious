<?php
/**
 * Employee Performance Detail — admin view
 *
 * Template variables:
 *   $target_user, $stats, $trend30, $trend7,
 *   $projects, $activity, $overdue, $open_tasks, $completed_tasks,
 *   $date_filter, $custom_from, $custom_to
 */
echo $this->asset->css('assets/css/custom_dashboard.css');
echo $this->asset->js('assets/js/custom_dashboard.js');
?>
$pct = $stats['total_assigned'] > 0
    ? round($stats['completed'] / $stats['total_assigned'] * 100)
    : 0;

$display_name = $target_user['name'] ?: $target_user['username'];

$data_url = $this->url->href('CustomDashboardController', 'employeeData', [
    'user_id'     => $target_user['id'],
    'date_filter' => $date_filter,
    'custom_from' => $custom_from,
    'custom_to'   => $custom_to,
]);
$page_url = $this->url->href('CustomDashboardController', 'employeeDetail', ['user_id' => $target_user['id']]);

$kdb_cards = [
    ['label' => t('Total Assigned'), 'value' => $stats['total_assigned'], 'icon' => 'tasks',              'class' => 'card-blue'],
    ['label' => t('Completed'),      'value' => $stats['completed'],      'icon' => 'check-circle',        'class' => 'card-green'],
    ['label' => t('In Progress'),    'value' => $stats['in_progress'],    'icon' => 'spinner',             'class' => 'card-teal'],
    ['label' => t('Pending'),        'value' => $stats['pending'],        'icon' => 'clock-o',             'class' => 'card-grey'],
    ['label' => t('Overdue'),        'value' => $stats['overdue'],        'icon' => 'exclamation-circle',  'class' => 'card-red'],
    ['label' => t('% Completion'),   'value' => $pct.'%',                 'icon' => 'trophy',              'class' => 'card-orange'],
];
?>

<div class="kdb-wrapper" id="kdb-employee-detail">

    <!-- Back link -->
    <div class="kdb-breadcrumb">
        <a href="<?= $this->url->href('CustomDashboardController', 'adminShow') ?>">
            <i class="fa fa-arrow-left"></i> <?= t('Back to Admin Dashboard') ?>
        </a>
    </div>

    <!-- Employee profile header -->
    <div class="kdb-emp-profile-header">
        <div class="kdb-emp-avatar-lg">
            <?= strtoupper(mb_substr($display_name, 0, 2)) ?>
        </div>
        <div class="kdb-emp-profile-info">
            <h1 class="kdb-page-title"><?= $this->text->e($display_name) ?></h1>
            <div class="kdb-emp-meta">
                <span><i class="fa fa-user"></i> @<?= $this->text->e($target_user['username']) ?></span>
                <?php if (!empty($target_user['email'])): ?>
                    <span><i class="fa fa-envelope-o"></i> <?= $this->text->e($target_user['email']) ?></span>
                <?php endif ?>
                <span class="kdb-role-badge"><?= $this->text->e($target_user['role']) ?></span>
            </div>
            <div class="kdb-completion-overview">
                <div class="kdb-big-pct"><?= $pct ?>%</div>
                <div class="kdb-big-label"><?= t('Completion Rate') ?></div>
            </div>
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

    <!-- Charts -->
    <div class="kdb-charts-row">
        <div class="kdb-panel kdb-panel-sm">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-pie-chart"></i> <?= t('Task Breakdown') ?></h3>
            </div>
            <div class="kdb-panel-body kdb-chart-center">
                <div id="kdb-emp-donut" class="kdb-chart-donut"
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

        <div class="kdb-panel kdb-panel-lg">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-line-chart"></i> <?= t('30-Day Trend') ?></h3>
            </div>
            <div class="kdb-panel-body">
                <div id="kdb-emp-trend30" class="kdb-chart-bar"
                     data-trend="<?= htmlspecialchars(json_encode($trend30), ENT_QUOTES) ?>">
                </div>
            </div>
        </div>

        <div class="kdb-panel kdb-panel-md">
            <div class="kdb-panel-header">
                <h3><i class="fa fa-bar-chart"></i> <?= t('Weekly Trend') ?></h3>
            </div>
            <div class="kdb-panel-body">
                <div id="kdb-emp-trend7" class="kdb-chart-bar"
                     data-trend="<?= htmlspecialchars(json_encode($trend7), ENT_QUOTES) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Per-project contribution -->
    <?php if (!empty($projects)): ?>
    <div class="kdb-panel">
        <div class="kdb-panel-header">
            <h3><i class="fa fa-folder-open"></i> <?= t('Project Contribution') ?></h3>
        </div>
        <div class="kdb-panel-body">
            <div id="kdb-emp-projects-chart" class="kdb-chart-bar-h"
                 data-projects="<?= htmlspecialchars(json_encode($projects), ENT_QUOTES) ?>">
            </div>
            <table class="kdb-table" style="margin-top:16px">
                <thead>
                    <tr>
                        <th><?= t('Project') ?></th>
                        <th><?= t('Open') ?></th>
                        <th><?= t('Completed') ?></th>
                        <th><?= t('Total') ?></th>
                        <th><?= t('Completion') ?></th>
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
                            <?php $pp = $p['total'] > 0 ? round($p['closed_tasks'] / $p['total'] * 100) : 0 ?>
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

    <!-- Tasks + activity -->
    <div class="kdb-two-col">
        <div class="kdb-col-main">

            <!-- Overdue -->
            <?php if (!empty($overdue)): ?>
            <div class="kdb-panel kdb-panel-alert">
                <div class="kdb-panel-header kdb-header-red">
                    <h3><i class="fa fa-exclamation-circle"></i>
                        <?= t('Overdue Tasks') ?> (<?= count($overdue) ?>)
                    </h3>
                </div>
                <div class="kdb-panel-body kdb-task-list">
                    <?php foreach ($overdue as $t): ?>
                        <?= $this->render('custom_dashboard/task_row', ['kdb_task' => $t]) ?>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif ?>

            <!-- Open tasks -->
            <div class="kdb-panel">
                <div class="kdb-panel-header">
                    <h3><i class="fa fa-list-ul"></i>
                        <?= t('Current Workload') ?>
                        <span class="kdb-count-badge"><?= count($open_tasks) ?></span>
                    </h3>
                </div>
                <div class="kdb-panel-body kdb-task-list">
                    <?php if (empty($open_tasks)): ?>
                        <p class="kdb-empty"><?= t('No open tasks.') ?></p>
                    <?php else: ?>
                        <?php foreach ($open_tasks as $t): ?>
                            <?= $this->render('custom_dashboard/task_row', ['kdb_task' => $t]) ?>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>

            <!-- Completed tasks -->
            <div class="kdb-panel">
                <div class="kdb-panel-header">
                    <h3><i class="fa fa-check-circle"></i>
                        <?= t('Recently Completed') ?>
                        <span class="kdb-count-badge kdb-badge-green"><?= count($completed_tasks) ?></span>
                    </h3>
                </div>
                <div class="kdb-panel-body kdb-task-list">
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

        <!-- Activity timeline -->
        <div class="kdb-col-side">
            <div class="kdb-panel kdb-panel-full-height">
                <div class="kdb-panel-header">
                    <h3><i class="fa fa-history"></i> <?= t('Activity Timeline') ?></h3>
                </div>
                <div class="kdb-panel-body kdb-activity-feed">
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

    </div>

</div><!-- /kdb-wrapper -->
