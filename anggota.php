<?php
session_start();
include 'config.php';

// --- PROSES DAFTAR ANGGOTA ---
if (isset($_POST['daftar'])) {
    // Ambil data dari form
    $nama = $_POST['nama'];
    $nomor_anggota = $_POST['nomor_anggota'];
    $no_identitas = $_POST['no_identitas'];
    $password = $_POST['password'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $alamat_identitas = $_POST['alamat_identitas'];
    $provinsi = $_POST['provinsi'];
    $kota = $_POST['kota'];
    $no_hp = $_POST['no_hp'];
    $no_telp_rumah = $_POST['no_telp_rumah'];
    $pendidikan_terakhir = $_POST['pendidikan_terakhir'];
    $pekerjaan = $_POST['pekerjaan'];
    $status_perkawinan = $_POST['status_perkawinan'];
    $nama_institusi = $_POST['nama_institusi'];
    $alamat_institusi = $_POST['alamat_institusi'];
    $telp_institusi = $_POST['telp_institusi'];
    $email = $_POST['email'];
    $persetujuan = isset($_POST['persetujuan']) ? 1 : 0;
    $kelas = $_POST['kelas'];
    $jurusan = $_POST['jurusan'];
    $angkatan = $_POST['angkatan'];

    // Jika alamat saat ini sama dengan alamat identitas
    if(isset($_POST['sama_dengan_identitas'])) {
        $alamat_saat_ini = $alamat_identitas;
        $provinsi_saat_ini = $provinsi;
        $kota_saat_ini = $kota;
    }

    $sql = "INSERT INTO anggota 
        (nama, nomor_anggota, no_identitas, password, tempat_lahir, tanggal_lahir, jenis_kelamin, 
        alamat_identitas, provinsi, kota, alamat_saat_ini, provinsi_saat_ini, kota_saat_ini, 
        no_hp, no_telp_rumah, pendidikan_terakhir, pekerjaan, status_perkawinan, 
        nama_institusi, alamat_institusi, telp_institusi, email, persetujuan, 
        kelas, jurusan, angkatan, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssssssssssssssssssssssss", 
        $nama, $nomor_anggota, $no_identitas, $password, $tempat_lahir, $tanggal_lahir, $jenis_kelamin, 
        $alamat_identitas, $provinsi, $kota, $alamat_saat_ini, $provinsi_saat_ini, $kota_saat_ini, 
        $no_hp, $no_telp_rumah, $pendidikan_terakhir, $pekerjaan, $status_perkawinan, 
        $nama_institusi, $alamat_institusi, $telp_institusi, $email, $persetujuan, 
        $kelas, $jurusan, $angkatan);

    if ($stmt->execute()) {
        $result = $conn->query("SELECT * FROM anggota WHERE nomor_anggota='$nomor_anggota'");
        $_SESSION['anggota'] = $result->fetch_assoc();
        header("Location: anggota.php?page=profil");
        exit;
    } else {
        echo "Gagal menyimpan data: " . $stmt->error;
    }
}

// --- PROSES LOGIN ---
if (isset($_POST['login'])) {
    $nomor_anggota = $_POST['nomor_anggota'];
    $password = $_POST['password'];
    $result = mysqli_query($conn, "SELECT * FROM anggota WHERE nomor_anggota='$nomor_anggota' AND password='$password'");
    if (mysqli_num_rows($result) > 0) {
    $_SESSION['anggota'] = mysqli_fetch_assoc($result);
    
    // Tambah baris ini
    $id_anggota = $_SESSION['anggota']['id'];
    mysqli_query($conn, "UPDATE anggota SET last_login = NOW() WHERE id = '$id_anggota'");

    header("Location: anggota.php?page=beranda");
    exit;
}

    } else {
    }

// --- PROSES LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: anggota.php");
    exit;
}

$page = $_GET['page'] ?? '';

