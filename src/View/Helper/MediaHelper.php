<?php

declare(strict_types=1);

namespace App\View\Helper;

use Cake\ORM\Entity;
use Cake\View\Helper;

/**
 * Media helper
 */
class MediaHelper extends Helper
{

    protected array $helpers = ['Url'];

    public function getImageUrl(?Entity $entity, $size = 'md', $index = 0): string
    {
        $image = null;

        if (!is_null($entity)) {
            $image = $entity->files[$index] ?? $entity;
        }

        $outputFormat = $this->getView()->get('config')['Cms']['images']['format'];

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
