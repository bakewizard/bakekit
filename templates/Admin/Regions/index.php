<?php

/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\Region>|\Cake\Collection\CollectionInterface<\App\Model\Entity\Region> $regions
 */
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-list me-2"></i><?= __('Regions list') ?></div>
        <div class="card-tools">
            <?= $this->Html->link('<i class="fa-solid fa-plus-circle"></i>', ['action' => 'add'], ['class' => 'btn btn-sm btn-outline-success', 'escape' => false]) ?>
            <?= $this->Form->button('<i class="fa-solid fa-minus-circle"></i> ', ['form' => 'index-form', 'class' => 'btn btn-sm btn-outline-danger', 'escapeTitle' => false]) ?>
        </div>
    </div>

    <?= $this->element('form/pager_top') ?>

    <div class="card-body">
        <div class="table-responsive">
            <?= $this->Form->create(null, ['id' => 'index-form', 'url' => ['action' => 'deleteMany', '?' => $this->request->getQueryParams()]]); ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col" class="text-center"><?= $this->Form->checkbox('check-all', ['id' => 'toggle-checkbox', 'hiddenField' => false]) ?></th>
                        <th scope="col"><?= $this->Paginator->sort('alias') ?></th>
                        <th scope="col"><?= $this->Paginator->sort('description') ?></th>
                        <th scope="col" class="actions"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regions as $region) : ?>
                        <tr>
                            <td class="text-center align-middle"><?= $this->Form->checkbox('ids[]', ['hiddenField' => false, 'value' => $region->id]) ?></td>
                            <td><?= h($region->alias) ?></td>
                            <td><?= h($region->description) ?></td>
                            <td class="text-center actions">
                                <?= $this->Html->link(
                                    '<i class="fa-solid fa-cubes"></i>',
                                    ['action' => 'view', $region->id],
                                    ['escape' => false, 'class' => 'btn btn-outline-primary'],
                                ) ?>
                                <?= $this->Html->link(
                                    '<i class="fa-solid fa-edit"></i>',
                                    ['action' => 'edit', $region->id, '?' => $this->request->getQueryParams()],
                                    ['escape' => false, 'class' => 'btn btn-outline-success'],
                                ) ?>
                                <?=
                                $this->Form->deleteLink(
                                    '<i class="fa-solid fa-trash"></i>',
                                    ['action' => 'delete', $region->id, '?' => $this->request->getQueryParams()],
                                    [
                                        'block' => true,
                                        'escape' => false,
                                        'confirm' => __('Are you sure you want to delete {0}?', $region->alias),
                                        'class' => 'btn btn-outline-danger',
                                        'data-bs-toggle' => 'modal',
                                        'data-bs-target' => '#confirm-modal',
                                    ],
                                )
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?= $this->Form->end(); ?>
        </div>
    </div>

    <?= $this->element('form/pager_bottom') ?>
</div>
