# ATLAAS

**Augmented Teaching & Learning Assistive AI System**

ATLAAS is a Laravel + Inertia (React) application for district-scoped teaching and learning: multi-tenant structure (district → school → user), role-based access (district admin, school admin, teacher, student), separate teacher and student portals, the ATLAAS assistive AI in student sessions, queued safety alerts, and session summaries.

---

## Table of contents

1. [What you are installing](#what-you-are-installing)
2. [Server requirements (Linux)](#server-requirements-linux)
3. [Install system packages](#install-system-packages)
4. [Database (MySQL or MariaDB)](#database-mysql-or-mariadb)
5. [Redis](#redis)
6. [Deploy application code](#deploy-application-code)
7. [Environment file (`.env`)](#environment-file-env)
8. [Web server: Apache](#web-server-apache)
9. [TLS (HTTPS) with Let’s Encrypt](#tls-https-with-lets-encrypt)
10. [Queues and Laravel Horizon](#queues-and-laravel-horizon)
11. [Task scheduler (cron)](#task-scheduler-cron)
12. [Google Workspace sign-in (OAuth)](#google-workspace-sign-in-oauth)
13. [LLM provider (OpenAI, local, Azure, Anthropic via gateway)](#llm-provider-openai-local-azure-anthropic-via-gateway)
14. [Production hardening checklist](#production-hardening-checklist)
15. [Reference: `.env` variables](#reference-env-variables)
16. [Demo accounts (after seeding)](#demo-accounts-after-seeding)
17. [Implementation phases](#implementation-phases)
18. [License](#license)

---

## What you are installing

| Component | Role |
|-----------|------|
| **Apache** (or nginx) | Serves HTTP/HTTPS and forwards PHP requests to the app |
| **PHP 8.2+** | Runs Laravel |
| **PHP extensions** | Required by Laravel and this project (see below) |
| **Composer** | PHP dependency manager |
| **Node.js 20+** and **npm** | Builds frontend assets (Vite/React); not needed on the server after `npm run build` if you build elsewhere |
| **MySQL or MariaDB** | Primary database (recommended for production) |
| **Redis** | Queues (`QUEUE_CONNECTION=redis`) and Horizon metadata |
| **Laravel Horizon** | Supervises queue workers (requires `ext-pcntl` and `ext-posix` on Linux) |

Optional but typical for production: **Postfix** or SMTP relay for mail, **Certbot** for TLS certificates, **UFW** or cloud firewall rules.

---

## Server requirements (Linux)

These instructions assume **Ubuntu Server 22.04 LTS or 24.04 LTS**. Adapt package names for Debian, RHEL, AlmaLinux, etc.

- A **non-root sudo user** for administration
- **Public hostname** (e.g. `atlaas.yourdistrict.edu`) with DNS **A/AAAA** records pointing to the server
- **At least 2 GB RAM** (more if you run local LLMs on the same host)

---

## Install system packages

### 1. Update the system

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install Apache, PHP, and required PHP extensions

Laravel and ATLAAS need a modern PHP and common extensions:

```bash
sudo apt install -y \
  apache2 \
  libapache2-mod-php8.3 \
  php8.3 \
  php8.3-cli \
  php8.3-common \
  php8.3-curl \
  php8.3-mbstring \
  php8.3-xml \
  php8.3-zip \
  php8.3-bcmath \
  php8.3-intl \
  php8.3-mysql \
  php8.3-sqlite3 \
  php8.3-readline \
  php8.3-gd \
  php8.3-pcntl \
  php8.3-posix
```

If `php8.3` is unavailable on your release, use `php8.2` consistently in package names.

**Why these matter**

- `curl`, `mbstring`, `xml`, `zip`, `bcmath`, `intl`, `mysql`, `sqlite3`: Laravel and dependencies
- `pcntl`, `posix`: **Laravel Horizon** (queue master process on Linux)
- `readline`, `gd`: useful for CLI and common packages

Enable Apache modules used by Laravel (rewrite, headers; optional SSL):

```bash
sudo a2enmod rewrite headers ssl
sudo systemctl restart apache2
```

### 3. Install Composer (globally)

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
composer --version
```

### 4. Install Node.js 20 LTS (for building assets on the server or a CI machine)

Using NodeSource (example for Ubuntu):

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

You can instead build assets on a developer machine and deploy only `public/build/`.

---

## Database (MySQL or MariaDB)

### Install MariaDB (example)

```bash
sudo apt install -y mariadb-server
sudo mysql_secure_installation
```

### Create database and user

```bash
sudo mysql -u root -p
```

In the MySQL shell:

```sql
CREATE DATABASE atlaas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'atlaas'@'localhost' IDENTIFIED BY 'choose_a_strong_password_here';
GRANT ALL PRIVILEGES ON atlaas.* TO 'atlaas'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Production:** Prefer a dedicated DB user with only the `atlaas` database privileges. If the DB is on another host, use `'atlaas'@'%'` only with firewall rules and TLS to the DB.

You will set `DB_*` variables in `.env` to match (see [Reference: `.env` variables](#reference-env-variables)).

---

## Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

**Harden Redis for internet-facing servers**

- By default Redis often listens on `127.0.0.1` only — **keep it that way** unless you have a separate app tier; then use TLS, ACL passwords, and firewall rules.
- Set `requirepass` in `redis.conf` if anything beyond localhost can reach Redis.
- In `.env` set `REDIS_PASSWORD` when you enable authentication.

ATLAAS uses Redis for queues (`QUEUE_CONNECTION=redis`) and Horizon.

---

## Deploy application code

Example: deploy under `/var/www/atlaas` with ownership for the web user (`www-data` on Ubuntu).

```bash
sudo mkdir -p /var/www
sudo chown $USER:$USER /var/www
cd /var/www
git clone <your-repo-url> atlaas
cd atlaas
```

Install PHP dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

Copy environment file and generate key:

```bash
cp .env.example .env
php artisan key:generate
```

Install JS dependencies and build production assets:

```bash
npm ci
npm run build
```

Run migrations and (optional) seed demo data:

```bash
php artisan migrate --force
# Optional demo users/spaces:
php artisan db:seed --force
```

Create storage link and fix permissions (critical on Linux):

```bash
php artisan storage:link
sudo chown -R www-data:www-data /var/www/atlaas/storage /var/www/atlaas/bootstrap/cache
sudo chmod -R ug+rwx /var/www/atlaas/storage /var/www/atlaas/bootstrap/cache
```

**Never** make the whole project world-writable. Only `storage` and `bootstrap/cache` need write access by the web/PHP user.

---

## Environment file (`.env`)

1. Copy from example: `cp .env.example .env`
2. Run `php artisan key:generate` once
3. Edit `.env` for production (see full reference below)

**Minimum production changes**

| Variable | Production value |
|----------|------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://your.full.hostname` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Your MySQL settings |
| `QUEUE_CONNECTION` | `redis` |
| `REDIS_*` | Match your Redis install |
| `SESSION_DRIVER` | `database` or `redis` |
| `SESSION_SECURE_COOKIE` | `true` when using HTTPS only |
| `MAIL_*` | Real SMTP for alerts and system mail |

After changing `.env`:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

To clear caches during troubleshooting:

```bash
php artisan optimize:clear
```

### Outgoing email (SMTP, app password, TLS vs SSL)

ATLAAS sends mail through Laravel’s **SMTP** mailer (e.g. safety alerts). Configure the mailbox and encryption in `.env`.

| Setting | Purpose |
|---------|---------|
| `MAIL_MAILER` | Set to `smtp` for real delivery (use `log` in local dev). |
| `MAIL_HOST` | SMTP server (e.g. `smtp.gmail.com`, `smtp.office365.com`). |
| `MAIL_PORT` | Usually **587** (TLS/STARTTLS) or **465** (SSL). |
| `MAIL_USERNAME` | Full email address for the sending mailbox. |
| `MAIL_PASSWORD` | **App password** or SMTP password (not your normal login if the provider forbids it). |
| `MAIL_FROM_ADDRESS` | “From” address (often the same as `MAIL_USERNAME` for Gmail/Workspace). |
| `MAIL_FROM_NAME` | Display name (e.g. `ATLAAS`). |

**Encryption — two equivalent ways** (if both are set, `MAIL_SCHEME` wins):

1. **`MAIL_SCHEME`** (Symfony mailer): `smtp` = use STARTTLS (typical with port **587**); `smtps` = implicit SSL (typical with port **465**).
2. **`MAIL_ENCRYPTION`**: `tls` or `starttls` → same as `MAIL_SCHEME=smtp`; `ssl` or `smtps` → same as `MAIL_SCHEME=smtps`.

Examples:

```env
# Google Workspace / Gmail (STARTTLS)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=noreply@yourdistrict.edu
MAIL_PASSWORD=your-16-char-app-password
MAIL_FROM_ADDRESS=noreply@yourdistrict.edu
MAIL_FROM_NAME="ATLAAS"

# Same provider using friendly TLS alias (omit MAIL_SCHEME)
# MAIL_ENCRYPTION=tls

# Implicit SSL (port 465)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_SCHEME=smtps
```

Optional: `MAIL_TIMEOUT` (seconds). After changes run `php artisan config:cache`.

---

## Web server: Apache

Point the site **`DocumentRoot`** at the `public` directory, not the project root.

Example virtual host: `/etc/apache2/sites-available/atlaas.conf`

```apache
<VirtualHost *:80>
    ServerName atlaas.yourdistrict.edu
    DocumentRoot /var/www/atlaas/public

    <Directory /var/www/atlaas/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/atlaas-error.log
    CustomLog ${APACHE_LOG_DIR}/atlaas-access.log combined
</VirtualHost>
```

Enable the site and reload:

```bash
sudo a2dissite 000-default.conf
sudo a2ensite atlaas.conf
sudo systemctl reload apache2
```

Laravel’s `public/.htaccess` handles front-controller routing when `AllowOverride All` is set.

**If you use php-fpm with Apache** instead of `libapache2-mod-php`, configure `ProxyPassMatch` or `SetHandler` to the PHP-FPM socket; the `DocumentRoot` rule is the same: **`public`**.

---

## TLS (HTTPS) with Let’s Encrypt

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d atlaas.yourdistrict.edu
```

Certbot will install a vhost for SSL. After HTTPS works:

- Set `APP_URL=https://atlaas.yourdistrict.edu` in `.env`
- Set `SESSION_SECURE_COOKIE=true`
- Optionally add `SESSION_DOMAIN=.yourdistrict.edu` if you share cookies across subdomains (only if you understand the implications)

Reload config cache: `php artisan config:cache`

---

## Queues and Laravel Horizon

Horizon runs queue workers for jobs (safety alerts, session summaries, etc.).

**Install Supervisor** so Horizon restarts if it exits:

```bash
sudo apt install -y supervisor
```

Create `/etc/supervisor/conf.d/atlaas-horizon.conf`:

```ini
[program:atlaas-horizon]
process_name=%(program_name)s
command=php /var/www/atlaas/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/atlaas/storage/logs/horizon.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start atlaas-horizon
```

Horizon’s UI is at `https://your-host/horizon`. Access is restricted to users with the **`district_admin`** role (see `app/Providers/HorizonServiceProvider.php`).

**Firewall:** Do not expose Redis (6379) or MySQL (3306) to the public internet.

---

## Task scheduler (cron)

Laravel’s scheduler runs maintenance and scheduled jobs. Add to `www-data` crontab (or the user that runs PHP):

```bash
sudo crontab -e -u www-data
```

Add:

```
* * * * * cd /var/www/atlaas && php artisan schedule:run >> /dev/null 2>&1
```

---

## Google Workspace sign-in (OAuth)

ATLAAS uses **Laravel Socialite** with the **`google`** driver. The login button points to `/auth/google`; Google redirects back to `/auth/google/callback`.

**Important behavior in this app**

- The user’s **email must already exist** in ATLAAS as an **active** user. Google sign-in **does not** auto-provision accounts; unknown emails see an error on the login page.
- Plan: create users (or sync roster) first, then allow Google for those addresses.

### A. Google Cloud project

1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Create a **project** (e.g. `ATLAAS Production`) or select an existing one.
3. For **Workspace-only** logins, your organization may use an **internal** OAuth consent screen (requires Google Workspace + Cloud Identity organization setup). Otherwise use **External** and add **test users** until the app is verified.

### B. OAuth consent screen

1. **APIs & Services → OAuth consent screen**
2. Choose **Internal** (Workspace users in your org only) or **External**
3. Fill app name, support email, authorized domains (your public domain, e.g. `yourdistrict.edu`)
4. Scopes: Socialite typically needs **openid**, **email**, **profile** (Google may add these by default when you configure the client)

### C. Create OAuth client credentials

1. **APIs & Services → Credentials → Create credentials → OAuth client ID**
2. Application type: **Web application**
3. **Authorized JavaScript origins**  
   - `https://atlaas.yourdistrict.edu`
4. **Authorized redirect URIs** (must match exactly)  
   - `https://atlaas.yourdistrict.edu/auth/google/callback`
5. Save the **Client ID** and **Client secret**

### D. `.env` and `config/services.php`

Set in `.env`:

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://atlaas.yourdistrict.edu/auth/google/callback
```

`config/services.php` already maps these to the `google` key for Socialite.

Run `php artisan config:cache` after changes.

### E. Workspace admin notes

- Restricting login to your domain is enforced by **who you create in ATLAAS** and **which emails you allow in Google**; for stricter control, use **Internal** OAuth app type or Google’s admin policies for which third-party apps users may access.
- **Domain-wide delegation** is a different pattern (service accounts impersonating users) and is **not** what this Socialite flow uses.

---

## LLM provider (OpenAI, local, Azure, Anthropic via gateway)

ATLAAS uses the **`openai-php/laravel`** client. It talks to an **OpenAI-compatible HTTP API**: same request shape as OpenAI’s `/v1/chat/completions`.

Configuration is driven by **`config/openai.php`**, which reads:

| `.env` variable | Purpose |
|-----------------|--------|
| `OPENAI_API_KEY` | API key (or placeholder for some local servers) |
| `OPENAI_BASE_URL` | Base URL for the API (include `/v1` if your provider expects it) |
| `OPENAI_MODEL` | Model id string sent to the provider |
| `OPENAI_ORGANIZATION` | Optional; OpenAI org id |
| `OPENAI_PROJECT` | Optional; OpenAI project id |
| `OPENAI_REQUEST_TIMEOUT` | Optional; seconds (default 30) |

After any change: `php artisan config:cache`.

### OpenAI (hosted)

```env
OPENAI_API_KEY=sk-...
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o-mini
```

### Local: Ollama

Install [Ollama](https://ollama.com/) on a server reachable from the app. Ollama exposes an OpenAI-compatible API on port **11434**.

```env
OPENAI_API_KEY=ollama
OPENAI_BASE_URL=http://127.0.0.1:11434/v1
OPENAI_MODEL=llama3.2
```

Use a non-root URL only on a trusted network; for production, put a reverse proxy with auth/TLS in front.

### Local: vLLM or other OpenAI-compatible servers

Point `OPENAI_BASE_URL` at your server’s OpenAI-compatible base (often `http://host:8000/v1`) and set `OPENAI_MODEL` to the served model name. Use an API key if your gateway requires one.

### Azure OpenAI

Azure uses a different host and often an **API key** plus **deployment name** as the model.

```env
OPENAI_API_KEY=your_azure_key
OPENAI_BASE_URL=https://YOUR_RESOURCE.openai.azure.com/openai/deployments/YOUR_DEPLOYMENT
OPENAI_MODEL=gpt-4o-mini
```

Exact URL patterns vary by Azure API version; follow Microsoft’s “OpenAI-compatible endpoint” docs for your resource.

### Anthropic (Claude)

The **Anthropic Messages API is not the same protocol** as OpenAI’s chat completions. This codebase does **not** call Anthropic natively.

**Practical options:**

1. Run an **OpenAI-compatible proxy** (e.g. **LiteLLM**, **LangChain proxy**, or similar) that accepts OpenAI-style requests and forwards to Anthropic; set `OPENAI_BASE_URL` to that proxy and `OPENAI_MODEL` to the Claude model id the proxy expects.
2. Use a provider that exposes **OpenAI-compatible** access to Claude (if your vendor documents it).

There is no `ANTHROPIC_*` block in this repo until such an integration is added in code.

### Verify connectivity (local/staging)

With `APP_ENV=local`, a district admin can hit the dev-only route **`/test-llm`** (see `routes/web.php`) after authenticating, to confirm the app reaches the configured endpoint. **Remove or protect this route before production** if you expose it beyond trusted admins.

---

## Production hardening checklist

Use this as a baseline before exposing the app to the internet.

### Application

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, real `APP_KEY` set (`php artisan key:generate` once, then back up the key securely)
- [ ] `APP_URL` matches the public HTTPS URL
- [ ] `php artisan config:cache route:cache view:cache` after deploy
- [ ] Demo seeders **not** run on production, or change all default passwords
- [ ] **Horizon** (`/horizon`) only for `district_admin`; confirm non-admins get 403

### Transport and cookies

- [ ] HTTPS everywhere (redirect HTTP → HTTPS)
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true` recommended when using HTTPS
- [ ] Sensible `SESSION_LIFETIME`; consider `SESSION_SAME_SITE=strict` or `lax` per your SSO needs

### Database and Redis

- [ ] MySQL user has privileges **only** on the ATLAAS database
- [ ] Strong DB password; MySQL bound to localhost or private network only
- [ ] Redis **not** exposed publicly; password/ACL if accessed beyond localhost

### Server and network

- [ ] OS firewall (`ufw`, cloud security groups): allow **80, 443** only; SSH from known IPs if possible
- [ ] SSH: key-based auth, `PermitRootLogin no`, `PasswordAuthentication no`
- [ ] Automatic security updates (`unattended-upgrades` on Ubuntu)
- [ ] If behind a load balancer or CDN, configure **trusted proxies** in Laravel so `Request::secure()` and client IPs are correct (see Laravel docs for `TrustProxies` / `bootstrap/app.php` in your Laravel version)

### Apache

- [ ] Disable unused modules and default sites
- [ ] `ServerTokens Prod`, `ServerSignature Off`
- [ ] Consider **ModSecurity** or a WAF in front of the app
- [ ] Rate limiting at the edge (CDN, reverse proxy) for `/login` and APIs

### Secrets and files

- [ ] `.env` not in web root, mode `600`, owned by root or deploy user — not world-readable
- [ ] `storage` and `bootstrap/cache` writable only by the PHP/queue user
- [ ] Off-site backups of DB and `APP_KEY`

### Mail

- [ ] Real SMTP (`MAIL_MAILER=smtp`) with `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, app password in `MAIL_PASSWORD`, and TLS (`MAIL_SCHEME=smtp` + 587) or SSL (`MAIL_SCHEME=smtps` + 465) for critical alerts

### Ongoing

- [ ] Monitor logs: `storage/logs/laravel.log`, Apache logs, Horizon log
- [ ] `composer update` / `npm audit` on a schedule in a staging environment first

---

## Reference: `.env` variables

| Variable | Description |
|----------|-------------|
| **Application** | |
| `APP_NAME` | Shown in UI / mail (default ATLAAS) |
| `APP_ENV` | `local`, `staging`, `production` |
| `APP_KEY` | Encryption key; **required**; from `key:generate` |
| `APP_DEBUG` | `false` in production |
| `APP_URL` | Public base URL with scheme (`https://...`) |
| **Database** | |
| `DB_CONNECTION` | `mysql`, `pgsql`, or `sqlite` |
| `DB_HOST` | Database host |
| `DB_PORT` | Port (3306 for MySQL) |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | DB user |
| `DB_PASSWORD` | DB password |
| **Session / cache** | |
| `SESSION_DRIVER` | `database`, `redis`, `file` |
| `SESSION_LIFETIME` | Minutes |
| `SESSION_ENCRYPT` | `true` recommended with HTTPS |
| `SESSION_SECURE_COOKIE` | `true` when HTTPS-only |
| `CACHE_STORE` | `database`, `redis`, `file` |
| **Queue / Redis** | |
| `QUEUE_CONNECTION` | `redis` for Horizon |
| `REDIS_CLIENT` | `predis` (this project includes Predis) or `phpredis` if ext installed |
| `REDIS_HOST` | Usually `127.0.0.1` |
| `REDIS_PASSWORD` | If Redis auth enabled |
| `REDIS_PORT` | Default `6379` |
| **Mail** | |
| `MAIL_MAILER` | `smtp`, `log` (dev only), etc. |
| `MAIL_HOST`, `MAIL_PORT` | SMTP server and port (587 TLS / 465 SSL typical) |
| `MAIL_USERNAME` | Mailbox address used to authenticate to SMTP |
| `MAIL_PASSWORD` | App password or SMTP password |
| `MAIL_SCHEME` | `smtp` (STARTTLS) or `smtps` (implicit SSL); overrides encryption below if set |
| `MAIL_ENCRYPTION` | `tls`, `starttls`, `ssl`, or `smtps` if you prefer not to set `MAIL_SCHEME` |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | From header |
| `MAIL_TIMEOUT` | Optional SMTP timeout (seconds) |
| `MAIL_URL` | Optional full SMTP DSN (overrides pieces above if used) |
| **Google OAuth** | |
| `GOOGLE_CLIENT_ID` | OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | OAuth secret |
| `GOOGLE_REDIRECT_URI` | Must match Google Console and route `/auth/google/callback` |
| **LLM (OpenAI-compatible)** | |
| `OPENAI_API_KEY` | Provider API key |
| `OPENAI_BASE_URL` | API base URL (often ends with `/v1`) |
| `OPENAI_MODEL` | Model name |
| `OPENAI_ORGANIZATION` | Optional (OpenAI) |
| `OPENAI_PROJECT` | Optional (OpenAI) |
| `OPENAI_REQUEST_TIMEOUT` | Optional timeout seconds |
| **Frontend** | |
| `VITE_APP_NAME` | Usually `${APP_NAME}` |

---

## Demo accounts (after seeding)

After `php artisan db:seed` (includes `TestDataSeeder`):

| Email | Password | Role |
|-------|----------|------|
| `teacher@demo.test` | `password` | Teacher |
| `student@demo.test` | `password` | Student |
| `admin@demo.test` | `password` | District admin |

**Do not leave these on a public production server.**

---

## Implementation phases

See the `/phases` directory for staged build instructions and feature checklists.

---

## License

MIT
