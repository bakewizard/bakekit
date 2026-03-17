<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $data
 */
?>
<div id="accordion">
    <?php foreach ($data as $plugin => $links) : ?>
        <div class="card mb-2">
            <div class="card-header">
                <h2 class="card-title">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#<?= strtolower($plugin) ?>" aria-expanded="false"
                        aria-controls="accordion">
                        <?= $plugin ?>
                    </button>
                </h2>
            </div>

            <div id="<?= strtolower($plugin) ?>" class="collapse" data-bs-parent="#accordion">
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($links as $link) : ?>
                            <?php if ($menu->isSystem()) : ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">
                                            <?= preg_replace('/([A-Z])/', ' ' . '$1', $link['controller']) ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php foreach ($link['actions'] as $action) : ?>
                                            <?php $url = array_merge($link['url'], ['action' => $action]) ?>
                                            <a href="<?= $this->Url->build($url) ?>" target="_self" class="list-group-item-action btn btn-sm btn-outline-secondary">
                                                <?php $icons = ['index' => 'fa-list', 'add' => 'fa-plus', 'settings' => 'fa-gear'] ?>
                                                <i class="fa-solid <?= $icons[$action] ?>"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </li>
                            <?php else : ?>
                                <a href="<?= $this->Url->build($link['url']) ?>" target="<?= $link['target'] ?>" class="list-group-item list-group-item-action">
                                    <strong><?= __($link['summary']) ?></strong>
                                    <?php if ($link['description']) : ?>
                                        - <small class="text-muted"><?= __($link['description']) ?></small>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
