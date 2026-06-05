<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\Resource;
use App\Lib\ResourcesExplorer;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Blocks Controller
 *
 * @property \App\Model\Table\BlocksTable $Blocks
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Block> paginate($object = null, array<string, mixed> $settings = [])
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class BlocksController extends AppController
{
    /**
     * @inheritDoc
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $action = $this->request->getParam('action');

        if (in_array($action, ['add', 'edit', 'config'])) {
            $this->addBreadcrumb('Themes', [
                'prefix' => 'Admin',
                'plugin' => null,
                'controller' => 'Themes',
            ]);
            $this->addBreadcrumb('Blocks', [
                'prefix' => 'Admin',
                'plugin' => null,
                'controller' => 'Themes',
                'action' => 'blocks',
            ]);
            $this->addBreadcrumb($action);
        }
    }

    /**
     * Add method
     *
     * @param string|null $id Region id.
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    #[Resource(label: 'Add a block')]
    public function add(?string $id = null)
    {
        $block = $this->Blocks->newEmptyEntity();
        $block->region_id = (int)$id;
        if ($this->request->is('post')) {
            $block = $this->Blocks->patchEntity($block, $this->request->getData());
            if ($this->Blocks->save($block)) {
                $this->Flash->success(__('The block has been saved.'));

                return $this->redirect(['controller' => 'Themes', 'action' => 'blocks']);
            }

            $this->Flash->error(__('The block could not be saved. Please, try again.'));
        }
        $this->set(compact('block'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Block id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    #[Resource(label: 'Edit a block')]
    public function edit(?string $id = null)
    {
        $block = $this->Blocks->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $block = $this->Blocks->patchEntity($block, $this->request->getData());
            if ($this->Blocks->save($block)) {
                $this->Flash->success(__('The block has been saved.'));

                return $this->redirect(['controller' => 'Themes', 'action' => 'blocks']);
            }
            $this->Flash->error(__('The block could not be saved. Please, try again.'));
        }
        $theme = Configure::read('System.theme');
        $regions = $this->Blocks->Regions->find('list', limit: 200)->where(['theme' => $theme]);
        $this->set(compact('block', 'regions'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Block id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    #[Resource(label: 'Delete a block')]
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $block = $this->Blocks->get($id);
        if ($this->Blocks->delete($block)) {
            $this->Flash->success(__('The block has been deleted.'));
        } else {
            $this->Flash->error(__('The block could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Themes', 'action' => 'blocks']);
    }

    /**
     * Moves block up
     *
     * @param string $id
     * @return \Cake\Http\Response|null
     */
    public function moveUp(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put']);
        $block = $this->Blocks->get($id);

        $sequence = $this->Blocks->getBehavior('Sequence');

        /** @var \ADmad\Sequence\Model\Behavior\SequenceBehavior $sequence */
        if ($sequence->moveUp($block)) {
            $this->Flash->success('The Block has been moved Up.');
        } else {
            $this->Flash->error('The Block could not be moved up. Please, try again.');
        }

        return $this->redirect(['controller' => 'Themes', 'action' => 'blocks']);
    }

    /**
     * Moves block down
     *
     * @param string $id
     * @return \Cake\Http\Response|null
     */
    public function moveDown(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put']);
        $block = $this->Blocks->get($id);

        /** @var \ADmad\Sequence\Model\Behavior\SequenceBehavior $sequence */
        $sequence = $this->Blocks->getBehavior('Sequence');

        if ($sequence->moveDown($block)) {
            $this->Flash->success('The Block has been moved down.');
        } else {
            $this->Flash->error('The Block could not be moved down. Please, try again.');
        }

        return $this->redirect(['controller' => 'Themes', 'action' => 'blocks']);
    }

    /**
     * Handles the configuration of a block's cell form.
     *
     * @param string $id
     * @return \Cake\Http\Response|null A redirect response or null on GET render.
     */
    #[Resource(label: 'Configure a block')]
    public function config(string $id): ?Response
    {
        $block = $this->Blocks->get($id);
        $pluginAndName = $block->cell_plugin ? "{$block->cell_plugin}.{$block->cell_name}" : $block->cell_name;
        $cellSettingsFormClass = App::classname($pluginAndName . 'CellConfig', 'Form/Cell', 'Form');
        $settings = new $cellSettingsFormClass();
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            if ($settings->validate($data)) {
                $block->params = $data;
                if ($this->Blocks->save($block)) {
                    $this->Flash->success(__('Configuration saved'));

                    return $this->redirect(['controller' => 'Themes', 'action' => 'blocks']);
                }
                $this->Flash->error(__('Configuration could not be saved. Please, try again.'));
            } else {
                $this->Flash->error(__('There was a problem submitting your form.'));
            }

            return $this->redirect($this->referer());
        }

        if ($this->request->is('get')) {
            if ($block->params) {
                $settings->setData($block->params);
            }
        }

        $this->set(compact('block', 'settings'));

        $this->setName('CellConfig');

        return $this->render("{$pluginAndName}/{$block->cell_action}");
    }

    /**
     * Gets plugin cells
     *
     * @param \App\Lib\ResourcesExplorer $re
     * @return void
     */
    public function getCells(ResourcesExplorer $re): void
    {
        /** @var \App\Model\Table\PluginsTable $table */
        $table = $this->fetchTable('Plugins');
        $activePlugins = $table->getActivePlugins(true);

        $data = $re->getCells($activePlugins);

        $this->set(compact('data'));
    }
}
