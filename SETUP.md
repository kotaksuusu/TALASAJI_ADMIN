# 🛠️ SETUP — TALASAJI_ADMIN (Laravel Backend + Admin Web)

Panduan lengkap setup repositori ini agar siap dipakai bersama Flutter App.

---

## ✅ Prasyarat

| Software | Minimal | Cek |
|----------|---------|-----|
| PHP | ^8.3 | `php -v` |
| Composer | 2.x | `composer --version` |
| Node.js | 18+ | `node -v` |
| NPM | 9+ | `npm -v` |

> **Database:** Pakai Supabase Cloud — tidak perlu install PostgreSQL lokal.

---

## 🚀 Langkah Instalasi

### 1. Clone Repo

```bash
git clone <url-repo-talasaji-admin>
cd TALASAJI_ADMIN
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Buat File .env

```bash
cp .env.example .env
```

Atau (PowerShell Windows):
```powershell
Copy-Item .env.example .env
```

### 4. Generate Application Key (WAJIB!)

```bash
php artisan key:generate
```

> Tanpa `APP_KEY`, session login, cookie, dan enkripsi data error.

### 5. Isi Kredensial Database di `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=aws-1-ap-southeast-2.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.mcdnxvsacyzjjubhczuv
DB_PASSWORD="M#u-3yGe*YsE-+J"
DB_SSLMODE=require

SUPABASE_URL=https://mcdnxvsacyzjjubhczuv.supabase.co
SUPABASE_SERVICE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_STORAGE_BUCKET=talasaji
```

### 6. Matikan RLS di Supabase (SEBELUM Migrate!)

1. Buka https://supabase.com/dashboard/project/mcdnxvsacyzjjubhczuv
2. **Table Editor** → klik setiap tabel → tab **RLS** → **Disable**
3. Tabel: `users`, `stores`, `categories`, `menus`, `tables`, `orders`, `order_items`, `payments`, `reviews`, `notifications`, `settings`, `personal_access_tokens`, `sessions`, `cache`, `cache_locks`

### 7. Jalankan Migrasi

```bash
php artisan migrate
```

### 8. Seed Data Awal

```bash
php artisan db:seed
```

### 9. Storage Link

```bash
php artisan storage:link
```

### 10. Install & Build Frontend Assets

```bash
npm install
npm run build
```

### 11. Hapus Cache

```bash
php artisan optimize:clear
```

### 12. Jalankan Server

```bash
php artisan serve --port=8000
```

---

## ✅ Verifikasi

```bash
curl http://localhost:8000/api/stores
# → JSON list of stores

# Admin Panel
# http://localhost:8000/admin/login
# admin@talasaji.com / admin123
```

---

## ❌ Error Umum

| Error | Solusi |
|-------|--------|
| `Target class [X] does not exist` | `composer dump-autoload` |
| `sessions table not found` | `php artisan migrate` |
| `APP_KEY` serialization error | `php artisan key:generate` |
| `Connection refused` | Cek DB credentials di `.env` |
| `permission denied` (RLS) | Disable RLS semua tabel |
| Login admin gagal | `php artisan db:seed` |
| Blade file not found | `npm install && npm run build` |
