<?php $this->assign('title', 'Welcome to BakeKit CMS'); ?>

<div class="mb-4 p-3 bg-light">
    <h1 class="display-4 text-center">BakeKit</h1>
    <p class="lead text-center">The first truly programmer-friendly CMS.</p>
    <p class="text-center">
        <?= $this->Html->link('Enter admin panel', ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'login'], ['escape' => false, 'class' => 'btn btn-outline-success']) ?>
    </p>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card shadow">
                <div class="card-body">
                    <p class="text-center"><i class="fa-solid fa-car-battery fa-5x"></i></p>
                    <h2 class="text-center">Powerful</h2>
                    <p>Fully written in CakePHP framework that gives you the ability to write more clean code using all the power of the CakePHP framework.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card shadow">
                <div class="card-body">
                    <p class="text-center"><i class="fa-solid fa-sliders-h fa-5x"></i></p>
                    <h2 class="text-center">Customizable</h2>
                    <p>Fully customizable admin panel. You can customize it by adding your menu items from plugins and adapt it to your site's needs.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card shadow">
                <div class="card-body">
                    <p class="text-center"><i class="fa-solid fa-laptop-code fa-5x"></i></p>
                    <h2 class="text-center">Programmer-friendly</h2>
                    <p>The first CMS built for programmers. Easily extend it with your own plugins and themes, just by following the CakePHP cookbook.</p>
                </div>
            </div>
        </div>
    </div>
</div>