<?php

declare(strict_types=1);

namespace App\Lib;

interface UploadHandlerInterface
{

    public function handle(array $files);

    public function remove(array $files);
}
