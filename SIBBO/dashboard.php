<?php
session_start();
require_once "config.php"; 

if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Statistik utama
// Ambil jumlah barang
$resultBarang = mysqli_query($link, "SELECT COUNT(*) as total FROM Barang");
$barangCount = mysqli_fetch_assoc($resultBarang)['total'] ?? 0;

// Ambil jumlah kategori
$resultKategori = mysqli_query($link, "SELECT COUNT(*) as total FROM Kategori");
$kategoriCount = mysqli_fetch_assoc($resultKategori)['total'] ?? 0;

// Ambil jumlah transaksi
$resultTransaksi = mysqli_query($link, "SELECT COUNT(*) as total FROM Transaksi");
$transaksiCount = mysqli_fetch_assoc($resultTransaksi)['total'] ?? 0;

// Ambil jumlah barang dengan stok rendah (misal stok <= 5)
$resultStokLow = mysqli_query($link, "SELECT COUNT(*) as total FROM Barang WHERE stok <= 5");
$stokLowCount = mysqli_fetch_assoc($resultStokLow)['total'] ?? 0;


// Statistik transaksi
$tglHariIni = date('Y-m-d');
$tglMingguIniAwal = date('Y-m-d', strtotime('monday this week'));
$tglBulanIniAwal = date('Y-m-01');

// Transaksi hari ini
$queryHariIni = "SELECT COUNT(*) AS total_hari_ini FROM Transaksi WHERE DATE(tanggal) = '$tglHariIni'";
$totalHariIni = mysqli_fetch_assoc(mysqli_query($link, $queryHariIni))['total_hari_ini'];

// Transaksi minggu ini
$queryMingguIni = "SELECT COUNT(*) AS total_minggu_ini FROM Transaksi WHERE DATE(tanggal) BETWEEN '$tglMingguIniAwal' AND '$tglHariIni'";
$totalMingguIni = mysqli_fetch_assoc(mysqli_query($link, $queryMingguIni))['total_minggu_ini'];

// Transaksi bulan ini
$queryBulanIni = "SELECT COUNT(*) AS total_bulan_ini FROM Transaksi WHERE DATE(tanggal) BETWEEN '$tglBulanIniAwal' AND '$tglHariIni'";
$totalBulanIni = mysqli_fetch_assoc(mysqli_query($link, $queryBulanIni))['total_bulan_ini'];

// Data untuk grafik penjualan
$queryPenjualan = "
    SELECT b.nama_barang, SUM(dt.jumlah) AS total_terjual
    FROM detailtransaksi dt
    JOIN Barang b ON dt.id_barang = b.id_barang
    GROUP BY dt.id_barang
    ORDER BY total_terjual DESC;
";

$penjualanData = mysqli_query($link, $queryPenjualan);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
   
    </style>
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

        <h1>Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>       


    <section>

        <h2>Statistik Utama</h2>
        <div class="stats">
            <div class="stat">
                <h3>Barang</h3>
                <p><?= $barangCount ?> Barang</p>
            </div>
            <div class="stat">
                <h3>Kategori</h3>
                <p><?= $kategoriCount ?> Kategori</p>
            </div>
            <div class="stat">
                <h3>Transaksi</h3>
                <p><?= $transaksiCount ?> Transaksi</p>
            </div>
            <div class="stat">
                <h3>Stok Rendah</h3>
                <p><?= $stokLowCount ?> Barang</p>
            </div>
        </div>

            <h2>Statistik Real-Time</h2>
<div>
    <p>Total Transaksi Hari Ini: <strong><?= $totalHariIni ?></strong></p>
    <p>Total Transaksi Minggu Ini: <strong><?= $totalMingguIni ?></strong></p>
    <p>Total Transaksi Bulan Ini: <strong><?= $totalBulanIni ?></strong></p>
</div>

        <h2>Grafik Penjualan</h2>
        <div>
    <label for="periode">Pilih Periode:</label>
    <select id="periode">
        <option value="day">Hari Ini</option>
        <option value="week">Minggu Ini</option>
        <option value="month">Bulan Ini</option>
    </select>
</div>

<canvas id="penjualanChart"></canvas>


    <script>
   $(document).ready(function () {
    const canvas = document.getElementById('penjualanChart');
    
    // Atur ukuran canvas (misal 50x50 pixel)
    canvas.width = 350;
    canvas.height = 350;
    
    const ctx = canvas.getContext('2d');
    let chart;

    function fetchData(period) {
        $.get(`get_chart_data.php?period=${period}`, function (data) {
            const chartData = JSON.parse(data);

            if (chart) {
                chart.destroy(); // hapus chart lama
            }

            chart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Penjualan Barang',
                        data: chartData.values,
                        backgroundColor: chartData.colors,
                    }]
                },
                options: {
                    responsive: false,  // nonaktifkan responsif supaya canvas pakai ukuran manual
                    maintainAspectRatio: false,
                }
            });
        });
    }

    fetchData('day');

    $('#periode').on('change', function () {
        fetchData($(this).val());
    });
});

</script>

    </section>
</body>
</html>