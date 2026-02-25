<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $roles
 * @var \App\Model\Entity\User $user
 */
?>
<?= $this->Html->script(['/backend/js/users'], ['block' => true, 'type' => 'module']); ?>
<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Edit User') ?></div>
    </div>
    <?= $this->Form->create($user, ['align' => 'horizontal', 'type' => 'file', 'id' => 'users-edit-form']) ?>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-form-label col-md-2" for="image">Image</label>
            <div class="col-md-10">
                <?=
                $this->Html->image($this->getImageUrl($user, 'lg'), [
                    'title' => $user->name,
                    'alt' => $user->name,
                    'width' => 200,
                    'height' => 200,
                    'class' => 'img-thumbnail',
                    'id' => 'users-main-image',
                ]);
                ?>
                <div class="my-2">
                    <?=
                    $this->Form->file('uploads[]', [
                        'accept' => 'image/*',
                        'id' => 'users-images-input',
                        'append' => $this->Form->deleteLink(
                            '<i class="fa-solid fa-lg fa-eraser"></i>',
                            ['action' => 'deleteFiles', $user->id],
                            [
                                    'block' => true,
                                    'escape' => false,
                                    'confirm' => __('Are you sure you want to delete {0}?', $user->full_name),
                                    'class' => 'text-danger',
                                    'data-bs-toggle' => 'modal',
                                    'data-bs-target' => '#confirm-modal',
                                ],
                        ),
                    ]);
                    ?>
                </div>
            </div>
        </div>
        <?= $this->Form->control('first_name'); ?>
        <?= $this->Form->control('last_name'); ?>
        <?= $this->Form->control('alias'); ?>
        <?= $this->Form->control('email'); ?>
        <?= $this->Form->control('password', ['value' => '']); ?>
        <?php if ($this->Auth->isRoot() && !$user->isRoot()) : ?>
            <?= $this->Form->control('role_id', ['options' => $roles]); ?>
        <?php endif; ?>
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
