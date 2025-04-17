<?php $this->assign('title', h($message)); ?>

<div class="alert alert-warning">
    <h5><i class="icon fa-solid fa-exclamation-triangle"></i> <?= __('Warning') ?></h5>
    <strong><?= h($message) ?></strong>
</div>