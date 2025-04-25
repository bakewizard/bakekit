# 🧩 Regions & Blocks

In **BakeKit CMS**, themes use region placeholders to mark where dynamic content should appear. These placeholders are defined using:

```php
<?= $this->region('region-name'); ?>
```

A region outputs no content by default. To make it functional, you must define it in the admin panel and assign blocks to it.

## 🗂️ What are Regions?

A **region** is simply a named container for **blocks**. It does not render anything on its own until it has one or more blocks assigned. You create regions to correspond with placeholders in your theme.

- The region **alias** must match the placeholder name.
- Use lowercase letters (`a-z`), digits (`0-9`), and hyphens (`-`) for aliases.
- The **description** appears in the admin list for reference.

## 🧱 What are Blocks?

Blocks are the actual content rendered in regions. A block can either:

- Contain **plain text** (manually entered content).
- Render a **[CakePHP 5 cell](https://book.cakephp.org/5/en/views/cells.html)**, optionally with settings and a custom template.

Blocks act as wrappers for CakePHP cells. This makes it easy to drop in CMS widgets like menus, article listings, or custom features.

## ⚙️ Managing Regions and Blocks

1. Go to: `SITE MANAGEMENT` → **Regions & Blocks**

2. Click **➕** to add a new region.
    - Fill in the **Alias** (must match your theme's placeholder).
    - Fill in a **Description** (optional, for admin display).

3. After creating a region, click the first button in the **Actions** column to view its blocks.

4. Click **➕** to add a new block:
    - **Alias**: Unique internal identifier.
    - **Title**: Optional heading shown on the frontend as block title.
    - **Description**: Admin-only field.
    - Click the `...` button next to the **Cell** field to open the cell selection window.

### 🔍 Selecting a Cell

- You'll see a list of **plugins**, each collapsible.
- Expand a plugin section to view its available cells with descriptions (e.g. _"Recent articles – Displays a recent articles list"_).
- Click a cell to select it. The modal closes and fills in the cell field with its name (e.g. `Blogger.Article::recent`).

You may optionally:

- Provide a **template name** (if left blank, `display.php` is used).
- Check **Enabled** to activate the block.

Finally, click **Save & Close**.

### ✏️ Text-only Blocks

If you leave the **cell field empty**, you can manually fill in the **Content** field to create a basic HTML/text block.

### 🔧 Block Settings

If a block uses a cell that supports settings:

- A **cog button** ⚙️ appears in the blocks list.
- Clicking it opens a settings form (if the cell's plugin includes one, e.g. `PluginName/src/Form/Cell/ArticleCellConfigForm.php`).
- The cell may also use a custom template (e.g. `PluginName/templates/cell/Article/recent.php`).

This gives you full flexibility for widget-style content management, while keeping everything dynamic and reusable.

---

That's it! Regions & Blocks are the foundation for dynamic content in your BakeKit CMS themes. 🎉
