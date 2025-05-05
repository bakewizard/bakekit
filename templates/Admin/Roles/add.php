<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $parentRoles
 * @var \App\Model\Entity\Role $role
 */
?>
<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Add Role') ?></div>
    </div>
    <?= $this->Form->create($role, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('parent_id', ['options' => $parentRoles, 'empty' => 'No parent role']); ?>
        <?= $this->Form->control('name'); ?>
        <?= $this->Form->control('alias'); ?>
    </div>
    <div class="card-footer">
        <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), ['class' => 'btn-success float-end', 'escapeTitle' => false]) ?>
        <?= $this->Html->link('<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'), ['controller' => 'roles', 'action' => 'index'], ['class' => 'btn btn-outline-danger', 'escape' => false]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>