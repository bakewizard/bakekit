<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AppMigration extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('resources')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('lft', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('rght', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('alias', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('label', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addPrimaryKey('id')
            ->addForeignKey('parent_id', 'resources', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addIndex('parent_id')
            ->create();

        $this->table('roles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('lft', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('rght', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('alias', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addPrimaryKey('id')
            ->addForeignKey('parent_id', 'roles', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addIndex('parent_id')
            ->addIndex('alias', ['unique' => true])
            ->create();

        $this->table('permissions')
            ->addColumn('role_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('resource_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('allowed', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['role_id', 'resource_id'])
            ->addForeignKey('role_id', 'roles', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addForeignKey('resource_id', 'resources', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->create();

        $this->table('plugins')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('alias', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => true,
            ])
            ->addColumn('parent_plugin', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('enabled', 'boolean', [
                'default' => '0',
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey('id')
            ->addIndex('alias', ['unique' => true])
            ->create();

        $this->table('settings')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('namespace', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('path', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addPrimaryKey('id')
            ->addIndex(['namespace', 'path'], ['unique' => true])
            ->create();

        $this->table('regions')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('alias', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addPrimaryKey('id')
            ->addIndex('alias', ['unique' => true])
            ->create();

        $this->table('menus')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addPrimaryKey('id')
            ->create();

        $this->table('menu_links')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('menu_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('icon', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('link', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('target', 'string', [
                'default' => '_self',
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('lft', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('rght', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addPrimaryKey('id')
            ->addForeignKey('menu_id', 'menus', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addForeignKey('parent_id', 'menu_links', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addIndex('menu_id')
            ->addIndex('parent_id')
            ->create();

        $this->table('blocks')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('alias', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => true,
            ])
            ->addColumn('region_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('cell', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('template', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('position', 'integer', [
                'default' => null,
                'limit' => 5,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('enabled', 'boolean', [
                'default' => '0',
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey('id')
            ->addForeignKey('region_id', 'regions', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addIndex('alias', ['unique' => true])
            ->addIndex('region_id')
            ->create();

        $this->table('meta')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('plugin_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('seo_title', 'string', [
                'default' => null,
                'limit' => 160,
                'null' => true,
            ])
            ->addColumn('seo_description', 'string', [
                'default' => null,
                'limit' => 280,
                'null' => true,
            ])
            ->addColumn('seo_keywords', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addPrimaryKey('id')
            ->addForeignKey('plugin_id', 'plugins', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addIndex('plugin_id')
            ->create();

        $this->table('users')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('first_name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('last_name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('alias', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('role_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addPrimaryKey('id')
            ->addForeignKey('role_id', 'roles', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->addIndex('role_id')
            ->addIndex('email', ['unique' => true])
            ->addIndex('alias', ['unique' => true])
            ->create();

        $this->table('user_images')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('path', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addPrimaryKey('id')
            ->addForeignKey('user_id', 'users', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addIndex('user_id')
            ->create();

        $this->table('blocks_i18n')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 10,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('locale', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('params', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addPrimaryKey(['id', 'locale'])
            ->create();

        $this->table('meta_i18n')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 10,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('locale', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('seo_title', 'string', [
                'default' => null,
                'limit' => 160,
                'null' => true,
            ])
            ->addColumn('seo_description', 'string', [
                'default' => null,
                'limit' => 280,
                'null' => true,
            ])
            ->addColumn('seo_keywords', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addPrimaryKey(['id', 'locale'])
            ->create();

        $this->table('menu_links_i18n')
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 10,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('locale', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addPrimaryKey(['id', 'locale'])
            ->create();
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('blocks_i18n')->drop()->save();
        $this->table('meta_i18n')->drop()->save();
        $this->table('menu_links_i18n')->drop()->save();
        $this->table('user_images')->drop()->save();
        $this->table('users')->drop()->save();
        $this->table('meta')->drop()->save();
        $this->table('blocks')->drop()->save();
        $this->table('menu_links')->drop()->save();
        $this->table('plugins')->drop()->save();
        $this->table('settings')->drop()->save();
        $this->table('regions')->drop()->save();
        $this->table('menus')->drop()->save();
        $this->table('permissions')->drop()->save();
        $this->table('resources')->drop()->save();
        $this->table('roles')->drop()->save();
    }
}
