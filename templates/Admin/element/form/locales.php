<?php
/**
 * @var \App\View\AppView $this
 * @var array $config
 */
?>
<?php $entityLocale = $this->request->getQuery('locale') ?? $config['App']['I18n']['currentLanguage'] ?>

<div class="btn-group">
    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <?= strtoupper($entityLocale) ?>
    </button>
    <div class="dropdown-menu">
        <?php foreach ($config['App']['I18n']['languages'] as $language): ?>
            <?php $locale = ($language === $config['App']['I18n']['defaultLanguage']) ? null : $language; ?>
            <?= $this->Html->link($language, ['?' => ['locale' => $locale] + $this->request->getQueryParams()] + $this->request->getParam('pass'), ['class' => ($language === $entityLocale) ? 'dropdown-item active' : 'dropdown-item text-body']) ?>
        <?php endforeach; ?>
    </div>
</div>