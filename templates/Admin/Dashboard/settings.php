<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $plugins
 * @var mixed $settings
 */
?>
<?php $this->assign('page', __('Settings')); ?>

<div class="row">
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <?= $this->Form->create($settings, ['align' => 'horizontal']) ?>
            <div class="card-header">
                <div class="card-title"><i class="fa-solid fa-house me-2"></i><?= __('Site') ?></div>
            </div>
            <div class="card-body">
                <?= $this->Form->control('siteName'); ?>
                <?= $this->Form->control('defaultDashboard', ['options' => $plugins]); ?>
                <?= $this->Form->control('images.format', ['options' => ['jpeg' => 'JPEG', 'webp' => 'WEBP', 'avif' => 'AVIF']]); ?>
                <?= $this->Form->control('images.quality'); ?>
            </div>
            <div class="card-footer">
                <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), ['class' => 'btn-outline-success float-end', 'escapeTitle' => false]) ?>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <?= $this->Form->create($settings, ['align' => 'horizontal']) ?>
            <div class="card-header">
                <div class="card-title"><i class="fa-solid fa-wrench me-2"></i><?= __('Maintenance') ?></div>
            </div>
            <div class="card-body">
                <?= $this->Form->control('maintenance.mode', ['options' => [0 => __('Off'), 1 => __('On')]]); ?>
                <?=
                $this->Form->control('maintenance.allowedIps', [
                    'append' => $this->Form->button('<i class="fa-solid fa-plus"></i>', [
                        'type' => 'button',
                        'class' => 'btn btn-success',
                        'data-ip' => $this->request->clientIp(),
                        'id' => 'add-ip', 'title' => __('Add my IP'),
                        'escapeTitle' => false,
                        'onclick' => 'document.getElementById(\'maintenance-allowedips\').value = this.dataset.ip',
                    ]),
                ]);
                ?>
                <?= $this->Form->control('maintenance.message'); ?>
            </div>
            <div class="card-footer">
                <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), ['class' => 'btn-outline-success float-end', 'escapeTitle' => false]) ?>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

