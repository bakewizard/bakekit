# BakeKit

[![GitHub stars](https://img.shields.io/github/stars/bakewizard/bakekit?style=social)](https://github.com/bakewizard/bakekit)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
![Coverage](https://img.shields.io/badge/coverage-68.55%25-yellowgreen)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://phpstan.org/)
[![GitHub release](https://img.shields.io/github/v/release/bakewizard/bakekit?label=stable&sort=semver)](https://github.com/bakewizard/bakekit/releases)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/bakewizard/bakekit/blob/develop/LICENSE)

---

**BakeKit** is a developer starter kit for building modern web applications with CakePHP 5.

Think of it as a **CakePHP skeleton application on steroids**.

BakeKit includes:

- Admin panel, authentication, and role management
- Plugin & theme system
- Menu, widget, and settings management
- A ready-to-use CakePHP skeleton application

## Why BakeKit?

Most CakePHP projects start the same way — building the same admin panel, authentication system, and permission logic from scratch, over and over again.

BakeKit is for developers and agencies who want to skip that part and focus on what actually makes their project unique. It is **not meant to dictate how your application should work**. Instead, it gives you the tools needed to build your own system on top of CakePHP.

You remain free to:

- write your own plugins
- customize the admin panel
- design your own themes
- structure your domain logic however you like

## What You Get Out of the Box

BakeKit provides the core infrastructure most applications need:

- **Admin Panel** - a clean backend interface for managing the system.
- **Authentication** - user login and authentication powered by CakePHP.
- **Roles & Permissions** - a flexible role hierarchy with granular access control.
- **Plugin System** - features are packaged as standard CakePHP plugins.
- **Theme System** - frontend layouts and components organized as themes.
- **Menus & Navigation** - dynamic menu management for frontend and backend.
- **Widgets (Cells)** - reusable components that can be placed in regions.
- **Settings** - centralized configuration management.

## Official BakeKit Plugins

Extend your app with ready-made BakeKit plugins:

| Plugin | Description |
|---|---|
| [FileManager](https://github.com/bakewizard/FileManager) | Upload and manage files and images |
| [Pages](https://github.com/bakewizard/Pages) | Static page management |
| [Blogger](https://github.com/bakewizard/Blogger) | Blog engine with posts, categories, tags, and comments |
| [Slideshow](https://github.com/bakewizard/Slideshow) | Image slideshow management |

## Get Started

```bash
composer create-project bakewizard/bakekit myapp
cd myapp
curl -sS https://getcomposer.org/installer | php -- --install-dir=bin
bin/cake install
bin/cake server
```

BakeKit uses a **local Composer instance** (`bin/composer.phar`) to manage plugins and themes.

This keeps application dependencies separate from the plugin ecosystem and allows
BakeKit to install, update, or remove plugins without affecting the main project.

## Contributing

BakeKit is actively looking for contributors!

If you're a CakePHP developer (or want to become one), check out the issues and help improve BakeKit.
Even small contributions like fixing typos, writing tests, or improving docs are welcome!

[👉 How to contribute](CONTRIBUTING.md)

## License

[MIT License](LICENSE)

## Ready to Bake?

With BakeKit, you're not starting from zero — you're starting with a warm, extensible CakePHP base, full of tools ready to power your next idea.

> **If you can make it, bake it with BakeKit.**

⭐ If BakeKit saves you time, consider starring the repository and checking out the [documentation](https://bakewizard.github.io/bakekit/).
