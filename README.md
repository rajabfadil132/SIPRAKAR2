# SIPRAKAR - SISTEM INFORMASI PENGELOLAAN SARANA DAN PRASARANA BERBASIS WEB

Aplikasi manajemen program kerja berbasis web menggunakan Laravel + React + Inertia.js + Tailwind CSS.

## Persyaratan Sistem

- **PHP** 8.2+
- **Composer** 2.x
- **Node.js** 18+
- **NPM** 9+
- **MySQL** 8.0+ atau **SQLite**
- **Git**

## Langkah Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/rajabfadil132/SIPRAKAR2.git
cd SIPRAKAR2
```

### 2. Install Dependency PHP

```bash
composer install
```

### 3. Install Dependency Node.js

```bash
npm install
```

### 4. Konfigurasi Environment

Salin file `.env.example` ke `.env`:

```bash
cp .env.example .env
```

Atau buat manual dengan isi berikut di `.env`:

```env
APP_NAME=SIPRAKAR
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Database - Gunakan MySQL atau SQLite
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siprakar_db
DB_USERNAME=root
DB_PASSWORD=

# Session & Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=database

# Redis (optional - untuk production)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Setup Database

**Opsi A: MySQL**
1. Buat database `siprakar_db` di MySQL
2. Jalankan migrasi:

```bash
php artisan migrate
```

**Opsi B: SQLite**
1. Ganti `DB_CONNECTION=mysql` menjadi `DB_CONNECTION=sqlite` di `.env`
2. Hapus baris DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
3. Buat file database:

```bash
touch database/database.sqlite
```

4. Jalankan migrasi:

```bash
php artisan migrate
```

### 7. Build Frontend

```bash
npm run build
```

### 8. Jalankan Aplikasi

```bash
php artisan serve
```

Buka browser di: **http://127.0.0.1:8000**

---

## Mode Development

Untuk development dengan hot reload:

```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite dev server
npm run dev
```

Buka browser di: **http://127.0.0.1:8000**

---

## Perintah Artisan Penting

```bash
# Clear cache & config
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Migrate database
php artisan migrate

# Rollback migration terakhir
php artisan migrate:rollback

# Fresh migrate (hapus & buat ulang database)
php artisan migrate:fresh

# Create cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# List routes
php artisan route:list

# Check routes (verbose)
php artisan route:list -v
```

---

## Struktur Direktori

```
SIPRAKAR2/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/           # Auth controllers
│   │   │   ├── System/         # System controllers
│   │   │   └── Siprakar/       # Main business logic
│   │   └── Middleware/
│   ├── Models/                 # Eloquent models
│   ├── Policies/               # Authorization policies
│   └── Providers/
├── bootstrap/
├── config/                      # Laravel config
├── database/
│   ├── migrations/             # Database migrations
│   ├── seeders/                # Database seeders
│   └── database.sqlite         # SQLite database (jika pakai SQLite)
├── public/
│   └── build/                  # Compiled frontend assets
├── resources/
│   ├── js/                     # React & Inertia frontend
│   └── views/                  # Blade views
├── routes/
│   └── web.php                 # Web routes
├── storage/
│   └── logs/                   # Application logs
├── .env                        # Environment variables
├── vite.config.js              # Vite configuration
└── tailwind.config.js          # Tailwind configuration
```

---

## Fitur Utama

- **Dashboard** - Overview program kerja
- **Program Kerja** - Manajemen program kerja
- **Pekerjaan** - Detail pekerjaan per program
- **RAB (Rencana Anggaran Biaya)** - Planning dan tracking anggaran
- **Arsip** - Data tersimpan (soft delete)
- **Master Data** - Kategori, role, lokasi, lantai, dll
- **Activity Log** - Audit trail perubahan
- **Notifikasi** - Sistem notifikasi real-time
- **User Management** - Role & permission management

---

## Troubleshooting

### Error: "Vite manifest not found"

Jalankan build ulang:

```bash
npm run build
```

### Error: "MySQL connection refused"

Pastikan MySQL service berjalan:

```bash
# Windows
net start mysql

# Linux/Mac
sudo service mysql start
```

### Error: "Class not found"

Clear cache dan regenerate autoload:

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Error: "Permission denied" pada storage

```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Deployment

### Persiapan Production

1. Set `APP_ENV=production` dan `APP_DEBUG=false` di `.env`
2. Setup database production (MySQL/PostgreSQL)
3. Setup Redis untuk session & queue
4. Build frontend:

```bash
npm run build
```

5. Cache config:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Web Server Configuration

**Apache** - buat `public/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L]
</IfModule>
```

**Nginx**:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## License

MIT License