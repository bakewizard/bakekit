<?php
/**
 * @var \App\View\AppView $this
 * @var array $config
 * @var mixed $regions
 * @var \App\Model\Entity\Block $block
 */
?>
<?= $this->Html->script(['/backend/js/blocks'], ['block' => true, 'type' => 'module']) ?>

<?= $this->element('form/cell_select_modal') ?>

<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Edit Block') ?></div>
        <?php if (count($config['App']['I18n']['languages']) > 1) : ?>
            <div class="card-tools">
                <?= $this->element('form/locales', ['locale' => $block->_locale]) ?>
            </div>
        <?php endif; ?>
    </div>
    <?= $this->Form->create($block, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('alias'); ?>
        <?= $this->Form->control('title'); ?>
        <?= $this->Form->control('description'); ?>
        <?= $this->Form->control('region_id', ['options' => $regions]); ?>
        <?php if ($block->hasValue('cell')) : ?>
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
        <?php endif; ?>
        <?php if (!$block->hasValue('cell')) : ?>
            <?= $this->Form->control('params', ['label' => 'Content', 'type' => 'textarea']); ?>
        <?php endif; ?>
        <?= $this->Form->control('enabled', ['switch' => true]); ?>
    </div>
    <div class="card-footer">
        <?= $this->element('form/save_buttons') ?>
        <?= $this->Html->link(
            '<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'),
            ['controller' => 'Themes', 'action' => 'blocks'],
            ['class' => 'btn btn-outline-danger', 'escape' => false],
        ) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
