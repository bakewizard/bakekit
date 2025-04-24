# 🛠️ Install BakeKit

Follow the steps below to install **BakeKit**, a modular CakePHP 5-based CMS starter with support for plugins, themes, widgets, and more.

---

## 📦 Requirements

Make sure your environment meets the following:

- PHP **8.1+**
- [Composer](https://getcomposer.org/)
- A web server (e.g., Apache or Nginx)
- A database (MySQL, MariaDB, etc.)

---

## 🚀 Installation

```bash
# 1. Clone the repository
git clone --depth 1 https://github.com/bakewizard/BakeKit.git

# 2. Change directory
cd BakeKit

# 3. Remove local Git history
rm -rf .git

# 4. Download Composer into /bin folder
curl -sS https://getcomposer.org/installer | php -- --install-dir=bin

# 5. Install PHP dependencies
bin/composer.phar install

# 6. Run the CakePHP installation script
bin/cake install
```
