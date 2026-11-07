# Perpus Aman

Prototipe Laravel untuk skripsi **Implementasi Hybrid Cryptography AES-256-GCM dan ML-KEM untuk Pengamanan Data Anggota pada Sistem Informasi Perpustakaan Universitas Mercu Buana**.

## Fitur

- Enkripsi enam atribut anggota (nomor anggota, nama, kampus, tanggal berlaku, email, dan telepon) sebelum disimpan.
- Skema hybrid ML-KEM-768, HKDF-SHA-256, dan AES-256-GCM serta pembanding AES-256-GCM.
- Dekripsi dengan verifikasi authentication tag dan uji perubahan ciphertext.
- Pengukuran waktu operasi dan storage overhead menggunakan data sintetis.
- Login untuk pengelola. Pendaftaran hanya dibuka sebelum akun pertama dibuat.
- Penyimpanan metadata/ciphertext di SQLite; plaintext tidak disimpan sebagai kolom.

ML-KEM memakai API native `node:crypto` (encapsulate/decapsulate). Aplikasi sengaja merupakan prototipe terpisah dan tidak terhubung ke SIA atau sistem operasional perpustakaan.

## Persyaratan

- PHP 8.2+
- Composer
- Node.js 24.7+ dengan dukungan `ml-kem-768` pada `node:crypto` (disarankan Node 24.18+)
- SQLite

Versi Node penting karena operasi ML-KEM dilakukan langsung oleh Node.js, bukan disimulasikan atau diganti algoritma lain. Atur `NODE_BINARY` di `.env` bila executable Node tidak bernama `node`.

