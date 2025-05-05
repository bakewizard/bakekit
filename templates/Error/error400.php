<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $message
 * @var mixed $url
 */
$this->layout = 'error';
$this->assign('title', $message);
?>

<div class="mb-4 p-3 bg-light">
    <h1 class="display-4 text-center"><?= h($message) ?></h1>
    <p class="lead text-center"><?= __d('cake', 'The requested address {0} was not found on this server.', "<strong>'{$url}'</strong>") ?></p>
    <p class="text-center">
        <?= $this->Html->link('Go back', ['prefix' => 'Admin', 'controller' => 'Dashboard'], ['escape' => false, 'class' => 'btn btn-success']) ?>
    </p>
</div>