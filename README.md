# 🧾 Lost & Found Website

Website **Lost & Found** dibuat menggunakan **PHP Native**, **MySQL**, **TailwindCSS**, dan **Bootstrap**.  
Tujuannya untuk membantu pengguna melaporkan barang hilang atau ditemukan, serta memudahkan pencarian barang melalui sistem yang sederhana namun modern.  

---

## ✨ Features

### 👤 Frontend User
- 🔐 **Authentication**: Register, Login, Logout dengan session management  
- 🏠 **Home**: Menampilkan daftar barang hilang/ditemukan terbaru dalam bentuk card grid  
- ➕ **Create Report**: Form step-by-step untuk melaporkan barang hilang atau ditemukan  
- 🔎 **Search**: Filter berdasarkan kategori, lokasi, dan status  
- 📄 **Detail Page**: Informasi lengkap barang dengan foto, deskripsi, serta kontak pemilik/penemu  
- 👤 **User Profile**: Manajemen akun & laporan milik user  

### 🔒 Security
- Password hashing dengan `password_hash()` dan verifikasi `password_verify()`  
- Session management untuk autentikasi  
- Validasi file upload (JPG/PNG, max 2MB)  
- SQL Injection protection dengan **PDO prepared statements**  
- XSS protection menggunakan `htmlspecialchars()`  

---

## 🗄️ Database Structure

### 📌 Users
- `id` (PK)  
- `name`  
- `email`  
- `password`  
- `phone`  
- `created_at`  

### 📌 Categories
- `id` (PK)  
- `name` (unique)  
- `description`  
- `created_at`  

### 📌 Listings
- `id` (PK)  
- `user_id` (FK → users)  
- `category_id` (FK → categories)  
- `title`  
- `description`  
- `location`  
- `photo`  
- `status` (lost / found / returned)  
- `date_lost_found`  
- `created_at`, `updated_at`  

### 📌 Messages
- `id` (PK)  
- `listing_id` (FK → listings)  
- `sender_id` (FK → users)  
- `receiver_id` (FK → users)  
- `message`  
- `created_at`  

---

## 🚀 Tech Stack
- **Backend**: PHP Native (PDO)  
- **Frontend**: TailwindCSS + Bootstrap  
- **Database**: MySQL  
- **Deployment**: Compatible with Apache/Nginx + PHP 8+  

---

## 📌 Installation
1. Clone repository:  
   ```bash
   git clone https://github.com/zDarkx1/lostandfound.git
