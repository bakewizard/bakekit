<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\Core\App;
use Cake\ORM\Entity;
use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\Utility\Text;

/**
 * Block Entity
 *
 * @property int $id
 * @property string $alias
 * @property string $title
 * @property string|null $description
 * @property int $region_id
 * @property string $cell
 * @property string|null $template
 * @property array|null $params
 * @property int|null $position
 * @property bool|null $enabled
 *
 * @property \App\Model\Entity\Region $region
 */
class Block extends Entity
{

    use TranslateTrait;

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'alias' => true,
        'title' => true,
        'description' => true,
        'region_id' => true,
        'cell' => true,
        'template' => true,
        'params' => true,
        'position' => true,
        'enabled' => true,
        'region' => true
    ];
    protected array $_virtual = [
        'cellFullName', 'cellPlugin', 'cellName', 'cellAction'
    ];

    public function parentNode()
    {
        return 'blocks';
    }

    public function nodeAlias()
    {
        return $this->alias;
    }

    public function hasConfig()
    {
        return App::classname($this->_getCellFullName() . 'CellConfig', 'Form/Cell', 'Form');
    }

    protected function _setAlias($alias)
    {
        return strtolower(Text::slug($alias));
    }

    protected function _getCellFullName()
    {
        if (isset($this->_fields['cell'])) {
            return explode('::', $this->_fields['cell'])[0];
        }
    }

    protected function _getCellPlugin()
    {
        if (isset($this->_fields['cell'])) {
            $parts = explode('.', $this->_fields['cell']);
            return count($parts) === 2 ? $parts[0] : false;
        }
    }

    protected function _getCellName()
    {
        if (isset($this->_fields['cell'])) {
            $parts = explode('.', $this->_fields['cell']);
            $cellAndAction = count($parts) === 2 ? $parts[1] : $parts[0];
            $parts = explode('::', $cellAndAction);
            return $parts[0];
        }
    }

    protected function _getCellAction()
    {
        if (isset($this->_fields['cell'])) {
            $parts = explode('::', $this->_fields['cell']);
            return count($parts) === 2 ? lcfirst($parts[1]) : 'display';
        }
    }

}
