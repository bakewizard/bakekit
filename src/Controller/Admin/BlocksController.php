<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\PluginManager;
use Cake\Core\App;
use Cake\Http\Response;

/**
 * Blocks Controller
 *
 * @property \App\Model\Table\BlocksTable $Blocks
 * @method \App\Model\Entity\Block[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class BlocksController extends AppController
{
    /**
     * Add method
     *
     * @param string|null $id Region id.
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(?string $id = null)
    {
        $block = $this->Blocks->newEmptyEntity();
        $block->region_id = $id;
        if ($this->request->is('post')) {
            $block = $this->Blocks->patchEntity($block, $this->request->getData());
            if ($this->Blocks->save($block)) {
                $this->Flash->success(__('The block has been saved.'));

                return $this->redirect(['controller' => 'Regions', 'action' => 'view', $id]);
            }

            $this->Flash->error(__('The block could not be saved. Please, try again.'));
        }
        $this->set(compact('block'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Block id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $block = $this->Blocks->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $block = $this->Blocks->patchEntity($block, $this->request->getData());
            if ($this->Blocks->save($block)) {
                $this->Flash->success(__('The block has been saved.'));

                return $this->redirect(['controller' => 'Regions', 'action' => 'view', $block->region_id]);
            }
            $this->Flash->error(__('The block could not be saved. Please, try again.'));
        }
        $regions = $this->Blocks->Regions->find('list', limit: 200);
        $this->set(compact('block', 'regions'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Block id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $block = $this->Blocks->get($id);
        if ($this->Blocks->delete($block)) {
            $this->Flash->success(__('The block has been deleted.'));
        } else {
            $this->Flash->error(__('The block could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Regions', 'action' => 'view', $block->region_id]);
    }

    /**
     * Moves block up
     *
     * @param string $id
     * @return void
     */
    public function moveUp(?string $id = null)
    {
        $this->request->allowMethod(['post', 'put']);
        $block = $this->Blocks->get($id);

        if ($this->Blocks->moveUp($block)) {
            $this->Flash->success('The Block has been moved Up.');
        } else {
            $this->Flash->error('The Block could not be moved up. Please, try again.');
        }

        return $this->redirect(['controller' => 'Regions', 'action' => 'view', $block->region_id]);
    }

    /**
     * Moves block down
     *
     * @param string $id
     * @return void
     */
    public function moveDown(?string $id = null)
    {
        $this->request->allowMethod(['post', 'put']);
        $block = $this->Blocks->get($id);

        if ($this->Blocks->moveDown($block)) {
            $this->Flash->success('The Block has been moved down.');
        } else {
            $this->Flash->error('The Block could not be moved down. Please, try again.');
        }

        return $this->redirect(['controller' => 'Regions', 'action' => 'view', $block->region_id]);
    }

    /**
     * Handles the configuration of a block's cell form.
     *
     * @param string $id
     * @return \Cake\Http\Response|null A redirect response or null on GET render.
     */
    public function config(string $id): ?Response
    {
        $block = $this->Blocks->get($id);
        $pluginAndName = $block->cellPlugin ? "{$block->cellPlugin}.{$block->cellName}" : $block->cellName;
        $cellSettingsFormClass = App::classname($pluginAndName . 'CellConfig', 'Form/Cell', 'Form');
        $settings = new $cellSettingsFormClass();
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            if ($settings->validate($data)) {
                $block->params = $data;
                if ($this->Blocks->save($block)) {
                    $this->Flash->success(__('Configuration saved'));

                    return $this->redirect(['controller' => 'Regions', 'action' => 'view', $block->region_id]);
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

        $this->render("{$pluginAndName}/{$block->cellAction}");

        return null;
    }

    /**
     * Gets plugin cells
     *
     * @return void
     */
    public function getCells()
    {
        $pm = new PluginManager();

        $data = $pm->getCells();

        $this->set(compact('data'));
    }
}
