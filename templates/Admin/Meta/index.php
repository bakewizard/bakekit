<?php
/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\Metum>|\Cake\Collection\CollectionInterface<\App\Model\Entity\Metum> $meta
 */
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-list me-2"></i><?= __('Meta list') ?></div>
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
                        <th scope="col"><?= $this->Paginator->sort('plugin_id') ?></th>
                        <th scope="col"><?= $this->Paginator->sort('title') ?></th>
                        <th scope="col" class="actions text-center"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meta as $metum) : ?>
                        <tr>
                            <td class="text-center align-middle"><?= $this->Form->checkbox('ids[]', ['hiddenField' => false, 'value' => $metum->id]) ?></td>
                            <td><strong><?= h($metum->plugin->name) ?></strong></td>
                            <td><?= $this->Text->truncate($metum->title, 50) ?></td>
                            <td class="text-center actions">
                                <?= $this->Html->link(
                                    '<i class="fa-solid fa-edit"></i>',
                                    ['action' => 'edit', $metum->id, '?' => $this->request->getQueryParams()],
                                    ['escape' => false, 'class' => 'btn btn-outline-success'],
                                ) ?>
                                <?=
                                $this->Form->deleteLink(
                                    '<i class="fa-solid fa-trash"></i>',
                                    ['action' => 'delete', $metum->id, '?' => $this->request->getQueryParams()],
                                    [
                                        'block' => true,
                                        'escape' => false,
                                        'confirm' => __('Are you sure you want to delete # {0}?', $metum->id),
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
