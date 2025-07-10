
# AI Management Portal

A secure, modular PHP-based management portal for creating, editing, testing, and versioning **Nemo Guardrails**, **AI Personas**, **Models**, and **Agent Rules** using YAML.  
**Built by Bryan Caddy.**

---

## 🚀 Features

- ✅ Centralized YAML management (guardrails, personas, models, agent rules)
- 🧠 Persona Builder with tone, constraints, and instructions
- 🛡 Guardrail Builder with block, log, and notify policy structures
- 📄 Raw YAML Editor with:
  - Version auto-incrementing (`# version: x.y.z`)
  - Top-level key enforcement
  - Syntax validation via Symfony YAML
  - Change diffing and audit logging
- 🕹 Test interfaces for personas, agents, models, and guardrails
- 📦 File-based YAML loading from `/data/` directories
- 🔐 Secure login with role-based access control
- 📜 Full change history logging (`yaml_edit_log`) with a UI for version history
- 🧼 Deletion confirmation requires typing `delete` to prevent accidents
- ⬇️ Download YAML files directly
- 🧭 Navigable UI with consistent structure

---

## 📦 Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB
- Apache or Nginx
- Composer (used for Symfony YAML)

---

## 🗂 Folder Structure

<pre>
/ai_guard_manager/
├── public/
│   ├── SEE BELOW
├── data/
│   ├── persona/
│   ├── guardrails/
│   ├── models/
│   └── agent_rules/
├── app/
│   ├── controllers/
│   │   ├── auth.php
│   │   ├── db.php
│   │   └── logger.php
│   └── helpers/
│       └── yaml_dirs.php
└── includes/
    └── layout.php


Public directory
| Filename | Purpose |
|---------|---------|
| `about.html` | Static about page. |
| `add_test_case.php` | Add new AI test cases linked to personas/guardrails. |
| `add_yaml.php` | Add new YAML files manually for any type. |
| `admin_console.php` | Admin landing page or control panel. |
| `aiguardmanager.png`, `logo.ico`, `Cloud-Shield-white-Logo-*.png` | Static assets for branding/UI. |
| `ai_test_guardrails.php` | Run LLM tests across guardrails. |
| `ai_yamltester.php` | Unified UI for testing persona/guardrail/model/agent YAML files. |
| `api/` | REST or AJAX endpoint folder (details depend on contents). |
| `audit_log.php`, `audit_log_viewer.php` | Display audit log and events. |
| `bug_tracker.php` | Simple internal issue tracker. |
| `build_rule.php` | Form-based rule builder for agents. |
| `captcha_challenge.php` | Custom CAPTCHA implementation endpoint. |
| `dashboard.php`, `dashboard_analytics.php` | Overview pages and test stats. |
| `delete_rule.php`, `delete_yaml.php` | Secure deletion handlers with confirmation prompt. |
| `download_rule.php`, `download_yaml.php` | Download YAML files from any type. |
| `edit_raw_yaml.php` | Edit YAML files directly with versioning. |
| `edit_rule.php` | Old-style rule editor (UI-based). Deprecated. |
| `edit_test_case.php` | Modify existing test cases. |
| `edit_user.php` | Update user account details. |
| `explain_output.php` | Explains why a guardrail/policy triggered. |
| `generate_nightly_test.php` | Schedule or trigger nightly tests. |
| `guardrail_builder.php` | Interactive builder for structured guardrails. |
| `guardrail_import.php` | Import existing YAML guardrails. |
| `help/` | Folder with help HTML content. |
| `help.html`, `help_concepts.html`, `help_guardrails.html`, etc. | Markdown-style docs converted to HTML. |
| `images/` | Static image assets. |
| `includes/` | Core includes (layout, auth, DB, logger). |
| `index.php` | Root file, likely redirects to login/dashboard. |
| `install_checker.php` | Verifies environment and file permissions. |
| `login.php`, `logout.php` | Auth entry and session destroy. |
| `manage_guardrails.php` | Guardrail listing and management page. |
| `manage_models.php` | Model YAML manager. |
| `manage_personas.php` | Manage and test AI personas. |
| `manage_rules.php` | Manage agent rules via file (not DB). |
| `model_builder.php` | Model YAML wizard (config/metadata). |
| `openai_inference.php` | LLM call logic using OpenAI API. |
| `persona_builder.php` | Form-based persona editor. |
| `persona_import.php` | Upload existing persona YAMLs. |
| `run_test.php`, `run_tests.php` | Execute a single or bulk YAML test suite. |
| `sso_config.php`, `sso_login.php`, `sso_redirect.php` | Single Sign-On (SSO) setup and handlers. |
| `sync_yaml_to_db.php` | Sync file-based YAMLs into MySQL. |
| `test_agent.php`, `test_guardrail.php`, `test_persona.php` | Manual test runners by type. |
| `test.php`, `test_yamlfiles.php` | Miscellaneous or debug test runners. |
| `test_api.php` | Test LLM-based response API endpoint. |
| `test_case_admin.php` | Admin UI for managing test cases. |
| `user_admin.php` | Add/edit/delete system users with audit logging. |
| `waf_admin.php` | Web Application Firewall (IP/country restriction settings). |
| `yaml_history.php` | View YAML version history and audit trail. |
</pre>
---

## ⚙️ Installation

1. **Clone the repository:**
   ```bash
   git clone https://yourdomain.com/ai_guard_manager.git
   cd ai_guard_manager
   ```

2. **Set file permissions:**
   ```bash
   chmod -R 755 data/*
   ```

3. **Configure MySQL:**
   - Create a database `ai_guard_manager`
   - Import `database.sql` (includes `users` and `yaml_edit_log`)
   - Add credentials in `app/controllers/db.php`

4. **Create an admin user:**
   Add this to a temporary PHP file:
   ```php
   <?php echo password_hash('yourpassword', PASSWORD_DEFAULT); ?>
   ```
   Use the output to insert into your `users` table.

5. **Secure authentication:**
   Every admin page begins with:
   ```php
   require __DIR__ . '/../app/controllers/auth.php';
   require_login();
   ```

6. **Deploy via Apache/Nginx:**
   Point your web server to the `/public` directory.

7. **Login:**
   Visit `/login.php` and enter your admin credentials.

---

## 📝 Editing YAML

Use `edit_raw_yaml.php` for full control:

- URL example:  
  `edit_raw_yaml.php?file=MyPersona.yaml&type=persona`

- Auto-increments `# version:` on each save  
- Logs changes to `yaml_edit_log`

---

## 🕵️ Version History

Each edit is recorded with:

- Editor email
- File type & name
- Old/New version
- Field-by-field diff
- Timestamp

Access via the **History** button on each `manage_*.php` page.

---

## ⚠️ Safe Deletes

All delete buttons use a confirmation prompt:

> "To confirm deletion of 'file.yaml', type: delete"

---

## 📤 SIEM Logging Example (Guardrails)

```yaml
- name: log-to-siem
  type: notify
  method: POST
  url: https://siem.example.com/ingest
  headers:
    Authorization: "Bearer token"
    Content-Type: "application/json"
  payload: |
    {
      "event_type": "{{ policy.name }}",
      "user_input": "{{ user_input }}",
      "timestamp": "{{ timestamp }}"
    }
```

---

## 📚 Help System

HTML help pages are located in `/public/help/`.
