<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\InstanceConfigTrait;

abstract class AbstractFileHandler
{
    use InstanceConfigTrait;

    /**
     * Handle the upload of files.
     *
     * @param array<\Cake\ORM\Entity> $files
     * @return void
     */
    abstract public function handle(array $files): void;

    /**
     * Remove specified files.
     *
     * @param array<\Cake\ORM\Entity> $files
     * @return void
     */
    abstract public function remove(array $files): void;
}
