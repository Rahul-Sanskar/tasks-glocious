<?php
/**
 * Shared date-filter bar partial.
 *
 * Required template variables (all passed from parent):
 *   $date_filter   string  — active filter key
 *   $custom_from   int     — unix ts (may be 0)
 *   $custom_to     int     — unix ts (may be 0)
 *   $data_url      string  — JSON endpoint used by JS for AJAX refresh
 *   $page_url      string  — full-page reload URL base (without date_filter param)
 */
$filters = [
    'today'      => t('Today'),
    'last7'      => t('Last 7 Days'),
    'last30'     => t('Last 30 Days'),
    'this_month' => t('This Month'),
    'prev_month' => t('Previous Month'),
    'custom'     => t('Custom Range'),
];
$active  = isset($date_filter) ? $date_filter : 'last30';
$cfrom   = isset($custom_from) && $custom_from > 0 ? date('Y-m-d', $custom_from) : '';
$cto     = isset($custom_to)   && $custom_to   > 0 ? date('Y-m-d', $custom_to)   : '';
?>
<div class="kdb-date-filter" id="kdb-date-filter"
     data-data-url="<?= isset($data_url) ? htmlspecialchars($data_url, ENT_QUOTES) : '' ?>"
     data-page-url="<?= isset($page_url) ? htmlspecialchars($page_url, ENT_QUOTES) : '' ?>">
    <div class="kdb-filter-pills">
        <?php foreach ($filters as $key => $label): ?>
            <button class="kdb-pill <?= $active === $key ? 'active' : '' ?>"
                    data-filter="<?= $key ?>">
                <?= $label ?>
            </button>
        <?php endforeach ?>
    </div>
    <div class="kdb-filter-custom <?= $active === 'custom' ? 'visible' : '' ?>" id="kdb-custom-range">
        <label><?= t('From') ?>
            <input type="date" id="kdb-from" value="<?= htmlspecialchars($cfrom, ENT_QUOTES) ?>" max="<?= date('Y-m-d') ?>">
        </label>
        <label><?= t('To') ?>
            <input type="date" id="kdb-to" value="<?= htmlspecialchars($cto, ENT_QUOTES) ?>" max="<?= date('Y-m-d') ?>">
        </label>
        <button class="btn btn-blue kdb-custom-apply" id="kdb-custom-apply"><?= t('Apply') ?></button>
    </div>
</div>
