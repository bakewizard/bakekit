<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Metum Entity
 *
 * @property int $id
 * @property int $plugin_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $seo_keywords
 *
 * @property \App\Model\Entity\Plugin $plugin
 */
class Metum extends Entity
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
        'plugin_id' => true,
        'title' => true,
        'description' => true,
        'seo_title' => true,
        'seo_description' => true,
        'seo_keywords' => true,
        'plugin' => true,
    ];
}
