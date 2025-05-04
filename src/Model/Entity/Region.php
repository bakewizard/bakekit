<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Utility\Text;

/**
 * Region Entity
 *
 * @property int $id
 * @property string $alias
 * @property string|null $description
 *
 * @property \App\Model\Entity\Block[] $blocks
 */
class Region extends Entity
{
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
        'description' => true,
        'blocks' => true,
    ];

    /**
     * Setter for the alias property, which automatically generates a URL-friendly slug.
     *
     * @param string $alias The alias value to set.
     * @return string The generated slug.
     */
    protected function _setAlias(string $alias): string
    {
        return strtolower(Text::slug($alias));
    }
}
