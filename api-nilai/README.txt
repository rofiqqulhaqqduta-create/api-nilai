API Nilai - PHP Native (tanpa framework, tanpa database)

CARA MENJALANKAN
1. Buka terminal DI DALAM folder api-nilai ini.
2. Jalankan:  php -S localhost:8000 index.php
3. Buka http://localhost:8000/ untuk halaman tabel + form.
4. Endpoint API: http://localhost:8000/api/nilai

ENDPOINT
GET  /api/nilai                       semua data
GET  /api/nilai?keterangan=Lulus      filter (Lulus / Tidak Lulus)
GET  /api/nilai/{id}                  satu data
POST /api/nilai                       tambah data (body JSON)

CONTOH POST (Linux/macOS/Git Bash)
curl -X POST http://localhost:8000/api/nilai -H "Content-Type: application/json" -d '{"nim":"12345678","nama":"Budi","mata_kuliah":"Pemrograman Web","nilai":85}'

CATATAN
- nim dikirim sebagai string 8 digit.
- Windows CMD: pakai tanda petik ganda + escape, PowerShell: pakai curl.exe, atau pakai Postman.
- Jika memakai Apache/XAMPP, folder ini harus menjadi document root agar path /api/nilai cocok.
- Folder data/ dan nilai.json harus bisa ditulis PHP.

XAMPP (Apache)
1. Ekstrak folder api-nilai ke C:\xampp\htdocs\
2. Start Apache di XAMPP Control Panel.
3. Buka http://localhost/api-nilai/  (pakai garis miring di akhir)
4. API: http://localhost/api-nilai/api/nilai
