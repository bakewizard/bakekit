<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\Resource;
use Cake\Cache\Cache;
use Cake\Event\EventInterface;
use Override;

/**
 * Menus Controller
 *
 * @property \App\Model\Table\MenusTable $Menus
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Menu> paginate(\Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $object = null, array $settings = [])
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class MenusController extends AppController
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $action = $this->request->getParam('action');

        $this->addBreadcrumb('Menus', [
            'prefix' => 'Admin',
            'plugin' => null,
            'controller' => 'Menus',
            'action' => 'index',
        ]);
        if (in_array($action, ['add', 'edit', 'view'])) {
            $this->addBreadcrumb($action);
        }

        if (!$this->request->is('get') && $this->request->getParam('action') !== 'add') {
            Cache::clear('menus');
        }
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    #[Resource(label: 'List menus')]
    public function index()
    {
        $menus = $this->paginate($this->Menus);

        $this->set(compact('menus'));
    }

    /**
     * View method
     *
     * @param string|null $id Menu id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    #[Resource(label: 'View menu links')]
    public function view(?string $id = null)
    {
        $menu = $this->Menus->get($id);

        $menuLinks = $this->Menus->MenuLinks->find('treeList', spacer: '---')
            ->where(['MenuLinks.menu_id' => $id]);

        $this->set(compact('menu', 'menuLinks'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    #[Resource(label: 'Create a menu')]
    public function add()
    {
        $menu = $this->Menus->newEmptyEntity();
        if ($this->request->is('post')) {
            $menu = $this->Menus->patchEntity($menu, $this->request->getData());
            if ($this->Menus->save($menu)) {
                $this->Flash->success(__('The menu has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The menu could not be saved. Please, try again.'));
        }
        $this->set(compact('menu'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Menu id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    #[Resource(label: 'Edit a menu')]
    public function edit(?string $id = null)
    {
        $menu = $this->Menus->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $menu = $this->Menus->patchEntity($menu, $this->request->getData());
            if ($this->Menus->save($menu)) {
                $this->Flash->success(__('The menu has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The menu could not be saved. Please, try again.'));
        }
        $this->set(compact('menu'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Menu id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    #[Resource(label: 'Delete a menu')]
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $menu = $this->Menus->get($id);

        if ($this->Menus->delete($menu)) {
            $this->Flash->success(__('The menu has been deleted.'));
        } else {
            $this->Flash->error(__('The menu could not be deleted.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
