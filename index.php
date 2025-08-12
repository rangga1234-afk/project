<?php
session_start();

// Buat folder uploads jika belum ada
$folders = ['uploads/covers', 'uploads/pdfs', 'uploads/foto_anggota'];
foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
}

// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "perpustakaan_smk";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Proses login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['loggedin'] = true;
        header("Location: beranda.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}

// Proses logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Proses rating dan baca buku
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['rating']) && isset($_POST['id_buku'])) {
        $id = intval($_POST['id_buku']);
        $nilai = intval($_POST['rating']);

        $result = mysqli_query($conn, "SELECT rating FROM buku WHERE id = $id");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $rating_lama = $row['rating'];
            $rating_baru = $rating_lama > 0 ? round(($rating_lama + $nilai) / 2, 1) : $nilai;
            mysqli_query($conn, "UPDATE buku SET rating = $rating_baru WHERE id = $id");
        }
    }

    if (isset($_POST['baca']) && isset($_POST['id_buku'])) {
        $id = intval($_POST['id_buku']);
        mysqli_query($conn, "UPDATE buku SET dibaca = dibaca + 1 WHERE id = $id");
        $file_result = mysqli_query($conn, "SELECT file_pdf FROM buku WHERE id = $id");
        if ($file_result && $file_result->num_rows > 0) {
            $file = $file_result->fetch_assoc();
            echo "<script>window.open('{$file['file_pdf']}', '_blank');</script>";
        }
    }
}

// Proses tambah buku
if (isset($_POST['tambah_buku'])) {
    $judul = $_POST['judul'];
    $stok = $_POST['stok'];

    // Upload cover buku
    $cover_name = $_FILES['cover']['name'];
    $cover_tmp = $_FILES['cover']['tmp_name'];
    $cover_path = "uploads/covers/" . uniqid() . '_' . $cover_name;

    if (move_uploaded_file($cover_tmp, $cover_path)) {
        // Upload file PDF
        $pdf_name = $_FILES['pdf']['name'];
        $pdf_tmp = $_FILES['pdf']['tmp_name'];
        $pdf_path = "uploads/pdfs/" . uniqid() . '_' . $pdf_name;

        if (move_uploaded_file($pdf_tmp, $pdf_path)) {
            $sql = "INSERT INTO buku (judul, cover, file_pdf, stok) VALUES ('$judul', '$cover_path', '$pdf_path', '$stok')";
            if (!$conn->query($sql)) {
                $error = "Error: " . $conn->error;
            } else {
                header("Location: beranda.php");
                exit;
            }
        } else {
            $error = "Gagal mengupload file PDF";
        }
    } else {
        $error = "Gagal mengupload cover buku";
    }
}

// Proses hapus buku
if (isset($_GET['hapus_buku'])) {
    $id = intval($_GET['hapus_buku']);
    
    $sql_select = "SELECT cover, file_pdf FROM buku WHERE id = $id";
    $result = $conn->query($sql_select);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (file_exists($row['cover'])) {
            unlink($row['cover']);
        }
        if (file_exists($row['file_pdf'])) {
            unlink($row['file_pdf']);
        }
        
        $sql = "DELETE FROM buku WHERE id = $id";
        if ($conn->query($sql)) {
            header("Location: beranda.php");
            exit;
        } else {
            $error = "Gagal menghapus buku: " . $conn->error;
        }
    } else {
        $error = "Buku tidak ditemukan!";
    }
}

// Proses tambah saran
if (isset($_POST['kirim_saran'])) {
    $nama = $_POST['nama'];
    $alasan = $_POST['alasan'];
    $tanggal = date('Y-m-d');
    
    $sql = "INSERT INTO saran (nama, alasan, tanggal) VALUES ('$nama', '$alasan', '$tanggal')";
    if (!$conn->query($sql)) {
        $error = "Error: " . $conn->error;
    } else {
        header("Location: beranda.php");
        exit;
    }
}

// Ambil keyword pencarian
$search_keyword = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : '';

// Query buku dengan filter pencarian
$sql_buku = "SELECT * FROM buku";
if (!empty($search_keyword)) {
    $sql_buku .= " WHERE judul LIKE '%$search_keyword%'";
}
$result_buku = $conn->query($sql_buku);
$buku = [];
if ($result_buku && $result_buku->num_rows > 0) {
    while($row = $result_buku->fetch_assoc()) {
        $buku[] = $row;
    }
}

// Cek apakah user sudah login
$loggedin = isset($_SESSION['loggedin']) ? $_SESSION['loggedin'] : false;

