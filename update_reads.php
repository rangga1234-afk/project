<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $bookId = (int)$_POST['id'];
    
    $query = "UPDATE buku SET dibaca = dibaca + 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bookId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>