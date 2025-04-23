<div class="card">
    <div class="card-header">
        <?= $this->Form->create(null, ['align' => 'horizontal', 'type' => 'file', 'url' => ['action' => 'install']]) ?>
        <?=
        $this->Form->control('plugin', [
            'type' => 'file',
            'class' => 'custom-file border',
            'label' => __('Install a new plugin'),
            'spacing' => 'mb-0',
            'append' => $this->Form->button('<i class="fa-solid fa-save me-2"></i>' . __('Install'), ['class' => 'btn-success', 'escapeTitle' => false])
        ]);
        ?>
        <?= $this->Form->end() ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col">Plugin</th>
                        <th scope="col">Alias</th>
                        <th scope="col">Description</th>
                        <th scope="col">Parent Plugin</th>
                        <th scope="col" class="actions"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plugins as $plugin): ?>
                        <tr>
                            <td>
                                <h5><?= h($plugin['name']) ?></h5>
                                <div class="pt-1">
                                    <?php if (isset($plugin['id']) && $plugin['enabled']): ?>
                                        <?= $this->Form->postLink('Deactivate', ['action' => 'deactivate', $plugin['id']], ['block' => true, 'class' => 'btn btn-sm btn-warning']) ?>
                                    <?php else: ?>
                                        <?= $this->Form->postLink('Activate', ['action' => 'activate', $plugin['name']], ['block' => true, 'class' => 'btn btn-sm btn-success']) ?>
                                        <?=
                                        $this->Form->deleteLink('Uninstall', ['action' => 'uninstall', $plugin['name']],
                                                [
                                                    'block' => true,
                                                    'confirm' => __('Are you sure you want to uninstall "{0}"?', $plugin['name']),
                                                    'class' => 'btn btn-sm btn-danger',
                                                    'data-bs-toggle' => 'modal',
                                                    'data-bs-target' => '#confirm-modal'
                                                ]
                                        )
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?= $plugin['alias'] ?? '-------' ?></strong>
                            </td>
                            <td><?= h($plugin['description']) ?></td>
                            <td class="text-center">
                                <strong><?= $plugin['parent_plugin'] ?? '-------' ?></strong>
                            </td>
                            <td class="text-center">
                                <?php if (isset($plugin['id'])): ?>
                                    <?= $this->Html->link('<i class="fa-solid fa-edit"></i>', ['action' => 'edit', $plugin['id']], ['escape' => false, 'class' => 'btn btn-outline-success']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
