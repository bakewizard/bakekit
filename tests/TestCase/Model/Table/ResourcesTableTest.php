<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ResourcesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ResourcesTable Test Case
 *
 * @uses \App\Model\Table\ResourcesTable
 */
class ResourcesTableTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Resources',
    ];

    /**
     * The ResourcesTable instance under test.
     *
     * @var \App\Model\Table\ResourcesTable
     */
    protected ResourcesTable $Resources;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->Resources = $this->fetchTable('Resources');
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->Resources);
        parent::tearDown();
    }

    /**
     * Test createNode() method
     *
     * Verifies that createNode() correctly creates a chain of
     * nested resources based on a slash-separated path.
     *
     * @return void
     */
    public function testCreateNode(): void
    {
        $result = $this->Resources->createNode('Site/SomePlugin/SomeController/someMethod');

        $this->assertNotFalse($result);
        $this->assertEquals('someMethod', $result->alias);

        $fullPath = $this->Resources->find('path', for: $result->id)->toArray();

        $this->assertNotEmpty($fullPath);
        $this->assertEquals('SomePlugin', $fullPath[1]->alias);
        $this->assertEquals('SomeController', $fullPath[2]->alias);
        $this->assertEquals('someMethod', $fullPath[3]->alias);
    }

    /**
     * Test checkNode() method with non-existent node
     *
     * Verifies that checkNode() returns null when a node
     * with the given alias and parent does not exist.
     *
     * @return void
     */
    public function testCheckNodeReturnsNullForNonExistent(): void
    {
        $this->assertNull($this->Resources->checkNode('NonExistent'));
    }

    /**
     * Test checkNode() method with existing node
     *
     * Verifies that checkNode() returns the correct Resource entity
     * when a node with the given alias exists.
     *
     * @return void
     */
    public function testCheckNodeReturnsCorrectEntity(): void
    {
        $node = $this->Resources->createNode('Test');
        $found = $this->Resources->checkNode('Test');

        $this->assertNotNull($found);
        $this->assertEquals($node->id, $found->id);
    }

    /**
     * Test addResources() method
     *
     * Verifies that addResources() builds a full resource tree
     * from a structured array under a 'Site' root node.
     *
     * Checks both plugin and controller/action nesting.
     *
     * @return void
     */
    public function testAddResourcesCreatesFullTree(): void
    {
        $tree = [
            'BlogPlugin' => [
                'Posts' => ['index', 'add', 'edit'],
                'Comments' => ['approve', 'delete'],
            ],
            'UserPlugin' => [
                'Users' => ['login', 'logout', 'register'],
            ],
        ];

        $this->Resources->addResources($tree);

        $site = $this->Resources->checkNode('Site');
        $this->assertNotNull($site, 'Site node should exist');

        // Check BlogPlugin hierarchy
        $blog = $this->Resources->checkNode('BlogPlugin', $site->id);
        $this->assertNotNull($blog);

        $posts = $this->Resources->checkNode('Posts', $blog->id);
        $this->assertNotNull($posts);
        $this->assertNotNull($this->Resources->checkNode('index', $posts->id));
        $this->assertNotNull($this->Resources->checkNode('add', $posts->id));
        $this->assertNotNull($this->Resources->checkNode('edit', $posts->id));

        $comments = $this->Resources->checkNode('Comments', $blog->id);
        $this->assertNotNull($comments);
        $this->assertNotNull($this->Resources->checkNode('approve', $comments->id));
        $this->assertNotNull($this->Resources->checkNode('delete', $comments->id));

        // Check UserPlugin hierarchy
        $user = $this->Resources->checkNode('UserPlugin', $site->id);
        $this->assertNotNull($user);

        $users = $this->Resources->checkNode('Users', $user->id);
        $this->assertNotNull($users);
        $this->assertNotNull($this->Resources->checkNode('login', $users->id));
        $this->assertNotNull($this->Resources->checkNode('logout', $users->id));
        $this->assertNotNull($this->Resources->checkNode('register', $users->id));
    }

    /**
     * Test deleteResources() method
     *
     * Verifies that deleteResources() deletes only nodes
     * with a specific alias under a given parent ID (default 1).
     *
     * @return void
     */
    public function testDeleteResourcesRemovesOnlyMatchingAliasUnderParentIdOne(): void
    {
        // Seed the structure
        $this->Resources->createNode('Site');
        $this->Resources->createNode('ToDelete', 1);
        $this->Resources->createNode('Other', 2); // under "ToDelete"

        $this->Resources->deleteResources('ToDelete');

        $node = $this->Resources->checkNode('ToDelete', 1);
        $this->assertNull($node);
    }
}
