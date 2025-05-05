<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 */
?>
<div class="error-page">
    <h2 class="headline text-danger">503</h2>
    <div class="error-content">
        <h3><i class="fa-solid fa-exclamation-triangle text-danger"></i> <?= __('Maintenance mode') ?></h3>
        <p><?= $message ?></p>
    </div>
</div>
