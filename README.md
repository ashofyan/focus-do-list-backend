# Todo Management System - Backend Laravel

Backend API untuk todo management, milestone progress, daily progress, dan encrypted notes. Project ini memakai Laravel sebagai API service, PostgreSQL sebagai database utama, dan Auth Service eksternal untuk autentikasi user.

## Stack

| Komponen | Teknologi |
| --- | --- |
| Backend | Laravel |
| Database | PostgreSQL |
| Queue/Cache/Session | Database |
| Auth | External Auth Service via Bearer Token |
| API Format | JSON |

## Auth Architecture

Service ini tidak lagi menjadikan Laravel Sanctum sebagai sumber autentikasi utama untuk request API aplikasi. Auth user diproxy dan divalidasi melalui Auth Service eksternal.

Flow auth:

1. Frontend login/register melalui endpoint auth di service ini.
2. Backend meneruskan request ke Auth Service eksternal.
3. Frontend menyimpan token dari Auth Service.
4. Semua protected API memakai header:

```http
Authorization: Bearer <token>
Accept: application/json
```

5. Middleware `auth.service` memvalidasi token ke Auth Service melalui endpoint `/api/auth/me`.
6. Jika valid, data user dari Auth Service dipakai sebagai `$request->user()`.

Konfigurasi Auth Service:

```env
AUTH_SERVICE_URL=https://auth.fynworks.my.id
```

Jika Auth Service tidak tersedia, endpoint auth akan mengembalikan `503 Auth service unavailable`.

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Isi konfigurasi database dan Auth Service di `.env` sebelum menjalankan migration.

Minimal konfigurasi:

```env
APP_NAME="Todo Manager"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=todo_db
DB_USERNAME=postgres
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

AUTH_SERVICE_URL=https://auth.fynworks.my.id
```

## Menjalankan Aplikasi

```bash
php artisan serve
```

Workflow development bawaan project:

```bash
composer run dev
```

Verifikasi:

```bash
php artisan route:list --path=api
php artisan test
```

## Modul Utama

### Todos

Todo adalah task utama aplikasi. Todo mendukung group, label, sub-task, pinned, priority, status, due date, dan relasi ke milestone.

Endpoint utama:

```http
GET    /api/todos
POST   /api/todos
GET    /api/todos/{id}
PUT    /api/todos/{id}
DELETE /api/todos/{id}
PATCH  /api/todos/{id}/complete
PATCH  /api/todos/{id}/pin
```

Filter list:

```http
GET /api/todos?status=pending
GET /api/todos?priority=high
GET /api/todos?group_id=1
GET /api/todos?milestone_id=1
GET /api/todos?date=2026-05-06
```

### Sub-Tasks

Sub-task melekat ke todo.

```http
POST   /api/todos/{id}/sub-tasks
PATCH  /api/todos/{id}/sub-tasks/{sid}
PUT    /api/todos/{id}/sub-tasks/{sid}
DELETE /api/todos/{id}/sub-tasks/{sid}
```

### Milestones

Milestone sekarang terhubung dengan task/todo. Progress milestone dihitung dari jumlah todo yang `completed` dibanding total todo pada milestone.

Payload create/update milestone bisa mengirim `task_ids`:

```json
{
  "title": "Release v1",
  "category": "Work",
  "due_date": "2026-05-20",
  "notes": "Target release",
  "color": "#E8593C",
  "task_ids": [1, 2, 3]
}
```

Response milestone memuat:

```json
{
  "progress": 67,
  "task_progress": {
    "total": 3,
    "completed": 2,
    "pending": 1,
    "progress": 67
  },
  "todos": []
}
```

Endpoint:

```http
GET    /api/milestones
POST   /api/milestones
GET    /api/milestones/{id}
PUT    /api/milestones/{id}
DELETE /api/milestones/{id}
PATCH  /api/milestones/{id}/progress
```

Catatan: jika milestone memiliki task, progress manual akan digantikan oleh hasil kalkulasi task.

### Daily Progress

Daily progress mencatat pekerjaan yang sudah dilakukan pada hari tertentu. Item bisa berdiri sendiri atau dikaitkan ke todo melalui `todo_id`.

Endpoint:

