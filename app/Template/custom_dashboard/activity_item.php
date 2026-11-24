<?php
/**
 * Single activity timeline entry.
 * Variable: $kdb_event — row from project_activities joined to tasks/projects
 */
$event_icons = [
    'task.create'          => ['icon' => 'plus-circle',    'label' => t('Task created')],
    'task.update'          => ['icon' => 'pencil',         'label' => t('Task updated')],
    'task.close'           => ['icon' => 'check-circle',   'label' => t('Task completed')],
    'task.open'            => ['icon' => 'circle-o',       'label' => t('Task reopened')],
    'task.move.column'     => ['icon' => 'arrows-h',       'label' => t('Task moved')],
    'task.assignee_change' => ['icon' => 'user',           'label' => t('Assignee changed')],
    'task.file.create'     => ['icon' => 'paperclip',      'label' => t('File attached')],
    'comment.create'       => ['icon' => 'comment-o',      'label' => t('Comment added')],
    'comment.update'       => ['icon' => 'comment',        'label' => t('Comment updated')],
];

$ename = $kdb_event['event_name'] ?? '';
$meta  = $event_icons[$ename] ?? ['icon' => 'circle', 'label' => ucwords(str_replace(['.', '_'], ' ', $ename))];
$when  = !empty($kdb_event['date_creation']) ? date('d M Y, H:i', $kdb_event['date_creation']) : '';
?>
<div class="kdb-activity-item">
    <div class="kdb-activity-icon">
        <i class="fa fa-<?= $meta['icon'] ?>"></i>
    </div>
    <div class="kdb-activity-body">
        <div class="kdb-activity-label">
            <?= $meta['label'] ?>:
            <?php if (!empty($kdb_event['task_id'])): ?>
                <a href="<?= $this->url->href('TaskViewController', 'show', ['task_id' => $kdb_event['task_id']]) ?>">
                    #<?= (int) $kdb_event['task_id'] ?> <?= $this->text->e($kdb_event['task_title'] ?? '') ?>
                </a>
            <?php endif ?>
        </div>
        <div class="kdb-activity-meta">
            <span><i class="fa fa-folder-o"></i> <?= $this->text->e($kdb_event['project_name'] ?? '') ?></span>
            <span class="kdb-activity-time"><i class="fa fa-clock-o"></i> <?= $when ?></span>
        </div>
    </div>
</div>
