<div class="float-end">
    <?= $this->Form->button('<i class="fa-solid fa-times-circle"></i> ' . __('Save & Close'), ['class' => 'btn-outline-success', 'escapeTitle' => false]) ?>
    <?php if ($this->request->getParam('action') === 'edit'): ?>
        <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), ['name' => 'redirect', 'value' => 'edit', 'class' => 'btn-outline-success', 'escapeTitle' => false]) ?>
    <?php endif; ?>
    <?php if ($this->request->getParam('action') === 'add' && empty($this->request->getQueryParams())): ?>
        <?= $this->Form->button('<i class="fa-solid fa-plus-circle"></i> ' . __('Save & New'), ['name' => 'redirect', 'value' => 'add', 'class' => 'btn-outline-success', 'escapeTitle' => false]) ?>
    <?php endif; ?>
</div>