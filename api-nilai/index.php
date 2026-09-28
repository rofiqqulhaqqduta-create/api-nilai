<?php
const FILE_DATA = __DIR__ . '/data/nilai.json';

// Kirim response JSON lalu hentikan program
function kirim(int $status, array $body): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Baca isi file JSON menjadi array PHP
function bacaData(): array
{
    if (!file_exists(FILE_DATA)) {
        return [];
    }
    $isi = file_get_contents(FILE_DATA);
    return json_decode($isi, true) ?? [];
}

// Tulis array PHP ke file JSON (LOCK_EX mencegah tabrakan tulis)
function simpanData(array $data): void
{
    file_put_contents(
        FILE_DATA,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

// Aturan bisnis: 60 ke atas Lulus, di bawah 60 Tidak Lulus
function tentukanKeterangan(float $nilai): string
{
    return $nilai >= 60 ? 'Lulus' : 'Tidak Lulus';
}

// Latihan 4: grade huruf
function tentukanGrade(float $nilai): string
{
    if ($nilai >= 85) return 'A';
    if ($nilai >= 70) return 'B';
    if ($nilai >= 60) return 'C';
    if ($nilai >= 50) return 'D';
    return 'E';
}

// ---------- Routing ----------
$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Dukungan subfolder (XAMPP: htdocs/api-nilai): buang prefix folder dari path
$root = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$dir  = str_replace('\\', '/', __DIR__);
if ($root !== '' && stripos($dir, $root) === 0) {
    $base = substr($dir, strlen($root));
    if ($base !== '' && stripos($path, $base) === 0) {
        $path = substr($path, strlen($base));
    }
}

// Latihan 5: tampilkan halaman klien (hanya untuk php -S)
if ($method === 'GET' && ($path === '' || $path === '/client.html')) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/client.html');
    exit;
}

// Latihan 3: /api/nilai atau /api/nilai/{id}
if (!preg_match('#^/api/nilai(?:/(\d+))?$#', $path, $m)) {
    kirim(404, ['status' => 'error', 'pesan' => 'Endpoint tidak ditemukan']);
}
$id = isset($m[1]) ? (int) $m[1] : null;

// ---------- GET ----------
if ($method === 'GET') {
    $data = bacaData();

    // GET /api/nilai/{id}
    if ($id !== null) {
        foreach ($data as $baris) {
            if (($baris['id'] ?? null) === $id) {
                kirim(200, ['status' => 'success', 'data' => $baris]);
            }
        }
        kirim(404, ['status' => 'error', 'pesan' => 'Data dengan id ' . $id . ' tidak ditemukan']);
    }

    // Latihan 2: filter ?keterangan=Lulus
    if (isset($_GET['keterangan'])) {
        $pilihan = ['lulus' => 'Lulus', 'tidak lulus' => 'Tidak Lulus'];
        $f = is_string($_GET['keterangan']) ? strtolower(trim($_GET['keterangan'])) : '';

        if (!isset($pilihan[$f])) {
            kirim(400, ['status' => 'error', 'pesan' => 'keterangan harus Lulus atau Tidak Lulus']);
        }

        $target = $pilihan[$f];
        $data = array_values(array_filter($data, function ($baris) use ($target) {
            return ($baris['keterangan'] ?? '') === $target;
        }));
    }

    kirim(200, [
        'status' => 'success',
        'total'  => count($data),
        'data'   => $data,
    ]);
}

// ---------- POST /api/nilai ----------
if ($method === 'POST' && $id === null) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        kirim(400, ['status' => 'error', 'pesan' => 'Body harus berupa JSON yang valid']);
    }

    // is_string mencegah error jika klien mengirim array/angka pada field teks
    $nim   = is_string($input['nim'] ?? null) ? trim($input['nim']) : '';
    $nama  = is_string($input['nama'] ?? null) ? trim($input['nama']) : '';
    $mk    = is_string($input['mata_kuliah'] ?? null) ? trim($input['mata_kuliah']) : '';
    $nilai = $input['nilai'] ?? null;

    $error = [];
    // Latihan 1: nim wajib, 8 digit angka (dikirim sebagai string)
    if (!preg_match('/^\d{8}$/', $nim)) $error[] = 'nim wajib diisi, 8 digit angka (kirim sebagai string)';
    if ($nama === '') $error[] = 'nama wajib diisi';
    if ($mk === '')   $error[] = 'mata_kuliah wajib diisi';
    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $error[] = 'nilai harus angka 0 sampai 100';
    }

    if ($error) {
        kirim(400, ['status' => 'error', 'pesan' => 'Data tidak valid', 'detail' => $error]);
    }

    $data = bacaData();

    // id baru = id terbesar + 1
    $idBaru = $data ? max(array_column($data, 'id')) + 1 : 1;

    $baru = [
        'id'          => $idBaru,
        'nim'         => $nim,
        'nama'        => $nama,
        'mata_kuliah' => $mk,
        'nilai'       => $nilai + 0,
        'grade'       => tentukanGrade((float) $nilai),
        'keterangan'  => tentukanKeterangan((float) $nilai),
    ];

    $data[] = $baru;
    simpanData($data);

    kirim(201, ['status' => 'success', 'pesan' => 'Data berhasil disimpan', 'data' => $baru]);
}

// ---------- Method lain ----------
header('Allow: GET, POST');
kirim(405, ['status' => 'error', 'pesan' => 'Method tidak diizinkan']);
