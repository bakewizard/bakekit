<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $roles
 */
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-list me-2"></i><?= __('Roles list') ?></div>
        <div class="card-tools">
            <?=
            $this->Form->deleteLink('<i class="fa-solid fa-redo-alt"></i> ' . __('Reload resources'), ['action' => 'reloadResources'], [
                'block' => true,
                'confirm' => __('This action will recreate all resources and clear all permissions. Do you want to proceed?'),
                'escape' => false,
                'class' => 'btn btn-sm btn-outline-danger',
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#confirm-modal'
            ])
            ?>
            <?= $this->Html->link('<i class="fa-solid fa-plus-circle"></i>', ['action' => 'add'], ['class' => 'btn btn-sm btn-outline-success', 'escape' => false]) ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col"><?= __('Name') ?></th>
                        <th scope="col" class="actions"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $id => $role): ?>
                        <tr>
                            <td><?= $role ?></td>
                            <td class="text-center actions">  
                                <?= $this->Html->link('<i class="fa-solid fa-id-card"></i>', ['action' => 'editPermissions', $id], ['escape' => false, 'class' => 'btn btn-outline-secondary']); ?>
                                <?= $this->Html->link('<i class="fa-solid fa-users"></i>', ['action' => 'view', $id], ['escape' => false, 'class' => 'btn btn-outline-primary']) ?>
                                <?= $this->Html->link('<i class="fa-solid fa-edit"></i>', ['action' => 'edit', $id, '?' => $this->request->getQueryParams()], ['escape' => false, 'class' => 'btn btn-outline-success']) ?>
                                <?=
                                $this->Form->deleteLink('<i class="fa-solid fa-trash"></i>', ['action' => 'delete', $id, '?' => $this->request->getQueryParams()],
                                        [
                                            'block' => true,
                                            'escape' => false,
                                            'confirm' => __('Are you sure you want to delete {0} role?', trim($role, '-')),
                                            'class' => 'btn btn-outline-danger',
                                            'data-bs-toggle' => 'modal',
                                            'data-bs-target' => '#confirm-modal'
                                        ]
                                )
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>