```http
GET    /api/daily-progress
POST   /api/daily-progress
GET    /api/daily-progress/today
GET    /api/daily-progress/{id}
PUT    /api/daily-progress/{id}
DELETE /api/daily-progress/{id}
```

Payload create:

```json
{
  "title": "Review PR billing flow",
  "description": "Sudah cek validasi invoice dan error handling",
  "progress_date": "2026-05-19",
  "todo_id": 12
}
```

`progress_date` dan `todo_id` opsional. Jika `progress_date` tidak dikirim, backend memakai tanggal hari ini. `todo_id` hanya bisa mengarah ke todo milik user yang sedang login.

Filter list:

```http
GET /api/daily-progress?date=2026-05-19
GET /api/daily-progress?todo_id=12
GET /api/daily-progress?search=billing
```

### Encrypted Notes

Encrypted notes memakai konsep end-to-end encryption / zero-knowledge architecture.

Aturan penting:

- Backend hanya menyimpan ciphertext.
- Backend tidak menerima plaintext note.
- Backend tidak menerima password user.
- Backend tidak melakukan encrypt/decrypt.
- Title dan content tidak searchable di backend karena encrypted.
- Search hanya metadata seperti archive, deleted, pinned, updated timestamp, sync timestamp, dan encryption version.

Endpoint:

```http
GET    /api/notes
POST   /api/notes
GET    /api/notes/{id}
PUT    /api/notes/{id}
DELETE /api/notes/{id}
POST   /api/notes/{id}/restore
POST   /api/notes/{id}/archive
POST   /api/notes/{id}/pin
```

Payload encrypted note:

```json
{
  "encrypted_title": "base64-ciphertext-title",
  "encrypted_content": "base64-ciphertext-content",
  "note_iv": "base64-random-iv-or-nonce",
  "note_tag": ["base64-encrypted-or-blind-index-tag"],
  "encryption_version": 1,
  "is_archived": false,
  "is_pinned": false,
  "last_synced_at": "2026-05-06T04:30:00Z"
}
```

Standard response:

```json
{
  "success": true,
  "message": "Encrypted note created.",
  "data": {}
}
```

List filter:

```http
GET /api/notes?page=1&per_page=20
GET /api/notes?archived=true
GET /api/notes?deleted=true
GET /api/notes?pinned=true
GET /api/notes?updated_since=2026-05-06T00:00:00Z
GET /api/notes?synced_since=2026-05-06T00:00:00Z
GET /api/notes?encryption_version=1
```

Dokumentasi detail encrypted notes ada di:

```text
docs/encrypted-notes.md
```

## Struktur Tabel

| Tabel | Fungsi |
| --- | --- |
| users | Data user lokal untuk constraint/relasi internal |
| groups | Kategori atau kelompok todo |
| labels | Label untuk todo |
| todos | Task utama, termasuk relasi `milestone_id` |
| sub_tasks | Sub-task dalam todo |
| todo_label | Pivot many-to-many todo dan label |
| milestones | Milestone/project dengan progress dari todo |
| daily_progresses | Catatan pekerjaan yang sudah dilakukan per tanggal, dengan relasi opsional ke todo |
| encrypted_notes | Ciphertext notes dengan UUID, archive, pin, soft delete, dan sync metadata |
| jobs | Laravel queue jobs |
| failed_jobs | Jobs yang gagal dieksekusi |
| cache | Database cache |
| sessions | Database session |

## Security Notes

- Semua protected routes wajib melewati `auth.service`.
- Ownership data divalidasi per `user_id`.
- Encrypted notes memakai policy authorization Laravel.
- Endpoint notes memakai rate limiter `encrypted-notes`.
- Backend tidak boleh melakukan logging plaintext, password, encryption key, atau decrypted notes.
- Frontend bertanggung jawab penuh atas key derivation, encryption, dan decryption.

## Development Notes

- Jangan melakukan pencarian atau modifikasi di folder `vendor`.
- Migration utama ditujukan untuk PostgreSQL.
- Untuk encrypted notes, gunakan UUID sebagai identifier.
- Untuk sync multi-device notes, gunakan `updated_at`, `updated_since`, `last_synced_at`, dan `synced_since`.
