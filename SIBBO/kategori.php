<?php
session_start();
require_once "config.php";
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Proses pengeditan kategori via AJAX
if (isset($_POST['update_field'])) {
    $id_kategori = intval($_POST['id_kategori']);
    $field = mysqli_real_escape_string($link, $_POST['field']);
    $value = mysqli_real_escape_string($link, $_POST['value']);

    // Validasi field yang diperbolehkan
    $allowed_fields = ['nama_kategori'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['status' => 'error', 'message' => 'Field tidak valid']);
        exit;
    }

    // Cek duplikasi nama kategori
    if ($field === 'nama_kategori') {
        $checkQuery = "SELECT COUNT(*) AS count FROM Kategori WHERE LOWER($field) = LOWER('$value') AND id_kategori != $id_kategori";
        $checkResult = mysqli_fetch_assoc(mysqli_query($link, $checkQuery));
        if ($checkResult['count'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nama kategori sudah ada']);
            exit;
        }
    }

    // Update database
    $query = "UPDATE Kategori SET $field = '$value' WHERE id_kategori = $id_kategori";
    if (mysqli_query($link, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data']);
    }
    exit;
}

// Tambah kategori
if (isset($_POST['add'])) {
    $nama_kategori = mysqli_real_escape_string($link, $_POST['nama_kategori']);
    if ($nama_kategori != "") {
        $checkQuery = "SELECT COUNT(*) AS count FROM Kategori WHERE LOWER(nama_kategori) = LOWER('$nama_kategori')";
        $checkResult = mysqli_fetch_assoc(mysqli_query($link, $checkQuery));
        if ($checkResult['count'] == 0) {
            mysqli_query($link, "INSERT INTO Kategori (nama_kategori) VALUES ('$nama_kategori')");
        } else {
            echo "<script>alert('Kategori dengan nama ini sudah ada!');</script>";
        }
    }
    header("Location: kategori.php");
    exit;
}

// Hapus kategori
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($link, "DELETE FROM Kategori WHERE id_kategori = $id");
    header("Location: kategori.php");
    exit;
}

// Pencarian kategori
$searchName = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
$query = "
    SELECT k.id_kategori, k.nama_kategori, COUNT(b.id_barang) AS jumlah_barang
    FROM Kategori k
    LEFT JOIN Barang b ON k.id_kategori = b.id_kategori
";
if ($searchName !== '') {
    $query .= " WHERE LOWER(k.nama_kategori) LIKE '%" . strtolower($searchName) . "%'";
}
$query .= " GROUP BY k.id_kategori ORDER BY k.nama_kategori ASC";
$result = mysqli_query($link, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Kategori</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<header>
    <div class="header-left">
        <h1>Manajemen Kategori</h1>
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

<h2>Manajemen Kategori</h2>

<!-- Form Pencarian Kategori -->
<form method="GET" action="">
    <input type="text" name="search" placeholder="Cari kategori" value="<?= htmlspecialchars($searchName) ?>">
    <button type="submit">Cari</button>
    <a href="kategori.php">Reset</a>
</form>

<!-- Form Tambah Kategori -->
<form method="POST" action="">
    <input type="text" name="nama_kategori" placeholder="Nama kategori" required>
    <button type="submit" name="add">Tambah Kategori</button>
</form>



<!-- Tabel Kategori -->
<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nama Kategori</th>
        <th>Jumlah Barang</th>
        <th>Aksi</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <tr data-id="<?= $row['id_kategori'] ?>">
        <td><?= $row['id_kategori'] ?></td>
        <td contenteditable="true" data-field="nama_kategori"><?= htmlspecialchars($row['nama_kategori']) ?></td>
        <td><?= $row['jumlah_barang'] ?></td>
        <td>
            <a href="kategori.php?delete=<?= $row['id_kategori'] ?>" onclick="return confirm('Hapus kategori ini?')">Hapus</a>
        </td>
    </tr>
    <?php } ?>
</table>



<div id="notification"></div>

<script>
    $(document).ready(function() {
        function showNotification(message, isError = false) {
            const notification = $('#notification');
            notification.text(message);
            notification.removeClass('error');

            if (isError) {
                notification.addClass('error');
            }

            notification.fadeIn(200); // Tampilkan notifikasi
            setTimeout(() => {
                notification.fadeOut(500); // Hilangkan notifikasi setelah 2 detik
            }, 2000);
        }

        $('[contenteditable]').on('blur', function() {
            const row = $(this).closest('tr');
            const id = row.data('id');
            const field = $(this).data('field');
            const value = $(this).text().trim();

            $.post('kategori.php', { update_field: true, id_kategori: id, field: field, value: value }, function(response) {
                const result = JSON.parse(response);
                if (result.status === 'success') {
                    showNotification('Data berhasil disimpan');
                } else {
                    showNotification('Gagal menyimpan data: ' + result.message, true);
                }
            });
        });
    });
</script>

</body>
</html>
