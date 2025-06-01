<?php
declare(strict_types=1);

namespace App\Lib;

use DirectoryIterator;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

/**
 * Handles shared logic for installing, validating, and extracting plugins or themes.
 */
class ExtensionHandler
{
    /**
     * Discovers extensions in the given base directory by scanning for subdirectories
     * that contain a valid composer.json file.
     *
     * @param string $baseDir Base directory where extensions are stored.
     * @return array<string, array{
     *     name: string,
     *     description: string,
     *     license: string|array<int, string>,
     *     extra: array<string, mixed>
     * }>
     */
    public function discover(string $baseDir): array
    {
        $results = [];

        if (!is_dir($baseDir)) {
            return [];
        }

        $dir = new DirectoryIterator($baseDir);
        foreach ($dir as $info) {
            if (!$info->isDir() || $info->isDot()) {
                continue;
            }

            $name = $info->getFilename();
            $config = $this->readComposerConfig($baseDir . $name);

            if (empty($config)) {
                continue;
            }

            $results[$name] = [
                'name' => $name,
                'description' => $config['description'] ?? '--- No description ---',
                'license' => $config['license'] ?? '--- No license ---',
                'extra' => $config['extra'] ?? [],
            ];
        }

        ksort($results);

        return $results;
    }

    /**
     * Complete logic to install (load) an extension from upload.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file The uploaded ZIP file.
     * @param string $baseDir Base directory where the extension should go (e.g. themesDir or pluginsDir).
     * @return string Installed name (folder)
     * @throws \Exception
     */
    public function load(UploadedFileInterface $file, string $baseDir): string
    {
        $this->validateZipMime($file);

        $filename = $file->getClientFilename();
        if (!$filename) {
            throw new Exception(__('Uploaded file has no name.'));
        }

        $name = basename($filename, '.zip');
        $this->validateName($name);

        $target = $baseDir . $name . DIRECTORY_SEPARATOR;
        if (is_dir($target)) {
            throw new Exception(__('Folder with the name "{0}" already exists.', $name));
        }

        $tempPath = $this->moveToTemp($file);

        try {
            $this->unpack($tempPath, $baseDir);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }

        return $name;
    }

    /**
     * Uninstalls (unloads) an extension by removing its folder.
     *
     * @param string $name Folder name of extension.
     * @param string $baseDir Base directory.
     * @return void
     * @throws \Exception
     */
    public function unload(string $name, string $baseDir): void
    {
        $path = $baseDir . $name . DIRECTORY_SEPARATOR;

        if (!is_dir($path)) {
            throw new Exception(__('Extension "{0}" not found.', $name));
        }

        $fs = new Filesystem();
        if ($fs->exists($path)) {
            $fs->remove($path);
        }
    }

    /**
     * Reads and parses composer.json in the given directory.
     *
     * @param string $path
     * @return array<string, mixed>
     */
    public function readComposerConfig(string $path): array
    {
        // if (!$path) {
        //     $path = ROOT;
        // }

        $configFile = $path . DS . 'composer.json';

        if (!file_exists($configFile) || !is_readable($configFile)) {
            return [];
        }

        $content = file_get_contents($configFile);
        if ($content === false) {
            return [];
        }

        return json_decode($content, true) ?? [];
    }

    /**
     * Validates an extension name (e.g., "MyTheme" or "CoolPlugin").
     *
     * @param string $name
     * @return void
     * @throws \Exception
     */
    private function validateName(string $name): void
    {
        if (!preg_match('/^[A-Z][a-zA-Z0-9]+$/', $name)) {
            throw new Exception(__('Invalid file name: {0}', $name));
        }
    }

    /**
     * Validates the MIME type of an uploaded ZIP file.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     * @return void
     * @throws \Exception
     */
    private function validateZipMime(UploadedFileInterface $file): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new Exception(__('Unable to open MIME detector.'));
        }

        $uri = $file->getStream()->getMetadata('uri');
        $mimeType = finfo_file($finfo, $uri);
        finfo_close($finfo);

        if ($mimeType !== 'application/zip') {
            throw new Exception(__('Uploaded file is not a ZIP archive (detected: {0})', $mimeType));
        }
    }

    /**
     * Moves an uploaded ZIP file to a safe temporary location.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     * @return string Path to the temporary file
     * @throws \Exception
     */
    private function moveToTemp(UploadedFileInterface $file): string
    {
        $tempPath = TMP . uniqid('extension_', true) . '.zip';
        $file->moveTo($tempPath);

        if (!file_exists($tempPath)) {
            throw new Exception(__('Failed to move uploaded file.'));
        }

        return $tempPath;
    }

    /**
     * Extracts a ZIP file into a target directory.
     *
     * @param string $inputZipPath
     * @param string $targetDir
     * @return void
     * @throws \Exception
     */
    private function unpack(string $inputZipPath, string $targetDir): void
    {
        if (!is_dir($targetDir)) {
            throw new Exception(__('Target directory does not exist.'));
        }

        if (!is_writable($targetDir)) {
            throw new Exception(__('Target directory is not writable.'));
        }

        $zip = new ZipArchive();
        if ($zip->open($inputZipPath) !== true) {
            throw new Exception(__('Failed to open ZIP archive.'));
        }

        if (!$zip->extractTo($targetDir)) {
            $zip->close();
            throw new Exception(__('Failed to extract ZIP archive.'));
        }

        $zip->close();
    }
}
