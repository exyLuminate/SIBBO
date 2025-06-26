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
<html lang="id">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    </style>
</head>

<body>

       <div class="app-container">
        <?php include 'sidebar.php'; // Kita akan buat file sidebar terpisah ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Dashboard</h1>
                <div class="admin-info">
                    Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?>!</strong>
                </div>
            </header>

            <div class="content-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon barang"><i class="fas fa-box"></i></div>
                        <div class="info"><h3>Total Barang</h3><p><?= $barangCount ?></p></div>
                    </div>
                    <div class="stat-card">
                        <div class="icon kategori"><i class="fas fa-tags"></i></div>
                        <div class="info"><h3>Total Kategori</h3><p><?= $kategoriCount ?></p></div>
                    </div>
                    <div class="stat-card">
                        <div class="icon transaksi"><i class="fas fa-shopping-cart"></i></div>
                        <div class="info"><h3>Total Transaksi</h3><p><?= $transaksiCount ?></p></div>
                    </div>
                     <div class="stat-card">
                        <div class="icon stok-rendah"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="info"><h3>Stok Rendah</h3><p><?= $stokLowCount ?></p></div>
                    </div>
                </div>
                
                <div class="card">
                    <h2>Grafik Penjualan</h2>
                    <div class="form-inline" style="justify-content: flex-end;">
                        <label for="periode">Periode:</label>
                        <select id="periode">
                            <option value="day">Hari Ini</option>
                            <option value="week">Minggu Ini</option>
                            <option value="month">Bulan Ini</option>
                        </select>
                    </div>
                    <canvas id="penjualanChart" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </main>
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