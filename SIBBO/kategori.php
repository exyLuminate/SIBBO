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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="app-container">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class ="main-header">
            <h1>Manajemen Kategori</h1>
            <div class="admin-info">
                Selamat datang, <strong><?php echo htmlspecialchars( $_SESSION['username']); ?>!    </strong>
            </div>
        </header>
    

        <div class="content-body">

            <div class="card">
                <h3>Tambah Kategori Baru</h3>
                <!-- Form Tambah Kategori -->
                <form method="POST" action="" class="form-inline">
                <input type="text" name="nama_kategori" placeholder="Nama kategori">
                <button type="submit" name="add" class="btn btn-primary">Tambah Kategori</button>
                </form>
            </div>

            <div class="card">
                <div class="form-inline">
                     <!-- Form Cari Kategori -->
        <form method="GET" action="" style="display: contents;">
                    <input type="text" name="search" placeholder="Cari kategori.." value="">
                    <button type="submit" name="add" class="btn btn-primary">Cari Kategori</button>
                    <a href="kategori.php" class="btn btn-link">Reset</a>
                    </form>
                </div>

                <!-- Tabel Kategori -->
<table class="data-table">
<thead>
    <tr>
        <th>ID</th>
        <th>Nama Kategori</th>
        <th>Jumlah Barang</th>
        <th>Aksi</th>
    </tr>
</thead>

<tbody>  
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <tr data-id="<?= $row['id_kategori'] ?>">
        <td><?= $row['id_kategori'] ?></td>
        <td contenteditable="true" data-field="nama_kategori"><?= htmlspecialchars($row['nama_kategori']) ?></td>
        <td><?= $row['jumlah_barang'] ?></td>
        <td>
             <a href="kategori.php?delete=<?= $row['id_kategori'] ?>" onclick="return confirm('Hapus kategori ini?')" class="btn btn-danger">
        <i class="fas fa-trash"></i> </a>
        </td>
    </tr>
    <?php } ?>  
</tbody>
        </div>


</table>

</main>

</div>




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
