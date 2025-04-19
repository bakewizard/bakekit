<?php

declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace App\View;

use Cake\Cache\Cache;
use Cake\ORM\Entity;
use Cake\View\View;
use Cake\View\Exception\MissingCellException;
use Cake\View\Exception\MissingCellTemplateException;

/**
 * Application View
 *
 * Your application’s default view class
 *
 * @link http://book.cakephp.org/3.0/en/views.html#the-app-view
 */
class AppView extends View
{

    private $_regions = [];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading helpers.
     *
     * e.g. `$this->loadHelper('Html');`
     *
     * @return void
     */
    #[\Override]
    public function initialize(): void
    {
        parent::initialize();

        $this->loadHelper('Form', ['className' => 'BootstrapUI.Form', 'grid' => [
                'left' => 2,
                'middle' => 10,
                'right' => 4
        ]]);
        $this->loadHelper('Html', ['className' => 'BootstrapUI.Html']);
        $this->loadHelper('Paginator', ['className' => 'BootstrapUI.Paginator']);
        $this->loadHelper('Breadcrumbs', ['className' => 'BootstrapUI.Breadcrumbs']);
        $this->loadHelper('Auth');
        $this->loadHelper('Menu');

        if (!$this->isRenderingCell()) {
            $this->_regions = Cache::read('regions', 'cms');
            $this->Form->setTemplates([
                'confirmJs' => 'app.initModal({{formName}}); return false;'
            ]);
        }
    }

    public function region($alias, array $arguments = [])
    {
        if (!isset($this->_regions[$alias])) {
            return $this->request->getSession()->check('Auth.User') ? '<span class="fw-bold text-info">[{$alias}]</span>' : '';
        }

        $html = '';
        $lang = $this->request->getParam('lang');
        foreach ($this->_regions[$alias]->blocks as $block) {
            if ($block->isEmpty('cell')) {
                $html .= $lang ? ($block->translation($lang)->params ?? $block->params) : $block->params;
                continue;
            }

            if ($lang && isset($block->translation($lang)->title)) {
                $block->title = $block->translation($lang)->title;
            }

            $options = ['block' => $block, 'parentView' => $this];
            try {
                $cell = $this->cell($block->cell, $arguments, $options);
                $html .= $cell->render(!empty($block->template) ? $block->template : null);

                $cellView = $cell->getView();
                $css = $cellView->fetch('css');
                $script = $cellView->fetch('script');
                if (!empty($css)) {
                    $this->prepend('css', $css);
                }
                if (!empty($script)) {
                    $this->prepend('script', $script);
                }
            } catch (MissingCellException | MissingCellTemplateException $e) {
                $html .= '<span class="fw-bold text-danger">' . $e->getMessage() . '</span>';
            }
        }
        return $html;
    }

    public function getImageUrl(?Entity $entity, $size = 'md', $index = 0): string
    {
        $image = null;

        if (!is_null($entity)) {
            $image = $entity->files[$index] ?? $entity;
        }

        $outputFormat = $this->get('config')['Cms']['images']['format'];

        $imagePath = '/img/noimage.svg';
        if ($image) {
            $name = $image->id . '-' . $size . '.' . $outputFormat;
            $absPath = WWW_ROOT . 'media' . $image->path;
            if (is_file($absPath . DIRECTORY_SEPARATOR . $name)) {
                $imagePath = '/' . basename(WWW_ROOT . 'media') . $image->path . '/' . $name;
            }
        }

        return $this->Url->build($imagePath, ['fullBase' => true]);
    }

    protected function isRenderingCell(): bool
    {
        return str_contains($this->getTemplatePath(), 'cell');
    }
}
