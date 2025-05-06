<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string|null $namespace
 * @property string $path
 * @property string|null $value
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Setting extends Entity
{
    /**
     * @inheritDoc
     */
    protected array $_accessible = [
        'namespace' => true,
        'path' => true,
        'value' => true,
        'created' => true,
        'modified' => true,
    ];
}
