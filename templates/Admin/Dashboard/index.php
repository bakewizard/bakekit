<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="card">
    <div class="card-body">
        <h3>Welcome to BakeKit!</h3>
        <p class="text-muted">Here are the first steps to start working:</p>
        <ol>
            <li><?= $this->Html->link(
                '<i class="fa-solid fa-fw fa-plug"></i> ' . __('Load plugins'),
                ['controller' => 'Plugins'],
                ['escape' => false, 'class' => 'text-decoration-none'],
            ) ?></li>
            <li><?= $this->Html->link(
                '<i class="fa-regular fa-fw fa-images"></i> ' . __('Select theme'),
                ['controller' => 'Themes'],
                ['escape' => false, 'class' => 'text-decoration-none'],
            ) ?></li>
            <li><?= $this->Html->link(
                '<i class="fa-solid fa-fw fa-cubes"></i> ' . __('Add some regions and blocks'),
                ['controller' => 'Regions'],
                ['escape' => false, 'class' => 'text-decoration-none'],
            ) ?></li>
            <li><?= $this->Html->link(
                '<i class="fa-solid fa-fw fa-sitemap"></i> ' . __('Add menus'),
                ['controller' => 'Menus'],
                ['escape' => false, 'class' => 'text-decoration-none'],
            ) ?></li>
            <li><?= $this->Html->link(
                '<i class="fa-regular fa-fw fa-eye"></i> ' . __('View your site'),
                $this->homeUrl(),
                ['escape' => false, 'class' => 'text-decoration-none', 'target' => '_blank'],
            ) ?></li>
        </ol>
    </div>
</div>
