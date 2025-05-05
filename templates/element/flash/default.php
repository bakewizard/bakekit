<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $message
 * @var array $params
 */
$class = 'info';
if (!empty($params['class'])) {
    $class .= ' ' . $params['class'];
}
?>

<div class="alert alert-<?= h($class) ?> alert-dismissible" role="alert">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <div><i class="fa-solid fa-info-circle me-2"></i><?= h($message) ?></div>
</div>
