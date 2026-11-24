<?php
/**
 * Single task row for dashboard task lists.
 * Variable: $kdb_task  — single task array from CustomDashboardModel
 */
$now      = time();
$is_over  = !empty($kdb_task['date_due']) && $kdb_task['date_due'] > 0 && $kdb_task['date_due'] <= $now && ($kdb_task['is_active'] ?? 1) == 1;
$due_fmt  = !empty($kdb_task['date_due']) && $kdb_task['date_due'] > 0
    ? date('d M Y', $kdb_task['date_due'])
    : '—';

$prio_map = [
    0  => ['label' => t('Normal'),   'class' => 'kdb-prio-normal'],
    1  => ['label' => t('Low'),      'class' => 'kdb-prio-low'],
    2  => ['label' => t('Medium'),   'class' => 'kdb-prio-medium'],
    3  => ['label' => t('High'),     'class' => 'kdb-prio-high'],
    -1 => ['label' => t('Very Low'), 'class' => 'kdb-prio-low'],
];
$prio  = isset($kdb_task['priority']) ? (int) $kdb_task['priority'] : 0;
$pinfo = $prio_map[$prio] ?? $prio_map[0];
?>
<div class="kdb-task-row <?= $is_over ? 'kdb-task-overdue' : '' ?>">
    <div class="kdb-task-title">
        <a href="<?= $this->url->href('TaskViewController', 'show', ['task_id' => $kdb_task['id']]) ?>">
            #<?= (int) $kdb_task['id'] ?> <?= $this->text->e($kdb_task['title']) ?>
        </a>
    </div>
    <div class="kdb-task-meta">
        <span class="kdb-badge kdb-badge-project">
            <i class="fa fa-folder-o"></i>
            <?= $this->text->e($kdb_task['project_name'] ?? '') ?>
        </span>
        <span class="kdb-badge <?= $pinfo['class'] ?>">
            <i class="fa fa-flag"></i> <?= $pinfo['label'] ?>
        </span>
        <span class="kdb-badge <?= !empty($kdb_task['column_name']) ? 'kdb-badge-column' : '' ?>">
            <i class="fa fa-columns"></i>
            <?= $this->text->e($kdb_task['column_name'] ?? '') ?>
        </span>
        <span class="kdb-badge <?= $is_over ? 'kdb-badge-overdue' : 'kdb-badge-due' ?>">
            <i class="fa fa-calendar"></i> <?= $due_fmt ?>
        </span>
    </div>
</div>
