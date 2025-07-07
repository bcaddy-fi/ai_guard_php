
# 🔐 SSO_SETUP.md

## AI Management Portal – SSO Configuration Guide

This guide outlines how to configure **Single Sign-On (SSO)** using **OpenID Connect (OIDC)** providers such as **Azure**, **Google**, or **Keycloak** for the AI Management Portal.

---

## ✅ Requirements

- PHP 8.0+ with `curl` and `json` extensions
- MySQL or MariaDB
- SSO provider with OpenID Connect support
- Publicly reachable `sso_login.php` and `sso_redirect.php`

---

## 1. 📄 Database Table: `sso_providers`

Create a table to store SSO configuration:

```sql
CREATE TABLE sso_providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_name VARCHAR(255),
  issuer_url VARCHAR(500),
  client_id VARCHAR(255),
  client_secret VARCHAR(255),
  redirect_uri VARCHAR(500),
  enabled TINYINT(1) DEFAULT 1
);
```

Insert a provider (example: Keycloak):

```sql
INSERT INTO sso_providers (provider_name, issuer_url, client_id, client_secret, redirect_uri) VALUES (
  'Keycloak',
  'https://sso.example.com/realms/aiguard',
  'client-id-here',
  'client-secret-here',
  'https://yourdomain.com/sso_redirect.php'
);
```

---

## 2. ⚙️ User Table Field Updates

Ensure your `users` table has:

```sql
ALTER TABLE users ADD COLUMN use_sso TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN sso_provider VARCHAR(255) DEFAULT NULL;
```

Set users to use SSO:

```sql
UPDATE users SET use_sso = 1, sso_provider = 'Keycloak' WHERE email = 'user@example.com';
```

---

## 3. 🚦 Redirect URI

In your SSO provider’s dashboard, register this URI:

```
https://yourdomain.com/sso_redirect.php
```

This is where tokens will be returned after authentication.

---

## 4. 🔁 Login Flow

The SSO login flow includes:

1. User accesses `/sso_login.php`
2. App redirects to provider's auth page
3. User logs in
4. Provider returns code to `/sso_redirect.php`
5. App:
   - Validates code
   - Fetches user info
   - Matches or auto-creates user
   - Starts session and redirects to dashboard

---

## 5. 👤 Auto-User Creation (Optional)

When a new SSO user logs in:

- If the email doesn’t exist, auto-create the account with:
  - `role = none`
  - `enabled = 0`

Admin must assign a role manually.

---

## 6. 🛡 Roles & Permissions

Recommended roles:

| Role     | Permissions                          |
|----------|--------------------------------------|
| `read`   | View dashboard only                  |
| `engineer` | Manage models, personas, guardrails |
| `admin`  | All access including user management |

---

## 7. 🧪 Troubleshooting

- `The provider authorization_endpoint could not be fetched`:  
  ➤ Ensure your `issuer_url` exposes a valid `/.well-known/openid-configuration`

- `invalid_grant` or `invalid_client`:  
  ➤ Double-check your `client_id`, `client_secret`, and allowed redirect URIs.

---

## 🔧 Example PHP Usage

In `sso_login.php`, you’ll redirect the user to the provider:

```php
// Fetch provider metadata from DB
$provider = get_provider('Keycloak'); // your function
$oidc = new \Jumbojett\OpenIDConnectClient(
  $provider['issuer_url'],
  $provider['client_id'],
  $provider['client_secret']
);

$oidc->setRedirectURL($provider['redirect_uri']);
$oidc->authenticate();
$userInfo = $oidc->requestUserInfo();
```

---

## 📂 Files Involved

| File              | Description                      |
|-------------------|----------------------------------|
| `sso_login.php`   | Initiates login with provider    |
| `sso_redirect.php`| Handles token and user creation  |
| `auth.php`        | Adds session and SSO logic       |
