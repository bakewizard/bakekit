<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * App\Model\Behavior\AttachmentBehavior Test Case
 *
 * AttachmentBehavior hooks into CakePHP's ORM lifecycle to manage file uploads.
 *
 * initialize():
 *   - sets modelPath from registry alias (plugin/table) when not overridden
 *   - allows custom modelPath via config
 *   - sets tableAlias to singular(TableAlias) + 'Files'
 *
 * beforeFind():
 *   - adds containment for the file association
 *   - enables auto-fields
 *
 * beforeMarshal():
 *   - skips processing when no 'uploads' key in data
 *   - populates existing file entries that have no id yet
 *   - appends new file entries from leftover uploads
 *   - skips uploads with UPLOAD_ERR_NO_FILE
 *   - preserves existing file entries that already have an id
 *
 * generatePath():
 *   - returns '' when dirDepth = 0
 *   - returns a path with N segments when dirDepth = N
 *
 * @uses \App\Model\Behavior\AttachmentBehavior
 */
class AttachmentBehaviorTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
    ];

    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Use the real UsersTable which already has AttachmentBehavior attached
        $this->table = TableRegistry::getTableLocator()->get('Users');
    }

    protected function tearDown(): void
    {
        TableRegistry::getTableLocator()->clear();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // initialize()
    // -------------------------------------------------------------------------

    /**
     * Without a custom modelPath the behavior derives it from the table alias:
     * Users (no plugin) → /system/users
     */
    public function testInitializeDefaultModelPathNoPlugin(): void
    {
        $behavior = $this->table->getBehavior('Attachment');
        $this->assertSame('/system/users', $behavior->getConfig('modelPath'));
    }

    /**
     * tableAlias is set to singular(TableAlias) + 'Files'.
     * Users → User + Files → UserFiles
     */
    public function testInitializeSetsTableAlias(): void
    {
        // Manually fire beforeFind — the event only runs on query execution,
        // so we invoke the behavior directly to inspect the contain list.
        $query = $this->table->find();
        $event = new Event('Model.beforeFind', $this->table);
        /** @var \App\Model\Behavior\AttachmentBehavior $behavior */
        $behavior = $this->table->getBehavior('Attachment');
        $behavior->beforeFind($event, $query, new ArrayObject(), true);

        $this->assertArrayHasKey('UserFiles', $query->getContain());
    }

    /**
     * A custom modelPath is stored with a leading slash prepended.
     */
    public function testInitializeCustomModelPath(): void
    {
        // Create a fresh table with custom config
        $table = $this->makeTable('CustomUsers', 'users');
        $table->addBehavior('Attachment', ['modelPath' => 'custom/avatars']);

        $behavior = $table->getBehavior('Attachment');
        $this->assertSame('/custom/avatars', $behavior->getConfig('modelPath'));
    }

    /**
     * Plugin table alias (Plugin.Table) maps to /plugin/table.
     */
    public function testInitializeModelPathWithPlugin(): void
    {
        $table = $this->makeTable('MyPlugin.Photos', 'photos');
        $table->addBehavior('Attachment');

        $behavior = $table->getBehavior('Attachment');
        $this->assertSame('/myplugin/photos', $behavior->getConfig('modelPath'));
    }

    // -------------------------------------------------------------------------
    // beforeFind()
    // -------------------------------------------------------------------------

    /**
     * beforeFind() must add containment for the file association so eager-loading works.
     */
    public function testBeforeFindAddsContain(): void
    {
        $query = $this->table->find();
        $event = new Event('Model.beforeFind', $this->table);

        /** @var \App\Model\Behavior\AttachmentBehavior $behavior */
        $behavior = $this->table->getBehavior('Attachment');
        $behavior->beforeFind($event, $query, new ArrayObject(), true);

        $this->assertArrayHasKey('UserFiles', $query->getContain());
    }

    // -------------------------------------------------------------------------
    // beforeMarshal()
    // -------------------------------------------------------------------------

    /**
     * When 'uploads' is absent from data, beforeMarshal() must return immediately
     * and leave data unchanged.
     */
    public function testBeforeMarshalSkipsWhenNoUploadsKey(): void
    {
        $data = new ArrayObject(['name' => 'Alice']);
        $options = new ArrayObject();
        $event = new Event('Model.beforeMarshal', $this->table);

        /** @var \App\Model\Behavior\AttachmentBehavior $behavior */
        $behavior = $this->table->getBehavior('Attachment');
        $behavior->beforeMarshal($event, $data, $options);

        $this->assertFalse(isset($data['files']), 'files key must not be created when uploads is absent');
    }

    /**
     * A new upload (file entry without id) should have name/path/format/tmp_name populated
     * from the UploadedFile object.
     */
    public function testBeforeMarshalPopulatesNewFileEntry(): void
    {
        $upload = $this->makeUpload('photo.jpg', '/tmp/php_abc', 'image/jpeg');
        $data = new ArrayObject([
            'uploads' => [$upload],
            'files' => [
                ['id' => null, 'name' => '', 'path' => '', 'format' => ''],
            ],
        ]);

        $this->invokeMarshal($data);

        $this->assertSame('photo.jpg', $data['files'][0]['name']);
        $this->assertSame('image/jpeg', $data['files'][0]['format']);
        $this->assertSame('/tmp/php_abc', $data['files'][0]['tmp_name']);
        $this->assertStringStartsWith('/system/users', $data['files'][0]['path']);
    }

    /**
     * An upload with UPLOAD_ERR_NO_FILE should be skipped — the file entry stays blank.
     */
    public function testBeforeMarshalSkipsNoFileError(): void
    {
        $upload = $this->makeUpload('', '', '', UPLOAD_ERR_NO_FILE);
        $data = new ArrayObject([
            'uploads' => [$upload],
            'files' => [
                ['id' => null, 'name' => '', 'path' => '', 'format' => ''],
            ],
        ]);

        $this->invokeMarshal($data);

        // File entry should not have been modified
        $this->assertSame('', $data['files'][0]['name']);
        $this->assertFalse(isset($data['files'][0]['tmp_name']));
    }

    /**
     * A file entry that already has an id (existing record) must be left untouched.
     */
    public function testBeforeMarshalPreservesExistingFileWithId(): void
    {
        $upload = $this->makeUpload('new.jpg', '/tmp/new', 'image/jpeg');
        $data = new ArrayObject([
            'uploads' => [$upload],
            'files' => [
                ['id' => 7, 'name' => 'old.jpg', 'path' => '/old/path', 'format' => 'image/jpeg'],
            ],
        ]);

        $this->invokeMarshal($data);

        // Existing entry must be untouched
        $this->assertSame('old.jpg', $data['files'][0]['name']);
        $this->assertFalse(isset($data['files'][0]['tmp_name']));
    }

    /**
     * Leftover uploads (more uploads than file entries) should be appended as new file entries.
     */
    public function testBeforeMarshalAppendsLeftoverUploads(): void
    {
        $upload1 = $this->makeUpload('a.jpg', '/tmp/a', 'image/jpeg');
        $upload2 = $this->makeUpload('b.jpg', '/tmp/b', 'image/jpeg');

        $data = new ArrayObject([
            'uploads' => [$upload1, $upload2],
            'files' => [], // no pre-existing entries
        ]);

        $this->invokeMarshal($data);

        $this->assertCount(2, $data['files']);
        $this->assertSame('a.jpg', $data['files'][0]['name']);
        $this->assertSame('b.jpg', $data['files'][1]['name']);
    }

    /**
     * Leftover uploads with UPLOAD_ERR_NO_FILE must not be appended.
     */
    public function testBeforeMarshalSkipsLeftoverNoFileError(): void
    {
        $goodUpload = $this->makeUpload('good.jpg', '/tmp/good', 'image/jpeg');
        $emptyUpload = $this->makeUpload('', '', '', UPLOAD_ERR_NO_FILE);

        $data = new ArrayObject([
            'uploads' => [$goodUpload, $emptyUpload],
            'files' => [],
        ]);

        $this->invokeMarshal($data);

        $this->assertCount(1, $data['files']);
        $this->assertSame('good.jpg', $data['files'][0]['name']);
    }

    /**
     * When 'files' key is absent but 'uploads' is present, files must be initialised as [].
     */
    public function testBeforeMarshalInitialisesFilesKeyWhenMissing(): void
    {
        $upload = $this->makeUpload('x.jpg', '/tmp/x', 'image/jpeg');
        $data = new ArrayObject(['uploads' => [$upload]]);

        $this->invokeMarshal($data);

        $this->assertIsArray($data['files']);
        $this->assertCount(1, $data['files']);
    }

    // -------------------------------------------------------------------------
    // generatePath() (via modelPath stored in file entries)
    // -------------------------------------------------------------------------

    /**
     * dirDepth=0 means no subdirectory — path stays as modelPath with no suffix.
     */
    public function testGeneratePathReturnsEmptyStringWhenDepthIsZero(): void
    {
        $upload = $this->makeUpload('img.jpg', '/tmp/img', 'image/jpeg');
        $data = new ArrayObject([
            'uploads' => [$upload],
            'files' => [['id' => null, 'name' => '', 'path' => '', 'format' => '']],
        ]);

        $this->invokeMarshal($data);

        // path must equal exactly the modelPath (no extra segments appended)
        $this->assertSame('/system/users', $data['files'][0]['path']);
    }

    /**
     * dirDepth=N must append N two-character hex segments to modelPath.
     */
    public function testGeneratePathProducesCorrectDepth(): void
    {
        $table = $this->makeTable('Photos', 'photos');
        $table->addBehavior('Attachment', ['dirDepth' => 3]);

        $upload = $this->makeUpload('img.jpg', '/tmp/img', 'image/jpeg');
        $data = new ArrayObject([
            'uploads' => [$upload],
            'files' => [['id' => null, 'name' => '', 'path' => '', 'format' => '']],
        ]);

        $event = new Event('Model.beforeMarshal', $table);
        /** @var \App\Model\Behavior\AttachmentBehavior $behavior */
        $behavior = $table->getBehavior('Attachment');
        $behavior->beforeMarshal($event, $data, new ArrayObject());

        $path = $data['files'][0]['path'];
        // Must match: /system/photos/xx/xx/xx (3 hex pairs)
        $this->assertMatchesRegularExpression(
            '#^/system/photos(/[0-9a-f]{2}){3}$#',
            $path,
        );
    }

    /**
     * Two invocations with dirDepth > 0 should (almost certainly) produce different paths.
     */
    public function testGeneratePathIsRandom(): void
    {
        $table = $this->makeTable('Photos2', 'photos');
        $table->addBehavior('Attachment', ['dirDepth' => 2]);

        $paths = [];
        for ($i = 0; $i < 10; $i++) {
            $upload = $this->makeUpload('img.jpg', '/tmp/img', 'image/jpeg');
            $data = new ArrayObject([
                'uploads' => [$upload],
                'files' => [['id' => null, 'name' => '', 'path' => '', 'format' => '']],
            ]);

            $event = new Event('Model.beforeMarshal', $table);
            /** @var \App\Model\Behavior\AttachmentBehavior $behavior */
            $behavior = $table->getBehavior('Attachment');
            $behavior->beforeMarshal($event, $data, new ArrayObject());
            $paths[] = $data['files'][0]['path'];
        }

        // At least 2 distinct paths out of 10 iterations — statistically guaranteed
        $this->assertGreaterThan(1, count(array_unique($paths)));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Creates a mock UploadedFileInterface with the given attributes.
     */
    private function makeUpload(
        string $clientFilename,
        string $tmpUri,
        string $mediaType,
        int $error = UPLOAD_ERR_OK,
    ): UploadedFileInterface {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')->with('uri')->willReturn($tmpUri);

        $upload = $this->createMock(UploadedFileInterface::class);
        $upload->method('getError')->willReturn($error);
        $upload->method('getClientFilename')->willReturn($clientFilename);
        $upload->method('getClientMediaType')->willReturn($mediaType);
        $upload->method('getStream')->willReturn($stream);

        return $upload;
    }

    /**
     * Dispatches beforeMarshal on the UsersTable behavior.
     */
    private function invokeMarshal(ArrayObject $data): void
    {
        $event = new Event('Model.beforeMarshal', $this->table);
        /** @var \App\Model\Behavior\AttachmentBehavior $behavior */
        $behavior = $this->table->getBehavior('Attachment');
        $behavior->beforeMarshal($event, $data, new ArrayObject());
    }

    /**
     * Creates a minimal standalone Table with the given alias and DB table.
     *
     * @param string $alias  Registry alias (e.g. 'MyPlugin.Photos')
     * @param string $dbTable Actual DB table name
     */
    private function makeTable(string $alias, string $dbTable): Table
    {
        $table = TableRegistry::getTableLocator()->get($alias, [
            'table' => $dbTable,
            'className' => Table::class,
        ]);
        $table->setTable($dbTable);

        return $table;
    }
}
