<?php
session_start();
require_once "config.php";
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Proses perubahan data barang via AJAX
if (isset($_POST['update_field'])) {
    $id_barang = intval($_POST['id_barang']);
    $field = mysqli_real_escape_string($link, $_POST['field']);
    $value = mysqli_real_escape_string($link, $_POST['value']);

    // Validasi field yang diperbolehkan untuk diubah
    $allowed_fields = ['nama_barang', 'id_kategori', 'harga', 'stok'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['status' => 'error', 'message' => 'Field tidak valid']);
        exit;
    }

    // Update database
    $query = "UPDATE Barang SET $field = '$value' WHERE id_barang = $id_barang";
    if (mysqli_query($link, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data']);
    }
    exit;
}

// Tambah barang
if (isset($_POST['add'])) {
    $nama_barang = mysqli_real_escape_string($link, $_POST['nama_barang']);
    $id_kategori = intval($_POST['id_kategori']);
    $harga = floatval($_POST['harga']);
    $stok = intval($_POST['stok']);

    if ($nama_barang != "" && $id_kategori > 0) {
        mysqli_query($link, "INSERT INTO Barang (nama_barang, id_kategori, harga, stok) VALUES ('$nama_barang', $id_kategori, $harga, $stok)");
    }
    header("Location: barang.php");
    exit;
}

// Hapus barang
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($link, "DELETE FROM Barang WHERE id_barang = $id");
    header("Location: barang.php");
    exit;
}

// Pencarian
$search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : "";

// Ambil data barang dengan filter pencarian
$query = "SELECT b.id_barang, b.nama_barang, b.id_kategori, k.nama_kategori, b.harga, b.stok 
          FROM Barang b 
          JOIN Kategori k ON b.id_kategori = k.id_kategori";

if ($search) {
    $query .= " WHERE b.nama_barang LIKE '%$search%' OR k.nama_kategori LIKE '%$search%'";
}

$result = mysqli_query($link, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Barang</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Manajemen Barang</h1>
                 <div class="admin-info">
                    Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?>!</strong>
                </div>
            </header>

            <div class="content-body">
                <div class="card">
                    <h3>Tambah Barang Baru</h3>
                    <form method="POST" action="" class="form-inline">
                        <input type="text" name="nama_barang" placeholder="Nama barang" required>
                        <select name="id_kategori" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php /* PHP loop untuk kategori */ ?>
                        </select>
                        <input type="number" name="harga" placeholder="Harga" step="0.01" min="0" required>
                        <input type="number" name="stok" placeholder="Stok" min="0" required>
                        <button type="submit" name="add" class="btn btn-primary">Tambah</button>
                    </form>
                </div>

                <div class="card">
                    <div class="form-inline">
                         <form method="GET" action="" style="display: contents;">
                             <input type="text" name="search" placeholder="Cari barang atau kategori" value="<?= htmlspecialchars($search) ?>">
                             <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                             <a href="barang.php" class="btn btn-link">Reset</a>
                         </form>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr data-id="<?= $row['id_barang'] ?>">
                                <td><?= $row['id_barang'] ?></td>
                                <td contenteditable="true" data-field="nama_barang"><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td>
                                    <select data-field="id_kategori">
                                        <?php /* PHP loop untuk kategori dengan selected */ ?>
                                    </select>
                                </td>
                                <td contenteditable="true" data-field="harga"><?= number_format($row['harga'], 2) ?></td>
                                <td contenteditable="true" data-field="stok"><?= $row['stok'] ?></td>
                                <td>
                                    <a href="barang.php?delete=<?= $row['id_barang'] ?>" onclick="return confirm('Hapus barang ini?')" class="btn btn-danger"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="notification"></div>

   <script>
    function showNotification(message, isError = false) {
        const notification = $('#notification');
        notification
            .text(message)
            .removeClass('error')
            .addClass(isError ? 'error' : '')
            .fadeIn();

        setTimeout(() => {
            notification.fadeOut();
        }, 2000); // Menghilang setelah 2 detik
    }

    $(document).ready(function() {

     // Untuk elemen select
    $('select[data-field="id_kategori"]').on('change', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        const field = $(this).data('field');
        const value = $(this).val();

        $.post('barang.php', { update_field: true, id_barang: id, field: field, value: value }, function(response) {
            const result = JSON.parse(response);
            if (result.status === 'success') {
                showNotification('Kategori berhasil diubah');
            } else {
                showNotification('Gagal mengubah kategori: ' + result.message, true);
            }
        });
    });

        // Untuk kolom yang dapat diedit
        $('[contenteditable]').on('blur', function() {
            const row = $(this).closest('tr');
            const id = row.data('id');
            const field = $(this).data('field');
            const value = $(this).text().trim();

                 // Validasi untuk field harga dan stok
            if ((field === 'harga' || field === 'stok') && (!/^\d+(\.\d+)?$/.test(value) || parseFloat(value) < 0)) {
                showNotification('Masukkan angka positif untuk ' + field, true);
                $(this).focus();
                return;
            }

            $.post('barang.php', { update_field: true, id_barang: id, field: field, value: value }, function(response) {
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
