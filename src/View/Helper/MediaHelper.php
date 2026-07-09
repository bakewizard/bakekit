<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\ORM\Entity;
use Cake\View\Helper;

/**
 * Media helper
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class MediaHelper extends Helper
{
    /**
     * @var array<string>
     */
    protected array $helpers = ['Url'];

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

        $config = $this->getView()->get('config');
        $outputFormat = $config['System']['images']['format'] ?? 'jpeg';

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
}
