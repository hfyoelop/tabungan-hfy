<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id_transaksi = $_GET['id'];
    $user_id      = $_SESSION['user_id'];

    // Menghapus data transaksi sesuai ID dan User
    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id'      => $id_transaksi,
        ':user_id' => $user_id
    ]);
}

header("Location: index.php");
exit();
?>