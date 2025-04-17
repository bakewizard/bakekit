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
        <header>
            <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#header-navbar" aria-controls="header-navbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="header-navbar">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" target="_blank" href="https://book.cakephp.org/5/">Documentation</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" target="_blank" href="https://api.cakephp.org/5.1/">API</a>
                        </li>
                    </ul>
                    <form class="form-inline my-2 my-lg-0">
                        <a target="_blank" href="https://cakephp.org/">
                            <img src="/img/cake.icon.png" width="30" height="30" alt="CakePHP">
                        </a>
                    </form>
                </div>
            </nav>
        </header>

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
