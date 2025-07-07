
### 📘 How to Set the OpenAI API Key as an Environment Variable (PHP + Apache/Nginx)

Securely storing your OpenAI API key in an environment variable keeps it out of your codebase and version control. Below are three recommended ways to set it depending on your server setup.

---

## 🔧 Option 1: Apache (mod_php)

1. **Edit Apache Virtual Host Configuration**
   - File: `/etc/apache2/sites-available/000-default.conf` (or your custom vhost)

2. **Add the environment variable:**
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /var/www/html

       # Set the OpenAI API key
       SetEnv OPENAI_API_KEY "sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
   </VirtualHost>
   ```

3. **Restart Apache:**
   ```bash
   sudo systemctl restart apache2
   ```

4. **Access the variable in PHP:**
   ```php
   $api_key = getenv('OPENAI_API_KEY');
   ```

---

## 🔧 Option 2: Nginx + PHP-FPM

1. **Edit PHP-FPM Pool Configuration**
   - File: `/etc/php/8.x/fpm/pool.d/www.conf`

2. **Add the variable:**
   ```ini
   env[OPENAI_API_KEY] = "sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
   ```

3. **Restart PHP-FPM and Nginx:**
   ```bash
   sudo systemctl restart php8.x-fpm
   sudo systemctl restart nginx
   ```

4. **Access in PHP:**
   ```php
   $api_key = getenv('OPENAI_API_KEY');
   ```

---

## 🔧 Option 3: PHP Dotenv (.env File)

> Use this if you are using Laravel or want to load from `.env` manually in a custom app.

1. **Install Dotenv:**
   ```bash
   composer require vlucas/phpdotenv
   ```

2. **Create a `.env` file in your project root:**
   ```
   OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```

3. **Load it in PHP:**
   ```php
   use Dotenv\Dotenv;

   $dotenv = Dotenv::createImmutable(__DIR__);
   $dotenv->load();

   $api_key = $_ENV['OPENAI_API_KEY'];
   ```

---

## 🛡️ Security Tips

- Never hardcode API keys directly in your PHP files.
- Always add `.env` to `.gitignore` to prevent accidental exposure.
- Use `getenv()` or `$_ENV` to access the key safely at runtime.
- Limit file and process access to authorized users only.

