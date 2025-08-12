<?php
session_start();
// ====================== KONEKSI DATABASE ======================
$host = "localhost";
$user = "root";
$pass = "";
$database = "perpustakaan_smk";

$conn = mysqli_connect($host, $user, $pass) or die("Koneksi gagal: " . mysqli_connect_error());
if (!mysqli_select_db($conn, $database)) {
    $create_db = "CREATE DATABASE $database";
    if (!mysqli_query($conn, $create_db)) {
        die("Gagal membuat database: " . mysqli_error($conn));
    }
    mysqli_select_db($conn, $database);
}

// Buat tabel jika belum ada
$create_tables = [
    "CREATE TABLE IF NOT EXISTS buku (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        stok INT NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS peminjaman (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_anggota INT NOT NULL,
        nama_peminjam VARCHAR(255) NOT NULL,
        id_buku INT NOT NULL,
        jumlah INT NOT NULL,
        tanggal_pinjam DATE NOT NULL,
        tanggal_kembali DATE NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS anggota (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nomor_anggota VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nama VARCHAR(255) NOT NULL,
        no_identitas VARCHAR(50) NOT NULL,
        tempat_lahir VARCHAR(100) NOT NULL,
        tanggal_lahir DATE NOT NULL,
        jenis_kelamin ENUM('Laki-laki','Perempuan') NOT NULL,
        status_perkawinan VARCHAR(50) NOT NULL,
        alamat_identitas TEXT NOT NULL,
        provinsi VARCHAR(100) NOT NULL,
        kota VARCHAR(100) NOT NULL,
        no_hp VARCHAR(20) NOT NULL,
        email VARCHAR(100) NOT NULL,
        pendidikan_terakhir VARCHAR(50) NOT NULL,
        pekerjaan VARCHAR(100) NOT NULL,
        nama_institusi VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($create_tables as $query) {
    if (!mysqli_query($conn, $query)) {
        die("Gagal membuat tabel: " . mysqli_error($conn));
    }
}

// ====================== PERBAIKI STRUKTUR TABEL ======================
// Daftar tabel dan kolom yang perlu diperiksa/ditambahkan
$tables_to_alter = [
    'anggota' => [
        'status' => "ALTER TABLE anggota ADD COLUMN status ENUM('Aktif','Nonaktif','Diblokir') DEFAULT 'Aktif'"
    ],
    'buku' => [
        'cover' => "ALTER TABLE buku ADD COLUMN cover VARCHAR(255) DEFAULT ''",
        'file_pdf' => "ALTER TABLE buku ADD COLUMN file_pdf VARCHAR(255) DEFAULT ''",
        'rating' => "ALTER TABLE buku ADD COLUMN rating FLOAT DEFAULT 0"
    ],
    'peminjaman' => [
        'status' => "ALTER TABLE peminjaman ADD COLUMN status ENUM('Aktif','Dikembalikan','Terlambat') DEFAULT 'Aktif'",
        'tanggal_dikembalikan' => "ALTER TABLE peminjaman ADD COLUMN tanggal_dikembalikan DATE DEFAULT NULL"
    ]
];

// Periksa dan tambahkan kolom yang belum ada
foreach ($tables_to_alter as $table => $columns) {
    foreach ($columns as $column => $sql) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
        if (!$check || mysqli_num_rows($check) == 0) {
            if (!mysqli_query($conn, $sql)) {
                die("Gagal menambahkan kolom $column: " . mysqli_error($conn));
            }
        }
    }
}

// Buat tabel tambahan jika belum ada
$additional_tables = [
    "CREATE TABLE IF NOT EXISTS rating (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_anggota INT NOT NULL,
        id_buku INT NOT NULL,
        rating INT NOT NULL,
        tanggal_rating TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS log_aktivitas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        aktivitas VARCHAR(255) NOT NULL,
        waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($additional_tables as $query) {
    if (!mysqli_query($conn, $query)) {
        die("Gagal membuat tabel: " . mysqli_error($conn));
    }
}

// Tambahkan buku contoh jika belum ada
$check_books = mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku");
if ($check_books && mysqli_fetch_assoc($check_books)['total'] == 0) {
    mysqli_query($conn, "INSERT INTO buku (judul, stok, cover, file_pdf) VALUES 
        ('Pemrograman PHP', 10, 'cover_php.jpg', 'php_buku.pdf'),
        ('Database MySQL', 8, 'cover_mysql.jpg', 'mysql_buku.pdf'),
        ('Web Design', 15, 'cover_webdesign.jpg', 'webdesign_buku.pdf')");
}

// ====================== PROSES LOGIN ANGGOTA ======================
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $nomor_anggota = mysqli_real_escape_string($conn, $_POST['nomor_anggota']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM anggota WHERE nomor_anggota = '$nomor_anggota'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $anggota = mysqli_fetch_assoc($result);
        
        // Verifikasi password (support hash dan plaintext)
        if (password_verify($password, $anggota['password']) || $password === $anggota['password']) {
            $_SESSION['anggota'] = $anggota;
            
            // Catat log aktivitas
            mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) 
                                VALUES ({$anggota['id']}, 'Login anggota')");
            
            header("Location: ".$_SERVER['PHP_SELF']); // Redirect untuk refresh
            exit;
        } else {
            $login_error = "Password salah!";
        }
    } else {
        $login_error = "Nomor anggota tidak ditemukan!";
    }
}

// ====================== PROSES DAFTAR ANGGOTA ======================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['daftar'])) {
    // Ambil data dari form
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $nomor_anggota = mysqli_real_escape_string($conn, $_POST['nomor_anggota']);
    $no_identitas = mysqli_real_escape_string($conn, $_POST['no_identitas']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $status_perkawinan = $_POST['status_perkawinan'];
    $alamat_identitas = mysqli_real_escape_string($conn, $_POST['alamat_identitas']);
    $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi']);
    $kota = mysqli_real_escape_string($conn, $_POST['kota']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pendidikan_terakhir = mysqli_real_escape_string($conn, $_POST['pendidikan_terakhir']);
    $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $nama_institusi = mysqli_real_escape_string($conn, $_POST['nama_institusi']);

    // Query pendaftaran
    $sql = "INSERT INTO anggota (
        nama, nomor_anggota, no_identitas, password, tempat_lahir, tanggal_lahir, 
        jenis_kelamin, status, status_perkawinan, alamat_identitas, provinsi, kota, 
        no_hp, email, pendidikan_terakhir, pekerjaan, nama_institusi
    ) VALUES (
        '$nama', '$nomor_anggota', '$no_identitas', '$password', '$tempat_lahir', 
        '$tanggal_lahir', '$jenis_kelamin', 'Aktif', '$status_perkawinan', '$alamat_identitas', 
        '$provinsi', '$kota', '$no_hp', '$email', '$pendidikan_terakhir', 
        '$pekerjaan', '$nama_institusi'
    )";
    
    if (mysqli_query($conn, $sql)) {
        // Auto login setelah pendaftaran
        $result = mysqli_query($conn, "SELECT * FROM anggota WHERE nomor_anggota='$nomor_anggota'");
        $_SESSION['anggota'] = mysqli_fetch_assoc($result);
        
        // Catat log aktivitas
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) 
                            VALUES ({$_SESSION['anggota']['id']}, 'Pendaftaran anggota baru')");
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Pendaftaran gagal: ".mysqli_error($conn);
    }
}

