<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis      = $_POST['jenis'] ?? '';
    $jumlah     = floatval($_POST['jumlah'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (in_array($jenis, ['masuk', 'keluar']) && $jumlah > 0) {
        $stmtInsert = $pdo->prepare("INSERT INTO transaksi (user_id, jenis, jumlah, keterangan) VALUES (:user_id, :jenis, :jumlah, :keterangan)");
        $stmtInsert->execute([
            ':user_id'    => $user_id,
            ':jenis'      => $jenis,
            ':jumlah'     => $jumlah,
            ':keterangan' => $keterangan
        ]);
    }
    
    header("Location: index.php");
    exit();
}

$stmtMasuk = $pdo->prepare("SELECT SUM(jumlah) AS total FROM transaksi WHERE user_id = :user_id AND jenis = 'masuk'");
$stmtMasuk->execute([':user_id' => $user_id]);
$totalMasuk = $stmtMasuk->fetch()['total'] ?? 0;

$stmtKeluar = $pdo->prepare("SELECT SUM(jumlah) AS total FROM transaksi WHERE user_id = :user_id AND jenis = 'keluar'");
$stmtKeluar->execute([':user_id' => $user_id]);
$totalKeluar = $stmtKeluar->fetch()['total'] ?? 0;

$saldoAkhir = $totalMasuk - $totalKeluar;

$stmtRiwayat = $pdo->prepare("SELECT * FROM transaksi WHERE user_id = :user_id ORDER BY tanggal DESC");
$stmtRiwayat->execute([':user_id' => $user_id]);
$riwayat = $stmtRiwayat->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABUNGAN HFY ONLY</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f5f7fa; color: #4a5568; padding: 25px 15px; }
        .container { max-width: 950px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 18px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 25px; }
        .header h1 { font-size: 20px; color: #2d3748; font-weight: 600; }
        .btn-logout { background-color: #edf2f7; color: #e53e3e; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; }
        .btn-logout:hover { background-color: #fed7d7; }

        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 25px; }
        .card { background: #ffffff; padding: 22px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-left: 5px solid transparent; }
        .card h3 { font-size: 12px; color: #a0aec0; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
        .card p { font-size: 24px; font-weight: 700; }
        
        .card.saldo { border-left-color: #319795; }
        .card.saldo p { color: #2c7a7b; }
        .card.masuk { border-left-color: #3182ce; }
        .card.masuk p { color: #2b6cb0; }
        .card.keluar { border-left-color: #dd6b20; }
        .card.keluar p { color: #c05621; }

        .content { display: grid; grid-template-columns: 1fr 1.8fr; gap: 20px; }
        @media (max-width: 768px) { .content { grid-template-columns: 1fr; } }
        
        .box { background: #ffffff; padding: 22px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .box h2 { font-size: 16px; margin-bottom: 18px; color: #2d3748; font-weight: 600; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 14px; }
        label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #4a5568; }
        input[type="number"], input[type="text"], select { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #fafbfe; font-size: 14px; outline: none; }
        
        button { width: 100%; padding: 11px; background: #319795; border: none; color: white; font-weight: 600; border-radius: 8px; cursor: pointer; font-size: 14px; margin-top: 5px; }
        button:hover { background: #2c7a7b; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table th, table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #edf2f7; }
        table th { color: #a0aec0; font-size: 11px; text-transform: uppercase; }
        
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-masuk { background-color: #e6fffa; color: #234e52; }
        .badge-keluar { background-color: #feebc8; color: #742a2a; }
        
        .btn-delete { color: #e53e3e; text-decoration: none; font-weight: 600; font-size: 12px; padding: 4px 8px; border-radius: 4px; background: #fff5f5; cursor: pointer; }
        .btn-delete:hover { background: #fed7d7; }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-box {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .modal-box h3 { margin-bottom: 8px; color: #2d3748; font-size: 16px; }
        .modal-box p { font-size: 14px; color: #718096; margin-bottom: 20px; }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; }
        .btn-cancel { background: #edf2f7; color: #4a5568; padding: 8px 16px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; }
        .btn-confirm { background: #e53e3e; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Halo, <?= htmlspecialchars($user_nama) ?> ✨</h1>
        <a href="logout.php" class="btn-logout">Keluar</a>
    </div>

    <div class="cards">
        <div class="card saldo">
            <h3>Sisa Saldo</h3>
            <p>Rp <?= number_format($saldoAkhir, 0, ',', '.') ?></p>
        </div>
        <div class="card masuk">
            <h3>Total Setoran</h3>
            <p>Rp <?= number_format($totalMasuk, 0, ',', '.') ?></p>
        </div>
        <div class="card keluar">
            <h3>Total Penarikan</h3>
            <p>Rp <?= number_format($totalKeluar, 0, ',', '.') ?></p>
        </div>
    </div>

    <div class="content">
        <div class="box">
            <h2>+ Tambah Transaksi</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Jenis Transaksi</label>
                    <select name="jenis" required>
                        <option value="masuk">Setor (Pemasukan)</option>
                        <option value="keluar">Tarik (Pengeluaran)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nominal (Rp)</label>
                    <input type="number" name="jumlah" min="1" required placeholder="Contoh: 50000">
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" placeholder="Contoh: Tabungan uang saku" required>
                </div>
                <button type="submit">Simpan Data</button>
            </form>
        </div>

        <div class="box">
            <h2>Riwayat Transaksi</h2>
            <?php if (count($riwayat) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Ket.</th>
                            <th>Jumlah</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $row): ?>
                            <tr>
                                <td><?= date('d/m/y H:i', strtotime($row['tanggal'])) ?></td>
                                <td>
                                    <?php if ($row['jenis'] === 'masuk'): ?>
                                        <span class="badge badge-masuk">SETOR</span>
                                    <?php else: ?>
                                        <span class="badge badge-keluar">TARIK</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td><strong>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></strong></td>
                                <td>
                                    <a href="javascript:void(0)" class="btn-delete" onclick="bukaModal('hapus.php?id=<?= $row['id'] ?>')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #a0aec0; margin-top: 30px; font-size: 14px;">Belum ada riwayat transaksi.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="customModal" class="modal-overlay">
    <div class="modal-box">
        <h3>Konfirmasi Hapus</h3>
        <p>Apakah kamu yakin ingin menghapus transaksi ini?</p>
        <div class="modal-buttons">
            <button class="btn-cancel" onclick="tutupModal()">Batal</button>
            <a id="linkHapus" href="#" class="btn-confirm">Hapus</a>
        </div>
    </div>
</div>

<script>
function bukaModal(url) {
    document.getElementById('linkHapus').href = url;
    document.getElementById('customModal').style.display = 'flex';
}

function tutupModal() {
    document.getElementById('customModal').style.display = 'none';
}
</script>

</body>
</html>