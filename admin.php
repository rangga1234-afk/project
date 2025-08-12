<?php
session_start();
include 'config.php';

// --- LOGIN PROSES ---
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    $data = mysqli_fetch_assoc($cek);

    if ($data && md5($password) == $data['password'] && ($data['role'] == 'admin' || $data['role'] == 'pustakawan')) {
        $_SESSION['role'] = $data['role'];
        $_SESSION['username'] = $data['username'];
        header("Location: admin.php");
        exit;
    } else {
        $error = "Login gagal! Username atau password salah.";
    }
}

if (isset($_POST['ubah_status']) && isset($_POST['anggota_id']) && isset($_POST['status'])) {
    $id = intval($_POST['anggota_id']);
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE anggota SET status='$status' WHERE id=$id");
}

// --- TAMBAH BUKU ---
if (isset($_POST['tambah_buku'])) {
    $judul = $_POST['judul'];
    $stok = $_POST['stok'];

    $cover_name = $_FILES['cover']['name'];
    $cover_tmp = $_FILES['cover']['tmp_name'];
    $cover_path = "uploads/covers/" . uniqid() . '_' . $cover_name;

    if (move_uploaded_file($cover_tmp, $cover_path)) {
        $pdf_name = $_FILES['pdf']['name'];
        $pdf_tmp = $_FILES['pdf']['tmp_name'];
        $pdf_path = "uploads/pdfs/" . uniqid() . '_' . $pdf_name;

        if (move_uploaded_file($pdf_tmp, $pdf_path)) {
            $sql = "INSERT INTO buku (judul, cover, file_pdf, stok) VALUES ('$judul', '$cover_path', '$pdf_path', '$stok')";
            if (!$conn->query($sql)) {
                $error = "Error: " . $conn->error;
            } else {
                header("Location: admin.php");
                exit;
            }
        } else {
            $error = "Gagal mengupload file PDF";
        }
    } else {
        $error = "Gagal mengupload cover buku";
    }
}

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// --- PROTEKSI HALAMAN ADMIN ---
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'pustakawan')) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <a href="index.php" class="btn btn-secondary mb-3">← Kembali ke Beranda</a>
    </div>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header text-center">Login Admin / Pustakawan</div>
                <div class="card-body">
                    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100" name="login">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

// --- Aksi CRUD ---
if (isset($_GET['hapus_anggota'])) {
    $id = $_GET['hapus_anggota'];
    mysqli_query($conn, "DELETE FROM anggota WHERE id='$id'");
    header("Location: admin.php");
}

if (isset($_GET['hapus_peminjam'])) {
    $id = $_GET['hapus_peminjam'];
    mysqli_query($conn, "DELETE FROM peminjaman WHERE id='$id'");
    header("Location: admin.php");
}

if (isset($_POST['ubah_status'])) {
    $anggota_id = $_POST['anggota_id'];
    $status_baru = $_POST['status'];

    // Update status di database
    $update = mysqli_query($conn, "UPDATE anggota SET status='$status_baru' WHERE id='$anggota_id'");
    if ($update) {
        echo "<script>location.href='admin.php';</script>"; // reload supaya tidak resubmit form
    } else {
        echo "<div class='alert alert-danger'>Gagal mengubah status.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Halo, <?= htmlspecialchars($_SESSION['username']); ?> (<?= $_SESSION['role']; ?>)</h2>
    <a href="admin.php?logout=true" class="btn btn-danger mb-3">Logout</a>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#tambahBukuModal">+ Tambah Buku</button>

    <!-- Data Anggota -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">Data Anggota</div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th>Nama</th><th>Nomor</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php
            $anggota = mysqli_query($conn, "SELECT * FROM anggota");
            while ($a = mysqli_fetch_assoc($anggota)) {
                // Cegah error jika 'status' tidak ada
                $status = isset($a['status']) ? $a['status'] : 'aktif';

                echo "<tr>
                    <td>{$a['nama']}</td>
                    <td>{$a['nomor_anggota']}</td>
                    <td>
                        <form method='post' class='d-flex'>
                            <input type='hidden' name='anggota_id' value='{$a['id']}'>
                            <select name='status' class='form-select form-select-sm me-2' onchange='this.form.submit()'>
                                <option value='aktif' " . ($status == 'aktif' ? 'selected' : '') . ">aktif</option>
                                <option value='nonaktif' " . ($status == 'nonaktif' ? 'selected' : '') . ">nonaktif</option>
                                <option value='diblokir' " . ($status == 'diblokir' ? 'selected' : '') . ">diblokir</option>
                            </select>
                            <input type='hidden' name='ubah_status' value='1'>
                        </form>
                    </td>
                    <td>
                        <a href='?hapus_anggota={$a['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Hapus anggota ini?')\">Hapus</a>
                    </td>
                </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
   

    <!-- Data Peminjam -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Data Peminjaman</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Nama Peminjam</th><th>Buku</th><th>Tgl Pinjam</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php
                $peminjam = mysqli_query($conn, "
                    SELECT p.id, a.nama, b.judul AS judul_buku, p.tanggal_pinjam
                    FROM peminjaman p
                    JOIN anggota a ON p.id_anggota = a.id
                    JOIN buku b ON p.id_buku = b.id
                ");
                while ($p = mysqli_fetch_assoc($peminjam)) {
                    echo "<tr>
                        <td>{$p['nama']}</td>
                        <td>{$p['judul_buku']}</td>
                        <td>{$p['tanggal_pinjam']}</td>
                        <td><a href='?hapus_peminjam={$p['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Hapus data ini?')\">Hapus</a></td>
                    </tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Peminjaman -->
    <div class="card mb-5">
        <div class="card-header bg-secondary text-white">Riwayat Peminjaman</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>Nama</th><th>id_buku</th><th>Tgl Pinjam</th><th>Tgl Kembali</th></tr></thead>
                <tbody>
                <?php
                $riwayat = mysqli_query($conn, "SELECT * FROM peminjaman");
                while ($r = mysqli_fetch_assoc($riwayat)) {
                    echo "<tr>
                        <td>{$r['nama_peminjam']}</td>
                        <td>{$r['id_buku']}</td>
                        <td>{$r['tanggal_pinjam']}</td>
                        <td>{$r['tanggal_kembali']}</td>
                    </tr>";
                }
                ?>
                </tbody>
            </table>
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
                        <input type="text" class="form-control" name="judul" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover Buku</label>
                        <input type="file" class="form-control" name="cover" accept="image/*" required>
                        <small class="text-muted">Format: JPG, PNG</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File PDF Buku</label>
                        <input type="file" class="form-control" name="pdf" accept=".pdf" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Buku</label>
                        <input type="number" class="form-control" name="stok" min="1" required>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
