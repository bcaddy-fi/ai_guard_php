# AI Management Portal

A secure PHP-based management portal for creating, editing, and managing **Nemo Guardrails** YAML policies and AI personas.  
**Built by Bryan Caddy.**

---

## 🚀 Features

- Guardrail YAML Builder (block/log/notify policies)
- Persona Builder for AI roles with tone/rules/constraints
- Inline YAML editing with validation and auto-formatting
- Dashboard with secure login
- SIEM logging-ready YAML templates
- Web Application Firewall (WAF) with IP, country, and exploit protection
- CAPTCHA challenge for rate-limited users (hCaptcha or reCAPTCHA)
- Audit log and WAF denial logs
- Confluence-style help documentation
- Extensible for enterprise policy and persona governance

---

## 📦 Requirements

- PHP 8.0+
- MySQL 5.7+ (or MariaDB)
- Apache or Nginx
- Composer (optional, for YAML parsing)

---

## 📁 Folder Structure
(Partial List)
<pre>
/ai-portal/
├── config/
│   └── waf_config.php ← Editable WAF rules/settings
├── public/
│   ├── captcha_challenge.php
│   ├── waf_admin.php  ← Admin UI to configure WAF
│   ├── persona_help.html
│   ├── guardrail_help.html
│   ├── layout.php
│   ├── login.php
│   ├── logout.php
│   ├── build_persona.php
│   ├── build_guardrail.php
│   ├── manage_personas.php
│   ├── manage_guardrails.php
│   └── help_waf.html  ← WAF help page
│   ├── includes/
│   │   ├── auth.php
│   │   ├── waf.php        ← WAF logic
│   │   └── header.php     ← Central header (includes waf.php)
├── data/
│   ├── persona/
│   └── guardrails/
├── api/
├── app/
│   ├── controllers/
│   │   ├── auth.php
│   │   ├── db.php
│   │   ├── log_api_interaction.php
└── README.md		
</pre>

---

## 🛠️ Installation Steps

1. **Clone the repository:**

   ```bash
   git clone https://yourdomain.com/ai-portal.git
   cd ai-portal
   ```

2. **Set file permissions:**

   ```bash
   chmod -R 755 data/persona
   chmod -R 755 data/guardrails
   ```

3. **Configure MySQL:**

   - Create a database (e.g., `ai_guard_manager`) and define your credentials in `includes/db.php`
   - Import `database.sql`
   - Add this to any temporary file to get a hashed password:

     ```php
     <?php
     echo password_hash('supersecretpassword', PASSWORD_DEFAULT);
     ?>
     ```

4. **Secure authentication:**

   Make sure each PHP page includes:

   ```php
   require 'includes/auth.php';
   require_login();
   ```

5. **Deploy under your web server:**

   Point your Apache/Nginx root to the `/ai-portal` folder.

6. **Login:**

   Navigate to `/login.php` and enter your admin credentials.

---

## 🔒 Web Application Firewall (WAF)

Enable and configure WAF via `/waf_admin.php`.

### Features:

- **IP Filtering**  
  - Block specific IPs (blacklist)  
  - Whitelist specific IPs (block all others)

- **Country Restrictions**  
  - Allow or block access based on IP geolocation

- **SQL Injection Blocking**  
  - Regex detection on `$_GET` and `$_POST` values

- **XSS Attack Blocking**  
  - Detects `<script>` or suspicious tags

- **User-Agent Filtering**  
  - Blocks curl, wget, sqlmap, etc.

- **Referer Validation**  
  - Blocks POST requests with empty or missing referrer headers

- **Rate Limiting**  
  - Denies users after N violations (default: 10 in 10 minutes)

- **CAPTCHA Integration**  
  - hCaptcha or Google reCAPTCHA supported  
  - Triggered after multiple WAF denials  
  - Configurable via `waf_admin.php`

- **JSON-Aware Blocking**  
  - Returns JSON error for API requests with proper headers

- **Audit Logging**  
  - All WAF denials are logged in `waf_denials` table for review

---

## ✍️ Editing Personas & Policies

Use Guardrail Wizard or Persona Builder via the top navigation.

Generated YAML files are stored in:

```
data/guardrails/
data/persona/
```

All files include a header:

```
# Policy built using AI Guard Manager by Bryan Caddy.
```

---

## 🧠 Logging & Auditing (Optional)

Example guardrail action for logging to a SIEM:

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

## 📖 Help

Documentation pages:

- `help/persona_help.html`
- `help/guardrail_help.html`
- `help/help_waf.html` ← WAF usage instructions
