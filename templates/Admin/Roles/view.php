<div class="card card-primary card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-eye me-2"></i><?= __('Users') ?> of <strong><?= $role->name ?></strong> role</div>
    </div>
    <div class="card-body table-responsive p-0">
        <?php if (!empty($role->users)): ?>
            <table class="table table-hover">
                <tr>
                    <th scope="col"><?= __('First Name') ?></th>
                    <th scope="col"><?= __('Last Name') ?></th>
                    <th scope="col"><?= __('Email') ?></th>
                    <th scope="col"><?= __('Created') ?></th>
                    <th scope="col"><?= __('Modified') ?></th>
                    <th scope="col" class="actions"><?= __('Actions') ?></th>
                </tr>
                <?php foreach ($role->users as $user): ?>
                    <tr>
                        <td><?= h($user->first_name) ?></td>
                        <td><?= h($user->last_name) ?></td>
                        <td><?= h($user->email) ?></td>
                        <td><?= h($user->created) ?></td>
                        <td><?= h($user->modified) ?></td>
                        <td class="text-center actions">
                            <?= $this->Html->link('<i class="fa-solid fa-edit"></i>', ['controller' => 'Users', 'action' => 'edit', $user->id], ['escape' => false, 'class' => 'btn btn-outline-success']) ?>
                            <?=
                            $this->Form->deleteLink('<i class="fa-solid fa-trash"></i>', ['controller' => 'Users', 'action' => 'delete', $user->id],
                                    [
                                        'block' => true,
                                        'escape' => false,
                                        'confirm' => __('Are you sure you want to delete {0}?', $user->full_name),
                                        'class' => 'btn btn-outline-danger',
                                        'data-bs-toggle' => 'modal',
                                        'data-bs-target' => '#confirm-modal'
                                    ]
                            )
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
