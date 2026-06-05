<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\ResourcesExplorer;
use Cake\Event\EventInterface;
use Override;

/**
 * Roles Controller
 *
 * @property \App\Model\Table\RolesTable $Roles
 * @method \App\Model\Entity\Role[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role> paginate($object = null, array<string, mixed> $settings = [])
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class RolesController extends AppController
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $action = $this->request->getParam('action');

        $this->addBreadcrumb('Roles', [
            'prefix' => 'Admin',
            'plugin' => null,
            'controller' => 'Roles',
            'action' => 'index',
        ]);

        $request = $this->getRequest();
        if ($action !== 'index') {
            $id = $request->getParam('pass.0');
            $role = isset($id) ? $this->Roles->get($id) : $this->Roles->newEmptyEntity();
            $this->Authorization->authorize($role);
        }
    }

    /**
     * Displays all roles
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $query = $this->Roles->find('treeList', spacer: '-----');

        $this->Authorization->authorize($query);

        $roles = $query->toArray();

        $this->set(compact('roles'));
    }

    /**
     * View method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $role = $this->Roles->get($id, contain: ['Users']);

        $this->addBreadcrumb('view');

        $this->set('role', $role);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $role = $this->Roles->newEmptyEntity();
        if ($this->request->is('post')) {
            $role = $this->Roles->patchEntity($role, $this->request->getData());
            if ($this->Roles->save($role)) {
                $this->Flash->success(__('The role has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The role could not be saved. Please, try again.'));
        }

        $parentRoles = $this->Roles->ParentRoles->find('treeList', spacer: '-', limit: 200);

        $this->addBreadcrumb('add');

        $this->set(compact('role', 'parentRoles'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $role = $this->Roles->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $role = $this->Roles->patchEntity($role, $this->request->getData());
            if ($this->Roles->save($role)) {
                $this->Flash->success(__('The role has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The role could not be saved. Please, try again.'));
        }

        $parentRoles = $this->Roles->ParentRoles->find('treeList', spacer: '-', limit: 200);

        $this->addBreadcrumb('edit');

        $this->set(compact('role', 'parentRoles'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $role = $this->Roles->get($id);

        if ($this->Roles->delete($role)) {
            $this->Flash->success(__('The role has been deleted.'));
        } else {
            $this->Flash->error(__('The role could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Shows and saves permissions
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|void
     */
    public function editPermissions(?string $id = null)
    {
        if ($this->request->is('post') || $this->request->is('put')) {
            $permissions = $this->request->getData('perms');
            if ($permissions) {
                foreach ($permissions as $resource => $roles) {
                    foreach ($roles as $role => $perm) {
                        if (in_array($perm, ['allow', 'deny', 'inherit'])) {
                            $this->Roles->Permissions->{$perm}($role, $resource);
                        }
                    }
                }
            }
        }

        $role = $this->Roles->get($id);

        $resources = $this->Roles->Permissions->getPermissions($role->id);

        $this->addBreadcrumb('permissions');

        $this->set(compact('role', 'resources'));
    }

    /**
     * Clears role permissions
     *
     * @param string|null $id Role id
     * @return \Cake\Http\Response|null
     */
    public function resetPermissions(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->Roles->Permissions->deleteAll(['role_id is' => $id])) {
            $this->Roles->Permissions->clearPermissionsCache();
            $this->Flash->success(__('Permissions cleared.'));
        } else {
            $this->Flash->error(__('Error while trying to clear permissions.'));
        }

        return $this->redirect(['action' => 'editPermissions', $id]);
    }

    /**
     * Reloads resources
     *
     * This will delete all existing resources and recreate them from plugins.
     *
     * @param \App\Lib\ResourcesExplorer $re Resources explorer instance.
     * @return \Cake\Http\Response|null
     */
    public function reloadResources(ResourcesExplorer $re)
    {
        $this->request->allowMethod(['post', 'delete']);

        /** @var \App\Model\Table\PluginsTable $pluginsTable */
        $pluginsTable = $this->fetchTable('Plugins');
        /** @var \App\Model\Table\ResourcesTable $resourcesTable */
        $resourcesTable = $this->fetchTable('Resources');

        $conn = $resourcesTable->getConnection();
        $conn->transactional(function () use ($resourcesTable, $pluginsTable, $re, $conn): void {
            $this->Roles->Permissions->deleteAll(['1 = 1']);
            $conn->execute('DELETE FROM resources');
            $conn->execute('ALTER TABLE resources AUTO_INCREMENT = 1');

            $plugins = $pluginsTable->getActivePlugins(true, true);
            $resources = $re->getResources($plugins);
            $resourcesTable->addResources($resources);

            $this->Roles->Permissions->allow(1, 1);
            $this->Roles->Permissions->clearPermissionsCache();
        });

        $this->Flash->success(__('All resources were recreated.'));

        return $this->redirect(['action' => 'index']);
    }
}
