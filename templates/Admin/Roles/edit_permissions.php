<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $allowed
 * @var mixed $inherited
 * @var mixed $resources
 * @var \App\Model\Entity\Role $role
 */
?>
<div class="card">
    <div class="card-header with-border">
        <div class="card-title"><i class="fa-solid fa-list me-2"></i><strong><?= $role->name ?></strong> <?= __('permissions') ?></div>
        <?php if (!$role->isRoot()) : ?>
            <div class="card-tools">
                <?=
                $this->Form->deleteLink('<i class="fa-solid fa-trash-alt"></i> ' . __('Reset permissions'), ['action' => 'resetPermissions', $role->id], [
                    'block' => true,
                    'confirm' => __('Do you really want to reset all permissions?'),
                    'escape' => false,
                    'class' => 'btn btn-outline-danger',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#confirm-modal',
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>

    <?php echo $this->Form->create(); ?>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th><?= __('Resources') ?></th>
                        <th><?= $role->name ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $path => $resource) : ?>
                        <tr>
                            <td>
                                <?php $indentsCount = substr_count($path, '/'); ?>
                                <?php $indents = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $indentsCount) ?>

                                <?php if ($indentsCount === 0) : ?>
                                    <b><?= $resource['alias'] ?></b>
                                <?php elseif ($indentsCount === 1) : ?>
                                    <b><?= $indents ?>[<?= $resource['alias'] ?>]</b>
                                <?php elseif ($indentsCount === 2) : ?>
                                    <b><i><?= $indents . $resource['alias'] ?></i></b>
                                <?php else : ?>
                                    <i><?= $indents . $resource['alias'] ?></i>
                                <?php endif; ?>
                            </td>
                            <?php ['allowed' => $allowed, 'inherited' => $inherited] = $resource['permissions']; ?>
                            <?php $name = "perms.{$resource['id']}.{$role->id}"; ?>
                            <?php $value = $inherited ? 'inherit' : ($allowed ? 'allow' : 'deny'); ?>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text" style="color:hsl(<?= $allowed ? 120 : 0 ?>, 60%, <?= $inherited ? 70 : 50 ?>%)">
                                        <i class="fa-solid fa-lg fa-<?= $allowed ? 'check' : 'times' ?>-circle"></i>
                                    </span>
                                    <?php if ($role->isRoot()) : ?>
                                        <?= $this->Form->text($name, ['value' => ucfirst($value), 'disabled' => true]) ?>
                                    <?php else : ?>
                                        <?php $options = ['allow' => __('Allow'), 'deny' => __('Deny'), 'inherit' => __('Inherit')] ?>
                                        <?php unset($options[$value]) ?>
                                        <?= $this->Form->select($name, $options, ['empty' => ucfirst($value), 'class' => 'custom-select']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer">
        <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), ['class' => 'btn-outline-success float-end', 'escapeTitle' => false]) ?>
        <?= $this->Html->link('<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'), ['action' => 'index'], ['class' => 'btn btn-outline-danger', 'escape' => false]) ?>
    </div>

    <?php echo $this->Form->end(); ?>
</div>