// Redirect jika akses halaman terproteksi tanpa login
$restricted_pages = ['beranda', 'profil'];
if (in_array($page, $restricted_pages) && !isset($_SESSION['anggota'])) {
    header("Location: anggota.php?page=login");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perpustakaan Digital - Anggota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-header { background-color: #0d6efd; color: white; }
        .required:after { content:" *"; color: red; }
        .form-section { border-left: 4px solid #0d6efd; padding-left: 15px; margin-bottom: 25px; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center mb-4">Perpustakaan Digital</h2>
    
    <!-- Navigasi Dinamis -->
    <nav class="mb-4 d-flex flex-wrap gap-2 justify-content-center">
        <?php if(isset($_SESSION['anggota'])): ?>
            <a href="anggota.php?page=beranda" class="btn btn-outline-primary"><i class="bi bi-house-door"></i> Beranda</a>
            <a href="anggota.php?page=profil" class="btn btn-outline-primary"><i class="bi bi-person"></i> Profil</a>
            <a href="anggota.php?logout=true" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
        <?php else: ?>
            <a href="anggota.php?page=daftar" class="btn btn-success"><i class="bi bi-pencil-square"></i> Pendaftaran</a>
            <a href="anggota.php?page=login" class="btn btn-secondary"><i class="bi bi-box-arrow-in-right"></i> Login</a>
        <?php endif; ?>
    </nav>

    <!-- Konten Halaman -->
    <?php if ($page == 'beranda' && isset($_SESSION['anggota'])): ?>
        <!-- Halaman Beranda -->
         <div class="container">
    <a href="index.php" class="btn btn-secondary mb-3">← Kembali ke Beranda</a>
</div>
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h4 class="mb-0">Selamat datang, <?= $_SESSION['anggota']['nama'] ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5><i class="bi bi-person-badge"></i> Status Keanggotaan</h5>
                                <p class="mb-1">
                                    <i class="bi bi-calendar-check"></i> 
                                    Terdaftar sejak: <?= date('d M Y', strtotime($_SESSION['anggota']['created_at'])) ?>
                                </p>
                                <?php
$status = $_SESSION['anggota']['status_akun'] ?? 'nonaktif';

// Pilih warna badge sesuai status
$warna = [
    'aktif' => 'bg-success',
    'nonaktif' => 'bg-warning text-dark',
    'diblokir' => 'bg-danger'
];
?>

<p class="mb-0">
    <i class="bi bi-stars"></i> 
    Status: <span class="badge <?= $warna[$status] ?? 'bg-secondary' ?>"><?= ucwords($status) ?></span>
</p>
                    
                    <div class="dropdown">
  <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
    Reward & Poin
  </button>
  <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
    <li><a class="dropdown-item" href="#">Lihat Poin</a></li>
    <li><a class="dropdown-item" href="#">Tukar Reward</a></li>
  </ul>
</div>


    <?php elseif ($page == 'profil' && isset($_SESSION['anggota'])): ?>
        <!-- Halaman Profil -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0">Profil Anggota</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Informasi Pribadi</h5>
                        <table class="table table-sm">
                            <tr><th>Nama Lengkap</th><td><?= $_SESSION['anggota']['nama'] ?></td></tr>
                            <tr><th>Nomor Anggota</th><td><?= $_SESSION['anggota']['nomor_anggota'] ?></td></tr>
                            <tr><th>No. Identitas</th><td><?= $_SESSION['anggota']['no_identitas'] ?></td></tr>
                            <tr><th>Tempat/Tgl Lahir</th><td><?= $_SESSION['anggota']['tempat_lahir'] ?>, <?= date('d-m-Y', strtotime($_SESSION['anggota']['tanggal_lahir'])) ?></td></tr>
                            <tr><th>Jenis Kelamin</th><td><?= $_SESSION['anggota']['jenis_kelamin'] ?></td></tr>
                            <tr><th>Status Perkawinan</th><td><?= $_SESSION['anggota']['status_perkawinan'] ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Kontak & Institusi</h5>
                        <table class="table table-sm">
                            <tr><th>No. HP</th><td><?= $_SESSION['anggota']['no_hp'] ?></td></tr>
                            <tr><th>No. Telpon Rumah</th><td><?= $_SESSION['anggota']['no_telp_rumah'] ?></td></tr>
                            <tr><th>Email</th><td><?= $_SESSION['anggota']['email'] ?></td></tr>
                            <tr><th>Pekerjaan</th><td><?= $_SESSION['anggota']['pekerjaan'] ?></td></tr>
                            <tr><th>Institusi</th><td><?= $_SESSION['anggota']['nama_institusi'] ?></td></tr>
                            <tr><th>Pendidikan</th><td><?= $_SESSION['anggota']['pendidikan_terakhir'] ?></td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h5>Alamat</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Alamat Identitas</h6>
                            <p><?= $_SESSION['anggota']['alamat_identitas'] ?><br>
                            <?= $_SESSION['anggota']['kota'] ?>, <?= $_SESSION['anggota']['provinsi'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Alamat Saat Ini</h6>
                            <p><?= $_SESSION['anggota']['alamat_saat_ini'] ?><br>
                            <?= $_SESSION['anggota']['kota_saat_ini'] ?>, <?= $_SESSION['anggota']['provinsi_saat_ini'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h5>Data Pendidikan</h5>
                    <table class="table table-sm">
                        <tr><th>Kelas</th><td><?= $_SESSION['anggota']['kelas'] ?></td></tr>
                        <tr><th>Jurusan</th><td><?= $_SESSION['anggota']['jurusan'] ?></td></tr>
                        <tr><th>Angkatan</th><td><?= $_SESSION['anggota']['angkatan'] ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($page == 'daftar'): ?>
        <!-- Form Pendaftaran -->
         <div class="container">
        <a href="index.php" class="btn btn-secondary mb-3">← Kembali ke Beranda</a>
    </div>
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0 text-center">Formulir Pendaftaran Anggota</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <!-- Bagian 1: Identitas -->
                    <div class="form-section">
                        <h5 class="mb-3">Identitas</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">No. Identitas (KTP/NIK)</label>
                                <input type="text" name="no_identitas" class="form-control" placeholder="Masukkan nomor identitas" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label required">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                                <small class="text-muted">Sesuai dengan identitas</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select" required>
                                    <option value="" disabled selected>Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Status Perkawinan</label>
                                <select name="status_perkawinan" class="form-select" required>
                                    <option value="" disabled selected>Pilih Status</option>
                                    <option value="Belum Menikah">Belum Menikah</option>
                                    <option value="Menikah">Menikah</option>
                                    <option value="Duda">Duda</option>
                                    <option value="Janda">Janda</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 2: Alamat -->
                    <div class="form-section">
                        <h5 class="mb-3">Alamat</h5>
                        <div class="mb-3">
                            <label class="form-label required">Alamat Sesuai Identitas</label>
                            <textarea name="alamat_identitas" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Provinsi</label>
                                <select name="provinsi" class="form-select" required>
                                    <option value="" disabled selected>Pilih Provinsi</option>
                                    <option value="DKI Jakarta">DKI Jakarta</option>
                                    <option value="Jawa Barat">Jawa Barat</option>
                                    <option value="Jawa Tengah">Jawa Tengah</option>
                                    <option value="Jawa Timur">Jawa Timur</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Kota/Kabupaten</label>
                                <select name="kota" class="form-select" required>
                                    <option value="" disabled selected>Pilih Kota/Kabupaten</option>
                                    <option value="Jakarta Pusat">Jakarta Pusat</option>
                                    <option value="Jakarta Selatan">Jakarta Selatan</option>
                                    <option value="Jakarta Timur">Jakarta Timur</option>
                                    <option value="Jakarta Barat">Jakarta Barat</option>
                                </select>
                            </div>
                        </div>

                    <!-- Bagian 3: Kontak -->
                    <div class="form-section">
                        <h5 class="mb-3">Kontak</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Nomor HP</label>
                                <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 08993890323" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Telepon Rumah</label>
                                <input type="text" name="no_telp_rumah" class="form-control" placeholder="Contoh: 0217714718">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label required">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="alamat@email.com" required>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 4: Pendidikan & Pekerjaan -->
                    <div class="form-section">
                        <h5 class="mb-3">Pendidikan & Pekerjaan</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Pendidikan Terakhir</label>
                                <select name="pendidikan_terakhir" class="form-select" required>
                                    <option value="" disabled selected>Pilih Pendidikan</option>
                                    <option value="SD">SD</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                    <option value="D3">D3</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                    <option value="S3">S3</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Pekerjaan</label>
                                <select name="pekerjaan" class="form-select" required>
                                    <option value="" disabled selected>Pilih Pekerjaan</option>
                                    <option value="Pelajar">Pelajar</option>
                                    <option value="Mahasiswa">Mahasiswa</option>
                                    <option value="PNS">PNS</option>
                                    <option value="TNI/Polri">TNI/Polri</option>
                                    <option value="BUMN">BUMN</option>
                                    <option value="Swasta">Swasta</option>
                                    <option value="Wiraswasta">Wiraswasta</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Nama Institusi</label>
                                <input type="text" name="nama_institusi" class="form-control" placeholder="Sekolah/Universitas/Instansi/Kantor">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Alamat Institusi</label>
                                <textarea name="alamat_institusi" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telepon Institusi</label>
                                <input type="text" name="telp_institusi" class="form-control" placeholder="Contoh: 0217714718">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 5: Data Pendidikan -->
                    <div class="form-section">
                        <h5 class="mb-3">Data Pendidikan (Jika Pelajar/Mahasiswa)</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kelas</label>
                                <input type="text" name="kelas" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jurusan</label>
                                <input type="text" name="jurusan" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Angkatan</label>
                                <input type="text" name="angkatan" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 6: Nomor Anggota -->
                    <div class="form-section">
                        <div class="mb-3">
                            <label class="form-label required">Nomor Anggota</label>
                            <input type="text" name="nomor_anggota" class="form-control" placeholder="Masukkan nomor anggota" required>
                            <small class="text-muted">Nomor ini akan digunakan untuk login</small>
                        </div>
                    </div>

                    <!-- Bagian 7: Pernyataan -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="persetujuan" id="persetujuan" required>
                        <label class="form-check-label" for="persetujuan">
                            Saya menyatakan data yang diisi benar dan dapat dipertanggungjawabkan, serta setuju untuk mentaati segala peraturan Perpustakaan Digital
                        </label>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="daftar" class="btn btn-success btn-lg px-5">Daftar</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Fungsi untuk alamat sama dengan identitas
            document.getElementById('samaAlamat').addEventListener('change', function() {
                const alamatSaatIni = document.querySelector('[name="alamat_saat_ini"]');
                const provinsiSaatIni = document.querySelector('[name="provinsi_saat_ini"]');
                const kotaSaatIni = document.querySelector('[name="kota_saat_ini"]');
                
                if(this.checked) {
                    alamatSaatIni.value = "<?php echo isset($_POST['alamat_identitas']) ? $_POST['alamat_identitas'] : ''; ?>";
                    provinsiSaatIni.value = "<?php echo isset($_POST['provinsi']) ? $_POST['provinsi'] : ''; ?>";
                    kotaSaatIni.value = "<?php echo isset($_POST['kota']) ? $_POST['kota'] : ''; ?>";
                    
                    alamatSaatIni.readOnly = true;
                    provinsiSaatIni.disabled = true;
                    kotaSaatIni.disabled = true;
                } else {
                    alamatSaatIni.readOnly = false;
                    provinsiSaatIni.disabled = false;
                    kotaSaatIni.disabled = false;
                    
                    alamatSaatIni.value = "";
                    provinsiSaatIni.value = "";
                    kotaSaatIni.value = "";
                }
            });
        </script>

    <?php elseif ($page == 'login'): ?>
        <!-- Form Login -->
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="mb-0 text-center">Login Anggota</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
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
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Tampilan Default -->
        <div class="text-center py-5">
            <h4 class="text-muted">Selamat datang di Portal Anggota</h4>
            <p class="lead">
                <?php if(isset($_SESSION['anggota'])): ?>
                    Silakan pilih menu di atas untuk mulai menjelajah
                <?php else: ?>
                    Silakan daftar atau login untuk mengakses layanan
                <?php endif; ?>
            </p>
            <div class="mt-4">      
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>