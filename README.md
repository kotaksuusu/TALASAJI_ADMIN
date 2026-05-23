# TALASAJI Admin

Web dashboard admin untuk platform TALASAJI (digitalisasi UMKM kuliner & kantin).

## Tech Stack
- Laravel 11 (PHP 8.2+)
- Blade + Tailwind CSS
- Supabase (PostgreSQL)

## Setup

```bash
git clone <repo-url>
cd talasaji-admin
composer install
cp .env.example .env
# Isi kredensial Supabase di .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=AdminSeeder
php artisan storage:link
php artisan serve
```

Akses: http://localhost:8000/admin/login
Default login: admin@talasaji.com / admin123

## Integrasi Database
Database ini DIGUNAKAN BERSAMA dengan repo talasaji-app (Flutter).
Talasaji-admin adalah PEMILIK migrations.
Koordinasikan setiap perubahan schema dengan tim mobile sebelum migrate.
