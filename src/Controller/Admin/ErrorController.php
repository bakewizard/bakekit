<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller\Admin;

use Cake\Event\EventInterface;
use Override;

/**
 * Error Handling Controller
 *
 * Controller used by ExceptionRenderer to render error responses.
 *
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class ErrorController extends AppController
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        if (!$this->request->getSession()->check('Auth.User')) {
            $event->setResult($this->redirect([
                        'plugin' => null,
                        'prefix' => 'Admin',
                        'controller' => 'Users',
                        'action' => 'login',
            ]));
        }

        $this->viewBuilder()->setTemplatePath('Admin/Error');
    }
}
