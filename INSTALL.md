# Panduan Instalasi WAOCA

## 1. Server
Gunakan Apache/Nginx + PHP + MariaDB/MySQL. GitHub Pages tidak menjalankan PHP.

Kebutuhan umum: PHP, mysqli, curl, json, mbstring, dan Composer jika source memerlukannya.

## 2. Database
Buat database aplikasi, misalnya `simifm`, lalu jalankan `database/install.sql`.

Installer membuat tabel dasar `wa_template`, `wa_outbox`, dan `simifm_user`.

## 3. Konfigurasi
Isi `config.php` hanya di server production. Jangan commit password database ke GitHub.

## 4. OCA
Gunakan environment variable `OCA_WHATSAPP_TOKEN` untuk token OCA. Jangan menaruh token di source.

## 5. Template
Masukkan template WhatsApp yang sudah approved ke `wa_template`. Pastikan template_code, language, dan urutan variable sesuai OCA.

## 6. wa_outbox
Alur: SIMRS -> middleware WAOCA -> wa_outbox -> worker/cron -> OCA -> WhatsApp.

## 7. Testing
Tes koneksi database, lalu tes satu pesan menggunakan template approved sebelum melakukan pengiriman massal.

## 8. Cron
Contoh jika source memiliki worker:

`*/1 * * * * /usr/bin/php /path/WAOCA/worker.php >> /var/log/waoca.log 2>&1`

Sesuaikan nama worker dan path dengan source.

## 9. Backup
`mysqldump simifm > simifm-backup.sql`

## 10. Checklist
- database dibuat
- install.sql dijalankan
- config server diisi
- token OCA di environment variable
- template approved dimasukkan
- satu pesan test berhasil
- worker/cron aktif
- backup tersedia