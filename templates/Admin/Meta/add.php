<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $plugins
 * @var \App\Model\Entity\Metum $metum
 */
?>
<?= $this->Html->script(['/backend/plugins/tinymce/tinymce.min', '/backend/js/meta'], ['block' => true]) ?>
<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Add Metum') ?></div>
    </div>
    <?= $this->Form->create($metum, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('plugin_id', ['options' => $plugins]); ?>
        <?= $this->Form->control('title'); ?>
        <?= $this->Form->control('description'); ?>
        <?= $this->Form->control('seo_title'); ?>
        <?= $this->Form->control('seo_description'); ?>
        <?= $this->Form->control('seo_keywords'); ?>
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
