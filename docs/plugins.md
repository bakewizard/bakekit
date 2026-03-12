# 🔌 Plugins

BakeKit's plugin system is **built on Composer**. Every plugin is treated as a standard CakePHP plugin and is dynamically autoloaded behind the scenes using Composer.

The Admin Panel lets you upload, extract, and activate plugins without manually editing files or running Composer from the terminal.

Plugins are stored in the root-level `plugins/` directory.

---

## 📦 ZIP Archive Structure

```
FileManager.zip
└── FileManager/
    ├── config/
    ├── resources/
    ├── src/
    ├── templates/
    ├── webroot/
    └── composer.json
```

---

## 🧱 Requirements

Each plugin **must** contain a valid `composer.json`, for example:

```json
{
    "name": "bakewizard/filemanager",
    "description": "FileManager plugin for BakeKit",
    "license": "MIT",
    "type": "cakephp-plugin",
    "keywords": ["cakephp", "plugin", "file-manager", "file-browser", "bakekit"],
    "require": {
        "php": ">=8.3",
        "cakephp/cakephp": "5.3.*",
        "bakewizard/bakekit": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.5.3 || ^12.1.3 || ^13.0"
    },
    "autoload": {
        "psr-4": {
            "FileManager\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "FileManager\\Test\\": "tests/",
            "FileManager\\Test\\Fixture\\": "tests/Fixture/"
        }
    }
}
```

Optionally, you can define a parent plugin:
```json
"extra": {
  "parent-plugin": "Shop"
}
```

---

## 🚀 How Plugin Loading Works

### 1. **Upload**
Go to `Site Management → Plugins`, upload your `.zip`. BakeKit extracts it to `plugins/`.

### 2. **Read Metadata**
The system reads the plugin’s `composer.json` to extract the `description` and the optional `extra.parent-plugin` key (used in the Admin Panel to indicate a parent plugin relationship — for example, a delivery plugin that depends on the Shop plugin).

### 3. **Composer Integration**
BakeKit uses its internal Composer setup to register the plugin:

- `composer.phar` is downloaded during BakeKit's installation and placed in the `bin/` folder.
- Composer is run in sandboxed mode using custom environment variables to isolate its cache and config.
- It’s invoked via the phar:// wrapper.
- No need to touch your project’s `composer.json` or use the CLI — Composer is invoked programmatically:
  ```shell
  php bin/composer.phar dump-autoload -o
  ```
- The plugin is ready to use instantly — no manual steps needed.

---

## ⚙️ Settings and Dashboard

BakeKit plugins are just standard CakePHP 5 plugins without any extra overhead. But when we want to set plugin settings via the Admin Panel, we need to add some structure.

### 1. 🧩 Settings Form

Create a form class at `plugins/YourPluginName/src/Form/ConfigForm.php`

Example:

```php
<?php
declare(strict_types=1);

namespace FileManager\Form;

use Cake\Core\Configure;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * Config Form.
 */
class ConfigForm extends Form
{
    #[\Override]
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema->addField('basePath', 'string');
    }

    #[\Override]
    public function validationDefault(Validator $validator): Validator
    {
        return $validator->scalar('basePath')
            ->allowEmptyString('basePath')
            ->add('basePath', 'validFolderChars', [
                'rule' => ['custom', '/^(?!\/)(?!.*\.\.)([A-Za-z0-9_\-\/\.]+)$/'],
                'message' => 'Only letters, numbers, dashes, underscores, slashes, and dots are allowed. No backslashes, "..", or leading slash.'
            ]);
    }

    #[\Override]
    protected function _execute(array $data): bool
    {
        Configure::write($data);
        return Configure::dump('FileManager', 'db', array_keys($data));
    }
}
```

This form handles validation, saving configuration into the database, and auto-dumps the settings using CakePHP’s built-in Configure system.

### 2. 🖼️ Settings Template

Add the template view at `plugins/YourPluginName/templates/Admin/Dashboard/settings.php`

Example:

```php
<?php $this->assign('page', __('FileManager Settings')); ?>
<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><?= __('Main') ?></div>
    </div>
    <?= $this->Form->create($settings, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('basePath'); ?>
    </div>
    <div class="card-footer">
        <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), [
            'class' => 'btn-outline-success float-end',
            'escapeTitle' => false
        ]) ?>
        <?= $this->Html->link('<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'), [
            'controller' => 'Dashboard',
            'action' => 'index'
        ], ['class' => 'btn btn-outline-danger', 'escape' => false]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
```

This page displays your plugin’s settings form in the Admin Panel.

You can link to this page via:

🔗 `http://yourdomain.com/admin/plugin-name/dashboard/settings`


### 3. 🧭 Optional Dashboard

You can also add a custom dashboard view at `plugins/YourPluginName/templates/Admin/Dashboard/index.php`

Example:

```php
<?php $this->assign('page', __('File Manager')); ?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-tachometer-alt"></i> <?= __('Dashboard') ?></div>
    </div>
    <div class="card-body">
        <div class="list-group">
            <?= $this->Html->link('<i class="fa-solid fa-cog"></i> Settings', ['plugin' => 'FileManager', 'controller' => 'Dashboard', 'action' => 'settings'], ['escape' => false, 'class' => 'list-group-item']) ?>
        </div>
    </div>
</div>
```

You can also link to this dashboard directly with:

🔗 `http://yourdomain.com/admin/plugin-name/dashboard`

---

That’s it! Once these pieces are in place, your plugin will have a fully working Admin Panel settings and dashboard pages — the BakeKit way.

---

## 📚 Learn More

More about CakePHP 5 plugins and forms can be found in the official CakePHP Cookbook:

🔗 [https://book.cakephp.org/5/en/plugins.html](https://book.cakephp.org/5/en/plugins.html)

🔗 [https://book.cakephp.org/5/en/core-libraries/form.html](https://book.cakephp.org/5/en/core-libraries/form.html)

## 💡 Tip
You can create your own plugins using `cake bake plugin PluginName`, zip them up, and upload through the Admin panel.
