<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Menu $menu
 */
?>
<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Add Menu') ?></div>
    </div>
    <?= $this->Form->create($menu, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('name'); ?>
        <?= $this->Form->control('description'); ?>
        <?= $this->Form->control('prefix', [
            'type' => 'radio',
            'label' => __('Client'),
            'inline' => true,
            'options' => [
                ['text' => 'Frontend', 'value' => 0],
                ['text' => 'Backend', 'value' => 1],
            ],
        ]); ?>
        <?= $this->Form->control('enabled', ['switch' => true]); ?>
    </div>
    <div class="card-footer">
        <?= $this->element('form/save_buttons') ?>
        <?= $this->Html->link(
            '<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'),
            ['action' => 'index', '?' => $this->request->getQueryParams()],
            ['class' => 'btn btn-outline-danger', 'escape' => false],
        ) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
