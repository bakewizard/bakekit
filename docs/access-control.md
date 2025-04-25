# 📚 Access Control

BakeKit CMS provides a powerful, dynamic, and flexible permission system based on **hierarchical roles** and **dynamic action detection** from plugins. This ensures **flexible control** over what users can and cannot do within the CMS.

---

## 1. 📋 Roles Overview

- Roles are structured **like an upside-down tree**:
    - **Root Role** (topmost) has **all permissions**.
    - **Child Roles** inherit permissions from their parents **unless overridden**.
- The **Root Role**:
    - **Cannot be deleted**.
    - **Always has full access** to everything.

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

## 2. 🔒 Permissions

- Permissions are defined per **controller method** in the system.
- When **a new plugin is installed**, system **automatically scans** the plugin's class methods, and adds new permissions to the permissions list.
- When **a plugin is uninstalled**, Associated permissions are removed automatically.

---

## 3. 🧩 Permission Statuses

Each permission can have one of the following statuses:

| Status  | Meaning |
|---------|---------|
| ✅ Allow | Explicitly allow the action |
| ❌ Deny  | Explicitly deny the action |
| 🧬 Inherit | Follow the parent role's setting |

- **Inherit** means: "Do the same as the parent role."

---

## 4. 🛠️ Managing Roles and Permissions

You can manage roles and permissions via the **Site Management → Roles** page:

1. **View a list** of available resources (Plugins and their actions like `add`, `edit`, `delete`).
2. **Select** permission settings (`Allow`, `Deny`, `Inherit`) for each action.
3. **Save** changes to apply permissions.

You can also reset all **permissions** if needed.

---

## 5. 👤 User Management

- Each **user** in the system is assigned a **role**.
- Users automatically **gain the permissions** defined for their assigned role.
- Changing a user's role **immediately changes** their permissions.

---

## 6. 🔄 Dynamic Permissions from Plugins

- When a plugin is **loaded**, BakeKit **scans** it for **controllers and actions**.
- New permissions **appear** automatically in the Roles management screen.
- When a plugin is **unloaded**, system **removes** its permissions **cleanly**.