// ====================== PROSES LOGOUT ======================
if (isset($_GET['logout'])) {
    // Catat log aktivitas sebelum logout
    if (isset($_SESSION['anggota'])) {
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) 
                            VALUES ({$_SESSION['anggota']['id']}, 'Logout anggota')");
    }
    
    unset($_SESSION['anggota']);
    session_destroy();
    header("Location: peminjaman.php");
    exit;
}

// ====================== PROSES FORM PEMINJAMAN ======================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pinjam'])) {
    if (!isset($_SESSION['anggota'])) {
        $error = "Anda harus login terlebih dahulu!";
    } else {
        $id_anggota = $_SESSION['anggota']['id'];
        $nama = $_SESSION['anggota']['nama'];
        $buku_id = (int)$_POST['id_buku'];
        $jumlah = (int)$_POST['jumlah'];
        $kembali = $_POST['tanggal_kembali'];
        
        // Validasi status anggota
        $status_anggota = $_SESSION['anggota']['status'] ?? 'Aktif'; // Default jika tidak ada
        if ($status_anggota == 'Diblokir') {
            $error = "Akun Anda diblokir. Silakan hubungi admin untuk aktivasi.";
        } 
        // Ambil status anggota dari tabel anggota
$query_status = mysqli_query($conn, "SELECT status FROM anggota WHERE id = $id_anggota");
$data_status = mysqli_fetch_assoc($query_status);
$status_anggota = strtolower($data_status['status']); // pastikan lowercase

// Cek status anggota
if ($status_anggota == 'diblokir') {
    $error = "Akun diblokir: Anda tidak diizinkan untuk meminjam buku.";
} elseif ($status_anggota == 'nonaktif') {
    // Hitung peminjaman aktif
    $pinjam_aktif = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman 
                                          WHERE id_anggota = $id_anggota AND status = 'Aktif'");
    $total_aktif = mysqli_fetch_assoc($pinjam_aktif)['total'] ?? 0;

    if ($total_aktif >= 2) {
        $error = "Akun nonaktif: Maksimal 2 buku aktif yang bisa dipinjam.";
    }
}

// Jika tidak ada error, proses peminjaman
if (empty($error)) {
    // Lanjut proses simpan data peminjaman...
}
        
        // Lanjut jika tidak ada error
        if (!isset($error)) {
            // Validasi stok
            $stok_query = mysqli_query($conn, "SELECT stok, judul FROM buku WHERE id = $buku_id");
            if ($stok_query && mysqli_num_rows($stok_query) > 0) {
                $buku_data = mysqli_fetch_assoc($stok_query);
                $stok = $buku_data['stok'];
                $judul_buku = $buku_data['judul'];
                
                if ($jumlah > $stok) {
                    $error = "Stok buku '$judul_buku' tidak cukup! Stok tersedia: $stok";
                } else {
                    // Update stok buku
                    mysqli_query($conn, "UPDATE buku SET stok = stok - $jumlah WHERE id = $buku_id");
                    
                    // Simpan peminjaman
                    $sql = "INSERT INTO peminjaman (id_anggota, nama_peminjam, id_buku, jumlah, tanggal_pinjam, tanggal_kembali, status)
                            VALUES ($id_anggota, '$nama', $buku_id, $jumlah, CURDATE(), '$kembali', 'Aktif')";
                    
                    if (mysqli_query($conn, $sql)) {
                        $success = "Peminjaman buku '$judul_buku' oleh $nama berhasil disimpan!";
                        
                        // Catat log aktivitas
                        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) 
                                            VALUES ($id_anggota, 'Meminjam buku: $judul_buku')");
                    } else {
                        $error = "Error: " . mysqli_error($conn);
                    }
                }
            } else {
                $error = "Buku tidak ditemukan!";
            }
        }
    }
}

