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

## Pengelolaan kunci

Key pair ML-KEM-768 dibuat saat enkripsi pertama dan disimpan di `storage/app/private/keys/ml-kem-768.json`. Private key dibungkus menggunakan enkripsi Laravel dan `APP_KEY`; file kunci lokal tidak masuk Git. Simpan cadangan file tersebut dan `APP_KEY` secara terpisah. Perubahan `APP_KEY` tanpa pemulihan key lama membuat private key yang tersimpan tidak dapat dibuka kembali.

Kunci AES baseline diturunkan dari `APP_KEY` melalui HKDF. Nonce GCM dan salt HKDF dibuat acak untuk setiap proses enkripsi.

## Pengujian

```sh
php artisan test
```

Benchmark mencatat hasil runtime lokal; hasilnya bukan klaim performa umum. Gunakan data sintetis untuk demo dan pengujian. Prototipe ini belum menyediakan otorisasi berbasis peran, audit log, deployment hardening, backup terenkripsi, maupun integrasi dengan data produksi.
