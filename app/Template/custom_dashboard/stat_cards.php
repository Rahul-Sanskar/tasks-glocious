<?php
/**
 * Renders a horizontal row of KPI stat cards.
 *
 * Required variable:
 *   $kdb_cards  array of [
 *     'label'   => string,
 *     'value'   => int|string,
 *     'icon'    => FontAwesome icon name (without 'fa-'),
 *     'class'   => CSS modifier class ('card-blue','card-green','card-red','card-orange','card-grey','card-teal'),
 *     'data_key'=> string (used by JS for live update, optional),
 *   ]
 */
?>
<div class="kdb-stat-cards" id="kdb-stat-cards">
    <?php foreach ($kdb_cards as $card): ?>
    <div class="kdb-card <?= $card['class'] ?>" <?= isset($card['data_key']) ? 'data-key="'.$card['data_key'].'"' : '' ?>>
        <div class="kdb-card-icon">
            <i class="fa fa-<?= $card['icon'] ?>"></i>
        </div>
        <div class="kdb-card-body">
            <div class="kdb-card-value" <?= isset($card['data_key']) ? 'data-stat="'.$card['data_key'].'"' : '' ?>>
                <?= is_numeric($card['value']) ? (int) $card['value'] : $this->text->e($card['value']) ?>
            </div>
            <div class="kdb-card-label"><?= $this->text->e($card['label']) ?></div>
        </div>
    </div>
    <?php endforeach ?>
</div>
