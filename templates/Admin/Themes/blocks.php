<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface<\App\Model\Entity\Region>|array<\App\Model\Entity\Region> $regions
 * @var string $activeTheme
 */
?>
<?php $this->assign('page', 'Blocks'); ?>
<div class="card card-outline card-primary mb-3">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-images me-2"></i>
            <strong><?= h($activeTheme) ?></strong>
        </div>
    </div>
</div>

<?php foreach ($regions as $region) : ?>
    <div class="card card-success card-outline mb-3">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-cubes me-2"></i>
                <strong><?= h($region->alias) ?></strong>
                <?php if ($region->description) : ?>
                    <small class="text-muted ms-2"><?= h($region->description) ?></small>
                <?php endif; ?>
            </div>
            <div class="card-tools">
                <?= $this->Html->link(
                    '<i class="fa-solid fa-plus-circle"></i>',
                    ['controller' => 'Blocks', 'action' => 'add', $region->id],
                    ['class' => 'btn btn-sm btn-success', 'escape' => false],
                ) ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($region->blocks)) : ?>
                <p class="text-muted"><?= __('No blocks yet.') ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?= __('Alias') ?></th>
                                <th><?= __('Title') ?></th>
                                <th><?= __('Cell') ?></th>
                                <th><?= __('Enabled') ?></th>
                                <th></th>
                                <th class="actions text-center"><?= __('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($region->blocks as $block) : ?>
                                <tr>
                                    <td><?= h($block->alias) ?></td>
                                    <td><?= h($block->title) ?></td>
                                    <td class="text-center">
                                        <strong><?= $block->cell ?? '-----Text content-----' ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?= $block->enabled
                                            ? '<i class="fa-solid fa-check text-success fa-lg"></i>'
                                            : '<i class="fa-solid fa-xmark text-danger fa-lg"></i>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $this->Form->postLink(
                                            '<i class="fa-solid fa-arrow-down"></i>',
                                            ['controller' => 'Blocks', 'action' => 'moveDown', $block->id],
                                            ['escape' => false, 'class' => 'btn btn-outline-secondary'],
                                        ) ?>
                                        <?= $this->Form->postLink(
                                            '<i class="fa-solid fa-arrow-up"></i>',
                                            ['controller' => 'Blocks', 'action' => 'moveUp', $block->id],
                                            ['escape' => false, 'class' => 'btn btn-outline-secondary'],
                                        ) ?>
                                    </td>
                                    <td class="actions text-center">
                                        <?php if ($block->hasValue('cell') && $block->hasConfig()) : ?>
                                            <?= $this->Html->link(
                                                '<i class="fa-solid fa-cog"></i>',
                                                ['controller' => 'Blocks', 'action' => 'config', $block->id],
                                                ['escape' => false, 'class' => 'btn btn-outline-secondary'],
                                            ) ?>
                                        <?php endif; ?>
                                        <?= $this->Html->link(
                                            '<i class="fas fa-edit"></i>',
                                            ['controller' => 'Blocks', 'action' => 'edit', $block->id],
                                            ['escape' => false, 'class' => 'btn btn-outline-success'],
                                        ) ?>
                                        <?= $this->Form->deleteLink(
                                            '<i class="fa-solid fa-trash"></i>',
                                            ['controller' => 'Blocks', 'action' => 'delete', $block->id],
                                            [
                                                'block' => true,
                                                'escape' => false,
                                                'confirm' => __('Are you sure you want to delete {0}?', $block->title),
                                                'class' => 'btn btn-outline-danger',
                                                'data-bs-toggle' => 'modal',
                                                'data-bs-target' => '#confirm-modal',
                                            ],
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
