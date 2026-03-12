# 🔐 Access Control

BakeKit provides a powerful, flexible access control system for managing users, roles, and permissions.
It uses hierarchical roles and dynamic action detection from plugins to automatically identify available actions.
This approach gives you fine-grained, dynamic control over what users can see and do within the system, making it easy to adapt permissions as your project grows.

---

## 1. 👥 Users

- Each **user** in the system is assigned a **role**.
- Users automatically **gain the permissions** defined for their assigned role.
- Changing a user's role **immediately changes** their permissions.

---

## 2. 📋 Roles

- Roles are structured **like an upside-down tree**:
    - **Root Role** (topmost) has **all permissions**.
    - **Child Roles** inherit permissions from their parents **unless overridden**.

> The **Root Role** cannot be deleted and always has full access to everything.

Example:

```
Root
 ├── Manager
 │    ├── Editor
 │    └── Author
 └── Support
```

Each child can **inherit**, **allow**, or **deny** specific actions.

---

## 3. 📜 Permissions

- Each Permission is tied to a resource.
- Each permission can have one of the following statuses:


| Status  | Meaning |
|---------|---------|
| ✅ Allow | Explicitly allow the action |
| ❌ Deny  | Explicitly deny the action |
| 🧬 Inherit | Follow the parent role's setting |

---

## 4. 🗂️ Resources

`Resources` are the actions of BakeKit that you can control with permissions.

They are organized hierarchically, like roles:

```
Blogger
 ├── Articles
 │    ├── index
 │    ├── view
 |    └── add
 └── Categories
      ├── index
      ├── add
      └── edit
```

In this example:

- Blogger is a plugin.
- Articles and Categories are controllers of the Blogger.
- index, view, add, edit are the individual actions a user can perform.

> When a plugin is installed, its resources are added to the permissions list and removed upon uninstallation.

---

## 5. 📜 How It works

You can **Allow** or **Deny** permissions at any level.
Higher-level permissions automatically affect everything inside.

For example:

If you **Allow** access to **Articles**, the role will automatically be able to:

- View all articles
- Add new articles
- See the articles list

If you **Deny** access to **Categories**, the role will not see or manage categories at all.

You can still ***fine-tune***:

- Allow general access to **Articles**, but **Deny** just the **add** action if you don't want a role to create new content.

---

## 6. 🛠️ Setting Permissions

You can manage roles and permissions via the **Site Management → Roles** page:

1. Click the first button in **Actions** column to view a list of available resources.
2. Select permission settings (`Allow`, `Deny`, `Inherit`) for each action.
3. Save changes to apply permissions.

You can also reset all **permissions** if needed.

