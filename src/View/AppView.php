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
use Cake\View\Exception\MissingCellException;
use Cake\View\Exception\MissingCellTemplateException;
use Cake\View\View;
use Override;

/**
 * Application View
 *
 * Your application’s default view class
 *
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 * @property \BootstrapUI\View\Helper\FormHelper $Form
 * @property \BootstrapUI\View\Helper\HtmlHelper $Html
 * @property \BootstrapUI\View\Helper\PaginatorHelper $Paginator
 * @property \BootstrapUI\View\Helper\BreadcrumbsHelper $Breadcrumbs
 * @property \App\View\Helper\AuthHelper $Auth
 * @property \App\View\Helper\MenuHelper $Menu
 * @property \App\View\Helper\AdminMenuHelper $AdminMenu
 * @property \App\View\Helper\MediaHelper $Media
 * @property \App\View\Helper\SchemaHelper $Schema
 */
class AppView extends View
{
    private array $_regions = [];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading helpers.
     *
     * e.g. `$this->loadHelper('Html');`
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();

        $this->loadHelper('Form', ['className' => 'BootstrapUI.Form', 'grid' => [
                'left' => 2,
                'middle' => 10,
                'right' => 4,
        ]]);
        $this->loadHelper('Html', ['className' => 'BootstrapUI.Html']);
        $this->loadHelper('Paginator', ['className' => 'BootstrapUI.Paginator']);
        $this->loadHelper('Breadcrumbs', ['className' => 'BootstrapUI.Breadcrumbs']);
        $this->loadHelper('Auth');
        $this->loadHelper('Menu');

        if (!$this->isRenderingCell()) {
            $this->_regions = Cache::read('regions', 'cms');
            $this->Form->setTemplates([
                'confirmJs' => 'app.initModal({{formName}}); return false;',
            ]);
        }
    }

    /**
     * Returns a region content by alias
     *
     * @param string  $alias Region alias
     * @param array $arguments Cell argumants
     * @return string Region content
     */
    public function region(string $alias, array $arguments = []): string
    {
        if (!isset($this->_regions[$alias]) || !$this->_regions[$alias]->hasValue('blocks')) {
            return $this->request->getSession()->check('Auth.User') ? '<span class="fw-bold text-info">[' . $alias . ']</span>' : '';
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
                $html .= '<span class="fw-bold text-danger">[' . $e->getMessage() . ']</span>';
            }
        }

        return $html;
    }

    /**
     * Returns an image url
     *
     * @param \Cake\ORM\Entity|null $entity Entity
     * @param string $size Size
     * @param int $index Index
     * @return string Url
     */
    public function getImageUrl(?Entity $entity, string $size = 'md', int $index = 0): string
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

    /**
     * Checks if cell is rendered
     *
     * @return bool
     */
    protected function isRenderingCell(): bool
    {
        return str_contains($this->getTemplatePath(), 'cell');
    }
}
