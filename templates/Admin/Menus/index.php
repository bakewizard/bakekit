<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $_isSearch
 * @var \Cake\Collection\CollectionInterface<\App\Model\Entity\Menu>|array<\App\Model\Entity\Menu> $menus
 */
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-list me-2"></i><?= __('Menus list') ?></div>
        <div class="card-tools">
            <?= $this->Html->link('<i class="fa-solid fa-plus-circle"></i>', ['action' => 'add'], ['class' => 'btn btn-sm btn-outline-success', 'escape' => false]) ?>
        </div>
    </div>

    <div class="card-header">
        <?= $this->Form->create(null, ['valueSources' => 'query', 'class' => 'filter-form', 'id' => 'filter-form']); ?>
        <div class="row">
            <div class="col-sm-6">
                <?= $this->Form->control('prefix', ['options' => [0 => 'Frontend', 1 => 'Backend'], 'empty' => '---']); ?>
            </div>
            <div class="col-sm-6">
                <?= $this->Form->control('enabled', ['options' => [1 => 'Enabled', 0 => 'Disabled'], 'empty' => '---']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <button type="submit" id="button-filter" class="btn btn-outline-success"><i class="fa-solid fa-search"></i> <?= __('Filter') ?></button>
                <?php if (!empty($_isSearch)) : ?>
                    <?= $this->Html->link(
                        '<i class="fa-solid fa-times-circle"></i> ' . __('Clear'),
                        ['controller' => 'Menus', 'action' => 'index'],
                        ['class' => 'btn btn-outline-danger', 'escape' => false],
                    ) ?>
                <?php endif; ?>
            </div>
        </div>
        <?= $this->Form->end(); ?>
    </div>

    <?= $this->element('form/pager_top') ?>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col"><?= $this->Paginator->sort('name') ?></th>
                        <th scope="col"><?= $this->Paginator->sort('description') ?></th>
                        <th scope="col"><?= $this->Paginator->sort('prefix', __('Client')) ?></th>
                        <th scope="col""><?= $this->Paginator->sort('enabled') ?></th>
                        <th scope=" col" class="actions text-center"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menus as $menu) : ?>
                        <tr>
                            <td><?= h($menu->name) ?></td>
                            <td><?= h($menu->description) ?></td>
                            <td class="text-center">
                                <?= $menu->prefix ? $this->Html->badge(__('Backend'), ['class' => 'info']) : $this->Html->badge(__('Frontend'), ['classs' => 'success']) ?>
                            </td>
                            <td class="text-center">
                                <?= $menu->enabled ? '<i class="fa-solid fa-check text-success fa-lg"></i>' : '<i class="fa-solid fa-xmark text-danger fa-lg"></i>' ?>
                            </td>
                            <td class="text-center actions">
                                <?= $this->Html->link(
                                    '<i class="fa-solid fa-list"></i>',
                                    ['action' => 'view', $menu->id],
                                    ['escape' => false, 'class' => 'btn btn-outline-primary'],
                                ) ?>
                                <?= $this->Html->link(
                                    '<i class="fa-solid fa-edit"></i>',
                                    ['action' => 'edit', $menu->id, '?' => $this->request->getQueryParams()],
                                    ['escape' => false, 'class' => 'btn btn-outline-success'],
                                ) ?>
                                <?=
                                $this->Form->deleteLink(
                                    '<i class="fa-solid fa-trash"></i>',
                                    ['action' => 'delete', $menu->id, '?' => $this->request->getQueryParams()],
                                    [
                                        'block' => true,
                                        'confirm' => __('Are you sure you want to delete {0}?', $menu->name),
                                        'escape' => false,
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
        </div>
    </div>

    <?= $this->element('form/pager_bottom') ?>
</div>
