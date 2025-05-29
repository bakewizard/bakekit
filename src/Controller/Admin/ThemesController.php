<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\ExtensionHandler;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Response;
use Exception;
use Laminas\Diactoros\UploadedFile;

/**
 * Themes Controller
 *
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class ThemesController extends AppController
{
    /**
     * Path to the themes directory.
     *
     * @var string
     */
    private string $themesDir = ROOT . DS . 'themes' . DS;

    /**
     * Index method
     *
     * @param \App\Lib\ExtensionHandler $extensionHandler The extension handler.
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index(ExtensionHandler $extensionHandler)
    {
        $activeTheme = $this->getConfig('Cms.theme');

        $themes = $extensionHandler->discover($this->themesDir);

        $this->set(compact('activeTheme', 'themes'));
    }

    /**
     * View method
     *
     * @param \App\Lib\ExtensionHandler $extensionHandler The extension handler.
     * @param string $name Theme name.
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(ExtensionHandler $extensionHandler, string $name)
    {
        $activeTheme = $this->getConfig('Cms.theme');

        $config = $extensionHandler->readComposerConfig($this->themesDir . $name);

        $theme = [
            'name' => $name,
            'description' => $config['description'] ?? null,
            'license' => $config['license'] ?? null,
        ];

         $this->set(compact('activeTheme', 'theme'));
    }

    /**
     * Install method
     *
     * @param \App\Lib\ExtensionHandler $extensionHandler The extension handler.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Exception When error is encountered.
     */
    public function install(ExtensionHandler $extensionHandler): ?Response
    {
        $this->request->allowMethod(['post', 'put']);

        $file = $this->request->getUploadedFile('theme');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            $this->Flash->error(__('No file was uploaded.'));

            return $this->redirect(['action' => 'index']);
        }

        $error = $file->getError();
        if ($error !== UPLOAD_ERR_OK) {
            $message = UploadedFile::ERROR_MESSAGES[$error] ?? __('Unknown upload error.');
            $this->Flash->error($message);

            return $this->redirect(['action' => 'index']);
        }

        try {
            $name = $extensionHandler->load($file, $this->themesDir);
            $this->Flash->success(__('The theme "{0}" has been installed.', $name));
        } catch (Exception $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Uninstall a theme.
     *
     * @param \App\Lib\ExtensionHandler $extensionHandler The extension handler.
     * @param string $name The name of the theme to uninstall.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Exception When error is encountered.
     */
    public function uninstall(ExtensionHandler $extensionHandler, string $name): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $activeTheme = Configure::read('Cms.theme');

        if ($name === $activeTheme) {
            $this->Flash->error(__('Active theme cannot be uninstalled.'));
        } else {
            try {
                $extensionHandler->unload($name, $this->themesDir);
            } catch (Exception $e) {
                $this->Flash->error($e->getMessage());
            }
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Activate method
     *
     * @param string|null $name Theme name.
     * @return \Cake\Http\Response|null Redirects to index.
     */
    public function activate(?string $name = null): ?Response
    {
        $this->request->allowMethod(['post', 'put']);

        Configure::write('theme', $name);
        Configure::dump('Cms', 'db', ['theme']);
        Cache::delete('settings', 'cms');

        return $this->redirect(['action' => 'index']);
    }
}
