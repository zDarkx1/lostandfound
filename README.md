# Lost & Found Website

Website Lost & Found menggunakan PHP Native, TailwindCSS, dan MySQL untuk membantu pengguna melaporkan dan mencari barang hilang atau ditemukan.

## Fitur

### Frontend User
- **Autentikasi**: Register, Login, Logout dengan session management
- **Beranda**: Tampilan daftar barang hilang/ditemukan terbaru dengan desain card grid
- **Buat Laporan**: Form step-by-step untuk melaporkan barang hilang/ditemukan
- **Cari Barang**: Fitur pencarian dengan filter kategori, lokasi, dan status
- **Detail Barang**: Informasi lengkap barang dengan fitur kontak pemilik/penemu
- **Profil**: Manajemen akun dan laporan milik user

### Keamanan
- Password hashing menggunakan `password_hash()` dan `password_verify()`
- Session management untuk autentikasi
- Validasi file upload (JPG/PNG, max 2MB)
- SQL injection protection dengan PDO prepared statements
- XSS protection dengan `htmlspecialchars()`

## Struktur Database

### Tabel Users
- id (Primary Key)
- name, email, password, phone
- created_at

### Tabel Categories
- id (Primary Key)
- name, description
- created_at

### Tabel Listings
- id (Primary Key)
- user_id (Foreign Key ke users)
- category_id (Foreign Key ke categories)
- title, description, location, photo
- status (lost/found/returned)
- date_lost_found, created_at, updated_at

### Tabel Messages
- id (Primary Key)
- listing_id (Foreign Key ke listings)
- sender_id, receiver_id (Foreign Key ke users)
- message, created_at

### Tabel Reports
- id (Primary Key)
- listing_id (Foreign Key ke listings)
- reporter_id (Foreign Key ke users)
- reason, status, created_at

### Tabel Audit_logs
- id (Primary Key)
- user_id (Foreign Key ke users)
- action, table_name, record_id
- old_values, new_values (JSON)
- created_at

## Instalasi

1. **Setup Database**
   - Buat database MySQL baru
   - Update konfigurasi database di `config/db.php`
   - Jalankan website, tabel akan dibuat otomatis

2. **Setup Web Server**
   - Pastikan PHP >= 7.4 dan MySQL tersedia
   - Copy semua file ke web server directory
   - Pastikan folder `uploads/` dapat ditulis (chmod 777)

3. **Konfigurasi**
   - Edit `config/db.php` sesuai pengaturan database Anda
   - Pastikan TailwindCSS CDN dapat diakses

## Struktur File

```
/
├── config/
│   └── db.php              # Konfigurasi database
├── partials/
│   ├── navbar.php          # Navigation bar
│   └── footer.php          # Footer
├── assets/css/
│   └── tailwind.css        # Custom CSS dengan TailwindCSS
├── uploads/                # Folder untuk foto barang
├── index.php               # Halaman beranda
├── login.php               # Halaman login
├── register.php            # Halaman registrasi
├── logout.php              # Logout handler
├── laporan.php             # Form buat laporan
├── cari.php                # Halaman pencarian
├── detail.php              # Detail barang
├── profil.php              # Halaman profil user
├── report_listing.php      # Handler laporan konten
├── .htaccess               # Konfigurasi Apache
└── README.md               # Dokumentasi
```

## Penggunaan

1. **Register/Login**: User mendaftar dengan nama, email, password, dan nomor HP
2. **Buat Laporan**: User dapat melaporkan barang hilang atau ditemukan dengan foto
3. **Cari Barang**: Pencarian berdasarkan keyword, kategori, lokasi, dan status
4. **Kontak**: User dapat menghubungi pemilik/penemu barang melalui sistem pesan
5. **Manajemen**: User dapat mengelola laporan mereka di halaman profil

## Teknologi

- **Backend**: PHP Native dengan PDO untuk database
- **Frontend**: HTML5, TailwindCSS, JavaScript
- **Database**: MySQL
- **Security**: Session management, password hashing, input validation

## Catatan Keamanan

- Semua input user divalidasi dan disanitasi
- File upload dibatasi jenis dan ukuran
- Session timeout untuk keamanan
- Audit log untuk tracking aktivitas user
- Protection terhadap SQL injection dan XSS

## Kontribusi

Untuk pengembangan lebih lanjut, pastikan mengikuti best practices:
- Validasi input di sisi server
- Gunakan prepared statements untuk query database
- Implementasi CSRF protection jika diperlukan
- Regular security updates dan backup database