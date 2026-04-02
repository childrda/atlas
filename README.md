# ATLAS

**Augmented Teaching & Learning AI System**

ATLAS is a Laravel + Inertia (React) application for district-scoped teaching and learning workflows: multi-tenant structure (district → school → user), role-based access (district admin, teacher, student, etc.), and separate teacher and student portals.

## Requirements

- PHP 8.2+
- Composer
- Node.js 20+ (for Vite)
- MySQL, PostgreSQL, or SQLite (configure in `.env`)

## Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build   # or npm run dev while developing
php artisan serve
```

Demo accounts (after seeding):

- `teacher@demo.test` / `password`
- `student@demo.test` / `password`
- `admin@demo.test` / `password`

## Environment

Set `APP_NAME=ATLAS` in `.env` (default in `config/app.php`). For Google sign-in, configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `.env` and `config/services.php`.

## Implementation phases

See the `/phases` directory for staged build instructions.

## License

MIT
