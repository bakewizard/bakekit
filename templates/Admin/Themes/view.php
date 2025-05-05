<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $activeTheme
 * @var array $theme
 */
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-eye me-2"></i><?= $theme['name'] ?></div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <tr>
                <th><?= __('Name') ?></th>
                <td><?= h($theme['name']) ?></td>
            </tr>
            <tr>
                <th><?= __('Description') ?></th>
                <td><?= h($theme['description']) ?></td>
            </tr>
            <tr>
                <th><?= __('License') ?></th>
                <td><?= h($theme['license']) ?></td>
            </tr>
            <tr>
                <th><?= __('Active') ?></th>
                <td> <?= $theme['name'] == $activeTheme ? '<i class="fa-solid fa-check text-success fa-lg"></i>' : '<i class="fa-solid fa-xmark text-danger fa-lg"></i>' ?></td>
            </tr>
        </table>
    </div>
</div>
