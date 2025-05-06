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
 * @property \Cake\ORM\Entity[] $_i18n
 */
class Metum extends Entity
{
    /**
     * @inheritDoc
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
