<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\ComposerManager;
use App\Lib\PluginManager;
use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use DirectoryIterator;
use Exception;
use Laminas\Diactoros\UploadedFile;
use Override;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

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

        $this->pluginsDir = (string)current(App::path('plugins')) ?: '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!$this->request->is('get') && $this->request->getParam('action') !== 'add') {
            Cache::delete('plugins', 'cms');
        }
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
     * Installs a plugin.
     *
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Exception When error is encountered.
     */
    public function install(ComposerManager $composer)
    {
        $this->request->allowMethod(['post', 'put']);

        $file = $this->request->getUploadedFile('plugin');
        if ($file === null) {
            $this->Flash->error(__('No plugin upload field was submitted.'));

            return $this->redirect(['action' => 'index']);
        }
        $error = $file->getError();

        if ($error) {
            $this->Flash->error(UploadedFile::ERROR_MESSAGES[$error]);

            return $this->redirect(['action' => 'index']);
        }

        $filename = $file->getClientFilename();
        if ($filename === null) {
            throw new Exception(__('Uploaded file does not have a valid filename.'));
        }
        $plugin = basename($filename, '.zip');

        if (is_dir($this->pluginsDir . $plugin)) {
            $this->Flash->error(__('Folder with the name "{0}" already exists', $plugin));

            return $this->redirect(['action' => 'index']);
        }

        try {
            $this->unpack($file->getStream()->getMetadata('uri'), $this->pluginsDir);
            $composer->dumpAutoload(['--optimize' => true]);
            $this->Flash->success(__('The plugin has been installed.'));
        } catch (Exception $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Uninstalls a plugin.
     *
     * @param string $name Plugin name.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Exception When error is encountered.
     */
    public function uninstall(ComposerManager $composer, string $name)
    {
        $this->request->allowMethod(['post', 'delete']);

        try {
            $plugin = $this->Plugins->find()->where(['name' => $name])->first();
            if ($plugin) {
                if ($plugin->name == $this->getConfig('Cms.defaultDashboard')) {
                    throw new Exception(__('The plugin could not be uninstalled. Its dashboard is set as the default one.'));
                }

                $pm = new PluginManager();
                $pm->deleteMigrations($plugin->name);
                $pm->deleteSettings($plugin->name);
                $pm->deleteResources($plugin->name);

                Cache::delete('plugins', 'cms');
                Cache::clear('permissions');

                if (!$this->Plugins->delete($plugin)) {
                    throw new Exception(__('The plugin data could not be deleted from database.'));
                }
            }

            $this->clean($name);
            $composer->dumpAutoload(['--optimize' => true]);
            $this->Flash->success(__('The plugin has been uninstalled.'));
        } catch (Exception $e) {
            $this->Flash->error(__('There were errors while uninstalling the plugin.'));
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Activates a plugin
     *
     * @param string $name Plugin name
     * @return \Cake\Http\Response|null Redirects on successful activation, renders view otherwise.
     * @throws \Exception
     */
    public function activate(string $name)
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
                $pm = new PluginManager();
                $pm->addMigrations($name);
                $pm->addSettings($name);
                $pm->addResources($name);

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
            $this->Flash->success(__('The plugin has been activated.'));
        } catch (Exception $e) {
            $this->Flash->error(__('There were errors while activating the plugin.'));
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Deactivates a plugin
     *
     * @param string|int $id Plugin id
     * @return \Cake\Http\Response|null
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
     * Removes plugin files and cleans cache.
     *
     * @param string $plugin Plugin name.
     * @return void
     */
    private function clean(string $plugin): void
    {
        $cacheConfig = Inflector::dasherize($plugin);
        $fs = new Filesystem();

        if ($fs->exists(CACHE . $cacheConfig . DS)) {
            $fs->remove(CACHE . $cacheConfig . DS);
        }
        $fs->remove(ROOT . DS . 'plugins' . DS . $plugin . DS);
    }

    /**
     * Extracts an archive to a folder.
     *
     * @param string $input Input path.
     * @param string $output Output path.
     * @return void
     * @throws \Exception
     */
    private function unpack(string $input, string $output): void
    {
        $archive = new ZipArchive();

        if (!$archive->open($input)) {
            throw new Exception(__('Error occured while opening the archive.'));
        }

        if (!$archive->extractTo($output)) {
            $archive->close();
            throw new Exception(__('Error occured while extracting the archive.'));
        }
        $archive->close();
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
