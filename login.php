<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan Password wajib diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];

            header("Location: index.php");
            exit();
        } else {
            $error = 'Email atau Password salah!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Web Tabungan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f5f7fa; color: #4a5568; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: #ffffff; padding: 30px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); width: 100%; max-width: 380px; }
        h2 { text-align: center; color: #2d3748; margin-bottom: 24px; font-weight: 600; font-size: 22px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; color: #4a5568; font-size: 13px; font-weight: 600; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #fafbfe; font-size: 14px; outline: none; transition: border 0.2s; }
        input[type="email"]:focus, input[type="password"]:focus { border-color: #319795; background-color: #fff; }
        button { width: 100%; padding: 11px; background-color: #319795; border: none; color: white; font-size: 14px; font-weight: 600; border-radius: 8px; cursor: pointer; margin-top: 8px; transition: background 0.2s; }
        button:hover { background-color: #2c7a7b; }
        .alert { padding: 10px 14px; margin-bottom: 18px; border-radius: 8px; font-size: 13px; background-color: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        .footer-text { text-align: center; margin-top: 20px; font-size: 13px; color: #718096; }
        .footer-text a { color: #319795; text-decoration: none; font-weight: 600; }
        .footer-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="card">
    <h2>Login Akun ✨</h2>

    <?php if (!empty($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Masukkan email kamu" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required>
        </div>

        <button type="submit">Masuk</button>
    </form>

    <div class="footer-text">
        Belum punya akun? <a href="register.php">Daftar di sini</a>
    </div>
</div>

</body>
</html>