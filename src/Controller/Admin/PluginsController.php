<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\ComposerManager;
use App\Lib\PluginManager;
use Cake\Cache\Cache;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use Exception;
use Laminas\Diactoros\UploadedFile;

/**
 * @property \App\Model\Table\PluginsTable $Plugins
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class PluginsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index(PluginManager $pluginManager)
    {
        $installedPlugins = $this->Plugins->find('all')->all()->indexBy('name')->toArray();

        $plugins = $pluginManager->list();

        $this->set(compact('plugins', 'installedPlugins'));
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
                Cache::delete('plugins', 'cms');
                $this->Flash->success(__('The plugin has been saved.'));

                return $this->redirect(['action' => 'index']);
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
     * @param \App\Lib\PluginManager $pluginManager Plugin manager instance.
     * @param \App\Lib\ComposerManager $composer ComposerManager instance for autoloading.
     * @return \Cake\Http\Response|null Redirects to the index page.
     * @throws \Exception When error is encountered.
     */
    public function install(PluginManager $pluginManager, ComposerManager $composer): ?Response
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
            $name = $pluginManager->install($file);
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
     * @param \App\Lib\PluginManager $pluginManager Plugin manager instance.
     * @param \App\Lib\ComposerManager $composer ComposerManager instance for autoloading.
     * @param string $name The name of the plugin to uninstall.
     * @return \Cake\Http\Response|null Redirects to the index page.
     * @throws \Exception When error is encountered.
     */
    public function uninstall(PluginManager $pluginManager, ComposerManager $composer, string $name): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        try {
            $plugin = $this->Plugins->find()->where(['name' => $name])->first();

            if ($plugin && $plugin->name === $this->getConfig('Cms.defaultDashboard')) {
                throw new Exception(__('The plugin "{0}" could not be uninstalled. Its dashboard is set as the default one.', $plugin->name));
            }

            $pluginManager->uninstall($name, $plugin !== null);

            if ($plugin) {
                if (!$this->Plugins->delete($plugin)) {
                    throw new Exception(__('The plugin data could not be deleted from database.'));
                }
                Cache::delete('plugins', 'cms');
                Cache::clear('permissions');
            }

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
     * @param \App\Lib\PluginManager $pluginManager Plugin manager instance.
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
                $config = $pluginManager->activate($name);

                $entity = $this->Plugins->newEntity([
                    'name' => $name,
                    'alias' => Inflector::dasherize($name),
                    'description' => $config['description'] ?? null,
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
}
