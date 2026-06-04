<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Block $block
 */
?>
<?= $this->Html->script(['/backend/js/blocks'], ['block' => true, 'type' => 'module']) ?>

<?= $this->element('form/cell_select_modal') ?>

<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Add Block') ?></div>
    </div>
    <?= $this->Form->create($block, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('title'); ?>
        <?= $this->Form->control('description'); ?>
        <?= $this->Form->control('region_id', ['type' => 'hidden']); ?>
        <?=
        $this->Form->control('cell', [
            'append' => $this->Form->button('...', [
                'type' => 'button',
                'class' => 'btn btn-primary',
                'id' => 'cell-select-button',
                'title' => __('Select Cell'),
                'data-url' => $this->Url->build(['controller' => 'Blocks', 'action' => 'getCells']),
            ]),
            'readonly' => true,
        ]);
        ?>
        <?= $this->Form->control('template'); ?>
        <?= $this->Form->control('enabled', ['switch' => true]); ?>
    </div>
    <div class="card-footer">
        <?= $this->element('form/save_buttons') ?>
        <?= $this->Html->link('<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'), [
            'controller' => 'Themes',
            'action' => 'blocks',
            '?' => $this->request->getQueryParams(),
        ], ['class' => 'btn btn-outline-danger', 'escape' => false]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
