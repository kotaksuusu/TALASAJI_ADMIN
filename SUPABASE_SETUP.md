# SUPABASE SETUP

## Project Info
- Project Ref: mcdnxvsacyzjjubhczuv
- Host: db.mcdnxvsacyzjjubhczuv.supabase.co
- Port: 5432
- Database: postgres
- User: postgres

## Penting: Matikan Row Level Security (RLS)
Laravel connect langsung via PostgreSQL driver (bukan JWT Supabase).
RLS akan memblokir semua query jika aktif.

Cara mematikan RLS:
1. Buka Supabase Dashboard → Table Editor
2. Pilih setiap tabel → tab Auth → Disable RLS
3. Lakukan untuk semua tabel: users, stores, categories, menus, tables, orders, order_items, payments, reviews, settings, sessions, cache, cache_locks

## Migration
Jalankan dari project ini:
```bash
php artisan migrate
```

Jika gagal karena tabel sudah ada:
```bash
php artisan migrate --pretend
```
