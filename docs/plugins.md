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

## 🧱 Plugin Requirements

Each plugin **must** contain a valid `composer.json`, for example:

```json
{
  "name": "bakewizard/filemanager",
  "description": "FileManager plugin for BakeKit CMS",
  "type": "cakephp-plugin",
  "autoload": {
    "psr-4": {
      "Pages\\": "src/"
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
The system reads the plugin’s `composer.json` to extract the `description` and, optionally, the `extra.parent-plugin` key (used in the Admin Panel to indicate a parent plugin relationship — for example, a delivery plugin that depends on the Shop plugin).

### 3. **Composer Integration**
BakeKit uses its internal Composer tools to register the plugin:

- `composer.phar` is downloaded during BakeKit's installation and placed in the `bin/` folder.
- Composer is run in a sandboxed mode with custom environment variables to isolate cache and config.
- It’s loaded via the phar:// wrapper.
- Composer is invoked programmatically:
  ```shell
  php bin/composer.phar dump-autoload -o
  ```
✅ No terminal required — Composer is triggered programmatically.

### 4. **Autoload Update**
- The plugin is ready to use immediately — no need to touch your `composer.json`.

---

## 💡 Tip
You can create your own plugins using `cake bake plugin PluginName`, zip them, and upload through the Admin panel.
