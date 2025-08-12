<?php
require_once 'config.php';

if (isset($_POST['tambah_stok'])) {
    $buku_id = intval($_POST['buku_id']);
    $stok_baru = intval($_POST['stok']);

    if ($buku_id > 0 && $stok_baru > 0) {
        $query = "UPDATE buku SET stok = stok + $stok_baru WHERE id = $buku_id";
        $result = mysqli_query($conn, $query);

        if ($result) {
            echo "<script>alert('Stok berhasil ditambahkan!'); window.location.href='main.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan stok'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Data tidak valid'); window.history.back();</script>";
    }
}
?>
