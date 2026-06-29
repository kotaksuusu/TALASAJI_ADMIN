#!/usr/bin/env pwsh
<#
.SYNOPSIS
    One-click setup for TALASAJI_ADMIN (Laravel Backend + Admin Web)
.DESCRIPTION
    Menjalankan langkah 1-12 dari SETUP.md secara otomatis.
    Run this from repo root (D:\Coding\TALASAJI_ADMIN).
.PARAMETER Port
    Port untuk server (default: 8000)
.PARAMETER NoServe
    Install saja, jangan jalankan server
.EXAMPLE
    .\setup.ps1
    .\setup.ps1 -Port 8002
    .\setup.ps1 -NoServe
#>
param(
    [int]$Port = 8000,
    [switch]$NoServe
)

function Step($n, $m)  { Write-Host "`n[$n/12] $m" -ForegroundColor Cyan }
function Ok($m)        { Write-Host "  ✅ $m" -ForegroundColor Green }
function Warn($m)      { Write-Host "  ⚠️  $m" -ForegroundColor Yellow }
function Err($m)       { Write-Host "  ❌ $m" -ForegroundColor Red }

Write-Host "╔════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   TALASAJI ADMIN — Setup Script    ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════╝" -ForegroundColor Cyan

# ── Langkah 1: Clone (skip — udah di dalam repo) ──
Step 1 "Clone Repo"
Ok "Sudah berada di direktori repo"

# ── Langkah 2: Install PHP Dependencies ──
Step 2 "Install PHP Dependencies"
composer install --no-interaction
if ($LASTEXITCODE -eq 0) { Ok "Composer install selesai" }
else { Err "Gagal"; exit 1 }
composer dump-autoload

# ── Langkah 3: Buat .env ──
Step 3 "Buat File .env"
$ef = ".env"
if (Test-Path $ef) {
    $ans = Read-Host "  .env sudah ada. Timpa? (y/N)"
    if ($ans -eq 'y' -or $ans -eq 'Y') { Copy-Item ".env.example" $ef -Force; Ok ".env ditimpa" }
    else { Ok "Melewati .env" }
} else {
    Copy-Item ".env.example" $ef
    Ok ".env dibuat dari .env.example"
}

# ── Langkah 4: Generate APP_KEY ──
Step 4 "Generate Application Key"
php artisan key:generate --force
if ($LASTEXITCODE -eq 0) { Ok "APP_KEY generated" }
else { Err "Gagal"; exit 1 }

# ── Langkah 5: Isi kredensial DB ──
Step 5 "Isi Kredensial Database di .env"
$creds = @{
    'DB_HOST'     = 'aws-1-ap-southeast-2.pooler.supabase.com'
    'DB_PORT'     = '5432'
    'DB_DATABASE' = 'postgres'
    'DB_USERNAME' = 'postgres.mcdnxvsacyzjjubhczuv'
    'DB_PASSWORD' = 'M#u-3yGe*YsE-+J'
    'DB_SSLMODE'  = 'require'
}
$c = Get-Content $ef -Raw
foreach ($k in $creds.Keys) {
    $v = $creds[$k] -replace '\$', '`$'
    $c = $c -replace "^$k=.*", "$k=$v"
}
Set-Content $ef -Value $c -NoNewline
Ok "Kredensial database diisi"

# ── Langkah 6: Matikan RLS (manual — ingatkan aja) ──
Step 6 "Matikan RLS di Supabase"
Warn "Buka https://supabase.com → Table Editor → Disable RLS semua tabel"
$ans = Read-Host "  Sudah? (y/N)"
if ($ans -ne 'y' -and $ans -ne 'Y') { Warn "Lanjut, tapi migrasi bisa gagal" }

# ── Langkah 7: Migrate ──
Step 7 "Jalankan Migrasi"
php artisan migrate --force
if ($LASTEXITCODE -eq 0) { Ok "Migrasi sukses" }
else { Err "Migrasi gagal — cek RLS dan koneksi DB"; exit 1 }

# ── Langkah 8: Seed ──
Step 8 "Seed Data Awal"
php artisan db:seed --force
if ($LASTEXITCODE -eq 0) { Ok "Seeder sukses" }
else { Warn "Seeder gagal — mungkin data sudah ada" }

# ── Langkah 9: Storage Link ──
Step 9 "Storage Link"
php artisan storage:link --force 2>$null
Ok "Storage link dibuat"

# ── Langkah 10: Install & Build Frontend ──
Step 10 "Install & Build Frontend Assets"
npm install
npm run build
Ok "Frontend siap"

# ── Langkah 11: Hapus Cache ──
Step 11 "Hapus Cache"
php artisan optimize:clear
Ok "Cache dibersihkan"

# ── Langkah 12: Jalankan Server ──
Step 12 "Jalankan Server"
Write-Host ""
Write-Host "╔══════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  ✅ Setup selesai!                           ║" -ForegroundColor Cyan
Write-Host "╠══════════════════════════════════════════════╣" -ForegroundColor Cyan
Write-Host "║  Admin Panel → http://localhost:$Port/admin/login ║" -ForegroundColor White
Write-Host "║  API         → http://localhost:$Port/api/stores  ║" -ForegroundColor White
Write-Host "║  Login       → admin@talasaji.com / admin123      ║" -ForegroundColor White
Write-Host "╚══════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

if (-not $NoServe) {
    Write-Host "🚀 Menjalankan server (Ctrl+C untuk stop)..." -ForegroundColor Green
    php artisan serve --port=$Port
} else {
    Write-Host "Jalankan: php artisan serve --port=$Port" -ForegroundColor Yellow
}
