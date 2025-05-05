<?php
/**
 * @var \App\View\AppView $this
 */
?>
<ul id="control-sidebar-menu" class="nav sidebar-menu flex-column" role="menu" data-accordion="false">
    <li class="nav-item">
        <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Plugins', 'action' => 'index'], ['fullBase' => true]) ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-plug fa-fw"></i>
            <p>Plugins</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Themes', 'action' => 'index'], ['fullBase' => true]) ?>" class="nav-link">
            <i class="nav-icon fa-regular fa-images fa-fw"></i>
            <p>Themes</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Regions', 'action' => 'index'], ['fullBase' => true]) ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-cubes fa-fw"></i>
            <p>Regions & Blocks</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Menus', 'action' => 'index'], ['fullBase' => true]) ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-sitemap fa-fw"></i>
            <p>Menus</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Roles', 'action' => 'index'], ['fullBase' => true]) ?>"  class="nav-link">
            <i class="nav-icon fa-solid fa-users fa-fw"></i>
            <p>Roles</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Users', 'action' => 'index'], ['fullBase' => true]) ?>"  class="nav-link">
            <i class="nav-icon fa-solid fa-user-circle fa-fw"></i>
            <p>Users</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Meta', 'action' => 'index'], ['fullBase' => true]) ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-tags fa-fw"></i>
            <p>Meta</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'settings'], ['fullBase' => true]) ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-cogs fa-fw"></i>
            <p>Settings</p>
        </a>
    </li>
</ul>