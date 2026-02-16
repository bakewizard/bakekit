<?php
/**
 * @var \App\View\AppView $this
 * @var array $config
 */
?>
<?php $this->setLayout('login'); ?>
<div class="login-box" style="width: 25rem;">
    <div class="login-logo">
        <b><?= $config['Cms']['siteName'] ?? 'BakeKit CMS' ?></b>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">
                <?= $this->Flash->render(); ?>
                <?= $this->Flash->render('auth') ?>
            </p>

            <?= $this->Form->create() ?>

            <div class="input-group mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" autofocus>
                <div class="input-group-text">
                    <span class="fa-solid fa-envelope"></span>
                </div>
            </div>
            <div class="input-group mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password">
                <div class="input-group-text">
                    <span class="fa-solid fa-lock"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-8">
                    <?= $this->Form->control('remember_me', ['type' => 'checkbox']); ?>
                </div>
                <div class="col-4 text-end">
                    <?= $this->Form->button(__('Login'), ['class' => 'btn btn-success btn-block btn-flat']); ?>
                </div>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
