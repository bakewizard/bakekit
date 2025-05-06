<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\InstanceConfigTrait;

abstract class AbstractUploadHandler
{
    use InstanceConfigTrait;

    /**
     * Handle the upload of files.
     *
     * @param array $files An array of file data to be uploaded.
     * @return void
     */
    abstract public function handle(array $files): void;

    /**
     * Remove specified files.
     *
     * @param array $files An array of file data to be removed.
     * @return void
     */
    abstract public function remove(array $files): void;
}
