<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\Core\App;
use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * Block Entity
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $description
 * @property int $region_id
 * @property string|null $cell
 * @property string|null $template
 * @property string|null $params
 * @property int|null $position
 * @property bool $enabled
 *
 * @property \App\Model\Entity\Region $region
 * @property-read string $cell_full_name
 * @property-read string|false $cell_plugin
 * @property-read string $cell_name
 * @property-read string|null $cell_action
 * @property array<\Cake\ORM\Entity> $_i18n
 */
class Block extends Entity
{
    use TranslateTrait;

    /**
     * @inheritDoc
     */
    protected array $_accessible = [
        'title' => true,
        'description' => true,
        'region_id' => true,
        'cell' => true,
        'template' => true,
        'params' => true,
        'position' => true,
        'enabled' => true,
        'region' => true,
    ];
    protected array $_virtual = [
        'cellFullName', 'cellPlugin', 'cellName', 'cellAction',
    ];

    /**
     * Checks if a configuration form class exists for this block's cell.
     *
     * @return bool true if it exists, false otherwise.
     */
    public function hasConfig(): bool
    {
        $class = App::classname($this->_getCellFullName() . 'CellConfig', 'Form/Cell', 'Form');

        return is_null($class) ? false : true;
    }

    /**
     * Gets the full class name of the cell.
     *
     * @return string The full cell class name, or '' if the 'cell' field is not set.
     * @see \App\Model\Entity\Block::$cell_full_name
     */
    protected function _getCellFullName(): string
    {
        if (isset($this->_fields['cell'])) {
            return explode('::', $this->_fields['cell'])[0];
        }

        return '';
    }

    /**
     * Gets the plugin name of the cell, if any.
     *
     * @return string|false The plugin name, false if no plugin is specified or if the 'cell' field is not set.
     * @see \App\Model\Entity\Block::$cell_plugin
     */
    protected function _getCellPlugin(): string|false
    {
        if (isset($this->_fields['cell'])) {
            $parts = explode('.', $this->_fields['cell']);

            return count($parts) === 2 ? $parts[0] : false;
        }

        return false;
    }

    /**
     * Gets the short name of the cell.
     *
     * @return string The cell name, or '' if the 'cell' field is not set.
     * @see \App\Model\Entity\Block::$cell_name
     */
    protected function _getCellName(): string
    {
        if (isset($this->_fields['cell'])) {
            $parts = explode('.', $this->_fields['cell']);
            $cellAndAction = count($parts) === 2 ? $parts[1] : $parts[0];
            $parts = explode('::', $cellAndAction);

            return $parts[0];
        }

        return '';
    }

    /**
     * Gets the action method of the cell. Defaults to 'display'.
     *
     * @return string|null The cell action, or null if the 'cell' field is not set.
     * @see \App\Model\Entity\Block::$cell_action
     */
    protected function _getCellAction(): ?string
    {
        if (isset($this->_fields['cell'])) {
            $parts = explode('::', $this->_fields['cell']);

            return count($parts) === 2 ? lcfirst($parts[1]) : 'display';
        }

        return null;
    }
}
