# 🔐 Access Control

BakeKit provides a hybrid access control system that combines **RBAC** (Role-Based Access Control)
for HTTP request authorization with **ABAC** (Attribute-Based Access Control) for fine-grained
entity-level checks. It uses hierarchical roles and explicit resource declarations via PHP attributes
to give you precise, maintainable control over what users can do.

---

## 1. 👥 Users

- Each **user** is assigned a **role**.
- Users automatically **inherit the permissions** defined for their role.
- Changing a user's role **immediately changes** their permissions.
- Only the **Root user** can create or delete other users.
- Any user can view and edit **their own profile**.

---

## 2. 📋 Roles

Roles are structured as a **upside-down tree**:

- The **Root role** (id=1) has full access to everything and cannot be deleted.
- **Child roles** inherit permissions from their parent unless explicitly overridden.

```
Root
 └── Manager
      ├── Editor
      └── Author
```

> Only the Root user can create, edit, or delete roles.

### Permission inheritance

Each child role can **inherit**, **allow**, or **deny** any resource:

| Status | Meaning |
|--------|---------|
| ✅ Allow | Explicitly grant access |
| ❌ Deny | Explicitly deny access |
| 🧬 Inherit | Follow the parent role's setting |

The closest explicit record in the role hierarchy wins.
If no record exists anywhere in the chain, access is **allowed by default**.

**Example:**

```
Root       → allows Articles
  Manager  → no record    → inherits allow from Root
    Editor → denies Articles/delete → can do everything except delete
```

---

## 3. 🗂️ Resources

Resources are the specific actions that can be controlled via permissions.
They are organized as a tree mirroring the plugin/controller/action structure:

```
Site
 └── Blogger
      ├── Articles
      │    ├── List articles
      │    ├── Create an article
      │    ├── Edit an article
      │    └── Delete an article
      └── Categories
           ├── List categories
           └── Edit a category
```

- **Site** — the root node (required for tree inheritance to work)
- **Blogger** — a plugin name
- **Articles / Categories** — controller names
- **List articles, Edit an article...** — individual actions with human-readable labels

> Resources are added automatically when a plugin is installed and removed when it is uninstalled.

### Declaring resources in a plugin

Only actions explicitly marked with the `#[Resource]` attribute are registered as resources.
This keeps the permissions UI clean — utility methods like `moveUp`, `getCells`, or `getLinks`
are never shown.

```php
<?php

use App\Attribute\Resource;

class ArticlesController extends AppController
{
    #[Resource(label: 'List articles')]
    public function index() { ... }

    #[Resource(label: 'Edit an article')]
    public function edit(int $id) { ... }

    // Not a resource — never appears in the permissions UI
    public function moveUp(int $id) { ... }
}
```

The `label` is optional. If omitted, the method name (alias) is shown as a fallback.

---

## 4. ⚙️ How It Works

### Request-level check (RBAC)

Every request to the admin area is checked against the `resources` table:

```
HTTP request
    → RequestAuthorizationMiddleware
    → RequestPolicy::before()   — Root user? Allow immediately
    → RequestPolicy::canAccess() — build path Site/Plugin/Controller/action
    → PermissionsTable::check() — look up role's permission tree (cached)
    → allow / deny
```

The permission tree is **cached per role** and cleared automatically when
permissions or resources change.

### Entity-level check (ABAC)

For certain entities, a second check runs after the request is allowed:

| Entity | Rule |
|--------|------|
| User | Can only view and edit their own profile |
| Role | Only Root can create, edit, or delete roles |

---

## 5. 🛠️ Managing Permissions

Go to **Site Management → Roles**, then click the permissions button next to a role:

1. Each resource is shown with its human-readable label.
2. Select `Allow`, `Deny`, or `Inherit` for each action.
3. Save to apply.

You can also **reset all permissions** for a role to start fresh.

> The **Root role** permissions are read-only — Root always has full access.

---

## 6. 🔌 For Plugin Developers

To expose actions as manageable permissions, add `#[Resource]` to your controller methods:

```php
<?php

use App\Attribute\Resource;

#[Resource(label: 'Publish an article')]
public function publish(int $id) { ... }
```

**Guidelines:**
- Add `#[Resource]` only to actions an administrator should be able to control.
- Skip utility/AJAX actions (`moveUp`, `getItems`, etc.).
- Use clear English labels — they appear directly in the permissions UI.
- Actions without `#[Resource]` are accessible by default (open-by-default system).

If an action should be restricted to Root only regardless of permissions,
implement a Policy instead of relying on `#[Resource]`.
