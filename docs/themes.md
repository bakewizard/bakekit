# 🎨 Themes

Themes are handled similarly:

- Upload ZIPs containing themes or manually copy folders to the `themes/` directory.
- Activate the theme in `Site Management → Themes`.

Themes support **regions**:

- Regions are placeholders inside template files, defined using:
  ```php
  <?= $this->region('region-name'); ?>
  ```
- Regions only output content if they are created and populated in the Admin Panel under "Site Management → Regions & Blocks".
- Blocks are content containers that are assigned to regions
- A block can either render a block content (CakePHP cell) or static text

This makes layout customization flexible and theme development modular.

