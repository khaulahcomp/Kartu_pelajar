=====================================================
 APLIKASI KARTU PELAJAR - PANDUAN INSTALASI DI CPANEL
=====================================================

ISI PAKET
---------
- database.sql          -> struktur database (import via phpMyAdmin)
- config.php             -> pengaturan koneksi database (WAJIB DIEDIT)
- install.php            -> membuat akun admin pertama (jalankan 1x lalu hapus)
- login.php, index.php, students.php, student_form.php, settings.php, dll
- includes/               -> file pendukung (jangan diakses langsung)
- assets/                 -> CSS
- uploads/photos/         -> tempat foto pelajar tersimpan
- uploads/logo/            -> tempat logo sekolah tersimpan

LANGKAH INSTALASI
------------------
1. LOGIN CPANEL
   Masuk ke cPanel hosting Anda.

2. BUAT DATABASE MYSQL
   - Buka menu "MySQL Databases".
   - Buat database baru, contoh: cpaneluser_kartu
   - Buat user database baru beserta passwordnya.
   - Tambahkan user tersebut ke database dengan hak akses "ALL PRIVILEGES".
   - Catat: nama database, username, dan password.

3. IMPORT STRUKTUR DATABASE
   - Buka phpMyAdmin dari cPanel.
   - Pilih database yang baru dibuat.
   - Klik tab "Import", pilih file database.sql dari paket ini, lalu jalankan.
   - Pastikan tabel admin_users, settings, dan students berhasil dibuat.

4. UPLOAD FILE APLIKASI
   - Buka menu "File Manager" di cPanel (atau gunakan FTP).
   - Masuk ke folder public_html (atau subfolder/subdomain yang diinginkan,
     contoh: public_html/kartu-pelajar).
   - Upload seluruh isi paket ini (bisa upload file zip lalu "Extract").
   - Pastikan struktur folder (includes/, assets/, uploads/) ikut terupload.

5. EDIT config.php
   Buka file config.php dan sesuaikan dengan data database Anda:

     define('DB_HOST', 'localhost');
     define('DB_NAME', 'cpaneluser_kartu');
     define('DB_USER', 'cpaneluser_dbuser');
     define('DB_PASS', 'password_database_anda');

6. ATUR PERMISSION FOLDER UPLOAD
   Pastikan folder berikut memiliki izin tulis (biasanya 755, jika error coba 775):
     - uploads/photos/
     - uploads/logo/

7. JALANKAN INSTALL.PHP
   Buka di browser: https://namadomainanda.com/install.php
   Isi form untuk membuat akun admin pertama (nama, username, password).

8. HAPUS install.php
   Setelah akun admin berhasil dibuat, HAPUS file install.php dari server
   (lewat File Manager) untuk mencegah orang lain membuat akun admin baru.

9. LOGIN
   Buka https://namadomainanda.com/login.php dan login menggunakan akun
   yang baru dibuat.

10. ATUR IDENTITAS SEKOLAH
    Masuk ke menu "Pengaturan" untuk mengisi nama sekolah, alamat, logo,
    serta warna tema aplikasi dan kartu pelajar. Warna dan logo yang
    diatur di sini akan otomatis diterapkan ke seluruh aplikasi dan
    ke desain kartu pelajar.

11. TAMBAH DATA PELAJAR
    Masuk ke menu "Data Pelajar" -> "Tambah Pelajar" untuk mulai
    menginput data dan foto pelajar. Kartu pelajar bisa dicetak per
    orang (tombol "Kartu") atau banyak sekaligus (centang beberapa
    baris di halaman Data Pelajar lalu klik "Cetak Kartu Terpilih").

FITUR IMPORT & EXPORT EXCEL
----------------------------
Menu ini ada di halaman "Data Pelajar":