if (!$loggedin && basename($_SERVER['PHP_SELF']) != 'index.php') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan SMK Keling Kumang Sekadau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background-color: var(--dark);
            color: white;
            min-height: 100vh;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 5px;
            margin-bottom: 5px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--secondary);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-img-top {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            height: 200px;
            object-fit: cover;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-danger {
            background-color: var(--accent);
            border-color: var(--accent);
        }
        
        .section-title {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 15px;
            color: var(--primary);
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--secondary);
        }
        
        .footer {
            background-color: var(--primary);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .search-form {
            width: 50%;
        }

        @media (max-width: 768px) {
            .search-form {
                width: 100% !important;
            }
        }
        
        .social-icon:hover {
            background-color: var(--secondary);
            transform: translateY(-3px);
        }
        
        .book-cover {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .table-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .logo-container {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .logo-sekolah {
            width: 100px;
            height: auto;
            border: 3px solid white;
            border-radius: 50%;
            padding: 5px;
            background: white;
        }
        
        .school-name {
            font-family: 'Arial Rounded MT Bold', 'Segoe UI', sans-serif;
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 10px;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .school-location {
            font-size: 0.85rem;
            color: #ffd700;
            letter-spacing: 1px;
        }
        
        .search-results-header {
            background-color: #e9f7fe;
            border-left: 4px solid var(--secondary);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .no-results i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if ($loggedin): ?>
    <!-- Layout untuk user yang sudah login -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 col-lg-2 d-md-block sidebar collapse bg-dark">
                <div class="position-sticky pt-3">
                    <div class="text-center my-4">
                        <!-- Logo Sekolah -->
                        <div class="logo-container text-center">
                            <img src="logo_sekolah.jpg" alt="Logo Sekolah" style="width: 120px; height: auto;">
                            <div class="school-name">SMK KELING KUMANG</div>
                            <div class="school-location">SEKADAU</div>
                        </div>
                        <h5 class="text-white">Perpustakaan SMK Keling Kumang</h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="beranda.php">
                                <i class="fas fa-home"></i> Beranda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="anggota.php">📋 Data Anggota</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#saranModal">
                                <i class="fas fa-comment"></i> Kotak Saran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="peminjaman.php">
                                <i class="fas fa-book"></i> Pinjam Buku
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?logout=true">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-user-cog"></i> Admin Panel
                            </a>
                        </li>
                    </ul>
                    <div class="mt-5 px-3">
                        <h6 class="text-white text-uppercase"></h6>
                        <div class="d-flex align-items-center mt-3">
                            <img src="" class="rounded-circle me-3" alt="">
                            <div>
                                <p class="mb-0 text-white"></p>
                                <small class="text-muted"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 ms-sm-auto">
                <!-- Form pencarian -->
                <form class="d-flex search-form mb-4" method="GET" action="">
                    <input class="form-control me-2" type="search" name="search" placeholder="Cari judul buku..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                           aria-label="Search">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Cari
                    </button>
                    <?php if (!empty($search_keyword)): ?>
                    <a href="beranda.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-times me-1"></i> Reset
                    </a>
                    <?php endif; ?>
                </form>

                <?php if (!empty($search_keyword)): ?>
                <div class="search-results-header mb-4">
                    <h4 class="mb-0">
                        <i class="fas fa-search me-2"></i> Hasil Pencarian untuk "<?php echo htmlspecialchars($search_keyword); ?>"
                        <span class="badge bg-primary ms-2"><?php echo count($buku); ?> hasil ditemukan</span>
                    </h4>
                </div>
                <?php endif; ?>

                <!-- Tampilkan error jika ada -->
                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">
                        <?php echo empty($search_keyword) ? 'Total Koleksi Buku' : 'Hasil Pencarian'; ?>
                    </h2>
                    <div>
                        <span class="badge bg-primary p-2">
                            <i class="fas fa-book me-1"></i> Total: <?php echo count($buku); ?> Buku
                        </span>
                    </div>
                </div>
                
                <?php if (empty($buku)): ?>
                <div class="no-results">
                    <i class="fas fa-book-open"></i>
                    <h3 class="mb-3">Buku Tidak Ditemukan</h3>
                    <p class="text-muted mb-4">Maaf, tidak ada buku yang cocok dengan pencarian Anda.</p>
                    <a href="beranda.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda
                    </a>
                </div>
                <?php else: ?>
                <!-- Daftar Buku -->
                <div class="book-grid">
                    <?php foreach ($buku as $b): ?>
                    <div class="card">
                        <img src="<?php echo $b['cover']; ?>" class="card-img-top" alt="<?php echo $b['judul']; ?>" onerror="this.src='https://via.placeholder.com/200x300?text=Cover+Tidak+Tersedia'">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $b['judul']; ?></h5>
                            <!-- Info Rating, Dibaca, Stok -->
                            <div class="mb-2 text-muted small">
                                <i class="fas fa-star text-warning"></i> <?php echo number_format($b['rating'], 1); ?>
                                &nbsp; | &nbsp;
                                <i class="fas fa-eye text-secondary"></i> <?php echo $b['dibaca']; ?> dibaca
                                &nbsp; | &nbsp;
                                <i class="fas fa-book text-success"></i> <?= $b['stok']; ?> stok
                            </div>
                            
                            <!-- Tombol Baca -->
                            <form method="post" class="d-inline" onsubmit="window.open('<?php echo $b['file_pdf']; ?>', '_blank')">
                                <input type="hidden" name="id_buku" value="<?php echo $b['id']; ?>">
                                <button type="submit" name="baca" class="btn btn-sm btn-primary">
                                    <i class="fas fa-book-open me-1"></i> Baca
                                </button>
                            </form>
                            
                            <!-- Rating -->
                            <form method="post" class="mt-2">
                                <input type="hidden" name="id_buku" value="<?php echo $b['id']; ?>">
                                <select name="rating" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Beri Rating</option>
                                    <option value="1">⭐</option>
                                    <option value="2">⭐⭐</option>
                                    <option value="3">⭐⭐⭐</option>
                                    <option value="4">⭐⭐⭐⭐</option>
                                    <option value="5">⭐⭐⭐⭐⭐</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($search_keyword)): ?>
                    <!-- Kartu Tambah Buku hanya ditampilkan jika tidak sedang mencari -->
                    <div class="card bg-light border-dashed d-flex align-items-center justify-content-center">
                        
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (empty($search_keyword)): ?>
                <!-- Informasi Perpustakaan hanya ditampilkan di halaman utama -->
                <div class="row mt-5">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h4 class="section-title">Informasi Perpustakaan</h4>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-map-marker-alt me-2 text-primary"></i> Lokasi</span>
                                    <span>Jalan Keling Kumang No. 1</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-clock me-2 text-primary"></i> Jam Operasional</span>
                                    <span>07:00 - 15:00 (Senin-Jumat)<br>08:00 - 16:00 (Sabtu-Minggu)</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-tie me-2 text-primary"></i> Petugas</span>
                                    <span>Bu Aster Monica</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title border-bottom pb-2">Sosial Media</h5>
                                <div class="d-flex justify-content-start gap-3 mt-3">
                                    <a href="https://www.instagram.com/rangg_2112" target="_blank" class="text-decoration-none text-dark">
                                        <i class="fab fa-instagram fa-2x"></i>
                                    </a>
                                    <a href="https://www.facebook.com/profile.php?id=100093781012345" target="_blank" class="text-decoration-none text-dark">
                                        <i class="fab fa-facebook fa-2x"></i>
                                    </a>
                                    <a href="https://www.tiktok.com/@user852564103" target="_blank" class="text-decoration-none text-dark">
                                        <i class="fab fa-tiktok fa-2x"></i>
                                    </a>
                                </div>
                            </div>
                        </div>                      
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah Buku -->
    <div class="modal fade" id="tambahBukuModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Tambah Koleksi Buku</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul Buku</label>
                            <input type="text" class="form-control" name="judul" placeholder="Masukkan judul buku" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cover Buku</label>
                            <input type="file" class="form-control" name="cover" accept="image/jpeg,image/png" required>
                            <small class="text-muted">Format: JPG, PNG (maks 500MB)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File PDF Buku</label>
                            <input type="file" class="form-control" name="pdf" accept=".pdf" required>
                            <small class="text-muted">Format: PDF (maks 500MB)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stok Buku</label>
                            <input type="number" class="form-control" name="stok" min="1" placeholder="Masukkan jumlah stok buku" required>
                            <small class="text-muted">Masukkan jumlah stok awal buku.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="tambah_buku">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Kotak Saran -->
    <div class="modal fade" id="saranModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Kotak Saran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="nama" placeholder="Nama Anda" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alasan/Saran</label>
                            <textarea class="form-control" name="alasan" rows="4" placeholder="Masukkan saran atau alasan..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>" disabled>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="kirim_saran">Kirim Saran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Perpustakaan SMK Keling Kumang Sekadau</h5>
                    <p>Menyediakan berbagai koleksi buku untuk mendukung kegiatan belajar mengajar di sekolah.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>© <?php echo date('Y'); ?> Perpustakaan SMK Keling Kumang Sekadau</p>
                    <p>Jalan Keling Kumang No. 1, Sekadau</p>
                </div>
            </div>
        </div>
    </footer>
    
    <?php else: ?>
    <!-- Halaman Login -->
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-5">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="">
                            <h3 class="fw-bold">Perpustakaan SMK Keling Kumang</h3>
                            <p class="text-muted">Silakan login untuk mengakses sistem</p>
                        </div>
                        
                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" placeholder="Masukkan username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Masukkan password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>