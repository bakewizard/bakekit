<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $menuLinks
 * @var \App\Model\Entity\Menu $menu
 */
?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-eye me-2"></i><strong><?= h($menu->name) ?></strong> <?= __('links') ?></div>
        <div class="card-tools">
            <?= $this->Html->link('<i class="fa-solid fa-plus-circle"></i>', ['controller' => 'MenuLinks', 'action' => 'add', $menu->id], ['class' => 'btn btn-sm btn-outline-success', 'escape' => false]) ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col"><?= __('Title') ?></th>
                        <th scope="col"> </th>
                        <th scope="col" class="actions text-center"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <?php foreach ($menuLinks as $id => $link): ?>
                    <tr>
                        <td><?= h($link) ?></td>
                        <td class="text-center">
                            <?= $this->Form->postLink('<i class="fa-solid fa-arrow-down"></i>', ['controller' => 'MenuLinks', 'action' => 'moveDown', $id], ['escape' => false, 'class' => 'btn btn-outline-secondary']) ?>
                            <?= $this->Form->postLink('<i class="fa-solid fa-arrow-up"></i>', ['controller' => 'MenuLinks', 'action' => 'moveUp', $id], ['escape' => false, 'class' => 'btn btn-outline-secondary']) ?>
                        </td>
                        <td class="actions text-center">
                            <?= $this->Html->link('<i class="fa-solid fa-edit"></i>', ['controller' => 'MenuLinks', 'action' => 'edit', $id], ['escape' => false, 'class' => 'btn btn-outline-success']) ?>
                            <?=
                            $this->Form->deleteLink('<i class="fa-solid fa-trash"></i>', ['controller' => 'MenuLinks', 'action' => 'delete', $id],
                                    [
                                        'block' => true,
                                        'escape' => false,
                                        'confirm' => __('Are you sure you want to delete {0}?', $link),
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
        </div>
    </div>
</div>
