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

    protected ResourcesTable $Resources;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Resources = $this->fetchTable('Resources');
    }

    protected function tearDown(): void
    {
        unset($this->Resources);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // createNode()
    // -------------------------------------------------------------------------

    /**
     * createNode() should create a chain of nested nodes from a slash-separated path.
     */
    public function testCreateNode(): void
    {
        $result = $this->Resources->createNode('Site/SomePlugin/SomeController/someMethod');

        $this->assertNotNull($result);
        $this->assertEquals('someMethod', $result->alias);

        $fullPath = $this->Resources->find('path', for: $result->id)->toArray();
        $this->assertEquals('SomePlugin', $fullPath[1]->alias);
        $this->assertEquals('SomeController', $fullPath[2]->alias);
        $this->assertEquals('someMethod', $fullPath[3]->alias);
    }

    /**
     * createNode() should store the label on the leaf node only.
     * Intermediate nodes created from the path get an empty label.
     */
    public function testCreateNodeStoresLabel(): void
    {
        $result = $this->Resources->createNode('TestPlugin/Posts/edit', null, 'Edit a post');

        $this->assertNotNull($result);
        $this->assertEquals('edit', $result->alias);
        $this->assertEquals('Edit a post', $result->label);

        $fullPath = $this->Resources->find('path', for: $result->id)->toArray();
        $this->assertEquals('', $fullPath[0]->label); // TestPlugin
        $this->assertEquals('', $fullPath[1]->label); // Posts
        $this->assertEquals('Edit a post', $fullPath[2]->label); // edit
    }

    /**
     * createNode() without a label should leave label empty.
     */
    public function testCreateNodeWithoutLabel(): void
    {
        $result = $this->Resources->createNode('NoLabelAction');

        $this->assertNotNull($result);
        $this->assertEquals('', $result->label);
    }

    // -------------------------------------------------------------------------
    // checkNode()
    // -------------------------------------------------------------------------

    /**
     * checkNode() should return null when node does not exist.
     */
    public function testCheckNodeReturnsNullForNonExistent(): void
    {
        $this->assertNull($this->Resources->checkNode('NonExistent'));
    }

    /**
     * checkNode() should return the correct entity when node exists.
     */
    public function testCheckNodeReturnsCorrectEntity(): void
    {
        $node = $this->Resources->createNode('Test');
        $found = $this->Resources->checkNode('Test');

        $this->assertNotNull($found);
        $this->assertEquals($node->id, $found->id);
    }

    // -------------------------------------------------------------------------
    // addResources()
    // -------------------------------------------------------------------------

    /**
     * addResources() should build the full resource tree.
     * Format: [plugin => [controller => [action => label]]].
     */
    public function testAddResourcesCreatesFullTree(): void
    {
        $tree = [
            'BlogPlugin' => [
                'Posts' => [
                    'index' => 'List posts',
                    'add' => 'Create a post',
                    'edit' => 'Edit a post',
                ],
                'Comments' => [
                    'approve' => 'Approve a comment',
                    'delete' => 'Delete a comment',
                ],
            ],
            'UserPlugin' => [
                'Users' => [
                    'index' => 'List users',
                    'edit' => 'Edit a user',
                ],
            ],
        ];

        $this->Resources->addResources($tree);

        $site = $this->Resources->checkNode('Site');
        $this->assertNotNull($site, 'Site root node must exist');

        $blog = $this->Resources->checkNode('BlogPlugin', $site->id);
        $this->assertNotNull($blog);

        $posts = $this->Resources->checkNode('Posts', $blog->id);
        $this->assertNotNull($posts);

        $index = $this->Resources->checkNode('index', $posts->id);
        $this->assertNotNull($index);
        $this->assertEquals('List posts', $index->label);

        $edit = $this->Resources->checkNode('edit', $posts->id);
        $this->assertNotNull($edit);
        $this->assertEquals('Edit a post', $edit->label);

        $comments = $this->Resources->checkNode('Comments', $blog->id);
        $this->assertNotNull($comments);
        $this->assertNotNull($this->Resources->checkNode('approve', $comments->id));
        $this->assertNotNull($this->Resources->checkNode('delete', $comments->id));

        $userPlugin = $this->Resources->checkNode('UserPlugin', $site->id);
        $this->assertNotNull($userPlugin);

        $users = $this->Resources->checkNode('Users', $userPlugin->id);
        $this->assertNotNull($users);
        $this->assertNotNull($this->Resources->checkNode('index', $users->id));
        $this->assertNotNull($this->Resources->checkNode('edit', $users->id));
    }

    /**
     * Controllers with no actions should not be added to the tree.
     */
    public function testAddResourcesSkipsEmptyControllers(): void
    {
        $tree = [
            'TestPlugin' => [
                'EmptyController' => [],
                'Posts' => ['index' => 'List posts'],
            ],
        ];

        $this->Resources->addResources($tree);

        $site = $this->Resources->checkNode('Site');
        $plugin = $this->Resources->checkNode('TestPlugin', $site->id);
        $this->assertNotNull($plugin);

        // EmptyController should not be created
        $this->assertNull($this->Resources->checkNode('EmptyController', $plugin->id));

        // Posts should still be created
        $this->assertNotNull($this->Resources->checkNode('Posts', $plugin->id));
    }

    /**
     * Plugins where all controllers are empty should not be added at all.
     */
    public function testAddResourcesSkipsPluginWithNoActions(): void
    {
        $tree = [
            'EmptyPlugin' => [
                'Posts' => [],
            ],
        ];

        $this->Resources->addResources($tree);

        $site = $this->Resources->checkNode('Site');
        $this->assertNull($this->Resources->checkNode('EmptyPlugin', $site->id));
    }

    // -------------------------------------------------------------------------
    // deleteResources()
    // -------------------------------------------------------------------------

    /**
     * deleteResources() should remove only nodes matching the given alias under parent id=1.
     */
    public function testDeleteResourcesRemovesOnlyMatchingAlias(): void
    {
        $this->Resources->createNode('Site');
        $this->Resources->createNode('ToDelete', 1);

        $this->Resources->deleteResources('ToDelete');

        $this->assertNull($this->Resources->checkNode('ToDelete', 1));
    }
}
