<div class="card card-default">
    <div class="card-header with-border">
        <div class="card-title"><i class="fa-solid fa-list me-2"></i><?= __('Installed themes') ?></div>
        <div class="card-tools">
            <?= __('Active theme') ?> : <Strong><?= $activeTheme ?? '-----' ?></strong>
        </div>
    </div>
    <div class="card-header">
        <?= $this->Form->create(null, ['align' => 'horizontal', 'type' => 'file', 'url' => ['action' => 'install']]) ?>
        <?=
        $this->Form->control('theme', [
            'type' => 'file',
            'class' => 'custom-file border',
            'label' => __('Upload new theme'),
            'spacing' => 'mb-0',
            'append' => $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Install'), ['class' => 'btn-success', 'escapeTitle' => false])
        ]);
        ?>
        <?= $this->Form->end() ?>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="banner_images">
                <thead>
                    <tr>
                        <th><?= __('Theme') ?></th>
                        <th><?= __('Description') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($themes as $theme): ?>
                        <tr>
                            <td>
                                <h5>
                                    <?= $this->Html->link($theme['name'], ['action' => 'view', $theme['name']], ['class' => 'text-decoration-none']) ?>
                                </h5>
                            </td>
                            <td><?= h($theme['description']) ?></td>
                            <td class="text-center">
                                <?php if ($theme['name'] !== $activeTheme): ?>
                                    <?= $this->Form->postLink('Activate', ['action' => 'activate', $theme['name']], ['class' => 'btn btn-sm btn-success']) ?>
                                    <?=
                                    $this->Form->postLink('Delete', ['action' => 'uninstall', $theme['name']],
                                            [
                                                'method' => 'delete',
                                                'block' => true,
                                                'confirm' => __('Are you sure you want to uninstall {0} theme?', $theme['name']),
                                                'class' => 'btn btn-sm btn-danger',
                                                'data-bs-toggle' => 'modal',
                                                'data-bs-target' => '#confirm-modal'
                                            ]
                                    )
                                    ?>
                                <?php else: ?>
                                    <?= $this->Form->postLink('Deactivate', ['action' => 'activate'], ['class' => 'btn btn-sm btn-warning']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
