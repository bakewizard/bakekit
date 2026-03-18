<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Model\Entity\User;
use App\View\Helper\AuthHelper;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * AuthHelper Test Case
 *
 * @uses \App\View\Helper\AuthHelper
 */
class AuthHelperTest extends TestCase
{
    protected AuthHelper $helper;
    protected ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ServerRequest();
        $view = new View($this->request);
        $this->helper = new AuthHelper($view);
    }

    /**
     * isLoggedIn() returns true when session has Auth.User key.
     */
    public function testIsLoggedInReturnsTrueWhenSessionSet(): void
    {
        $this->request->getSession()->write('Auth.User', ['id' => 1]);

        $this->assertTrue($this->helper->isLoggedIn());
    }

    /**
     * isLoggedIn() returns false when session is empty.
     */
    public function testIsLoggedInReturnsFalseWhenSessionEmpty(): void
    {
        $this->assertFalse($this->helper->isLoggedIn());
    }

    /**
     * isLoggedIn() supports a custom model name.
     */
    public function testIsLoggedInSupportsCustomModel(): void
    {
        $this->request->getSession()->write('Auth.Admin', ['id' => 5]);

        $this->assertTrue($this->helper->isLoggedIn('Admin'));
        $this->assertFalse($this->helper->isLoggedIn('User'));
    }

    /**
     * isUserLoggedIn() returns true when session id matches the entity id.
     */
    public function testIsUserLoggedInReturnsTrueForCurrentUser(): void
    {
        $this->request->getSession()->write('Auth.User', ['id' => 2]);

        $user = new User(['id' => 2]);
        $this->assertTrue($this->helper->isUserLoggedIn($user));
    }

    /**
     * isUserLoggedIn() returns false when session id does not match.
     */
    public function testIsUserLoggedInReturnsFalseForDifferentUser(): void
    {
        $this->request->getSession()->write('Auth.User', ['id' => 1]);

        $user = new User(['id' => 2]);
        $this->assertFalse($this->helper->isUserLoggedIn($user));
    }

    /**
     * isUserLoggedIn() returns false when entity is null.
     */
    public function testIsUserLoggedInReturnsFalseForNull(): void
    {
        $this->assertFalse($this->helper->isUserLoggedIn(null));
    }

    /**
     * get() reads a value from the session.
     */
    public function testGetReadsSessionValue(): void
    {
        $this->request->getSession()->write('Auth.User.id', 42);

        $this->assertSame(42, $this->helper->get('id'));
    }

    /**
     * get() returns null when the key does not exist.
     */
    public function testGetReturnsNullForMissingKey(): void
    {
        $this->assertNull($this->helper->get('nonexistent'));
    }

    /**
     * get() supports a custom model name.
     */
    public function testGetSupportsCustomModel(): void
    {
        $this->request->getSession()->write('Auth.Admin.role', 'superadmin');

        $this->assertSame('superadmin', $this->helper->get('role', 'Admin'));
    }
}
