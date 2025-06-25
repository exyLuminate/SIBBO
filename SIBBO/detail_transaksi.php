<?php
session_start();
require_once "config.php";
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: laporan.php");
    exit;
}

$id_transaksi = intval($_GET['id']);
$result = mysqli_query($link, "SELECT dt.*, b.nama_barang FROM DetailTransaksi dt 
    JOIN Barang b ON dt.id_barang = b.id_barang 
    WHERE dt.id_transaksi = $id_transaksi");
?>

<!DOCTYPE html>
<html>
<head><title>Detail Transaksi</title></head>
<body>
<h2>Detail Transaksi #<?= $id_transaksi ?></h2>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>Nama Barang</th>
        <th>Jumlah</th>
        <th>Subtotal</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <tr>
        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
        <td><?= $row['jumlah'] ?></td>
        <td><?= number_format($row['subtotal'], 2) ?></td>
    </tr>
    <?php } ?>
</table>

<p><a href="laporan.php">Kembali ke Laporan</a></p>
</body>
</html>
