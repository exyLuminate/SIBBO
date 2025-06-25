<?php
require_once "config.php";

$period = isset($_GET['period']) ? $_GET['period'] : 'day';

if ($period == 'day') {
    $query = "
        SELECT b.nama_barang, SUM(dt.jumlah) AS jumlah 
        FROM DetailTransaksi dt 
        JOIN Barang b ON dt.id_barang = b.id_barang 
        JOIN Transaksi t ON dt.id_transaksi = t.id_transaksi 
        WHERE DATE(t.tanggal) = CURDATE() 
        GROUP BY b.id_barang
    ";
} elseif ($period == 'week') {
    $query = "
        SELECT b.nama_barang, SUM(dt.jumlah) AS jumlah 
        FROM DetailTransaksi dt 
        JOIN Barang b ON dt.id_barang = b.id_barang 
        JOIN Transaksi t ON dt.id_transaksi = t.id_transaksi 
        WHERE WEEK(t.tanggal) = WEEK(CURDATE()) 
        GROUP BY b.id_barang
    ";
} elseif ($period == 'month') {
    $query = "
        SELECT b.nama_barang, SUM(dt.jumlah) AS jumlah 
        FROM DetailTransaksi dt 
        JOIN Barang b ON dt.id_barang = b.id_barang 
        JOIN Transaksi t ON dt.id_transaksi = t.id_transaksi 
        WHERE MONTH(t.tanggal) = MONTH(CURDATE()) 
        GROUP BY b.id_barang
    ";
}

$result = mysqli_query($link, $query);

$labels = [];
$values = [];
$colors = [];

while ($row = mysqli_fetch_assoc($result)) {
    $labels[] = $row['nama_barang'];
    $values[] = $row['jumlah'];
    $colors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF)); // Random color
}

echo json_encode(['labels' => $labels, 'values' => $values, 'colors' => $colors]);
?>
