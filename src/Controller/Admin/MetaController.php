<?php
declare(strict_types=1);

namespace App\Controller\Admin;

/**
 * Meta Controller
 *
 * @property \App\Model\Table\MetaTable $Meta
 * @method \App\Model\Entity\Metum[] paginate($object = null, array $settings = [])
 */
class MetaController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $meta = $this->paginate($this->Meta->find('all', contain: ['Plugins']));

        $this->set(compact('meta'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $metum = $this->Meta->newEmptyEntity();
        if ($this->request->is('post')) {
            $metum = $this->Meta->patchEntity($metum, $this->request->getData());
            if ($this->Meta->save($metum)) {
                $this->Flash->success(__('The metum has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The metum could not be saved. Please, try again.'));
        }

        $plugins = $this->Meta->Plugins->find('list', limit: 200)->where(['parent_plugin is' => null]);

        $this->set(compact('metum', 'plugins'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Metum id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $metum = $this->Meta->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $metum = $this->Meta->patchEntity($metum, $this->request->getData());
            if ($this->Meta->save($metum)) {
                $this->Flash->success(__('The metum has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The metum could not be saved. Please, try again.'));
        }

        $plugins = $this->Meta->Plugins->find('list', limit: 200)->where(['parent_plugin is' => null]);

        $this->set(compact('metum', 'plugins'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Metum id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $metum = $this->Meta->get($id);
        if ($this->Meta->delete($metum)) {
            $this->Flash->success(__('The metum has been deleted.'));
        } else {
            $this->Flash->error(__('The metum could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
