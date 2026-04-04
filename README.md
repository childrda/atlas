# ATLAAS

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev/)
[![TypeScript](https://img.shields.io/badge/TypeScript-6-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Vite](https://img.shields.io/badge/Vite-7-646CFF?logo=vite&logoColor=white)](https://vitejs.dev/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3-9553E9)](https://inertiajs.com/)
[![Redis](https://img.shields.io/badge/Redis-queues-DC382D?logo=redis&logoColor=white)](https://redis.io/)
[![Laravel Horizon](https://img.shields.io/badge/Horizon-queues-405263?logo=laravel&logoColor=white)](https://laravel.com/docs/horizon)
[![GitHub](https://img.shields.io/badge/GitHub-childrda%2Fatlas-181717?logo=github)](https://github.com/childrda/atlas)

**Augmented Teaching & Learning Assistive AI System**

ATLAAS is a Laravel + Inertia (React) application for district-scoped teaching and learning: multi-tenant structure (district → school → user), role-based access (district admin, school admin, teacher, student), separate teacher and student portals, the ATLAAS assistive AI in student sessions (including **rich assistant replies**—images, inline diagrams, fun facts, and short quizzes when the model uses the tagged format), queued safety alerts, session summaries, **Compass View** (live teacher dashboard over **Laravel Reverb** WebSockets when enabled), and **Discover** (teacher-shared space library with optional **Meilisearch** via **Laravel Scout**).

---

## Table of contents

1. [What you are installing](#what-you-are-installing)
2. [Server requirements (Linux)](#server-requirements-linux)
3. [Install system packages](#install-system-packages)
4. [Database (MySQL or MariaDB)](#database-mysql-or-mariadb)
5. [Redis](#redis)
6. [Deploy application code](#deploy-application-code)
7. [Configuring environment variables](#configuring-environment-variables)
8. [Web server: Apache](#web-server-apache)
9. [TLS (HTTPS) with Let’s Encrypt](#tls-https-with-lets-encrypt)
10. [Queues and Laravel Horizon](#queues-and-laravel-horizon)
11. [Live teacher dashboard (Reverb and Compass View)](#live-teacher-dashboard-reverb-and-compass-view)
12. [Discover search (Scout and Meilisearch)](#discover-search-scout-and-meilisearch)
13. [Task scheduler (cron)](#task-scheduler-cron)
14. [Google Workspace sign-in (OAuth)](#google-workspace-sign-in-oauth)
15. [LLM provider (OpenAI-compatible API)](#llm-provider-openai-compatible-api)
16. [Production hardening checklist](#production-hardening-checklist)
17. [Demo accounts (after seeding)](#demo-accounts-after-seeding)
18. [Testing and smoke checks](#testing-and-smoke-checks)
19. [License](#license)

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
| **Laravel Reverb** | Optional WebSocket server for Compass View live updates (`php artisan reverb:start`); uses Redis when Reverb scaling is enabled |
| **Meilisearch** | Optional search engine for Discover when `SCOUT_DRIVER=meilisearch`; without it, Discover still lists, filters, and searches via SQL |

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

- `curl`, `mbstring`, `xml`, `zip`, `bcmath`, `intl`, `mysql`, `sqlite3`: Laravel and dependencies
- `pcntl`, `posix`: **Laravel Horizon** (queue master process on Linux)

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

### 4. Install Node.js 20 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

You can build assets on a developer machine and deploy only `public/build/`.

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

```sql
CREATE DATABASE atlaas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'atlaas'@'localhost' IDENTIFIED BY 'choose_a_strong_password_here';
GRANT ALL PRIVILEGES ON atlaas.* TO 'atlaas'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Production:** Prefer a dedicated DB user with only the `atlaas` database privileges. Match credentials to `DB_*` in `.env` (see [Configuring environment variables](#configuring-environment-variables)).

---

## Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

- Prefer binding Redis to **127.0.0.1** unless you have a separate app tier; then use TLS, ACL passwords, and firewall rules.
- Set `requirepass` in `redis.conf` if Redis is reachable beyond localhost, and set `REDIS_PASSWORD` in `.env`.

---

## Deploy application code

Example under `/var/www/atlaas` as user `www-data` on Ubuntu.

```bash
sudo mkdir -p /var/www
sudo chown $USER:$USER /var/www
cd /var/www
git clone <your-repo-url> atlaas
cd atlaas
```

```bash
composer install --no-dev --optimize-autoloader
```

On **Windows** (e.g. XAMPP), Composer may need:

```bash
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
```

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` using [Configuring environment variables](#configuring-environment-variables), then:

```bash
npm ci
npm run build
```

**After `git pull`:** run `npm ci` before `npm run build` whenever `package.json` or `package-lock.json` changed (avoids missing packages such as `zustand`, `laravel-echo`, `pusher-js`).

```bash
php artisan migrate --force
# Optional demo data (see [Demo accounts](#demo-accounts-after-seeding)): all seeded test users
# start with the password `password`. Change those passwords (or skip seeding) before any real use.
php artisan db:seed --force
php artisan storage:link
sudo chown -R www-data:www-data /var/www/atlaas/storage /var/www/atlaas/bootstrap/cache
sudo chmod -R ug+rwx /var/www/atlaas/storage /var/www/atlaas/bootstrap/cache
```

Only `storage` and `bootstrap/cache` need write access for the web/PHP user.

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Troubleshooting: `php artisan optimize:clear`

---

## Configuring environment variables

Copy `.env.example` to `.env`, run `php artisan key:generate` once, then set variables below. **After any change:** `php artisan config:cache` (and rebuild frontend if `VITE_*` changed: `npm run build`).

The canonical list in the repo is **`.env.example`**; this section explains what each group does and how ATLAAS uses it.

### Application identity and debugging

| Variable | What it does |
|----------|----------------|
| `APP_NAME` | Display name in the UI and mail (default ATLAAS). |
| `APP_ENV` | `local`, `staging`, or `production`. Use `production` on the internet. |
| `APP_KEY` | **Required.** Laravel encryption and signed cookies. Generate with `php artisan key:generate`; back it up securely. |
| `APP_DEBUG` | When `true`, shows stack traces (never in public production). |
| `APP_URL` | Public site URL including scheme, e.g. `https://atlaas.yourdistrict.edu`. Used for URL generation, OAuth redirects, and trusted URL checks. |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Default language and faker locale for seeding. |
| `APP_MAINTENANCE_DRIVER` | How maintenance mode is stored (`file` default). |
| `BCRYPT_ROUNDS` | Cost factor for hashing passwords (default 12). |

### Logging

| Variable | What it does |
|----------|----------------|
| `LOG_CHANNEL`, `LOG_STACK` | Where application logs go (`stack` / `single` typical). |
| `LOG_LEVEL` | Minimum log level (`debug` in dev, `warning` or `error` in production often). |
| `LOG_DEPRECATIONS_CHANNEL` | Optional channel for deprecation warnings. |

### Database

| Variable | What it does |
|----------|----------------|
| `DB_CONNECTION` | `mysql`, `pgsql`, or `sqlite`. Production should be `mysql` (or `pgsql`) with a real server. |
| `DB_HOST` | Database hostname or IP. |
| `DB_PORT` | Port (MySQL default 3306). |
| `DB_DATABASE` | Database name. |
| `DB_USERNAME` / `DB_PASSWORD` | Credentials (use a least-privilege user in production). |

SQLite (`DB_CONNECTION=sqlite` with `DB_DATABASE` path) is fine for local experimentation; the default `.env.example` uses SQLite for quick starts.

### Sessions and cookies

| Variable | What it does |
|----------|----------------|
| `SESSION_DRIVER` | `database`, `redis`, or `file`. `database` is a common default if you already use MySQL. |
| `SESSION_LIFETIME` | Idle timeout in minutes. |
| `SESSION_ENCRYPT` | Encrypt session payload (`true` recommended with HTTPS). |
| `SESSION_SECURE_COOKIE` | Set `true` when the site is **HTTPS-only** so cookies are not sent over plain HTTP. |
| `SESSION_PATH` / `SESSION_DOMAIN` | Cookie path and domain; set `SESSION_DOMAIN` only if you deliberately share cookies across subdomains. |

### Cache

| Variable | What it does |
|----------|----------------|
| `CACHE_STORE` | `database`, `redis`, or `file`. Use `redis` if you want cache in memory across workers. |
| `CACHE_PREFIX` | Optional key prefix when sharing Redis with other apps. |

### Filesystem

| Variable | What it does |
|----------|----------------|
| `FILESYSTEM_DISK` | Default disk for `Storage` (`local` typical; set `s3` if you configure AWS below). |

### Queues and Redis

| Variable | What it does |
|----------|----------------|
| `QUEUE_CONNECTION` | Use **`redis`** in production so **Horizon** can run safety alerts, session summaries, and optional Scout indexing. `sync` runs jobs in-process (dev only for heavy jobs). |
| `REDIS_CLIENT` | `predis` (bundled) or `phpredis` if the PHP extension is installed. |
| `REDIS_HOST` | Usually `127.0.0.1` on a single server. |
| `REDIS_PASSWORD` | Set when Redis `requirepass` is enabled. |
| `REDIS_PORT` | Default `6379`. |

### Mail (SMTP)

ATLAAS sends mail for **safety alerts** and system messages. For real delivery set `MAIL_MAILER=smtp` and configure host, auth, and encryption.

| Variable | What it does |
|----------|----------------|
| `MAIL_MAILER` | `smtp` for real mail; **`log`** writes messages to the log (good for local dev). |
| `MAIL_HOST` / `MAIL_PORT` | SMTP server; **587** often STARTTLS, **465** often implicit SSL. |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | SMTP login; many providers require an **app password**, not the normal login. |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | From header seen by recipients. |
| `MAIL_SCHEME` | Symfony style: `smtp` = STARTTLS (typical with 587), `smtps` = implicit SSL (typical with 465). If set, it overrides `MAIL_ENCRYPTION`. |
| `MAIL_ENCRYPTION` | Friendly aliases: `tls` / `starttls` or `ssl` / `smtps` if you prefer not to use `MAIL_SCHEME`. |
| `MAIL_TIMEOUT` | SMTP timeout in seconds. |
| `MAIL_URL` | Optional single DSN that overrides host/port/user/pass when set. |

Example (Google Workspace / Gmail with STARTTLS):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=noreply@yourdistrict.edu
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@yourdistrict.edu
MAIL_FROM_NAME="ATLAAS"
```

### Broadcasting, Reverb, and Compass View

Compass View uses **Laravel Echo** in the browser. When `BROADCAST_CONNECTION=reverb`, events go through **Laravel Reverb** (WebSockets). Otherwise use `log` or `null` to disable live updates (pages still load).

| Variable | What it does |
|----------|----------------|
| `BROADCAST_CONNECTION` | **`reverb`** enables live Compass updates; **`log`** or **`null`** disables WebSocket broadcasting. |
| `REVERB_APP_ID` | Application id shared by PHP, the Reverb server, and the browser. |
| `REVERB_APP_KEY` | Public key used by the browser (paired with secret server-side). |
| `REVERB_APP_SECRET` | Secret shared by Laravel and the Reverb server; use a long random value in production. |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | What the **browser** uses to open the WebSocket (`wss` / `https` in production behind TLS). |
| `REVERB_SERVER_HOST` / `REVERB_SERVER_PORT` | What **`php artisan reverb:start`** binds to (often `0.0.0.0` and `8080`); see `config/reverb.php`. |

**Vite / frontend (baked in at build time):**

| Variable | What it does |
|----------|----------------|
| `VITE_APP_NAME` | Usually `${APP_NAME}`; exposed to the client bundle. |
| `VITE_REVERB_APP_KEY` | Must match `REVERB_APP_KEY`. |
| `VITE_REVERB_HOST` / `VITE_REVERB_PORT` / `VITE_REVERB_SCHEME` | Must match what browsers use to reach Reverb (often your public hostname and `https` / `443` when TLS terminates at a proxy). |

If `VITE_REVERB_*` are unset or wrong, Compass loads but **live updates do not work** until you fix env and run `npm run build` again.

### Discover, Scout, and Meilisearch

| Variable | What it does |
|----------|----------------|
| `SCOUT_DRIVER` | **`meilisearch`** for full-text search against indexed learning spaces when Meilisearch is running. **`collection`** or driver mismatch falls back to SQL search on Discover library rows for text search; listing and filters always work. |
| `SCOUT_QUEUE` | `true` defers indexing to the queue (needs workers running). |
| `MEILISEARCH_HOST` | HTTP base URL of Meilisearch (e.g. `http://127.0.0.1:7700`). |
| `MEILISEARCH_KEY` | API key when Meilisearch is secured (`MEILI_MASTER_KEY` in Docker). |

After enabling Meilisearch: `php artisan scout:import "App\Models\LearningSpace"`. Do not expose Meilisearch port 7700 to the public internet without a proxy and TLS.

### Google OAuth (optional)

| Variable | What it does |
|----------|----------------|
| `GOOGLE_CLIENT_ID` | OAuth client id from Google Cloud Console. |
| `GOOGLE_CLIENT_SECRET` | OAuth client secret. |
| `GOOGLE_REDIRECT_URI` | Must exactly match the redirect URI in Google Console, typically `${APP_URL}/auth/google/callback`. |

Google sign-in **does not** create users automatically; the email must already exist in ATLAAS.

### LLM (OpenAI-compatible)

Student chat, toolkit tools, and safety flows call an **OpenAI-compatible** HTTP API (`config/openai.php` reads these):

| Variable | What it does |
|----------|----------------|
| `OPENAI_API_KEY` | API key (some local servers accept a placeholder). |
| `OPENAI_BASE_URL` | Base URL, often ending in `/v1`. |
| `OPENAI_MODEL` | Model name your provider expects. |
| `OPENAI_ORGANIZATION` / `OPENAI_PROJECT` | Optional OpenAI-specific headers. |
| `OPENAI_REQUEST_TIMEOUT` | Request timeout in seconds. |

### Student chat: rich responses (images)

The assistant can return structured segments (text, images, SVG diagrams, fun facts, quizzes). Image lookup is configured in **`config/atlas.php`** (`image_source`). Defaults use **Wikimedia** (no API key). Optional stock-photo providers:

| Variable | What it does |
|----------|----------------|
| `IMAGE_SOURCE` | `wikimedia` (default), `unsplash`, or `pexels`. |
| `UNSPLASH_ACCESS_KEY` | Unsplash API key; read as `config('services.unsplash.access_key')`. |
| `PEXELS_API_KEY` | Pexels API key; read as `config('services.pexels.api_key')`. |

Tag syntax and limits for the model are documented in **`phases/Phase3b_Rich_Responses.md`**.

### Student chat: text-to-speech (Kokoro)

Optional **Speak** control on each assistant message. **ATLAAS does not bundle speech synthesis:** to use TTS you must **run your own Kokoro server** (or have network access to one your organization already hosts). Typical setups use **[remsky/kokoro-fastapi](https://github.com/remsky/kokoro-fastapi)** — see that repository for images, GPU vs CPU, voices, and health checks. This project’s **`phases/Phase3c_TTS_Kokoro.md`** describes wiring and verification.

Laravel proxies audio requests to Kokoro; **students never call Kokoro directly.** Point **`TTS_KOKORO_URL`** at the base URL of that service (e.g. `http://localhost:8880` when Kokoro runs on the same machine, or `http://kokoro:8880` on a shared Docker network). Leave **`TTS_ENABLED=false`** until Kokoro is available, or the Speak action will fail (the UI degrades when the service is unreachable).

| Variable | What it does |
|----------|----------------|
| `TTS_ENABLED` | `true` to show Speak and enable `POST /learn/sessions/{id}/speak`. |
| `TTS_KOKORO_URL` | Base URL (e.g. `http://localhost:8880` dev, `http://kokoro:8880` on Docker network). |
| `TTS_DEFAULT_VOICE` | Fallback Kokoro voice id (e.g. `af_heart`). |
| `TTS_DEFAULT_SPEED` | Default speed; voice also follows **`users.preferred_language`** and slows slightly for younger **`grade_level`**. |

A minimal **`docker-compose.yml`** in the repo includes a `kokoro` service for local or server use. Do not expose Kokoro’s port on the public internet.

### Optional: AWS S3

| Variable | What it does |
|----------|----------------|
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_USE_PATH_STYLE_ENDPOINT` | Standard Laravel S3 disk configuration if you set `FILESYSTEM_DISK=s3` and configure `config/filesystems.php` accordingly. |

---

## Web server: Apache

Point **`DocumentRoot`** at the `public` directory.

Example `/etc/apache2/sites-available/atlaas.conf`:

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

```bash
sudo a2dissite 000-default.conf
sudo a2ensite atlaas.conf
sudo systemctl reload apache2
```

With **php-fpm**, point PHP at the FPM socket; `DocumentRoot` stays **`public`**.

---

## TLS (HTTPS) with Let’s Encrypt

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d atlaas.yourdistrict.edu
```

Then set `APP_URL` to `https://...`, `SESSION_SECURE_COOKIE=true`, and `php artisan config:cache`.

---

## Queues and Laravel Horizon

Horizon runs workers for safety alerts, session summaries, Scout indexing (if queued), etc.

```bash
sudo apt install -y supervisor
```

`/etc/supervisor/conf.d/atlaas-horizon.conf`:

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

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start atlaas-horizon
```

Horizon dashboard: `https://your-host/horizon` — restricted to **`district_admin`** (`app/Providers/HorizonServiceProvider.php`). Do not expose Redis or MySQL to the internet.

---

## Live teacher dashboard (Reverb and Compass View)

**Compass View** (`/teach/compass`) shows active student sessions, message activity, and safety alerts **in real time** when broadcasting is enabled. It uses **Laravel Reverb** (Pusher-compatible WebSockets). Student chat itself uses **SSE**, not Reverb.

1. Set **`BROADCAST_CONNECTION=reverb`** and all **`REVERB_*`** / **`VITE_REVERB_*`** variables ([see above](#broadcasting-reverb-and-compass-view)).
2. Run **`php artisan reverb:start`** (Supervisor/systemd in production).
3. Run **Horizon** (or a queue worker) so alert jobs can broadcast after processing.
4. Rebuild assets after changing **`VITE_*`**: `npm run build`.

**Production:** use **WSS** behind HTTPS, strong `REVERB_APP_SECRET`, and do not expose an unsecured WebSocket port. Channel rules live in **`routes/channels.php`** (teachers subscribe to their own `compass.{userId}` channel).

---

## Discover search (Scout and Meilisearch)

**Discover** (`/teach/discover`) lists spaces teachers shared publicly. Configure **`SCOUT_*`** and **`MEILISEARCH_*`** [as above](#discover-scout-and-meilisearch).

**Docker example:**

```bash
docker run -d -p 7700:7700 \
  -e MEILI_MASTER_KEY=your-master-key \
  getmeili/meilisearch:v1.7
```

Then `SCOUT_DRIVER=meilisearch`, matching host/key, and:

```bash
php artisan scout:import "App\Models\LearningSpace"
```

Only published, public, non-archived spaces with a published library row are indexed. If Meilisearch is down, publishing still works; errors are logged.

**Teacher workflow:** From a space, use **Share to Discover** when publishing; in Discover, search (debounced), filters, **Add to my spaces**, ratings, and **District approve** (district admins, same district as the space).

---

## Task scheduler (cron)

```bash
sudo crontab -e -u www-data
```

```
* * * * * cd /var/www/atlaas && php artisan schedule:run >> /dev/null 2>&1
```

---

## Google Workspace sign-in (OAuth)

ATLAAS uses **Laravel Socialite** (`/auth/google` → `/auth/google/callback`). Set **`GOOGLE_*`** in `.env` [as above](#google-oauth-optional).

**Console setup (summary):**

1. [Google Cloud Console](https://console.cloud.google.com/) — create or select a project.
2. OAuth consent screen — Internal (Workspace-only) or External with test users.
3. Credentials → OAuth client **Web application** — Authorized JavaScript origin: `https://atlaas.yourdistrict.edu`; Authorized redirect: `https://atlaas.yourdistrict.edu/auth/google/callback`.
4. Paste Client ID and secret into `.env`; run `php artisan config:cache`.

**Behavior:** The user’s email must **already exist** in ATLAAS. Unknown emails get an error on login.

---

## LLM provider (OpenAI-compatible API)

Same variables as [LLM (OpenAI-compatible)](#llm-openai-compatible) in the `.env` section.

**OpenAI hosted:**

```env
OPENAI_API_KEY=sk-...
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o-mini
```

**Ollama (local):**

```env
OPENAI_API_KEY=ollama
OPENAI_BASE_URL=http://127.0.0.1:11434/v1
OPENAI_MODEL=llama3.2
```

**Azure OpenAI** and **vLLM** use the same client with different `OPENAI_BASE_URL` / model strings; see provider docs.

**Anthropic** is not called natively; use an OpenAI-compatible gateway (e.g. LiteLLM) if you need Claude.

**Local check:** With `APP_ENV=local`, a district admin can use **`/test-llm`**. Remove or protect it in production if exposed.

**Rich student chat:** Replies may include resolved images and server-generated diagrams when the model follows the line-based tags described in **`phases/Phase3b_Rich_Responses.md`**. Set **`IMAGE_SOURCE`** and optional provider keys [as above](#student-chat-rich-responses-images).

**Speak (TTS):** Optional read-aloud needs a **[Kokoro FastAPI](https://github.com/remsky/kokoro-fastapi)** server you run or can reach internally; enable with **`TTS_ENABLED`** and **`TTS_KOKORO_URL`** [as above](#student-chat-text-to-speech-kokoro).

---

## Production hardening checklist

### Application

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, secure `APP_KEY` backed up
- [ ] `APP_URL` matches public HTTPS URL
- [ ] `php artisan config:cache route:cache view:cache` after deploy
- [ ] Demo seeders not on production — or [change demo passwords](#how-to-change-passwords-for-the-demo-accounts) immediately after seeding
- [ ] Horizon at `/horizon` only for `district_admin`
- [ ] Reverb behind TLS / reverse proxy if Compass live mode is on
- [ ] Meilisearch on a private network with a strong key if used; do not expose 7700 publicly without TLS

### Transport, database, Redis, Apache, secrets, mail

- [ ] HTTPS, `SESSION_SECURE_COOKIE=true`, consider `SESSION_ENCRYPT=true`
- [ ] DB user scoped to one database; DB not on public internet
- [ ] Redis not public; password if reachable off-box
- [ ] Firewall: 80/443 only where possible; SSH hardened
- [ ] `.env` mode `600`, not in web root
- [ ] `storage` / `bootstrap/cache` writable only by PHP/queue user
- [ ] Real SMTP for alert mail

### Ongoing

- [ ] Monitor `storage/logs/laravel.log`, Apache, Horizon log
- [ ] Test dependency updates in staging first

---

## Demo accounts (after seeding)

If you run **`php artisan db:seed`**, Laravel runs **`DatabaseSeeder`**, which includes **`TestDataSeeder`**. That creates three local-login users for development and demos.

**Security — read this before using seeders**

- Every seeded test account below is created with the **same default password: `password`**. That is intentional for local development only.
- **Before** you expose the app to other people, a shared network, or the internet, **change those passwords** (instructions below) **or** do not run `db:seed` on that environment and create real users another way.
- **Never** rely on default demo credentials on production. The [production checklist](#production-hardening-checklist) assumes you change or remove them.

| Email | Default password | Role |
|-------|------------------|------|
| `teacher@demo.test` | `password` | Teacher |
| `student@demo.test` | `password` | Student |
| `admin@demo.test` | `password` | District admin |

### How to change passwords for the demo accounts

Use **Artisan Tinker** on the server (or your local machine) after seeding. Replace `your-new-strong-password` with a unique, strong secret for each user if you prefer.

```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$newPassword = 'your-new-strong-password';

foreach (['admin@demo.test', 'teacher@demo.test', 'student@demo.test'] as $email) {
    User::where('email', $email)->update(['password' => Hash::make($newPassword)]);
}
```

To set **different** passwords per account, run separate updates:

```php
User::where('email', 'admin@demo.test')->update(['password' => Hash::make('distinct-admin-password')]);
User::where('email', 'teacher@demo.test')->update(['password' => Hash::make('distinct-teacher-password')]);
User::where('email', 'student@demo.test')->update(['password' => Hash::make('distinct-student-password')]);
```

Type `exit` to leave Tinker.

**Note:** Running **`php artisan db:seed`** again will **reset** the demo users via `TestDataSeeder` (including their passwords back to `password`). For a lasting change, either stop re-seeding over production data or edit **`database/seeders/TestDataSeeder.php`** to use `Hash::make(env('DEMO_USER_PASSWORD', 'password'))` (or similar) and set secrets only in `.env` — never commit real passwords.

---

## Testing and smoke checks

```bash
php artisan test
```

Uses SQLite in memory (`phpunit.xml`); `APP_KEY` is set for the test harness. Some environments warn if `.env` is missing; tests should still pass.

**Manual QA:** Exercise login, teacher and student flows, queues (with Horizon), optional Reverb (two browsers on Compass), optional Meilisearch (Discover keyword search), and mail (`MAIL_MAILER=log`).

The **`phases/`** directory in the repo contains detailed build notes and checkbox-style acceptance lists used during development; you can use them as an extended QA script—they are not required reading for a standard deploy if you follow this README.

---

## License

MIT
