<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\ComposerManager;
use App\Lib\ExtensionHandler;
use App\Lib\PluginManager;
use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use DirectoryIterator;
use Exception;
use Laminas\Diactoros\UploadedFile;
use Override;

/**
 * @property \App\Model\Table\PluginsTable $Plugins
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class PluginsController extends AppController
{
    private string $pluginsDir;

    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();

        $this->pluginsDir = current(App::path('plugins')) ?: '';
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $installedPlugins = $this->Plugins->find('all')->all()->indexBy('name')->toArray();
        $plugins = [];

        if (is_dir($this->pluginsDir)) {
            $dir = new DirectoryIterator($this->pluginsDir);
            foreach ($dir as $info) {
                if (!$info->isDir() || $info->isDot()) {
                    continue;
                }

                $name = $info->getFilename();
                $config = $this->getConfigData($this->pluginsDir . $name);

                if (empty($config)) {
                    continue;
                }

                $plugins[$name] = [
                    'id' => $installedPlugins[$name]->id ?? null,
                    'name' => $name,
                    'alias' => $installedPlugins[$name]->alias ?? '',
                    'description' => $config['description'] ?? '',
                    'parent_plugin' => $config['extra']['parent-plugin'] ?? null,
                    'enabled' => isset($installedPlugins[$name]) && $installedPlugins[$name]->enabled,
                ];
            }
        }

        ksort($plugins);

        $this->set(compact('plugins'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Plugin id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $plugin = $this->Plugins->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $plugin = $this->Plugins->patchEntity($plugin, $this->request->getData());
            if ($this->Plugins->save($plugin)) {
                $this->Flash->success(__('The plugin has been saved.'));

                $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The plugin could not be saved. Please, try again.'));
        }

        $this->set(compact('plugin'));
    }

    /**
     * Installs a plugin from an uploaded ZIP file.
     *
     * This method handles the file upload, extraction, and subsequent Composer autoload
     * dump to ensure the new plugin is recognized by the application.
     *
     * @param \App\Lib\ExtensionHandler $extensionHandler The extension handler.
     * @param \App\Lib\ComposerManager $composer ComposerManager instance for autoloading.
     * @return \Cake\Http\Response|null Redirects to the index page.
     * @throws \Exception When error is encountered.
     */
    public function install(ExtensionHandler $extensionHandler, ComposerManager $composer): ?Response
    {
        $this->request->allowMethod(['post', 'put']);

        $file = $this->request->getUploadedFile('plugin');

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
            $name = $extensionHandler->load($file, $this->pluginsDir);
            $composer->dumpAutoload(['--optimize' => true]);
            $this->Flash->success(__('The plugin "{0}" has been installed.', $name));
        } catch (Exception $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Uninstalls a plugin.
     *
     * This method removes the plugin's database entry, clears relevant caches,
     * deletes plugin files, and updates the Composer autoloader.
     *
     * @param \App\Lib\ExtensionHandler $extensionHandler The extension handler.
     * @param \App\Lib\PluginManager $pluginManager PluginManager instance for uninstall logic.
     * @param \App\Lib\ComposerManager $composer ComposerManager instance for autoloading.
     * @param string $name The name of the plugin to uninstall.
     * @return \Cake\Http\Response|null Redirects to the index page.
     * @throws \Exception When error is encountered.
     */
    public function uninstall(ExtensionHandler $extensionHandler, PluginManager $pluginManager, ComposerManager $composer, string $name): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        try {
            $plugin = $this->Plugins->find()->where(['name' => $name])->first();
            if ($plugin) {
                if ($plugin->name == $this->getConfig('Cms.defaultDashboard')) {
                    throw new Exception(__('The plugin could not be uninstalled. Its dashboard is set as the default one.'));
                }

                $pluginManager->uninstall($name);

                Cache::delete('plugins', 'cms');
                Cache::clear('permissions');

                if (!$this->Plugins->delete($plugin)) {
                    throw new Exception(__('The plugin data could not be deleted from database.'));
                }
            }

            $extensionHandler->unload($name, $this->pluginsDir);
            Cache::drop(Inflector::dasherize($name));
            $composer->dumpAutoload(['--optimize' => true]);
            $this->Flash->success(__('The plugin has been uninstalled.'));
        } catch (Exception $e) {
            $this->Flash->error(__('There were errors while uninstalling the plugin.'));
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Activates a plugin, either by enabling an existing entry or creating a new one.
     *
     * This method handles updating the database, clearing caches, and
     * setting the plugin as active.
     *
     * @param \App\Lib\PluginManager $pluginManager PluginManager instance for activation logic.
     * @param string $name The name of the plugin to activate.
     * @return \Cake\Http\Response|null Redirects to the index page.
     * @throws \Exception
     */
    public function activate(PluginManager $pluginManager, string $name): ?Response
    {
        $this->request->allowMethod(['post', 'put']);

        try {
            $plugin = $this->Plugins->find()->where(['name' => $name])->first();

            if ($plugin) {
                $plugin->enabled = true;

                if (!$this->Plugins->save($plugin)) {
                    throw new Exception(__('The plugin could not be activated. Please, try again.'));
                }
            } else {
                $pluginManager->activate($name);

                $config = $this->getConfigData($this->pluginsDir . $name);

                $entity = $this->Plugins->newEntity([
                    'name' => $name,
                    'alias' => Inflector::dasherize($name),
                    'description' => $config['description'],
                    'parent_plugin' => $config['extra']['parent-plugin'] ?? null,
                    'enabled' => true,
                ]);

                if (!$this->Plugins->save($entity)) {
                    throw new Exception(__('The plugin data could not be saved to database.'));
                }
            }
            Cache::delete('plugins', 'cms');
            Cache::clear('permissions');
            $this->Flash->success(__('The plugin has been activated.'));
        } catch (Exception $e) {
            $this->Flash->error(__('There were errors while activating the plugin.'));
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Deactivates a plugin.
     *
     * This method updates the plugin's status in the database and clears relevant cache.
     * It prevents deactivation if the plugin's dashboard is set as the default.
     *
     * @param string|int $id The ID of the plugin to deactivate.
     * @return \Cake\Http\Response|null Redirects to the index page.
     */
    public function deactivate(string|int $id): ?Response
    {
        $this->request->allowMethod(['post', 'put']);

        $plugin = $this->Plugins->get($id);

        if ($plugin->name == $this->getConfig('Cms.defaultDashboard')) {
            $this->Flash->error(__('The plugin could not be deactivated. Its dashboard is set as the default one.'));

            return $this->redirect(['action' => 'index']);
        }

        $plugin->enabled = false;

        if ($this->Plugins->save($plugin)) {
            Cache::delete('plugins', 'cms');
            $this->Flash->success(__('The plugin has been deactivated.'));
        } else {
            $this->Flash->error(__('The plugin could not be deactivated. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Reads composer.json into array.
     *
     * @param string $path
     * @return array<string, mixed>
     */
    private function getConfigData(?string $path = null): array
    {
        if (!$path) {
            $path = ROOT;
        }

        $configFile = $path . DS . 'composer.json';

        if (!file_exists($configFile) || !is_readable($configFile)) {
            return [];
        }

        $content = file_get_contents($configFile);
        if ($content === false) {
            return [];
        }

        return json_decode($content, true);
    }
}
