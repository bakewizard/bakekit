<?php
declare(strict_types=1);

namespace App\Lib;

use Psr\Http\Message\UploadedFileInterface;

class ThemeManager
{
    /**
     * Directory where plugins are stored
     *
     * @var string
     */
    private string $themesDir;

    /**
     * ExtensionHandler instance
     *
     * @var \App\Lib\ExtensionHandler
     */
    private ExtensionHandler $extensionHandler;

    /**
     * PluginManager constructor
     *
     * Sets plugins dir
     */
    public function __construct(ExtensionHandler $extensionHandler)
    {
        $this->extensionHandler = $extensionHandler;
        $this->themesDir = ROOT . DS . 'themes' . DS;
    }

    /**
     * Reads the composer.json file from the theme directory.
     *
     * @return array<string, mixed> Parsed composer.json data.
     */
    public function list(): array
    {
        return $this->extensionHandler->discover($this->themesDir);
    }

    /**
     * Reads a theme composer.json.
     *
     * @param string $theme Theme name to read config from.
     * @return array
     */
    public function view(string $theme): array
    {
        return $this->extensionHandler->readComposerConfig($this->themesDir . $theme);
    }

    /**
     * Installs a theme by loading it from an uploaded file.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded file containing the theme.
     * @return string Installed name (folder)
     * @throws \Exception If the theme cannot be loaded.
     */
    public function install(UploadedFileInterface $file): string
    {
        return $this->extensionHandler->load($file, $this->themesDir);
    }

    /**
     * Uninstalls a theme by removing its migrations, settings, and resources.
     *
     * @param string $theme Plugin name to uninstall.
     * @param bool $isActive Whether the theme is currently active.
     * @return void
     */
    public function uninstall(string $theme, bool $isActive = false): void
    {
        if (!$isActive) {
            $this->extensionHandler->unload($theme, $this->themesDir);
        }
    }
}
