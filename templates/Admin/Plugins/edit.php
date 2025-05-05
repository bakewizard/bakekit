<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Plugin $plugin
 */
?>
<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Edit plugin') ?></div>
    </div>
    <?= $this->Form->create($plugin, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('alias'); ?>
    </div>
    <div class="card-footer">
        <?= $this->element('form/save_buttons') ?>
        <?= $this->Html->link('<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'), ['action' => 'index', '?' => $this->request->getQueryParams()], ['class' => 'btn btn-outline-danger', 'escape' => false]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>