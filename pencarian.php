<?php
require_once 'config.php';

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

if (!empty($keyword)) {
    $stmt = $conn->prepare("SELECT * FROM buku WHERE judul LIKE ?");
    $search = "%$keyword%";
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM buku");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perpustakaan Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 300px;
            object-fit: cover;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Perpustakaan Digital</h2>

    <!-- Form Pencarian -->
    <form class="mb-4" method="GET" action="index.php">
        <div class="input-group">
            <input type="text" class="form-control" name="keyword" placeholder="Cari judul buku..." value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-primary" type="submit">Cari</button>
            <a href="index.php" class="btn btn-secondary">Beranda</a>
        </div>
    </form>

    <!-- Daftar Buku -->
    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($data = $result->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <img src="uploads/gambar/<?= htmlspecialchars($data['gambar']) ?>.png" class="card-img-top" alt="<?= htmlspecialchars($data['judul']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($data['judul']) ?></h5>
                            <a href="baca.php?id=<?= $data['id'] ?>" class="btn btn-sm btn-success">Baca</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Tidak ada buku ditemukan.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