- IMPORT EXCEL: upload file .xlsx (atau .csv) berisi data banyak pelajar
  sekaligus. Unduh dulu "Template Excel" dari halaman import, isi datanya,
  lalu upload kembali.
    * Kolom NISN atau NIS wajib diisi salah satu, dipakai sebagai kunci
      pencocokan data: jika NISN/NIS pada baris file sudah ada di database,
      data pelajar tersebut akan DIPERBARUI (bukan tercatat dobel). Jika
      belum ada, akan dibuat sebagai data baru.
    * Format Tanggal Lahir: YYYY-MM-DD (contoh 2010-05-17). Jika Anda
      mengetik tanggal dengan format Excel biasa (diformat sebagai sel
      Date), aplikasi akan otomatis mengonversinya saat import.
    * Foto pelajar TIDAK bisa diimpor lewat Excel (keterbatasan format
      spreadsheet) - tambahkan foto satu per satu lewat menu edit data.
    * Setelah proses selesai, akan muncul ringkasan: jumlah data baru
      ditambahkan, jumlah data diperbarui, dan baris yang dilewati
      (misalnya karena NISN/NIS kosong) beserta alasannya.

- EXPORT EXCEL: mengunduh seluruh data pelajar (atau hasil pencarian jika
  sedang memakai kotak pencarian) sebagai file .xlsx yang siap dibuka di
  Microsoft Excel / Google Sheets. Berguna untuk backup data atau diedit
  massal lalu diimpor ulang.

  Catatan: fitur ini murni ditulis dengan PHP native (ZipArchive +
  SimpleXML bawaan PHP), tanpa perlu composer/library tambahan, sehingga
  langsung jalan di shared hosting. Jika ekstensi ZipArchive/SimpleXML
  server Anda nonaktif, aplikasi otomatis beralih memakai format .csv
  (tetap bisa dibuka normal di Excel).

CARA UPDATE/REPLACE KE VERSI TERBARU (APLIKASI SUDAH TERPASANG DI CPANEL)
----------------------------------------------------------------------
UPDATE KALI INI (fitur pilih warna area kartu) MENAMBAH 2 KOLOM BARU
di tabel settings, jadi ADA LANGKAH TAMBAHAN: import migration_v3_warna_kartu.sql
(lihat langkah 2 di bawah). Update sebelumnya (import/export Excel, PDF
kolektif) tidak mengubah struktur database sama sekali.

Langkah aman melakukan update (data pelajar, foto, logo, dan pengaturan
tetap aman):

1. BACKUP TERLEBIH DAHULU (WAJIB)
   - Buka phpMyAdmin di cPanel -> pilih database aplikasi -> tab "Export"
     -> Quick export -> Go. Simpan file .sql hasil backup di komputer.
   - Buka File Manager cPanel -> masuk ke folder aplikasi -> pilih
     file config.php dan folder uploads/ -> klik kanan -> Compress
     (misal jadi backup-manual.zip) -> Download ke komputer Anda.

2. JALANKAN MIGRASI DATABASE (KHUSUS UPDATE FITUR WARNA KARTU INI)
   - Buka phpMyAdmin -> pilih database aplikasi -> tab "Import".
   - Pilih file migration_v3_warna_kartu.sql dari paket ini -> Go.
   - Jika muncul pesan "Duplicate column name", berarti migrasi ini
     sudah pernah dijalankan sebelumnya - abaikan saja, lanjut ke
     langkah berikutnya.
   (Untuk instalasi yang benar-benar baru, LEWATI langkah ini - kolom
   sudah otomatis ada karena sudah termasuk dalam database.sql.)

3. UPLOAD FILE VERSI TERBARU
   - Upload file zip aplikasi terbaru (dari paket ini) ke File Manager,
     lalu klik kanan -> Extract.
   - Saat proses extract, jika muncul konfirmasi "Overwrite existing
     files?", pilih YA/Overwrite (file lama akan ditimpa file baru).
   - File yang PALING PENTING untuk update kali ini: settings.php,
     includes/card_template.php, includes/pdf_card_writer.php,
     includes/functions.php, assets/css/style.css, dan
     migration_v3_warna_kartu.sql (baru). File lain aman ditimpa juga.

