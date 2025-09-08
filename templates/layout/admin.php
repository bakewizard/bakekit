<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $breadcrumbs
 */
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= $this->fetch('title') ?></title>
        <?= $this->Html->charset() ?>
        <?= $this->Html->meta('viewport', 'width=device-width, initial-scale=1') ?>
        <?= $this->Html->meta('icon') ?>
        <?= $this->fetch('meta') ?>
        <?= $this->Html->css('/backend/css/app') ?>
        <?= $this->fetch('css') ?>
    </head>
    <body class="layout-fixed sidebar-mini sidebar-expand-lg bg-body-tertiary">
        <!--begin::App Wrapper-->
        <div class="app-wrapper">
            <!--begin::Header-->
            <nav class="app-header navbar navbar-expand bg-body">
                <!--begin::Container-->
                <div class="container-fluid">
                    <!--begin::Left Navbar Links-->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" data-lte-toggle="sidebar" role="button" href="#"><i class="fa-solid fa-bars"></i></a>
                        </li>
                    </ul>
                    <!--end::Left Navbar Links-->

                    <!--begin::Custom Menu-->
                    <?= $this->region('custom-menu'); ?>
                    <!--end::Custom menu-->

                    <!--begin::Right Navbar links-->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $this->Url->build(['prefix' => false, 'plugin' => false, 'controller' => 'Index'], ['fullBase' => true]) ?>" target="_blank" title="Back to site">
                                <i class="fa-solid fa-reply"></i>
                            </a>
                        </li>
                        <!--begin::Theme switcher-->
                        <div id="theme-switcher" class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-sun"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="#" class="dropdown-item active" data-bs-theme="light"><i class="fa-solid fa-sun fa-fw me-2"></i>Light</a></li>
                                <li><a href="#" class="dropdown-item" data-bs-theme="dark"><i class="fa-solid fa-moon fa-fw me-2"></i>Dark</a></li>
                                <li><a href="#" class="dropdown-item" data-bs-theme="auto"><i class="fa-solid fa-circle-half-stroke fa-fw me-2"></i>Auto</a></li>
                            </ul>
                        </div>
                        <!--end::Theme switcher-->

                        <!--begin::Control sidebar-->
                        <li class="nav-item dropdown">
                            <a class="nav-link" data-bs-toggle="offcanvas" href="#control-sidebar" role="button" title="Site management">
                                <i class="fa-solid fa-cogs"></i>
                            </a>
                        </li>
                        <!--end::Control sidebar-->

                        <!--begin::User Menu Dropdown-->
                        <li class="nav-item dropdown user-menu">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <?= $this->Html->image($this->getImageUrl($this->getRequest()->getAttribute('identity'), 'th'), ['class' => 'user-image rounded-circle shadow']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                                <!--begin::User Image-->
                                <li class="user-header">
                                    <?= $this->Html->image($this->getImageUrl($this->getRequest()->getAttribute('identity'), 'lg'), ['class' => 'rounded-circle shadow']); ?>
                                    <p><?= $this->Auth->get('full_name') ?></p>
                                </li>
                                <!--end::User Image-->

                                <!--begin::Menu Footer-->
                                <li class="user-footer">
                                    <?= $this->Html->link('<i class="fa-solid fa-user"></i> Profile', ['plugin' => false, 'controller' => 'Users', 'action' => 'edit', $this->Auth->get('id')], ['class' => 'btn btn-outline-success btn-flat', 'escape' => false]) ?>
                                    <?= $this->Html->link('<i class="fa-solid fa-sign-out-alt"></i> Sign out', ['plugin' => false, 'controller' => 'Users', 'action' => 'logout', $this->Auth->get('id')], ['class' => 'btn btn-outline-danger btn-flat float-end', 'escape' => false]) ?>
                                </li>
                                <!--end::Menu Footer-->
                            </ul>
                        </li>
                        <!--end::User Menu Dropdown-->
                    </ul>
                    <!--end::Right Navbar links-->
                </div>
                <!--end::Container-->
            </nav>
            <!--end::Header-->

            <!--begin::Sidebar-->
            <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
                <!--begin::Sidebar Brand-->
                <div class="sidebar-brand">
                    <!--begin::Brand Link-->
                    <a href="/admin/" class="brand-link">
                        <!--begin::Brand Image-->
                        <img src="/img/logo.png" alt="Logo" class="brand-image opacity-75 shadow">
                        <!--end::Brand Image-->
                        <!--begin::Brand Text-->
                        <span class="brand-text fw-light">BakeKit</span>
                        <!--end::Brand Text-->
                    </a>
                    <!--end::Brand Link-->
                </div>
                <!--end::Sidebar Brand-->

                <!--begin::Sidebar Wrapper-->
                <div class="sidebar-wrapper">
                    <nav class="mt-2">
                        <!--begin::Sidebar Menu-->
                        <?= $this->region('plugins-menu', ['AdminMenu']); ?>
                        <!--end::Sidebar Menu-->
                    </nav>
                </div>
                <!--end::Sidebar Wrapper-->
            </aside>
            <!--end::Sidebar-->

            <!--begin::App Main-->
            <main class="app-main">
                <!-- Content Header (Page header) -->
                <div class="app-content-header">
                    <?php
                    $plugin = $this->request->getParam('plugin');
                    $controller = $this->request->getParam('controller');
                    $action = $this->request->getParam('action');
                    ?>
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-6">
                                <h3 class="mb-0">
                                    <span class="me-2"><?= $this->fetch('page') ?: preg_replace('/([A-Z])/', " " . '$1', $controller) ?></span>
                                    <?php if ($action === 'view'): ?>
                                        <?=
                                        $this->Html->link('<i class="fa-solid fa-arrow-left"></i> ' . __('Back'), [
                                            'plugin' => $plugin, 'controller' => $controller, 'action' => 'index', '?' => $this->request->getQueryParams()
                                                ], ['class' => 'btn btn-outline-danger', 'escape' => false])
                                        ?>
                                    <?php endif; ?>
                                </h3>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-end">
                                    <?php if (isset($plugin) || $controller !== 'Dashboard'): ?>
                                        <li class="breadcrumb-item">
                                            <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Dashboard']); ?>">
                                                <i class="fa-solid fa-tachometer-alt"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php foreach ($breadcrumbs as $i => $crumb): ?>
                                        <li class="breadcrumb-item">
                                            <a href="<?= $crumb['url'] ?>"><?= $crumb['title'] ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div><!-- /.col -->
                        </div><!-- /.row -->
                    </div><!-- /.container-fluid -->
                </div>
                <!-- /.content-header -->

                <!-- Main content -->
                <div class="app-content">
                    <div class="container-fluid">
                        <?= $this->Flash->render() ?>
                        <?= $this->fetch('content') ?>
                    </div>
                    <?= $this->fetch('postLink') ?>
                </div>
                <!-- /.content -->
            </main>
            <!--end::App Main-->

            <!--begin::Footer-->
            <footer class="app-footer">
                <div class="float-end d-none d-sm-inline">
                    <a class="nav-link" href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'info'], ['fullBase' => true]) ?>" target="_blank" title="PHP info">
                        <i class="fa-brands fa-php fa-xl"></i>
                    </a>
                </div>
                <strong>
                    &copy; 2015-<?= date("Y") ?>
                </strong>
            </footer>
            <!--end::Footer-->

            <!--begin::Control Sidebar-->
            <aside class="offcanvas offcanvas-end bg-body-secondary text-bg-white shadow" tabindex="-1" id="control-sidebar" data-bs-theme="dark">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title">SITE MANAGEMENT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <?= $this->element('/layout/control_sidebar') ?>
                </div>
            </aside>
            <!--end::Control Sidebar-->

            <?= $this->element('/form/confirm_modal') ?>

        </div>
        <!--end::App Wrapper-->
        <?= $this->Html->script('/backend/js/app') ?>
        <?= $this->fetch('script') ?>
    </body>
</html>
