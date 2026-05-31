<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\Resource;
use App\Lib\ComposerManager;
use App\Lib\ThemeManager;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventInterface;
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
     * @inheritDoc
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $action = $this->request->getParam('action');

        $this->addCrumb('Themes', [
            'prefix' => 'Admin',
            'plugin' => null,
            'controller' => 'Themes',
            'action' => 'index',
        ]);
        if (in_array($action, ['view', 'blocks'])) {
            $this->addCrumb($action);
        }
    }

    /**
     * Index method
     *
     * @param \App\Lib\ThemeManager $themeManager Theme manager instance.
     * @return \Cake\Http\Response|null|void Renders view
     */
    #[Resource(label: 'List themes')]
    public function index(ThemeManager $themeManager)
    {
        $activeTheme = $this->getConfig('System.theme');

        $themes = $themeManager->list();

        $this->set(compact('activeTheme', 'themes'));
    }

    /**
     * View method
     *
     * @param \App\Lib\ThemeManager $themeManager Theme manager instance.
     * @param string $name Theme name.
     * @return \Cake\Http\Response|null|void Renders view
     */
    #[Resource(label: 'View theme details')]
    public function view(ThemeManager $themeManager, string $name)
    {
        $activeTheme = $this->getConfig('System.theme');

        $config = $themeManager->view($name);

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
     * @param \App\Lib\ThemeManager $themeManager Theme manager instance.
     * @param \App\Lib\ComposerManager $composer ComposerManager instance for autoloading.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Exception When error is encountered.
     */
    #[Resource(label: 'Install a theme')]
    public function install(ThemeManager $themeManager, ComposerManager $composer): ?Response
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
            $name = $themeManager->install($file);
            $composer->dumpAutoload(['--optimize' => true]);
            $this->Flash->success(__('The theme "{0}" has been installed.', $name));
        } catch (Exception $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Uninstall a theme.
     *
     * @param \App\Lib\ThemeManager $themeManager Theme manager instance.
     * @param \App\Lib\ComposerManager $composer ComposerManager instance for autoloading.
     * @param string $name The name of the theme to uninstall.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Exception When error is encountered.
     */
    #[Resource(label: 'Uninstall a theme')]
    public function uninstall(ThemeManager $themeManager, ComposerManager $composer, string $name): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $activeTheme = Configure::read('System.theme');

        try {
            $themeManager->uninstall($name, $name === $activeTheme);
            $composer->dumpAutoload(['--optimize' => true]);
            $this->Flash->success(__('The theme "{0}" has been unnstalled.', $name));
        } catch (Exception $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Activate method
     *
     * @param string|null $name Theme name.
     * @return \Cake\Http\Response|null Redirects to index.
     */
    #[Resource(label: 'Activate a theme')]
    public function activate(?string $name = null): ?Response
    {
        $this->request->allowMethod(['post', 'put']);

        Configure::write('theme', $name);
        Configure::dump('System', 'db', ['theme']);

        if ($name) {
            $regionsTable = $this->fetchTable('Regions');
            $regionsTable->getEventManager()->dispatch(new Event('Region.rebuild', $regionsTable, ['theme' => $name]));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Regions method
     *
     * @return \Cake\Http\Response|void
     */
    #[Resource(label: 'View theme blocks')]
    public function blocks()
    {
        $activeTheme = $this->getConfig('System.theme');

        $regions = $this->fetchTable('Regions')
            ->findByTheme($activeTheme)
            ->contain(['Blocks' => fn($q) => $q->orderByAsc('position')])
            ->all();

        $this->set(compact('regions', 'activeTheme'));
    }
}
