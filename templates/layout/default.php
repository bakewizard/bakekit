<!DOCTYPE html>
<html>
    <head>
        <?= $this->Html->charset() ?>
        <title><?= $this->fetch('title') ?></title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= $this->Html->meta('icon') ?>
        <?= $this->Html->css('/backend/css/app') ?>
    </head>
    <body>
        <main role="main">
            <?= $this->fetch('content') ?>
        </main>

        <footer class="mt-auto py-2 bg-dark fixed-bottom">
            <div class="container text-center">
                <img src="/img/cake.power.gif" alt="CakePHP">
            </div>
        </footer>
    </body>
</html>
