<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $data
 */
?>
<div id="accordion">
    <?php foreach ($data as $plugin => $links): ?>
        <div class="card mb-2">
            <div class="card-header">
                <h2 class="card-title">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#<?= strtolower($plugin) ?>" aria-expanded="false" aria-controls="accordion">
                        <?= $plugin ?>
                    </button>
                </h2>
            </div>

            <div id="<?= strtolower($plugin) ?>" class="collapse" data-bs-parent="#accordion">
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($links as $link): ?>
                            <a href="<?= $this->Url->build($link['url']) ?>" target="<?= $link['target'] ?>" class="list-group-item list-group-item-action">
                                <strong><?= $link['summary'] ?></strong> - <small class="text-muted"><?= $link['description'] ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
