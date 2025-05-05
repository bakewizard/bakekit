<?php
/**
 * @var \App\View\AppView $this
 */
?>
<?php if ($this->Paginator->params()['pageCount'] > 1): ?>
    <div class="card-footer">
        <div class="row">
            <div class="col-12 col-xl-4 text-center mb-2 mb-xl-0 text-xl-start">
                <?= $this->Paginator->counter(__('Page {{page}}-{{pages}} of {{count}} records')); ?>
            </div>
            <div class="col-12 col-xl-8 d-flex justify-content-center justify-content-xl-end">
                <ul class="pagination pagination-sm m-0 float-end">
                    <?= $this->Paginator->prev('<i class="fa-solid fa-backward"></i>', ['escape' => false]); ?>
                    <?= $this->Paginator->numbers(['first' => 3, 'last' => 3]); ?>
                    <?= $this->Paginator->next('<i class="fa-solid fa-forward"></i>', ['escape' => false]); ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>