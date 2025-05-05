<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $message
 */
?>
<?php $this->assign('title', h($message)); ?>

<div class="alert alert-danger">
    <h5><i class="icon fa-solid fa-ban"></i> <?= __('Error') ?></h5>
    <strong><?= h($message) ?></strong>
</div>