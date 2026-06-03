# 🎨 Themes

Themes control the look and feel of your BakeKit frontend. They package templates, assets, and region definitions together in one place.

---

## 📦 Installing a Theme

Themes are distributed as **ZIP archives** and installed through the Admin Panel.

1. Go to: `SITE MANAGEMENT` → **Themes**
2. Click **Upload Theme** and select a `.zip` file.
3. BakeKit extracts the archive into the `themes/` directory.
4. Regions defined in the theme's `config/regions.php` are **automatically created** in the database.
5. Click **Activate** to make the theme live.

---

## ✅ Activating a Theme

Click **Activate** next to any installed theme. BakeKit will:

1. Save the theme name to the `System` configuration (persisted to the database).
2. Dispatch a `Region.rebuild` event to keep regions in sync with the active theme.

---

## 🗑️ Uninstalling a Theme

1. **Deactivate** the theme first.
2. Click **Uninstall** next to the theme.

> ⚠️ You cannot uninstall the currently active theme. Deactivate it first.

When a theme is uninstalled, all **regions and blocks belonging to that theme** are automatically removed from the database.

---

## 🏗️ Creating a Theme

A minimal theme has this structure:

```
themes/
└── MyTheme/
    ├── composer.json          ← theme metadata
    ├── config/
    │   └── regions.php        ← region definitions (optional)
    └── templates/
        └── layout/
            └── default.php    ← main layout template
```

### composer.json

```json
{
    "name": "vendor/customtheme",
    "description": "My custom BakeKit theme",
    "license": "MIT",
    "type": "cakephp-plugin",
    "keywords": [
        "cakephp",
        "theme",
        "bakekit"
    ],
    "require": {
        "php": ">=8.3",
        "cakephp/cakephp": "5.3.*",
        "bakewizard/bakekit": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "CustomTheme\\": "src/"
        }
    }
}
```

### config/regions.php

Declares the regions your theme provides. Must return a plain PHP array where keys are **aliases** and values are **descriptions**:

```php
<?php
return [
    'header'  => 'Top of the page header area',
    'sidebar' => 'Right-hand sidebar',
    'footer'  => 'Bottom footer area',
];
```

When a theme is installed via ZIP, these regions are automatically inserted into the database. When the theme is uninstalled, they are automatically deleted.

---

## 🧩 Regions & Blocks

Regions are placeholders inside your templates where dynamic content appears. Blocks are the actual content assigned to those placeholders.

### Defining Regions in Templates

Add a region placeholder anywhere in your layout:

```php
<?= $this->region('header'); ?>
```

The alias must exactly match the key defined in `config/regions.php`. The placeholder renders nothing until at least one block is assigned to the region.

### Adding a Block

1. Go to: `SITE MANAGEMENT` → **Themes**
2. Click **Blocks** button in the active theme row.
3. Click **➕** in the corresponding region to add a new block.
4. Fill in the following fields:

    - **Alias** — unique internal identifier.
    - **Title** — optional heading shown on the frontend.
    - **Description** — admin-only notes.
    - **Cell** — click `...` to pick a CakePHP cell from any installed plugin.
    - **Enabled** — check to make the block visible.

> 💡 Leave the **Cell** field empty and fill in the **Content** field to create a plain HTML/text block.

> 💡 Leave the template field blank to use the cell's default template automatically (e.g. `recent.php` for a `recent` action).

> 🔧 Cells appear in the cell picker only when their methods carry the `#[Link]` attribute. See [Plugins](plugins.md#cell-widgets) for details.

---

## 🔧 Block Settings

If a cell supports configurable settings, a **⚙️** button appears in the block list. Clicking it opens a settings form specific to that cell.

To add settings support to a cell, create two files:

### 1. Settings Form

`plugins/YourPlugin/src/Form/Cell/ArticleCellConfigForm.php`

```php
<?php
declare(strict_types=1);

namespace Blogger\Form\Cell;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

class ArticleCellConfigForm extends Form
{
    #[\Override]
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema->addField('numberOfArticlesToShow', ['type' => 'integer', 'default' => 5]);
    }

    #[\Override]
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->nonNegativeInteger('numberOfArticlesToShow')
            ->requirePresence('numberOfArticlesToShow');
    }
}
```

### 2. Settings Template

`plugins/YourPlugin/templates/Admin/CellConfig/Article/recent.php`

```php
<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-edit me-2"></i><?= __('Article cell') ?>
        </div>
    </div>
    <?= $this->Form->create($settings, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('numberOfArticlesToShow'); ?>
    </div>
    <div class="card-footer">
        <?= $this->Form->button(
            '<i class="fa-solid fa-save"></i> ' . __('Save'),
            ['class' => 'btn-success float-end', 'escapeTitle' => false]
        ) ?>
        <?= $this->Html->link(
            '<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'),
            ['controller' => 'Regions', 'action' => 'view', $block->region_id],
            ['class' => 'btn btn-outline-danger', 'escape' => false]
        ) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
```

---

## 📚 Learn More

- [Plugins](plugins.md) — extend BakeKit with installable plugins
- [CakePHP 5 Themes](https://book.cakephp.org/5/en/views/themes.html)
- [CakePHP 5 Cells](https://book.cakephp.org/5/en/views/cells.html)
- [CakePHP 5 Forms](https://book.cakephp.org/5/en/core-libraries/form.html)
