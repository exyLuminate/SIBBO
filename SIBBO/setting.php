<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_SESSION['username'];
    $old_password = md5($_POST['old_password']);  // hash password lama yang diinput user
    $new_password = md5($_POST['new_password']);  // hash password baru

    // Cek password lama
    $result = mysqli_query($link, "SELECT password FROM Admin WHERE username = '$username'");
    $row = mysqli_fetch_assoc($result);

    if ($row && $row['password'] === $old_password) {
        // Password lama cocok, update password baru
        mysqli_query($link, "UPDATE Admin SET password = '$new_password' WHERE username = '$username'");
        echo "<p style='color: green;'>Password berhasil diubah!</p>";
    } else {
        // Password lama salah
        echo "<p style='color: red;'>Password lama tidak cocok. Silakan coba lagi.</p>";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Setting Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div class="header-left">
             <h1>Setting Admin</h1>
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

<form method="POST" action="">
    <label>Password Lama:</label><br>
    <input type="password" name="old_password" required><br><br>

    <label>Password Baru:</label><br>
    <input type="password" name="new_password" required><br><br>

    <button type="submit">Update Password</button>
</form>


<p><a href="dashboard.php">Kembali ke Dashboard</a></p>
</body>
</html>