4. PASTIKAN FILE PENTING TIDAK IKUT TERTIMPA
   Setelah extract, PERIKSA dan KEMBALIKAN dari backup jika perlu:
   - config.php  -> harus tetap berisi data koneksi database Anda yang
     LAMA (jangan sampai tertimpa config.php contoh/kosong dari paket
     baru). Jika tertimpa, salin ulang isi DB_HOST/DB_NAME/DB_USER/DB_PASS
     dari backup.
   - uploads/photos/ dan uploads/logo/ -> pastikan foto-foto pelajar dan
     logo sekolah yang sudah ada sebelumnya masih ada (folder upload
     pada paket baru sengaja dikosongkan, jadi TIDAK akan menghapus foto
     lama selama Anda extract ke folder yang sama/menimpa, bukan
     menghapus folder uploads lama terlebih dahulu).

5. HAPUS install.php (JIKA ADA)
   Jika file install.php ikut ter-upload ulang, hapus lagi setelah
   dipastikan tidak dibutuhkan (karena akun admin sudah ada).

6. TES APLIKASI
   Buka kembali aplikasi di browser, login, lalu cek:
   - Data pelajar & foto masih ada (menu Data Pelajar)
   - Logo & warna tema sekolah masih sesuai (menu Pengaturan)
   - Menu "Import Excel", "Export Excel", dan tombol PDF Kolektif sudah
     muncul di halaman Data Pelajar.
   - Buka menu Pengaturan -> coba pilih salah satu preset warna area
     kartu -> Simpan -> buka kartu pelajar untuk melihat hasilnya.

TIPS: cara paling aman adalah upload file baru ke folder SEMENTARA dulu
(misal /public_html/kartu-pelajar-baru), lalu salin manual file config.php
dan folder uploads/ dari instalasi lama ke folder baru tersebut, testing
di URL folder baru, baru setelah yakin berjalan normal, pindahkan/ganti
folder lama dengan folder baru ini.

CETAK KARTU: BROWSER vs PDF KOLEKTIF
--------------------------------------
Ada 2 cara mencetak kartu pelajar:

1. CETAK VIA BROWSER (card.php / card_print_batch.php)
   Memakai tampilan halaman yang dioptimalkan untuk dicetak langsung dari
   browser (Ctrl+P). Cocok untuk cetak cepat dari komputer yang sedang
   dipakai. Saat dialog cetak muncul, pilih margin "None"/"Minimum" dan
   matikan "Fit to page" agar ukuran kartu presisi 85.6mm x 54mm.

2. PDF KOLEKTIF (card_print_pdf.php) - BARU
   Menghasilkan file PDF sungguhan (bukan sekadar tampilan cetak browser),
   otomatis menyusun banyak kartu (ukuran standar KTP/CR-80: 85.6x54mm)
   rapi di atas kertas A4 (2 kolom x beberapa baris per halaman), siap
   dicetak di percetakan atau printer kartu ID.
   - Tombol "PDF Kartu Terpilih": centang pelajar yang diinginkan di
     halaman Data Pelajar, lalu klik tombol ini.
   - Tombol "PDF Kartu Kolektif (Semua)": mengunduh PDF berisi SELURUH
     data pelajar (atau hasil pencarian jika sedang memakai kotak cari)
     sekaligus, tanpa perlu centang satu per satu.
   - Di halaman kartu perorangan juga tersedia tombol "Unduh PDF" untuk
     PDF satu kartu saja.

   Fitur ini memakai library FPDF (murni PHP, sudah disertakan di dalam
   folder includes/fpdf/ beserta file metrik font di includes/fpdf/font/)
   - TIDAK butuh composer maupun ekstensi tambahan di luar PHP standar,
     jadi langsung berfungsi di hampir semua shared hosting.

DESAIN KARTU PELAJAR
----------------------
- Ukuran kartu: 85.6mm x 54mm (ukuran standar KTP/kartu ATM, CR-80).
- Identitas yang tampil di kartu: Nama Lengkap, NISN/NIS, Tempat/Tanggal
  Lahir, dan Alamat saja (field lain seperti Kelas tetap tersimpan di
  database dan tampil di halaman Data Pelajar, hanya tidak dicetak di
  kartu agar kartu tetap ringkas).