// ====================== PROSES PENGEMBALIAN BUKU ======================
if (isset($_GET['kembali'])) {
    $id = (int)$_GET['kembali'];
    
    // Dapatkan data peminjaman untuk mengembalikan stok
    $peminjaman_query = mysqli_query($conn, "SELECT peminjaman.*, buku.judul 
                                            FROM peminjaman
                                            JOIN buku ON peminjaman.id_buku = buku.id
                                            WHERE peminjaman.id = $id");
    if ($peminjaman_query && mysqli_num_rows($peminjaman_query) > 0) {
        $p_data = mysqli_fetch_assoc($peminjaman_query);
        $buku_id = $p_data['id_buku'];
        $jumlah = $p_data['jumlah'];
        $id_anggota = $p_data['id_anggota'];
        $judul_buku = $p_data['judul'];
        
        // Kembalikan stok buku
        mysqli_query($conn, "UPDATE buku SET stok = stok + $jumlah WHERE id = $buku_id");
        
        // Update status peminjaman
        mysqli_query($conn, "UPDATE peminjaman SET status = 'Dikembalikan', tanggal_dikembalikan = CURDATE() 
                            WHERE id = $id");
        
        $success = "Buku '$judul_buku' berhasil dikembalikan!";
        
        // Catat log aktivitas
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) 
                            VALUES ($id_anggota, 'Mengembalikan buku: $judul_buku')");
    } else {
        $error = "Data peminjaman tidak ditemukan!";
    }
}

// ====================== PROSES RATING BUKU ======================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['beri_rating'])) {
    $id_buku = (int)$_POST['id_buku'];
    $rating = (int)$_POST['rating'];
    $id_anggota = $_SESSION['anggota']['id'];
    
    // Simpan rating
    $sql = "INSERT INTO rating (id_anggota, id_buku, rating) VALUES ($id_anggota, $id_buku, $rating)";
    if (mysqli_query($conn, $sql)) {
        $success = "Terima kasih atas rating Anda!";
        
        // Update rating rata-rata buku
        mysqli_query($conn, "UPDATE buku SET rating = (
            SELECT AVG(rating) FROM rating WHERE id_buku = $id_buku
        ) WHERE id = $id_buku");
        
        // Catat log aktivitas
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) 
                            VALUES ($id_anggota, 'Memberi rating buku ID $id_buku')");
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// ====================== PROSES PERUBAHAN STATUS ANGGOTA ======================
if (isset($_GET['ubah_status'])) {
    $id_anggota = (int)$_GET['id_anggota'];
    $status_baru = $_GET['status_baru'];
    
    mysqli_query($conn, "UPDATE anggota SET status = '$status_baru' WHERE id = $id_anggota");
    
    // Catat log aktivitas
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) 
                        VALUES ({$_SESSION['anggota']['id']}, 'Mengubah status anggota ID $id_anggota menjadi $status_baru')");
    
    $success = "Status anggota berhasil diubah!";
}

