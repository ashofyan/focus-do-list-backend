# Todo Management System - Backend Laravel

Backend API untuk manajemen todo menggunakan Laravel, PostgreSQL, database queue, dan Laravel Sanctum.

## Stack

| Komponen | Teknologi |
| --- | --- |
| Backend | Laravel |
| Database | PostgreSQL |
| Queue/Cache | Database |
| Auth | Laravel Sanctum |

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Isi konfigurasi database di `.env` sebelum menjalankan migration.

## Menjalankan Aplikasi

```bash
php artisan serve
```

Untuk workflow development bawaan project:

```bash
composer run dev
```

## Struktur Tabel

| Tabel | Fungsi |
| --- | --- |
| users | Data user |
| groups | Kategori atau kelompok todo |
| labels | Label untuk todo |
| todos | Todo utama |
| sub_tasks | Sub-task dalam todo |
| todo_label | Pivot many-to-many todo dan label |
| milestones | Milestone atau project jangka panjang |
| jobs | Laravel queue jobs |
| failed_jobs | Jobs yang gagal dieksekusi |
