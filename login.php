<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $identifier = $_POST['email_or_phone'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ? OR no_hp = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;

            // Catat login ke tabel login_history
            $user_id = $user['id'];
            $sqlHistory = "INSERT INTO login_history (user_id, login_time) VALUES (?, NOW())";
            $stmtHistory = $conn->prepare($sqlHistory);
            if ($stmtHistory) {
                $stmtHistory->bind_param("i", $user_id);
                $stmtHistory->execute();
            }

            header("Location: index.php");
            exit;
        } else {
            $login_error = "Email / No HP atau password salah.";
        }
    } else {
        $login_error = "Terjadi kesalahan: " . $conn->error;
    }
}
?>