// ====================== TAMPILAN HTML ======================
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Peminjaman Buku</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    :root {
      --primary: #0d6efd;
      --secondary: #6c757d;
      --success: #198754;
      --danger: #dc3545;
      --warning: #ffc107;
      --info: #0dcaf0;
      --light: #f8f9fa;
      --dark: #212529;
    }
    
    body {
      background-color: #f0f2f5;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding-top: 20px;
    }
    
    .header {
      background: linear-gradient(135deg, var(--primary), #0b5ed7);
      color: white;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    
    .card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      border: none;
      margin-bottom: 20px;
      transition: transform 0.3s;
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }
    
    .table-container {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    
    .btn-primary {
      background: var(--primary);
      border: none;
      padding: 10px 20px;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .btn-primary:hover {
      background: #0b5ed7;
      transform: translateY(-2px);
    }
    
    .modal-header {
      background: var(--primary);
      color: white;
    }
    
    .btn-close-white {
      filter: invert(1);
    }
    
    .alert {
      border-radius: 8px;
    }
    
    .table thead {
      background: var(--primary);
      color: white;
    }
    
    .table-hover tbody tr:hover {
      background-color: rgba(13, 110, 253, 0.05);
    }
    
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
    }
    
    .status-aktif {
      background-color: rgba(25, 135, 84, 0.15);
      color: var(--success);
    }
    
    .status-dikembalikan {
      background-color: rgba(13, 110, 253, 0.15);
      color: var(--primary);
    }
    
    .status-terlambat {
      background-color: rgba(220, 53, 69, 0.15);
      color: var(--danger);
    }
    
    .action-buttons .btn {
      padding: 5px 10px;
      font-size: 0.85rem;
      margin-right: 5px;
    }
    
    .login-container {
      max-width: 400px;
      margin: 50px auto;
      padding: 30px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 25px;
    }
    
    .login-logo {
      font-size: 3rem;
      color: var(--primary);
      margin-bottom: 15px;
    }
    
    .member-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 15px;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      color: white;
    }
    
    .user-icon {
      font-size: 1.2rem;
    }
    
    .form-section {
      border-left: 4px solid var(--primary);
      padding-left: 15px;
      margin-bottom: 25px;
    }
    
    .required:after {
      content: " *";
      color: red;
    }
    
    .book-cover {
      width: 60px;
      height: 80px;
      object-fit: cover;
      border-radius: 5px;
    }
    
    .badge-terlambat {
      background-color: var(--danger);
      color: white;
    }
    
    .badge-aktif {
      background-color: var(--success);
      color: white;
    }
    
    .badge-dikembalikan {
      background-color: var(--primary);
      color: white;
    }
    
    .nav-tabs .nav-link.active {
      font-weight: bold;
      border-bottom: 3px solid var(--primary);
    }
    
    .status-anggota {
      padding: 3px 8px;
      border-radius: 10px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    .status-anggota-aktif {
      background-color: rgba(25, 135, 84, 0.15);
      color: var(--success);
    }
    
    .status-anggota-nonaktif {
      background-color: rgba(108, 117, 125, 0.15);
      color: var(--secondary);
    }
    
    .status-anggota-diblokir {
      background-color: rgba(220, 53, 69, 0.15);
      color: var(--danger);
    }
  </style>
</head>
<body>
<div class="container">
  <!-- Header -->
  <div class="header p-4 mb-4">
    <div class="row align-items-center">
      <div class="col-md-6">
        <h1 class="display-5 fw-bold"><i class="bi bi-book me-2"></i>Sistem Peminjaman Buku</h1>
        <p class="lead mb-0">Perpustakaan SMK Keling Kumang</p>
      </div>
      <div class="col-md-6 text-end">
        <?php if (isset($_SESSION['anggota'])): ?>
          <div class="user-info">
            <i class="bi bi-person-circle user-icon"></i>
            <div>
              <div class="fw-bold"><?= htmlspecialchars($_SESSION['anggota']['nama']) ?></div>
              <div>
                <small><?= htmlspecialchars($_SESSION['anggota']['nomor_anggota']) ?></small>
                <?php if (isset($_SESSION['anggota']['status'])): ?>
                  <span class="status-anggota status-anggota-<?= strtolower($_SESSION['anggota']['status']) ?> ms-2">
                    <?= $_SESSION['anggota']['status'] ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <a href="?logout" class="btn btn-outline-light ms-3">
              <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
          </div>
        <?php else: ?>
          <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login Anggota
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Tampilan jika belum login -->
  <div class="container">
    <a href="index.php" class="btn btn-secondary mb-3">← Kembali ke Beranda</a>
  </div>
  
  <?php if (!isset($_SESSION['anggota'])): ?>
    <div class="login-container">
      <div class="login-header">
        <div class="login-logo">
          <i class="bi bi-person-circle"></i>
        </div>
        <h3>Login Anggota</h3>
        <p class="text-muted">Masukkan nomor anggota dan password untuk meminjam buku</p>
      </div>
      
      <?php if ($login_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
      <?php endif; ?>
      
      <form method="POST" action="">
        <div class="mb-3">
          <label for="nomor_anggota" class="form-label">Nomor Anggota</label>
          <input type="text" class="form-control" id="nomor_anggota" name="nomor_anggota" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="d-grid mb-3">
          <button type="submit" name="login" class="btn btn-primary">Masuk</button>
        </div>
        <div class="member-actions">
          <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#daftarModal">Daftar Anggota</button>
          <a href="#" class="btn btn-link">Lupa Password?</a>
        </div>
      </form>
    </div>
  <?php else: ?>
    <!-- Tampilan setelah login -->
    <!-- Alert Notifikasi -->
    <?php if(isset($success)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Statistik -->   
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card text-white bg-primary">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="bi bi-people display-4"></i>
              </div>
              <div>
                <?php
                $total_peminjam = mysqli_query($conn, "SELECT COUNT(DISTINCT nama_peminjam) AS total FROM peminjaman");
                $total = $total_peminjam ? mysqli_fetch_assoc($total_peminjam)['total'] : 0;
                ?>
                <h2 class="mb-0"><?= $total ?></h2>
                <p class="mb-0">Total Peminjam</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card text-white bg-success">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="bi bi-book display-4"></i>
              </div>
              <div>
                <?php
                $total_buku = mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku");
                $total_b = $total_buku ? mysqli_fetch_assoc($total_buku)['total'] : 0;
                ?>
                <h2 class="mb-0"><?= $total_b ?></h2>
                <p class="mb-0">Judul Buku</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card text-white bg-info">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="bi bi-clock-history display-4"></i>
              </div>
              <div>
                <?php
                // Hitung total peminjaman untuk anggota ini
                $id_anggota = $_SESSION['anggota']['id'];
                $total_pinjam = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE id_anggota = $id_anggota");
                $total_p = $total_pinjam ? mysqli_fetch_assoc($total_pinjam)['total'] : 0;
                ?>
                <h2 class="mb-0"><?= $total_p ?></h2>
                <p class="mb-0">Peminjaman Anda</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card text-white bg-warning">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="bi bi-exclamation-triangle display-4"></i>
              </div>
              <div>
                <?php
                // Hitung peminjaman terlambat untuk anggota ini
                $today = date('Y-m-d');
                $terlambat = mysqli_query($conn, "SELECT COUNT(*) AS total 
                                                FROM peminjaman 
                                                WHERE id_anggota = $id_anggota 
                                                AND status = 'Aktif' 
                                                AND tanggal_kembali < '$today'");
                $total_t = $terlambat ? mysqli_fetch_assoc($terlambat)['total'] : 0;
                ?>
                <h2 class="mb-0"><?= $total_t ?></h2>
                <p class="mb-0">Peminjaman Terlambat</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Peminjaman -->
    <div class="table-container">
      <div class="d-flex justify-content-between align-items-center p-3 bg-light">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Daftar Peminjaman Anda</h5>
        <div>
          <button class="btn btn-sm btn-outline-primary me-2" id="refreshBtn">
            <i class="bi bi-arrow-repeat"></i> Refresh
          </button>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tambahPinjamModal">
            <i class="bi bi-plus-circle me-1"></i> Pinjam Buku
          </button>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Buku</th>
              <th>Jumlah</th>
              <th>Tanggal Pinjam</th>
              <th>Tanggal Kembali</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            $id_anggota = $_SESSION['anggota']['id'];
            $today = date('Y-m-d');
            
            // FILTER BERDASARKAN ANGGOTA YANG LOGIN
            $sql = "SELECT peminjaman.*, buku.judul, buku.cover, buku.file_pdf 
                    FROM peminjaman
                    JOIN buku ON peminjaman.id_buku = buku.id
                    WHERE peminjaman.id_anggota = $id_anggota
                    ORDER BY peminjaman.tanggal_pinjam DESC";
            
            $query = mysqli_query($conn, $sql);
            
            if (!$query) {
                echo '<tr><td colspan="7" class="text-center text-danger py-4">Error: '.mysqli_error($conn).'</td></tr>';
            } else if (mysqli_num_rows($query) == 0) {
                echo '<tr><td colspan="7" class="text-center py-4">
                        <i class="bi bi-info-circle me-2"></i>Belum ada data peminjaman
                      </td></tr>';
            } else {
                while ($row = mysqli_fetch_assoc($query)) {
                  // Update status terlambat jika perlu
                  if (isset($row['status']) && $row['status'] == 'Aktif' && $row['tanggal_kembali'] < $today) {
                      mysqli_query($conn, "UPDATE peminjaman SET status = 'Terlambat' WHERE id = {$row['id']}");
                      $row['status'] = 'Terlambat';
                  }
                  
                  // Tentukan kelas badge berdasarkan status
                  $badge_class = '';
                  $status = $row['status'] ?? 'Aktif'; // Default jika tidak ada
                  
                  if ($status == 'Aktif') {
                      $badge_class = 'badge-aktif';
                  } else if ($status == 'Dikembalikan') {
                      $badge_class = 'badge-dikembalikan';
                  } else if ($status == 'Terlambat') {
                      $badge_class = 'badge-terlambat';
                  }
                  
                  echo "<tr>
                    <td>{$no}</td>
                    <td>
                      <div class='d-flex align-items-center'>
                        <img src='".($row['cover'] ?? '')."' class='book-cover me-3' alt='Cover Buku' onerror=\"this.src='https://via.placeholder.com/60x80?text=No+Cover'\">
                        <div>
                          <div class='fw-bold'>{$row['judul']}</div>
                          <div class='small text-muted'>ID: {$row['id_buku']}</div>
                        </div>
                      </div>
                    </td>
                    <td>{$row['jumlah']}</td>
                    <td>{$row['tanggal_pinjam']}</td>
                    <td>{$row['tanggal_kembali']}</td>
                    <td><span class='badge {$badge_class}'>{$status}</span></td>
                    <td class='action-buttons'>";
                  
                  // Tombol aksi berdasarkan status
                  if ($status == 'Aktif' || $status == 'Terlambat') {
                    echo "<a href='?kembali={$row['id']}' class='btn btn-sm btn-success' title='Kembalikan Buku'>
                            <i class='bi bi-arrow-return-left'></i>
                          </a>";
                  }
                  
                  echo "<button class='btn btn-sm btn-info' data-bs-toggle='modal' data-bs-target='#ratingModal{$row['id_buku']}' title='Beri Rating'>
                          <i class='bi bi-star'></i>
                        </button>
                        <a href='".($row['file_pdf'] ?? '#')."' target='_blank' class='btn btn-sm btn-primary' title='Baca Buku'>
                          <i class='bi bi-book'></i>
                        </a>
                    </td>
                  </tr>";
                  
                  // Modal Rating
                  echo "<div class='modal fade' id='ratingModal{$row['id_buku']}' tabindex='-1' aria-hidden='true'>
                          <div class='modal-dialog'>
                            <div class='modal-content'>
                              <div class='modal-header bg-primary text-white'>
                                <h5 class='modal-title'>Beri Rating Buku</h5>
                                <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                              </div>
                              <form method='POST'>
                                <div class='modal-body'>
                                  <h5>{$row['judul']}</h5>
                                  <div class='mb-3'>
                                    <label class='form-label'>Rating</label>
                                    <select name='rating' class='form-select' required>
                                      <option value='' disabled selected>Pilih Rating</option>
                                      <option value='1'>⭐ (Buruk)</option>
                                      <option value='2'>⭐⭐ (Cukup)</option>
                                      <option value='3'>⭐⭐⭐ (Baik)</option>
                                      <option value='4'>⭐⭐⭐⭐ (Sangat Baik)</option>
                                      <option value='5'>⭐⭐⭐⭐⭐ (Luar Biasa)</option>
                                    </select>
                                  </div>
                                  <input type='hidden' name='id_buku' value='{$row['id_buku']}'>
                                </div>
                                <div class='modal-footer'>
                                  <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>
                                  <button type='submit' name='beri_rating' class='btn btn-primary'>Simpan Rating</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>";
                  
                  $no++;
                }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Riwayat Peminjaman -->
    <div class="card mt-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Peminjaman</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Buku</th>
                <th>Jumlah</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Kembali</th>
                <th>Tanggal Dikembalikan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $sql_riwayat = "SELECT peminjaman.*, buku.judul 
                              FROM peminjaman
                              JOIN buku ON peminjaman.id_buku = buku.id
                              WHERE peminjaman.id_anggota = $id_anggota
                              ORDER BY peminjaman.tanggal_pinjam DESC
                              LIMIT 10"; // Batasi 10 riwayat terakhir
              $query_riwayat = mysqli_query($conn, $sql_riwayat);
              
              if (mysqli_num_rows($query_riwayat) > 0) {
                  while ($row = mysqli_fetch_assoc($query_riwayat)) {
                      $status = $row['status'] ?? 'Aktif'; // Default jika tidak ada
                      $badge_class = $status == 'Aktif' ? 'badge-aktif' : 
                                    ($status == 'Dikembalikan' ? 'badge-dikembalikan' : 'badge-terlambat');
                      
                      echo "<tr>
                        <td>{$no}</td>
                        <td>{$row['judul']}</td>
                        <td>{$row['jumlah']}</td>
                        <td>{$row['tanggal_pinjam']}</td>
                        <td>{$row['tanggal_kembali']}</td>
                        <td>".($row['tanggal_dikembalikan'] ?? '-')."</td>
                        <td><span class='badge {$badge_class}'>{$status}</span></td>
                      </tr>";
                      $no++;
                  }
              } else {
                  echo "<tr><td colspan='7' class='text-center py-4'>Belum ada riwayat peminjaman</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal Login -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Login Anggota</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label">Nomor Anggota</label>
            <input type="text" name="nomor_anggota" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="d-grid">
            <button type="submit" name="login" class="btn btn-primary">Masuk</button>
          </div>
          <div class="text-center mt-3">
            <button type="button" class="btn btn-link" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#daftarModal">Daftar Anggota Baru</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Daftar Anggota -->
<div class="modal fade" id="daftarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pendaftaran Anggota Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (isset($login_error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
          <!-- ... (form pendaftaran tetap sama) ... -->
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah Peminjaman -->
<div class="modal fade" id="tambahPinjamModal" tabindex="-1" aria-labelledby="tambahPinjamModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="tambahPinjamModalLabel"><i class="bi bi-journal-plus me-2"></i>Form Peminjaman Buku</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php if (isset($_SESSION['anggota'])): ?>
            <div class="alert alert-info">
              <i class="bi bi-info-circle me-2"></i>
              Anda login sebagai: <strong><?= htmlspecialchars($_SESSION['anggota']['nama']) ?></strong> 
              (<?= htmlspecialchars($_SESSION['anggota']['nomor_anggota']) ?>)
              <?php if (isset($_SESSION['anggota']['status'])): ?>
                <div class="mt-1">Status: 
                  <span class="status-anggota status-anggota-<?= strtolower($_SESSION['anggota']['status']) ?>">
                    <?= $_SESSION['anggota']['status'] ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          
          <div class="mb-3">
            <label for="id_buku" class="form-label">Pilih Buku <span class="text-danger">*</span></label>
            <select name="id_buku" class="form-select" required id="bukuSelect">
              <option value="" disabled selected>-- Pilih Buku --</option>
              <?php
              $buku_query = mysqli_query($conn, "SELECT * FROM buku WHERE stok > 0");
              if (!$buku_query) {
                  echo '<option value="">Error: '.mysqli_error($conn).'</option>';
              } else if (mysqli_num_rows($buku_query) == 0) {
                  echo '<option value="">Tidak ada buku tersedia</option>';
              } else {
                  while ($b = mysqli_fetch_assoc($buku_query)) {
                    echo "<option value='{$b['id']}' data-stok='{$b['stok']}'>{$b['judul']} (Stok: {$b['stok']})</option>";
                  }
              }
              ?>
            </select>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="jumlah" class="form-label">Jumlah Buku <span class="text-danger">*</span></label>
              <input type="number" name="jumlah" class="form-control" min="1" required id="jumlahInput" placeholder="Jumlah">
              <small class="text-muted" id="stokInfo">Stok tersedia: -</small>
            </div>
            <div class="col-md-6 mb-3">
              <label for="tanggal_kembali" class="form-label">Tanggal Kembali <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_kembali" class="form-control" required id="tanggalKembali">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x me-1"></i> Batal</button>
          <button type="submit" name="pinjam" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Peminjaman</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Set tanggal minimal besok
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  
  const minDate = tomorrow.toISOString().split('T')[0];
  document.getElementById('tanggalKembali').min = minDate;
  document.getElementById('tanggalKembali').value = minDate;

  // Update info stok saat buku dipilih
  document.getElementById('bukuSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
      const stok = selectedOption.getAttribute('data-stok') || 0;
      document.getElementById('stokInfo').textContent = `Stok tersedia: ${stok}`;
      document.getElementById('jumlahInput').max = stok;
    } else {
      document.getElementById('stokInfo').textContent = 'Stok tersedia: -';
    }
  });

  // Validasi jumlah tidak melebihi stok
  document.getElementById('jumlahInput').addEventListener('input', function() {
    const selectedOption = document.getElementById('bukuSelect').options[document.getElementById('bukuSelect').selectedIndex];
    if (selectedOption && selectedOption.value) {
      const stok = parseInt(selectedOption.getAttribute('data-stok') || 0);
      const jumlah = parseInt(this.value) || 0;
      
      if (jumlah > stok) {
        this.setCustomValidity(`Jumlah melebihi stok tersedia (${stok})`);
        this.classList.add('is-invalid');
      } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
      }
    }
  });

  // Refresh button
  document.getElementById('refreshBtn')?.addEventListener('click', function() {
    window.location.reload();
  });

  // Inisialisasi stok info
  document.addEventListener('DOMContentLoaded', function() {
    const bukuSelect = document.getElementById('bukuSelect');
    if (bukuSelect && bukuSelect.selectedIndex > 0) {
      const selectedOption = bukuSelect.options[bukuSelect.selectedIndex];
      const stok = selectedOption.getAttribute('data-stok') || 0;
      document.getElementById('stokInfo').textContent = `Stok tersedia: ${stok}`;
    }
  });
</script>
</body>
</html>