<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmtCek->execute([':email' => $email]);

        if ($stmtCek->rowCount() > 0) {
            $error = 'Email sudah terdaftar, silakan gunakan email lain!';
        } else {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $stmtInsert = $pdo->prepare("INSERT INTO users (nama, email, password) VALUES (:nama, :email, :password)");
            $simpan = $stmtInsert->execute([
                ':nama'     => $nama,
                ':email'    => $email,
                ':password' => $passwordHash
            ]);

            if ($simpan) {
                $sukses = 'Pendaftaran berhasil! Silakan <a href="login.php">login di sini</a>.';
            } else {
                $error = 'Gagal mendaftar, terjadi kesalahan sistem.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Web Tabungan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f5f7fa; color: #4a5568; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: #ffffff; padding: 30px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); width: 100%; max-width: 380px; }
        h2 { text-align: center; color: #2d3748; margin-bottom: 24px; font-weight: 600; font-size: 22px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; color: #4a5568; font-size: 13px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #fafbfe; font-size: 14px; outline: none; transition: border 0.2s; }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus { border-color: #319795; background-color: #fff; }
        button { width: 100%; padding: 11px; background-color: #319795; border: none; color: white; font-size: 14px; font-weight: 600; border-radius: 8px; cursor: pointer; margin-top: 8px; transition: background 0.2s; }
        button:hover { background-color: #2c7a7b; }
        .alert { padding: 10px 14px; margin-bottom: 18px; border-radius: 8px; font-size: 13px; }
        .alert-danger { background-color: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        .alert-success { background-color: #e6fffa; color: #234e52; border: 1px solid #b2f5ea; }
        .alert-success a { color: #319795; font-weight: bold; }
        .footer-text { text-align: center; margin-top: 20px; font-size: 13px; color: #718096; }
        .footer-text a { color: #319795; text-decoration: none; font-weight: 600; }
        .footer-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="card">
    <h2>Registrasi Akun ✨</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($sukses)): ?>
        <div class="alert alert-success"><?= $sukses ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Masukkan email" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Buat password baru" required>
        </div>

        <button type="submit">Daftar</button>
    </form>

    <div class="footer-text">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>
</div>

</body>
</html>