- Warna latar kartu otomatis dibuat gradasi lembut (smooth) mengikuti
  warna Primary & Secondary yang diatur di menu Pengaturan - tidak perlu
  mengatur warna kartu secara terpisah.
- Logo sekolah ditampilkan apa adanya (transparan, tanpa bingkai bulat/
  latar putih) agar cocok dengan logo bentuk apa pun.

WARNA AREA KARTU (TENGAH) - BISA DIKUSTOMISASI
-------------------------------------------------
Di menu Pengaturan, bagian "Warna Area Kartu (Tengah)":
- Pilih "Otomatis" agar warna area tengah kartu mengikuti Warna Utama
  (Primary) yang sudah diatur, ATAU
- Pilih "Warna Kustom" untuk memakai warna berbeda khusus untuk area
  tengah kartu (misalnya sekolah ingin logo/header biru tapi area kartu
  memakai warna hijau/marun/ungu yang lebih elegan).
- Tersedia 8 preset warna elegan yang tinggal diklik (Biru Elegan, Hijau
  Zamrud, Ungu Lembut, Merah Marun, Abu Elegan, Emas Krem, Teal Modern,
  Cokelat Hangat), atau gunakan color-picker untuk warna bebas.
- Atur "Intensitas Warna" (Putih Solid / Sangat Lembut / Lembut / Sedang
  / Lebih Pekat) untuk mengatur seberapa kuat warnanya tampil - warna
  SELALU ditampilkan sebagai gradasi transparan/lembut, bukan warna
  solid penuh, supaya teks identitas pelajar tetap mudah dibaca.
- Pratinjau kartu di halaman Pengaturan langsung berubah saat Anda
  memilih warna/intensitas (tanpa perlu simpan dulu).

PENTING - MIGRASI DATABASE UNTUK FITUR WARNA KARTU:
Jika aplikasi Anda SUDAH terpasang sebelumnya (bukan instalasi baru),
fitur ini butuh 2 kolom baru di tabel settings. Import file
migration_v3_warna_kartu.sql lewat phpMyAdmin SEBELUM memakai fitur ini
(lihat bagian "CARA UPDATE/REPLACE KE VERSI TERBARU" di bawah). Jika
lupa mengimport dan mencoba menyimpan pengaturan warna, aplikasi akan
menampilkan pesan error yang mengingatkan untuk import file ini (bukan
error 500/blank), jadi aman dicoba.

CATATAN CETAK
--------------
Gunakan printer kartu ID atau kertas stiker/PVC sesuai kebutuhan sekolah.

KEAMANAN
--------
- Segera hapus install.php setelah akun admin dibuat.
- Jangan bagikan isi config.php ke pihak lain.
- Disarankan mengaktifkan SSL/HTTPS (biasanya gratis via AutoSSL di cPanel).

TROUBLESHOOTING
----------------
- "Koneksi database gagal": periksa kembali DB_HOST/DB_NAME/DB_USER/DB_PASS
  di config.php.
- Foto/logo tidak muncul: periksa permission folder uploads/photos dan
  uploads/logo (harus bisa ditulis oleh web server).
- Halaman blank/error 500: aplikasi ini ditulis agar kompatibel mulai
  PHP 7.2 ke atas, jadi seharusnya jalan di hampir semua paket hosting.
  Jika tetap muncul error 500:
    1. Cek versi PHP aktif di cPanel -> menu "MultiPHP Manager", pastikan
       domain/subdomain aplikasi memakai PHP 7.4 atau lebih baru (bukan
       PHP 5.x yang sudah sangat usang).
    2. Lihat pesan error sesungguhnya lewat cPanel -> menu "Errors" atau
       "Metrics > Errors" (biasanya menampilkan log error PHP terbaru).
    3. Atau aktifkan sementara tampilan error dengan menambahkan baris
       berikut di baris paling atas config.php (HAPUS lagi setelah
       selesai debug, jangan dibiarkan aktif di server produksi):
         ini_set('display_errors', 1);
         error_reporting(E_ALL);