## Menjalankan

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`, lalu buat akun pengelola pertama. Setelah akun dibuat, pendaftaran publik ditutup.

## Perlindungan akun Admin

Semua akun pada prototipe ini memiliki akses Admin. Penghapusan akun memerlukan kata sandi saat ini dan ditolak jika akun tersebut adalah Admin terakhir; penghapusan akun oleh salah satu dari beberapa Admin tetap diperbolehkan.

Jika Admin lupa kata sandi, gunakan **Lupa kata sandi?** pada halaman login. Tautan reset dikirim ke alamat email akun dan dapat digunakan untuk menetapkan kata sandi baru. Pastikan mailer di `.env` dikonfigurasi agar email benar-benar terkirim. Secara default `MAIL_MAILER=log`, sehingga tautan reset dicatat ke `storage/logs/laravel.log` dan tidak dikirim melalui email.

## Retensi audit log

Log aktivitas disimpan selama 365 hari sejak dibuat. Log yang lebih lama dihapus otomatis setiap hari oleh scheduler Laravel. Atur `ACTIVITY_LOG_RETENTION_DAYS` di `.env` ke bilangan bulat minimal `1` untuk mengubah masa retensi. Untuk menjalankan pembersihan manual, gunakan `php artisan activity-logs:purge`. Di lingkungan produksi, pastikan Laravel scheduler dijalankan secara rutin (`php artisan schedule:run` setiap menit melalui cron).

## Penanganan error

Detail error dari proses Node.js hanya dicatat di log aplikasi; respons ke pengguna menggunakan pesan umum. Error internal yang tidak ditangani juga menampilkan halaman 500 umum atau pesan JSON umum, termasuk saat debug aktif. Untuk menyelidiki kegagalan, Admin dapat memeriksa log aplikasi di `storage/logs/laravel.log` melalui akses server yang sesuai.

## Pengelolaan kunci

Key pair ML-KEM-768 dibuat saat enkripsi pertama dan disimpan di `storage/app/private/keys/ml-kem-768.json`. Private key dibungkus menggunakan enkripsi Laravel dan `APP_KEY`; file kunci lokal tidak masuk Git. Saat digunakan, aplikasi memastikan lokasi key berada di luar direktori publik, mengatur permission direktori menjadi `0700`, serta file private key dan file lock menjadi `0600`. Jika permission aman tidak dapat diterapkan, operasi gagal. Lokasi alternatif melalui `MLKEM_KEY_PATH` juga harus berada di luar direktori publik. Simpan cadangan file tersebut dan `APP_KEY` secara terpisah. Perubahan `APP_KEY` tanpa pemulihan key lama membuat private key yang tersimpan tidak dapat dibuka kembali.

Kunci AES baseline diturunkan dari `APP_KEY` melalui HKDF. Nonce GCM dan salt HKDF dibuat acak untuk setiap proses enkripsi.

## Backup dan pemulihan

Database, file private key ML-KEM, dan rahasia konfigurasi adalah satu set pemulihan yang saling terkait. Backup yang hanya berisi database atau hanya file key tidak cukup. Simpan backup di media/lokasi terpisah dengan enkripsi at rest dan akses terbatas. Perintah snapshot di bawah tidak mengenkripsi hasilnya sendiri; gunakan volume terenkripsi atau enkripsi backup sebelum memindahkannya ke penyimpanan jangka panjang. Jangan masukkan file backup atau nilai `APP_KEY` ke Git, tiket, atau log.

### Membuat backup

1. Pastikan paket SQLite CLI (`sqlite3`) tersedia dan siapkan tujuan backup pada volume terenkripsi dengan akses terbatas. Contoh berikut memakai `/secure-backups/perpusaman`; ganti dengan lokasi backup yang sesuai.
2. Buat snapshot SQLite yang konsisten menggunakan perintah `.backup` (sesuaikan sumber bila `DB_DATABASE` memakai lokasi selain default):

   ```sh
   umask 077
   mkdir -p /secure-backups/perpusaman
   sqlite3 database/database.sqlite ".backup '/secure-backups/perpusaman/database.sqlite'"
   ```

3. Salin file key ke tujuan yang sama, lalu simpan salinan `APP_KEY` dan konfigurasi `MLKEM_KEY_PATH`/`DB_DATABASE` pada secret manager atau media terenkripsi terpisah. Jika `MLKEM_KEY_PATH` tidak diatur, file key ada di `storage/app/private/keys/ml-kem-768.json`.

   ```sh
   cp storage/app/private/keys/ml-kem-768.json /secure-backups/perpusaman/ml-kem-768.json
   chmod 600 /secure-backups/perpusaman/database.sqlite /secure-backups/perpusaman/ml-kem-768.json
   ```

4. Catat waktu backup dan identitas versi rahasia di secret manager tanpa mencatat nilai rahasianya. Uji pemulihan berkala di lingkungan terisolasi, termasuk login serta dekripsi satu record uji. Jangan menguji dengan menimpa data produksi.

### Memulihkan backup

1. Hentikan akses tulis ke aplikasi dan worker. Pilih satu set backup yang cocok; pulihkan database dan private key yang dibuat pada waktu backup yang sama.
2. Pulihkan database ke path `DB_DATABASE`, private key ke path `MLKEM_KEY_PATH` (atau lokasi default), dan pulihkan `APP_KEY` pasangannya dari secret manager. Jangan membuat `APP_KEY` baru untuk membuka backup lama.
3. Pastikan direktori key hanya dapat diakses akun aplikasi (`0700`) dan file key hanya dapat dibaca/ditulis akun aplikasi (`0600`). Bersihkan cache konfigurasi lalu periksa migrasi tanpa menjalankan migrasi otomatis:

   ```sh
   chmod 700 storage/app/private/keys
   chmod 600 storage/app/private/keys/ml-kem-768.json
   php artisan config:clear
   php artisan migrate:status
   ```

   Sesuaikan perintah permission jika menggunakan `MLKEM_KEY_PATH` alternatif. Pastikan file dimiliki akun sistem yang menjalankan aplikasi.
4. Sebelum membuka akses, verifikasi aplikasi dapat membaca database dan private key, login Admin berhasil, dan record uji lama dapat didekripsi. Jika verifikasi gagal, hentikan pemulihan dan pulihkan kembali set backup yang cocok.

### Rotasi APP_KEY dan private key ML-KEM

Jangan menjalankan `php artisan key:generate` pada instalasi aktif sebagai cara rotasi. `APP_KEY` melindungi pembungkus private key ML-KEM dan menjadi sumber kunci AES baseline, sedangkan private key ML-KEM diperlukan untuk membuka record hybrid yang sudah tersimpan. Mengganti salah satu kunci tanpa mengenkripsi ulang data dapat membuat data lama tidak dapat dipulihkan.

Laravel `APP_PREVIOUS_KEYS` dapat membantu membaca data yang dibungkus Laravel `Crypt` dengan kunci sebelumnya, tetapi aplikasi ini menurunkan kunci AES baseline langsung dari `APP_KEY`. Karena itu, `APP_PREVIOUS_KEYS` saja **tidak** membuat seluruh data baseline kompatibel setelah rotasi. Aplikasi juga belum menyediakan alat migrasi/rotasi data end-to-end; jangan mengganti `APP_KEY` atau menimpa file key ML-KEM pada sistem yang memiliki data penting sampai prosedur migrasi diuji.

Rencana rotasi yang aman:

1. Jadwalkan maintenance dan buat serta uji backup lengkap sesuai prosedur di atas. Siapkan pemulihan rollback sebelum mengubah kunci.
2. Gunakan alat migrasi terisolasi yang mendukung pembacaan dengan kunci lama dan penulisan dengan kunci baru. Untuk `APP_KEY`, alat itu harus mengenkripsi ulang seluruh private key ML-KEM yang dibungkus serta seluruh ciphertext AES baseline. Untuk rotasi ML-KEM, alat itu harus mendekripsi dan mengenkripsi ulang seluruh payload hybrid dengan pasangan kunci baru.
3. Verifikasi setiap record hasil migrasi dapat didekripsi dan autentikasi tetap valid. Jangan menghapus atau menimpa kunci lama sebelum verifikasi dan backup hasil migrasi berhasil.
4. Aktifkan kunci baru, pantau error dekripsi, lalu simpan backup lama secara terisolasi hanya selama masa rollback yang ditetapkan. Hapus kunci lama dari konfigurasi/penyimpanan setelah rollback tidak lagi diperlukan dan seluruh pemulihan dengan kunci baru telah diuji.

## Pengujian

```sh
php artisan test
```

Benchmark mencatat hasil runtime lokal; hasilnya bukan klaim performa umum. Gunakan data sintetis untuk demo dan pengujian. Prototipe ini belum menyediakan otorisasi berbasis peran, deployment hardening, backup otomatis terenkripsi, maupun integrasi dengan data produksi.
