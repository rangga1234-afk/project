<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Beranda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Halo, <?php echo $_SESSION['username']; ?>! Selamat datang di Perpustakaan Digital.</h2>
    <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
</body>
</html>
