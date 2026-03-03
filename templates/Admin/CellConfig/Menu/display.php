<?php
/**
 * @var \App\View\AppView $this
 * @var object $settings
 * @var \App\Model\Entity\Block $block
 */
?>
<?= $this->Html->script(['/backend/js/menu'], ['block' => true, 'type' => 'module']) ?>

<template id="attribute-item-template">
    <li class="list-group-item">
        <div class="form-group row text mb-0">
            <label class="col-form-label col-md-2" for=""></label>
            <div class="col-md-10">
                <div class="input-group">
                    <input type="text" name="" id="" class="form-control" value="">
                    <button type="button" class="btn btn-outline-success remove-attribute-btn" title="Remove attribute">
                        <i class="fa-solid fa-minus-circle"></i>
                    </button>
                </div>
            </div>
        </div>
    </li>
</template>

<div class="card card-primary card-outline">
    <div class="card-header">
        <div class="card-title"><?= __('Menu cell') ?></div>
    </div>
    <?= $this->Form->create($settings, ['align' => 'horizontal']) ?>
    <div class="card-body" id="menu-cell-attributes">
        <?= $this->Form->control('menu', ['options' => $settings->getMenus(), 'empty' => '------']); ?>
        <?php foreach ($settings->getSchema()->fields() as $param) : ?>
            <?php if ($param === 'menu') : ?>
                <?php continue; ?>
            <?php endif; ?>
            <div class="card mb-2">
                <div class="card-header">
                    <h2 class="card-title"><?= ucfirst(strtolower(preg_replace('/([A-Z]+)/', ' $1', $param))) ?></h2>
                    <div class="card-tools">
                        <div class="input-group">
                            <input type="text" class="form-control">
                            <button type="button" class="btn btn-outline-success add-attribute-btn" title="<?= __('Add attribute') ?>">
                                <i class="fa-solid fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <ul class="list-group list-group-flush" data-param="<?= $param ?>">
                    <?php if (isset($block->params[$param])) :
                        ; ?>
                        <?php foreach ($block->params[$param] as $attribute => $value) : ?>
                            <li class="list-group-item">
                                <div class="form-group row text mb-0">
                                    <label class="col-form-label col-md-2" for="<?= strtolower($param) . '-' . $attribute ?>"><?= $attribute ?></label>
                                    <div class="col-md-10">
                                        <div class="input-group">
                                            <input type="text"
                                                name="<?= $param . '[' . $attribute . ']' ?>"
                                                id="<?= strtolower($param) . '-' . $attribute ?>"
                                                class="form-control"
                                                value="<?= $value ?>">
                                            <button type="button" class="btn btn-outline-success remove-attribute-btn" title="<?= __('Remove attribute') ?>">
                                                <i class="fa-solid fa-minus-circle"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card-footer">
        <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), ['class' => 'btn-outline-success float-end', 'escapeTitle' => false]) ?>
        <?= $this->Html->link(
            '<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'),
            ['controller' => 'Regions', 'action' => 'view', $block->region_id],
            ['class' => 'btn btn-outline-danger', 'escape' => false],
        ) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
