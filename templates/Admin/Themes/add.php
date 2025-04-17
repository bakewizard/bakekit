<div class="card card-primary card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit"></i> <?= __('Add Theme') ?></div>
    </div>
    <?= $this->Form->create($theme, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('name'); ?>
        <?= $this->Form->control('description'); ?>
        <?= $this->Form->control('active'); ?>
    </div>
    <div class="card-footer">
        <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), ['class' => 'btn-success float-end', 'escapeTitle' => false]) ?>
        <?= $this->Html->link('<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'), ['controller' => 'Themes', 'action' => 'index'], ['class' => 'btn btn-danger', 'escape' => false]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>