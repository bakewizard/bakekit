<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Event\EventInterface;
use Override;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class UsersController extends AppController
{
    /** @inheritDoc */
    #[Override]
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['login']);

        $request = $this->getRequest();
        if (in_array($request->getParam('action'), ['view', 'add', 'edit', 'delete'])) {
            $id = $request->getParam('pass.0');
            $user = isset($id) ? $this->Users->get($id) : $this->Users->newEmptyEntity();
            $this->Authorization->authorize($user);
        }
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders the index view.
     */
    public function index()
    {
        $user = $this->request->getAttribute('identity');
        $query = $user->applyScope('index', $this->Users->find('all', contain: ['Roles']));

        $users = $this->paginate($query);

        $this->set(compact('users'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $images = $this->getConfig('Cms.images');

        /** @var \App\Model\Behavior\UploadBehavior $upload */
        $upload = $this->Users->getBehavior('Upload');

        $upload->getUploadHandler()->setConfig([
            'format' => $images['format'],
            'quality' => $images['quality'],
        ]);

        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The user could not be saved. Please, try again.'));
            }
        }
        $roles = $this->Users->Roles->find('treeList', spacer: '-- ', limit: 200);

        $this->set(compact('user', 'roles'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $images = $this->getConfig('Cms.images');

        /** @var \App\Model\Behavior\UploadBehavior $upload */
        $upload = $this->Users->getBehavior('Upload');

        $upload->getUploadHandler()->setConfig([
            'format' => $images['format'],
            'quality' => $images['quality'],
        ]);

        $user = $this->Users->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The user could not be saved. Please, try again.'));
            }
        }
        $roles = $this->Users->Roles->find('treeList', spacer: '-- ', limit: 200);

        $this->set(compact('user', 'roles'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);

        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Login action.
     *
     * @return \Cake\Http\Response|null|void Redirects the user to the dashboard upon successful login.
     */
    public function login()
    {
        $this->request->allowMethod(['get', 'post']);

        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $plugin = $this->getConfig('Cms.defaultDashboard', 'System');
            $defaultDashboard = ['plugin' => $plugin == 'System' ? null : $plugin, 'prefix' => 'Admin', 'controller' => 'Dashboard'];
            $target = $this->Authentication->getLoginRedirect() ?? $defaultDashboard;

            return $this->redirect($target);
        }

        if ($this->request->is('post')) {
            $this->Flash->error(__('User name or password is incorrect'));
        }
    }

    /**
     * Logout action.
     *
     * @return \Cake\Http\Response|null|void Redirects the user to the login page after logout.
     */
    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result !== null && $result->isValid()) {
            $logoutUrl = $this->Authentication->logout();

            return $this->redirect($logoutUrl ?? '/');
        }
    }
}
