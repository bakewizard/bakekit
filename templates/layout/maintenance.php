<?php
/**
 * @var \App\View\AppView $this
 */
?>
<!doctype html>
<html lang="en">
    <head>
        <?= $this->Html->charset() ?>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- Tell the browser to be responsive to screen width -->
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Maintenance mode</title>
        <?= $this->Html->css('/backend/css/app') ?>
    </head>
    <body>
        <?= $this->fetch('content') ?>
    </body>
</html>
