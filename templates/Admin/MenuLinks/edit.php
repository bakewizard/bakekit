<?= $this->Html->script(['/backend/js/menus'], ['block' => true]) ?>

<?= $this->element('form/link_select_modal') ?>

<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Edit Menu Link') ?></div>
        <?php if (count($config['App']['I18n']['languages']) > 1): ?>
            <div class="card-tools">
                <?= $this->element('form/locales', ['locale' => $menuLink->_locale]) ?>
            </div>
        <?php endif; ?>
    </div>
    <?= $this->Form->create($menuLink, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('parent_id', ['options' => $parentMenuLinks, 'empty' => 'No parent category']); ?>
        <?= $this->Form->control('title'); ?>
        <?= $this->Form->control('icon'); ?>
        <?=
        $this->Form->control('link', [
            'id' => 'link-select-input',
            'append' => $this->Form->button('...', [
                'type' => 'button',
                'class' => 'btn btn-primary',
                'id' => 'link-select-button',
                'title' => __('Select Link'),
                'data-url' => $this->Url->build(['controller' => 'MenuLinks', 'action' => 'getLinks', $menuLink->menu_id])
        ])]);
        ?>
        <?= $this->Form->control('target', ['options' => $targets]); ?>
    </div>
    <div class="card-footer">
        <?= $this->element('form/save_buttons') ?>
        <?= $this->Html->link('<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'), ['controller' => 'Menus', 'action' => 'view', $menuLink->menu_id, '?' => $this->request->getQueryParams()], ['class' => 'btn btn-outline-danger', 'escape' => false]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
