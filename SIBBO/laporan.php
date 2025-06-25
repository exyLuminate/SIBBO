<?php
session_start();
require_once "config.php";
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];

// Inisialisasi variabel pencarian
$searchMetode = isset($_GET['metode']) ? intval($_GET['metode']) : 0;
$searchDate = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
$searchAdmin = isset($_GET['admin']) ? $_GET['admin'] : '';


// Query pencarian
$query = "
    SELECT t.id_transaksi, t.tanggal, SUM(dt.subtotal) AS total_harga, a.username AS admin_username, mp.nama_metode
    FROM Transaksi t
    LEFT JOIN DetailTransaksi dt ON t.id_transaksi = dt.id_transaksi
    LEFT JOIN Admin a ON t.id_admin = a.id_admin
    LEFT JOIN MetodePembayaran mp ON t.id_metode = mp.id_metode
    WHERE 1=1
";

if ($searchDate !== '') {
    $query .= " AND DATE(t.tanggal) = '$searchDate'";
}

if ($searchAdmin !== '') {
    $query .= " AND LOWER(a.username) LIKE '%" . strtolower($searchAdmin) . "%'";
}

if ($searchMetode !== 0) {
    $query .= " AND t.id_metode = $searchMetode";
}

$query .= " GROUP BY t.id_transaksi, t.tanggal, a.username, mp.nama_metode ORDER BY t.tanggal DESC";



$result = mysqli_query($link, $query);
?>



<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
        <div class="header-left">
             <h1>Dashboard</h1>
        </div>
        <nav class="header-right">
            <a href="dashboard.php"> Dashboard</a>
            <a href="barang.php">Manajemen Barang</a>
            <a href="kategori.php">Manajemen Kategori</a>
            <a href="transaksi.php">Transaksi Penjualan</a>
            <a href="laporan.php">Laporan Penjualan</a>
            <a href="setting.php">Pengaturan</a>
            <a href="logout.php" style="color: red;">Logout</a>
        </nav>
    </header>

<h2>Laporan Penjualan</h2>

<!-- Form Pencarian -->
<form method="GET" action="">
    <label for="tanggal">Tanggal:</label>
    <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($searchDate) ?>">
    
        <label for="admin">Admin:</label>
    <input type="text" id="admin" name="admin" placeholder="Cari berdasarkan admin" value="<?= htmlspecialchars($searchAdmin) ?>">

    
    <label for="metode">Metode Pembayaran:</label>
    <select id="metode" name="metode">
        <option value="">-- Semua Metode --</option>
        <?php
        $metodeResult = mysqli_query($link, "SELECT * FROM MetodePembayaran");
        while ($metode = mysqli_fetch_assoc($metodeResult)) {
            $selected = ($metode['id_metode'] == $searchMetode) ? 'selected' : '';
            echo "<option value='{$metode['id_metode']}' $selected>" . htmlspecialchars($metode['nama_metode']) . "</option>";
        }
        ?>
    </select>
    
    <button type="submit">Cari</button>
    <a href="laporan.php">Reset</a>
</form>

<!-- Tabel Laporan -->
<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID Transaksi</th>
        <th>Tanggal</th>
        <th>Total Harga</th>
        <th>Admin</th>
        <th>Metode Pembayaran</th>
        <th>Detail</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
   <tr>
    <td><?= $row['id_transaksi'] ?></td>
    <td><?= $row['tanggal'] ?></td>
    <td><?= number_format($row['total_harga'], 2) ?></td>
<td><?= htmlspecialchars($row['admin_username']) ?></td>
    <td><?= htmlspecialchars($row['nama_metode']) ?></td>
    <td><a href="detail_transaksi.php?id=<?= $row['id_transaksi'] ?>">Lihat Detail</a></td>
</tr>

    <?php } ?>
</table>

<p><a href="dashboard.php">Kembali ke Dashboard</a></p>
</body>
</html>
