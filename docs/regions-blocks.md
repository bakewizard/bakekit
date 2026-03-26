# 🧩 Regions & Blocks

Regions & Blocks are the foundation for dynamic content in **BakeKit** themes.
Themes use region placeholders to mark where dynamic content should appear. These placeholders are defined using:

```php
<?= $this->region('region-name'); ?>
```

A region outputs no content by default. To make it functional, you must define it in the admin panel and assign blocks to it.

---

## 🗂️ What are Regions?

A **region** is simply a named container for **blocks**. It does not render anything on its own until it has one or more blocks assigned. You create regions to correspond with placeholders in your theme.

* The region **Alias** must match the placeholder name.
* Use lowercase letters (`a-z`), digits (`0-9`), and hyphens (`-`) for aliases.
* The **Description** only appears in the admin panel for clarity.

---

## 🧱 What are Blocks?

Blocks are the actual content rendered in regions. A block can either:

* Contain **plain text** (manually entered content).
* Render a **[CakePHP 5 cell](https://book.cakephp.org/5/en/views/cells.html)**, optionally with settings and a custom template.

Blocks act as wrappers for CakePHP cells. This makes it easy to drop in widgets like menus, article listings, or custom features.

---

## ⚙️ Managing Regions & Blocks

1. Go to: `SITE MANAGEMENT` → **Regions & Blocks**
2. Click **➕** to add a new region.

   * Fill in the **Alias** (must match your theme's placeholder).
   * Fill in a **Description** (optional, for admin display).
3. After creating a region, click the first button in the **Actions** column to view its blocks.
4. Click **➕** to add a new block:

   * **Alias**: Unique internal identifier.
   * **Title**: Optional heading shown on the frontend as block title.
   * **Description**: Admin-only field.
   * Click the `...` button next to the **Cell** field to open the cell selection window.

### 🔍 Selecting a Cell

* You'll see a list of **plugins**, each collapsible.
* Expand a plugin section to view its available cells with descriptions (e.g. *"Recent articles – Displays a recent articles list"*).
* Click a cell to select it. The modal closes and fills in the cell field with its name (e.g. `Blogger.Article::recent`).
* Check **Enabled** to activate the block.

> 💡 You may optionally enter a template name. If left blank, the system will automatically use the default template corresponding to the cell's action name (e.g., `recent.php` for the `recent` action).

Finally, click **Save & Close**.

> 🔧 Cells appear in this selector only when their methods are annotated with the `#[Link]` attribute. See [Plugins](plugins.md#cell-widgets) for how to declare them in your plugin.

### ✏️ Text-only Blocks

If you leave the **cell field empty**, you can manually fill in the **Content** field to create a basic HTML/text block.

---

## 🔧 Block Settings

If a block uses a cell that supports settings, a **cog button ⚙️** appears in the blocks list. Clicking it opens a settings form.

You can add the settings support by creating a settings form and a view.

### 1. 🧩 Settings Form

Create a form class at `plugins/YourPluginName/src/Form/Cell/ArticleCellConfigForm.php`

Example:

```php
<?php
declare(strict_types=1);

namespace Blogger\Form\Cell;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * CellConfig Form.
 */
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
        return $validator->nonNegativeInteger('numberOfArticlesToShow')->requirePresence('numberOfArticlesToShow');
    }
}
```

This form handles validation.

### 2. 🖼️ Settings Template

Add the template view at `plugins/YourPluginName/templates/Admin/CellConfig/Article/recent.php`

Example:

```php
<div class="card card-success card-outline">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-edit me-2"></i><?= __('Article cell') ?></div>
    </div>
    <?= $this->Form->create($settings, ['align' => 'horizontal']) ?>
    <div class="card-body">
        <?= $this->Form->control('numberOfArticlesToShow'); ?>
    </div>
    <div class="card-footer">
        <?= $this->Form->button('<i class="fa-solid fa-save"></i> ' . __('Save'), ['class' => 'btn-success float-end', 'escapeTitle' => false]) ?>
        <?= $this->Html->link('<i class="fa-solid fa-times-circle"></i> ' . __('Cancel'), ['controller' => 'Regions', 'action' => 'view', $block->region_id], ['class' => 'btn btn-outline-danger', 'escape' => false]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
```

---

## 📚 Learn More

More about CakePHP 5 themes and forms can be found in the official CakePHP Cookbook:

🔗 <https://book.cakephp.org/5/en/views/themes.html>

🔗 <https://book.cakephp.org/5/en/core-libraries/form.html>

> "That's it! 🎉. Now go ahead and define your first region — your layout is about to get a lot more dynamic."
