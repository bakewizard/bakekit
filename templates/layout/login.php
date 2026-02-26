<?php
/**
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html>

<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <?= $this->Html->css('/backend/css/app') ?>
    <?= $this->Html->script('/backend/js/app', ['type' => 'module']) ?>
</head>

<body class="login-page bg-body-secondary app-loaded">
    <?= $this->fetch('content') ?>
</body>

</html>
