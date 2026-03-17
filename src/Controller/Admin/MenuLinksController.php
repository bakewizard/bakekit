<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\ResourcesExplorer;
use Cake\Cache\Cache;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Override;

/**
 * MenuLinks Controller
 *
 * @property \App\Model\Table\MenuLinksTable $MenuLinks
 * @method \App\Model\Entity\MenuLink[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MenuLink> paginate($object = null, array<string, mixed> $settings = [])
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class MenuLinksController extends AppController
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!$this->request->is('get')) {
            Cache::clear('menus');
        }
    }

    /**
     * Add method
     *
     * @param string|null $id Menu id.
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(?string $id = null)
    {
        $menuLink = $this->MenuLinks->newEmptyEntity();
        $menuLink->menu_id = (int)$id;
        if ($this->request->is('post')) {
            $menuLink = $this->MenuLinks->patchEntity($menuLink, $this->request->getData());
            if ($this->MenuLinks->save($menuLink)) {
                $this->Flash->success(__('Menu link has been added.'));

                return $this->redirect(['controller' => 'Menus', 'action' => 'view', $id]);
            }

            $this->Flash->error(__('There were errors while adding menu link. Please, try again.'));
        }
        $parentMenuLinks = $this->MenuLinks->ParentMenuLinks->find('treeList', spacer: '---', limit: 200)->where(['menu_id' => $id]);
        $targets = ['_self' => __('This tab'), '_blank' => __('New tab')];
        $this->set(compact('menuLink', 'targets', 'parentMenuLinks'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Menu Link id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $menuLink = $this->MenuLinks->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $menuLink = $this->MenuLinks->patchEntity($menuLink, $this->request->getData());
            if ($this->MenuLinks->save($menuLink)) {
                $this->Flash->success(__('The menu link has been saved.'));

                return $this->redirect(['controller' => 'Menus', 'action' => 'view', $menuLink->menu_id]);
            }

            $this->Flash->error(__('The menu link could not be saved. Please, try again.'));
        }
        $parentMenuLinks = $this->MenuLinks->ParentMenuLinks->find('treeList', spacer: '---', limit: 200)->where(['menu_id' => $menuLink->menu_id]);
        $targets = ['_self' => __('This tab'), '_blank' => __('New tab')];
        $this->set(compact('menuLink', 'targets', 'parentMenuLinks'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Menu Link id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $menuLink = $this->MenuLinks->get($id);
        if ($this->MenuLinks->delete($menuLink)) {
            $this->Flash->success(__('The menu link has been deleted.'));
        } else {
            $this->Flash->error(__('The menu link could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Menus', 'action' => 'view', $menuLink->menu_id]);
    }

    /**
     * Moves item up
     *
     * @param string $id
     * @return \Cake\Http\Response|null
     */
    public function moveUp(?string $id = null)
    {
        $this->request->allowMethod(['post', 'put']);
        $menuLink = $this->MenuLinks->get($id);
        $this->MenuLinks->setTreeScope($menuLink->menu_id);
        /** @var \Cake\ORM\Behavior\TreeBehavior $tree */
        $tree = $this->MenuLinks->getBehavior('Tree');
        if ($tree->moveUp($menuLink)) {
            $this->Flash->success(__('The Menu link has been moved Up.'));
        } else {
            $this->Flash->error(__('The Menu link could not be moved up. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Menus', 'action' => 'view', $menuLink->menu_id]);
    }

    /**
     * Moves item down
     *
     * @param string $id
     * @return \Cake\Http\Response|null
     */
    public function moveDown(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put']);
        $menuLink = $this->MenuLinks->get($id);
        $this->MenuLinks->setTreeScope($menuLink->menu_id);
        /** @var \Cake\ORM\Behavior\TreeBehavior $tree */
        $tree = $this->MenuLinks->getBehavior('Tree');
        if ($tree->moveDown($menuLink)) {
            $this->Flash->success(__('The Menu link has been moved down.'));
        } else {
            $this->Flash->error(__('The Menu link could not be moved down. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Menus', 'action' => 'view', $menuLink->menu_id]);
    }

    /**
     * Gets plugin links.
     *
     * @param \App\Lib\ResourcesExplorer $re
     * @param string|null $id Menu id.
     * @return void
     */
    public function getLinks(ResourcesExplorer $re, ?string $id = null)
    {
        $menu = $this->MenuLinks->Menus->get($id);

        /** @var \App\Model\Table\PluginsTable $table */
        $table = $this->fetchTable('Plugins');
        $activePlugins = $table->getActivePlugins();

        $data = $menu->isSystem() ? $re->getAdminLinks($activePlugins) : $re->getLinks($activePlugins);

        $this->set(compact('data', 'menu'));
    }
}
