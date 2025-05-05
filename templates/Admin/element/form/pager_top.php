<?php
/**
 * @var \App\View\AppView $this
 */
$paging = $this->Paginator->params();
$showOptions = [20, 40, 60];
$currentLimit = $paging['perPage'] ?? $showOptions[0];
$currentPage = $paging['page'] ?? 1;
?>

<?php if ($paging['pageCount'] > 1): ?>
    <div class="card-header">
        <div class="row">
            <div class="col-12 col-xl-4 text-center mb-2 mb-xl-0 text-xl-start">
                <label><?= __('Show') ?>: </label>
                <div class="btn-group">
                    <button class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?= $currentLimit ?>
                    </button>
                    <div class="dropdown-menu">
                        <?php foreach ($showOptions as $i => $limit): ?>
                            <?php
                            $class = $limit === $currentLimit ? 'dropdown-item active' : 'dropdown-item';
                            $urlParams = ['limit' => ($i > 0 ? $limit : null), 'page' => ($currentPage > 1 ? $currentPage : null)];
                            ?>
                            <?=
                            $this->Html->link($limit,
                                    ['?' => array_filter(array_merge($this->request->getQueryParams(), $urlParams))],
                                    ['class' => $class, 'rel' => 'nofollow'])
                            ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-8 d-flex justify-content-center justify-content-xl-end">
                <ul class="pagination pagination-sm m-0 float-end">
                    <?= $this->Paginator->prev('<i class="fa-solid fa-backward"></i>', ['escape' => false]) ?>
                    <?= $this->Paginator->numbers(['first' => 3, 'last' => 3]) ?>
                    <?= $this->Paginator->next('<i class="fa-solid fa-forward"></i>', ['escape' => false]) ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>
