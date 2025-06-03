<?php
declare(strict_types=1);

namespace App\Test\TestCase\Core\Configure\Engine;

use App\Core\Configure\Engine\DbConfig;
use Cake\TestSuite\TestCase;

class DbConfigTest extends TestCase
{
    protected array $fixtures = ['app.Settings'];

    protected DbConfig $dbConfig;

    public function setUp(): void
    {
        parent::setUp();
        $table = $this->fetchTable('Settings');
        $this->dbConfig = new DbConfig($table, 'default');
    }

    /**
     * Tests reading a specific namespace
     */
    public function testReadByNamespace(): void
    {
        $result = $this->dbConfig->read('Cms');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Cms', $result);
        $this->assertEquals('BakeKit CMS', $result['Cms']['siteName']);
        $this->assertEquals('jpeg', $result['Cms']['images']['format']);
    }

    /**
     * Tests reading all namespaces using '*'
     */
    public function testReadAll(): void
    {
        $result = $this->dbConfig->read('*');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Cms', $result);
        $this->assertEquals('BakeKit CMS', $result['Cms']['siteName']);
    }

    /**
     * Tests reading an unknown namespace returns an empty array
     */
    public function testReadUnknownNamespaceReturnsEmpty(): void
    {
        $result = $this->dbConfig->read('NonexistentPlugin');
        $this->assertSame([], $result);
    }

    /**
     * Tests that nested keys are grouped under correct structure
     */
    public function testReadGroupsNestedKeys(): void
    {
        $result = $this->dbConfig->read('Cms');
        $this->assertArrayHasKey('maintenance', $result['Cms']);
        $this->assertEquals('0', $result['Cms']['maintenance']['mode']);
    }

    /**
     * Tests writing new settings data using dump()
     */
    public function testDumpWritesData(): void
    {
        $data = [
            'theme' => 'MyTheme',
            'maintenance' => [
                'mode' => '1',
                'message' => 'Down for maintenance',
            ],
        ];

        $result = $this->dbConfig->dump('Cms', $data);
        $this->assertTrue($result);

        $table = $this->fetchTable('Settings');
        $count = $table->find()
            ->where(['namespace' => 'Cms', 'path' => 'maintenance.message'])
            ->count();

        $this->assertSame(1, $count);
    }

    /**
     * Tests dump() returns false if data is empty
     */
    // public function testDumpReturnsFalseOnNoData(): void
    // {
    //     $result = $this->dbConfig->dump('Cms', []);
    //     $this->assertFalse($result);
    // }

    /**
     * Tests overwriting an existing value with dump()
     */
    public function testDumpOverwritesExistingValue(): void
    {
        $data = ['siteName' => 'NewName'];
        $this->dbConfig->dump('Cms', $data);

        $result = $this->dbConfig->read('Cms');
        $this->assertEquals('NewName', $result['Cms']['siteName']);
    }

    /**
     * Tests nested data insertion creates correct dot-path
     */
    public function testDumpAddsNestedSetting(): void
    {
        $data = ['images' => ['compression' => 'auto']];
        $this->dbConfig->dump('Cms', $data);

        $table = $this->fetchTable('Settings');
        $exists = $table->exists([
            'namespace' => 'Cms',
            'path' => 'images.compression',
        ]);

        $this->assertTrue($exists);
    }

    /**
     * Tests that null values are properly stored
     */
    public function testDumpHandlesNullValues(): void
    {
        $data = ['maintenance' => ['allowedIps' => null]];
        $this->dbConfig->dump('Cms', $data);

        $table = $this->fetchTable('Settings');
        $value = $table->find()
            ->where(['namespace' => 'Cms', 'path' => 'maintenance.allowedIps'])
            ->first()?->value;

        $this->assertNull($value);
    }
}
