<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $message
 */
?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <div><i class="fa-solid fa-ban me-2"></i><?= h($message) ?></div>
</div>