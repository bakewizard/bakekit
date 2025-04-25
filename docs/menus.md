# 📑 Menus

Menus in **BakeKit CMS** allow you to create navigational structures for both the backend (admin interface) and frontend. They are fully customizable and can contain static or dynamic links. Menus can be rendered anywhere in your theme using a region + block combination, just like any other widget.

---

## 🛠️ Creating a Menu

1. Go to: `SITE MANAGEMENT` → **Menus**
2. Click the **➕ icon** to create a new menu.
3. Fill in:
    - **Name**: Display name for admins (e.g. `Main Navigation`)
    - **Description**: Optional helper for admins
    - **Client**: Choose `Frontend` or `Backend`
    - **Enabled**: ✅ Checked to activate
4. Click **Save & Close**

Your new menu will now appear in the Menus list.

---

## 🔗 Adding Links

1. In the **Actions** column of Menus list, click the first button to show links.
2. Click the **➕ icon** to add a link.
3. Fill in:
    - **Title**: Link label (e.g. `Blog`)
    - **Icon**: (Optional) Use **Bootstrap**/**Font Awesome** classes like `bi bi-book` or `fa fa-home`

    - **Link**: You have two options:
        - **Custom link**: Enter a full internal or external URL.
        - **Plugin action**: Click the `...` button next to the link field. This opens a window similar to the **Cell selector** in Blocks. You may select a page from the plugin-provided options, organized by plugin name, to automatically populate the link field.
        - **Target**: Where to open a link(This tab/New tab)
4. Click **Save & Close**

---

## 🧱 Rendering Menus in Theme

To display a menu, you must:

1. Create a region in [**Regions & Blocks**](regions-blocks.md) with **Alias** that matches the placeholder in your theme (`custom-menu`)
2. Add a block with a `Menu cell` to that region:
3. Select the created menu in Block Settings (e.g. `Main Navigation`)

In your theme layout, use:

```php
<?= $this->region('custom-menu'); ?>
```

The menu will now render dynamically wherever you place that region.