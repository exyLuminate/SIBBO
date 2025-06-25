<?php
// Konfigurasi database
$host = "localhost"; // Nama host
$user = "root"; // Username MySQL (default: root)
$password = ""; // Password MySQL (default: kosong untuk localhost)
$database = "SIBBO"; // Nama database Anda

// Koneksi ke database
$link = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$link) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
