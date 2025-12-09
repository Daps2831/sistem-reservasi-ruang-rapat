# Sistem Reservasi Ruang Rapat

Aplikasi web untuk mengelola reservasi ruang rapat dengan fitur pencegahan konflik jadwal otomatis.

## 📋 Daftar Isi

- [Skema Database](#skema-database)
- [Alur Logika Mencegah Konflik Jadwal](#alur-logika-mencegah-konflik-jadwal)
- [Dokumentasi Prompt AI](#dokumentasi-prompt-ai)
- [Instalasi](#instalasi)
- [Fitur Aplikasi](#fitur-aplikasi)

---

## 🗄️ Skema Database

Aplikasi ini menggunakan 3 tabel utama dengan relasi sebagai berikut:

![Database Schema](sistem-reservasi-erd.png)

### Struktur Tabel

#### 1. **users** (Karyawan)
| Field | Type | Keterangan |
|-------|------|------------|
| id | bigint | Primary Key |
| name | string | Nama lengkap user |
| email | string | Email (unique) |
| password | string | Password (hashed) |
| created_at | timestamp | Waktu dibuat |
| updated_at | timestamp | Waktu diupdate |

#### 2. **rooms** (Ruang Rapat)
| Field | Type | Keterangan |
|-------|------|------------|
| id | bigint | Primary Key |
| name | string | Nama ruang rapat |
| capacity | integer | Kapasitas orang |
| description | text | Deskripsi & fasilitas |
| created_at | timestamp | Waktu dibuat |
| updated_at | timestamp | Waktu diupdate |

#### 3. **reservations** (Jadwal Reservasi)
| Field | Type | Keterangan |
|-------|------|------------|
| id | bigint | Primary Key |
| user_id | bigint | Foreign Key ke users |
| room_id | bigint | Foreign Key ke rooms |
| start_time | datetime | Waktu mulai reservasi |
| end_time | datetime | Waktu selesai reservasi |
| notes | text | Catatan/agenda rapat |
| created_at | timestamp | Waktu dibuat |
| updated_at | timestamp | Waktu diupdate |

### Relasi Tabel

- **User → Reservations**: One to Many (1 user bisa punya banyak reservasi)
- **Room → Reservations**: One to Many (1 room bisa punya banyak reservasi)
- **Reservation → User**: Many to One (1 reservasi milik 1 user)
- **Reservation → Room**: Many to One (1 reservasi untuk 1 room)

---

## 🔒 Alur Logika Mencegah Konflik Jadwal

### 1. Validasi Input Dasar

```php
// Validasi waktu mulai dan selesai
$validated = $request->validate([
    'room_id' => 'required|exists:rooms,id',
    'start_time' => 'required|date|after:now',
    'end_time' => 'required|date|after:start_time',
]);
```

**Kriteria:**
- `start_time` tidak boleh waktu lampau
- `end_time` harus setelah `start_time`

---

### 2. Validasi Jam Kerja (08:00 - 17:00)

```php
$workStart = Carbon::parse($startTime->format('Y-m-d') . ' 08:00:00');
$workEnd = Carbon::parse($startTime->format('Y-m-d') . ' 17:00:00');

if ($startTime->lt($workStart) || $startTime->gte($workEnd)) {
    return back()->withErrors([
        'start_time' => 'Waktu mulai harus antara jam 08:00 - 17:00.'
    ]);
}

if ($endTime->gt($workEnd) || $endTime->lte($workStart)) {
    return back()->withErrors([
        'end_time' => 'Waktu selesai harus antara jam 08:00 - 17:00.'
    ]);
}
```

**Kriteria:**
- Waktu mulai: >= 08:00 dan < 17:00
- Waktu selesai: > 08:00 dan <= 17:00

---

### 3. Validasi Konflik Jadwal dengan Universal Overlap Formula

#### Formula Matematis:
```
Terjadi konflik JIKA:
(StartA < EndB) AND (EndA > StartB)
```

**Penjelasan:**
- StartA = waktu mulai reservasi yang sudah ada
- EndA = waktu selesai reservasi yang sudah ada
- StartB = waktu mulai reservasi baru yang ingin dibuat
- EndB = waktu selesai reservasi baru yang ingin dibuat

#### Implementasi di Database dengan Lock:

```php
return DB::transaction(function () use ($validated, $startTime, $endTime) {
    // Cek konflik dengan Universal Overlap Formula
    $conflict = Reservation::where('room_id', $validated['room_id'])
        ->where('start_time', '<', $endTime)      // StartA < EndB
        ->where('end_time', '>', $startTime)       // EndA > StartB
        ->lockForUpdate()                          // Lock row untuk mencegah race condition
        ->exists();

    if ($conflict) {
        return back()->withErrors([
            'start_time' => 'Ruangan sudah dibooking pada waktu tersebut.'
        ])->withInput();
    }

    // Simpan jika aman
    Reservation::create([...]);
});
```

---

### 4. Penjelasan Skenario Konflik

#### ✅ **Skenario 1: Tidak Ada Konflik**
```
Rapat A: 09:00 - 10:00
Rapat B: 10:00 - 11:00

Cek: (09:00 < 11:00) AND (10:00 > 10:00) 
     = TRUE AND FALSE = FALSE ✓ TIDAK KONFLIK
```

#### ❌ **Skenario 2: Konflik - Overlap di Tengah**
```
Rapat A: 09:00 - 11:00
Rapat B: 10:00 - 12:00

Cek: (09:00 < 12:00) AND (11:00 > 10:00)
     = TRUE AND TRUE = TRUE ✗ KONFLIK!
```

#### ❌ **Skenario 3: Konflik - Rapat B Menelan Rapat A**
```
Rapat A: 10:00 - 11:00
Rapat B: 09:00 - 12:00

Cek: (10:00 < 12:00) AND (11:00 > 09:00)
     = TRUE AND TRUE = TRUE ✗ KONFLIK!
```

#### ❌ **Skenario 4: Konflik - Mulai Bersamaan**
```
Rapat A: 10:00 - 11:00
Rapat B: 10:00 - 11:30

Cek: (10:00 < 11:30) AND (11:00 > 10:00)
     = TRUE AND TRUE = TRUE ✗ KONFLIK!
```

---

### 5. Race Condition Prevention dengan `lockForUpdate()`

**Masalah Tanpa Lock:**
```
Timeline:
t1: User A cek konflik → Kosong ✓
t2: User B cek konflik → Kosong ✓
t3: User A simpan → Sukses
t4: User B simpan → Sukses (DOUBLE BOOKING!)
```

**Solusi Dengan Lock:**
```
Timeline:
t1: User A cek konflik + LOCK → Kosong ✓
t2: User B cek konflik → TUNGGU (row terkunci)
t3: User A simpan → Sukses, LOCK released
t4: User B cek konflik → Konflik terdeteksi! ✓
```

`lockForUpdate()` memastikan hanya 1 transaksi yang bisa membaca dan menulis pada saat bersamaan.

---

### 6. Authorization dengan Policy

```php
// ReservationPolicy.php
public function delete(User $user, Reservation $reservation): bool
{
    return $user->id === $reservation->user_id;
}

// ReservationController.php
public function destroy(Reservation $reservation)
{
    $this->authorize('delete', $reservation);
    $reservation->delete();
}
```

**Kriteria:**
- Hanya pembuat reservasi yang bisa membatalkannya
- Otomatis throw 403 Forbidden jika bukan pemilik

---

## 🤖 Dokumentasi Prompt AI

### Tools yang Digunakan
- **AI Assistant**: GitHub Copilot (Claude Sonnet 4.5)
- **IDE**: Visual Studio Code
- **Pendekatan**: Iterative prompting dengan context-aware conversation

### Strategi Prompting

#### 1. **Initial Setup - Membuat Struktur Dasar**

**Prompt:**
```
Saya ingin membuat sistem reservasi ruang rapat, dengan:
1. Melihat daftar ruang rapat yang tersedia beserta jadwalnya
2. Membuat reservasi ruang rapat untuk waktu tertentu (dengan memeriksa konflik jadwal)
3. Membatalkan reservasi yang telah dibuat secara pribadi (otentikasi diperlukan)
4. Melihat riwayat reservasi pribadi

Untuk sekarang saya baru membuat model reservation dan juga model room, 
buatkan relasi dan juga atribut dengan ketentuan:
1. rooms (id, name, capacity, description)
2. reservations (id, user_id, room_id, start_time, end_time, notes)
```

**Output:** Migration files + Model relationships

---

#### 2. **Controller Logic - Implementasi Business Logic**

**Prompt:**
```
Implementasikan:
1. Mengambil data ruangan dari database dan mengirimnya ke View dashboard 
   pada room controller
2. Alur Logika Mencegah Konflik Jadwal dan validasi jam kerja diterapkan 
   pada reservation controller
3. Menampilkan riwayat reservasi user yang sedang login urutkan dari yang terbaru

Logika Validasi yang diperlukan:
• Validasi Input Dasar (start_time tidak boleh waktu lampau, end_time harus setelah start time)
• Jam Kerja: start_time dan end_time harus di antara 08:00 - 17:00
• Tidak Tumpang Tindih (whereBetween untuk start_time dan end_time, 
  plus cek yang booking lebih panjang)
• Simpan jika aman
• Logic batalkan jika user ingin membatalkannya (pakai validasi kepemilikan user)
```

**Output:** Complete controller methods with validation

---

#### 3. **Revision - Memperbaiki Bug dan Security**

**Prompt:**
```
Revisi:
1. Logika strict inequality agar tidak terjadi skenario bug false positive conflict
   (contoh: Rapat A 09:00-10:00, Rapat B 10:00-11:00 seharusnya BOLEH)
2. Policy / Gate Authorization pada method destroy: $this->authorize('delete', $reservation)
3. Gunakan rumus Universal Overlap: (StartA < EndB) && (EndA > StartB)
4. Gunakan lockForUpdate() untuk mencegah Race Condition
```

**Output:** Improved logic dengan formula yang benar + security enhancement

---

#### 4. **Authentication - Membuat Login/Register**

**Prompt:**
```
Buat pages register dan loginnya
```

**Follow-up Prompt:**
```
Revisi: buat layouts app.blade sederhana agar bisa @extends('layouts.app') 
di register dan juga login
```

**Output:** Complete authentication system dengan layout

---

#### 5. **UI/UX - Dashboard dan Views**

**Prompt:**
```
1. Buat navbar di app layout berisi routing ke reservasi dan dashboard, 
   tambahkan juga fitur logout
2. Buat page reservasi menggunakan backend yang sudah dibuat, 
   letakkan di /views/reservations/
3. Buat page dashboard sederhana berisi riwayat reservasi
```

**Output:** Complete UI dengan Tailwind CSS

---

#### 6. **Enhancement - Detail Room Page**

**Prompt:**
```
1. Buat ke page baru (berdasarkan id) list room dengan tombol lihat daftar reservasi
2. Di page baru roomnya buatkan list pendaftar dan juga tanggal serta rentang jamnya,
   agar seseorang bisa memilih langsung tanpa menerka kapan kosongnya
```

**Output:** Room detail page dengan jadwal lengkap

---

#### 7. **Client-side Validation**

**Prompt:**
```
Buat javascript untuk validasi waktu sebelum klik buat reservasi, 
agar data divalidasi sebelum dikirimkan
```

**Output:** Comprehensive JavaScript validation

---

#### 8. **Database Seeding**

**Prompt:**
```
Saya telah membuat seeder untuk room, Isi dengan Data Dummy 
dengan berbagai macam kapasitas
```

**Output:** RoomSeeder dengan 10 data dummy

---

---

### Alur Kerja dengan AI

```
1. Define Requirements
   ↓
2. Prompt AI untuk Setup Awal (Models, Migrations, Relations)
   ↓
3. Review & Test
   ↓
4. Prompt AI untuk Business Logic (Controllers, Validation)
   ↓
5. Review & Test
   ↓
6. Prompt AI untuk Revision (Bug Fixes, Security)
   ↓
7. Review & Test
   ↓
8. Prompt AI untuk UI/UX (Views, Frontend)
   ↓
9. Review & Test
   ↓
10. Prompt AI untuk Enhancement (Features, Optimization)
    ↓
11. Final Review & Documentation
```

---

## 📸 Screenshot Aplikasi

### Dashboard - Daftar Ruang Rapat
Halaman utama yang menampilkan semua ruang rapat yang tersedia dengan informasi kapasitas dan jumlah reservasi. User dapat langsung membuat reservasi atau melihat jadwal detail.

![Dashboard](screenshots/dashboard.png)

### Modal Form Reservasi
Form untuk membuat reservasi baru dengan validasi real-time. Sistem akan otomatis memvalidasi jam kerja (08:00-17:00) dan mencegah konflik jadwal.

![Modal Reservasi](screenshots/modal-reservasi.png)

### Detail Ruang Rapat - Jadwal Reservasi
Halaman detail menampilkan semua jadwal reservasi yang sudah terbooking, dikelompokkan per tanggal. User dapat melihat siapa yang booking dan jam berapa saja.

![Detail Room](screenshots/detail-room.png)

### Riwayat Reservasi Pribadi
Halaman yang menampilkan semua reservasi yang telah dibuat oleh user yang sedang login, dengan opsi untuk membatalkan reservasi yang akan datang.

![Riwayat Reservasi](screenshots/riwayat-reservasi.png)

---

## 💻 Instalasi

### Requirements
- PHP >= 8.2
- Composer
- MySQL/PostgreSQL
- Node.js & NPM (untuk assets)

### Langkah Instalasi

1. **Clone Repository**
```bash
git clone https://github.com/Daps2831/sistem-reservasi-ruang-rapat.git
cd sistem-reservasi-ruang-rapat
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Configuration**
Edit file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_reservasi
DB_USERNAME=root
DB_PASSWORD=
```

5. **Run Migrations & Seeders**
```bash
php artisan migrate
php artisan db:seed --class=RoomSeeder
```

6. **Start Development Server**
```bash
php artisan serve
```

7. **Access Application**
```
http://localhost:8000
```

---

## ✨ Fitur Aplikasi

### 1. Authentication
- ✅ Register akun baru
- ✅ Login/Logout
- ✅ Session management

### 2. Dashboard
- ✅ Daftar semua ruang rapat
- ✅ Info kapasitas dan total reservasi
- ✅ Quick reservation button

### 3. Room Detail
- ✅ Lihat jadwal reservasi per ruangan
- ✅ Informasi pemesan dan waktu
- ✅ Badge status (Reservasi Anda/Tidak Tersedia)
- ✅ Grouped by date

### 4. Reservation Management
- ✅ Buat reservasi baru
- ✅ Validasi konflik jadwal otomatis
- ✅ Validasi jam kerja (08:00-17:00)
- ✅ Client-side validation (JavaScript)
- ✅ Server-side validation (Laravel)
- ✅ Race condition prevention (DB Lock)
- ✅ Lihat riwayat reservasi pribadi
- ✅ Batalkan reservasi (dengan authorization)

### 5. Security
- ✅ CSRF Protection
- ✅ Policy-based Authorization
- ✅ Database Transaction & Locking
- ✅ Password Hashing

---

## 🎯 Cara Penggunaan

### 1. Registrasi & Login

**Registrasi Akun Baru:**
1. Akses halaman `/register`
2. Isi form dengan nama, email, dan password
3. Klik tombol "Daftar"
4. Otomatis login dan redirect ke dashboard

**Login:**
1. Akses halaman `/login`
2. Masukkan email dan password
3. Optional: Centang "Ingat saya" untuk auto-login
4. Klik tombol "Login"

---

### 2. Membuat Reservasi

**Dari Dashboard:**
1. Pilih ruang rapat yang diinginkan
2. Klik tombol **"Buat Reservasi"** (biru)
3. Modal form akan muncul
4. Isi waktu mulai dan selesai
5. (Opsional) Tambahkan catatan/agenda
6. Klik **"Buat Reservasi"**

**Validasi Otomatis:**
- ⏰ Waktu harus dalam jam kerja (08:00-17:00)
- 📅 Tidak boleh waktu lampau
- ⏱️ Durasi minimal 30 menit, maksimal 8 jam
- 🚫 Sistem akan tolak jika ada konflik jadwal

**Dari Detail Room:**
1. Klik tombol **"Lihat Jadwal Reservasi"** pada room card
2. Periksa jadwal yang sudah ada
3. Pilih waktu yang kosong
4. Klik **"Buat Reservasi"** di pojok kanan atas
5. Isi form dan submit

---

### 3. Melihat Jadwal Reservasi

**Cara 1: Dari Dashboard**
- Klik tombol **"Lihat Jadwal Reservasi"** (outline biru) pada room yang diinginkan
- Akan muncul halaman detail dengan list semua reservasi

**Cara 2: Dari Navbar**
- Klik menu **"Dashboard"** untuk melihat semua ruangan
- Pilih ruangan dan klik tombol lihat jadwal

**Informasi yang Ditampilkan:**
- 📅 Tanggal dan hari
- ⏰ Rentang waktu (HH:MM - HH:MM)
- 👤 Nama pemesan
- 📝 Agenda/catatan rapat (jika ada)
- 🏷️ Badge status:
  - 🟢 Hijau "Reservasi Anda" - milik Anda
  - 🔴 Merah "Tidak Tersedia" - milik orang lain

---

### 4. Melihat Riwayat Reservasi Pribadi

1. Klik menu **"Reservasi Saya"** di navbar
2. Akan tampil semua reservasi yang pernah Anda buat
3. Diurutkan dari yang terbaru (created_at DESC)

**Status Reservasi:**
- ⏳ **Akan Datang** - Badge hijau, ada tombol "Batalkan"
- ✅ **Selesai** - Badge abu-abu, tidak bisa dibatalkan

---

### 5. Membatalkan Reservasi

**Syarat:**
- ✅ Hanya pembuat reservasi yang bisa membatalkan
- ✅ Hanya reservasi yang **akan datang** yang bisa dibatalkan
- ❌ Reservasi yang sudah lewat tidak bisa dibatalkan

**Cara:**
1. Buka halaman **"Reservasi Saya"**
2. Cari reservasi yang ingin dibatalkan
3. Klik tombol **"Batalkan"** (merah)
4. Konfirmasi pembatalan
5. Reservasi akan dihapus dari sistem

---

### 6. Logout

**Cara 1: Dari Navbar**
- Klik tombol **"Logout"** (merah) di pojok kanan atas navbar

**Keamanan:**
- Session akan di-invalidate
- Token CSRF akan di-regenerate
- User akan redirect ke halaman login

---

## 🛡️ Fitur Keamanan

### 1. Authentication & Authorization
- **Laravel Sanctum**: Session-based authentication
- **Policy Gates**: Authorization berbasis kepemilikan resource
- **Middleware Auth**: Proteksi routes yang memerlukan login
- **Password Hashing**: Menggunakan bcrypt dengan salt

### 2. Database Security
- **SQL Injection Prevention**: Eloquent ORM dengan prepared statements
- **Mass Assignment Protection**: `$fillable` whitelist pada models
- **Foreign Key Constraints**: Cascade delete untuk data integrity

### 3. Transaction & Concurrency
- **Database Transaction**: ACID compliance
- **Pessimistic Locking**: `lockForUpdate()` untuk race condition
- **Atomic Operations**: Semua operasi CUD dalam transaction

### 4. Input Validation
- **Server-side**: Laravel validation rules
- **Client-side**: JavaScript validation sebelum submit
- **CSRF Protection**: Token validation pada setiap POST request
- **XSS Prevention**: Blade templating auto-escape output

### 5. Business Logic Security
- **Jam Kerja Validation**: Hardcoded 08:00-17:00
- **Conflict Detection**: Universal overlap formula
- **Ownership Check**: Policy-based authorization
- **Time Validation**: Tidak boleh booking waktu lampau

---

## 🐛 Troubleshooting

### Error: "Ruangan sudah dibooking pada waktu tersebut"

**Penyebab:**
- Ada reservasi lain di waktu yang sama atau overlap

**Solusi:**
1. Klik **"Lihat Jadwal Reservasi"** pada ruangan tersebut
2. Periksa jadwal yang sudah ada
3. Pilih waktu yang tidak konflik
4. Pastikan waktu mulai dan selesai tidak overlap dengan reservasi lain

---

### Error: "Waktu mulai harus antara jam 08:00 - 17:00"

**Penyebab:**
- Waktu yang dipilih di luar jam kerja

**Solusi:**
- Pilih waktu mulai antara **08:00 - 16:59**
- Pilih waktu selesai antara **08:01 - 17:00**
- Pastikan reservasi dalam 1 hari (tidak lintas hari)

---

### Error: "This action is unauthorized"

**Penyebab:**
- Mencoba membatalkan reservasi orang lain

**Solusi:**
- Hanya bisa membatalkan reservasi sendiri
- Cek di halaman **"Reservasi Saya"**
- Pastikan Anda yang membuat reservasi tersebut

---

### Error: "Durasi reservasi minimal 30 menit"

**Penyebab:**
- Durasi terlalu pendek

**Solusi:**
- Pastikan durasi minimal 30 menit
- Maksimal 8 jam per reservasi

