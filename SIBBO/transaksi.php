<?php
session_start();
require_once "config.php";
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = date('Y-m-d H:i:s');
    $id_admin = $_SESSION['id_admin']; // Gunakan session id_admin
    $id_metode = intval($_POST['id_metode']);
    $total_harga = 0;

    if ($id_metode <= 0) {
        echo "<p style='color: red;'>Metode pembayaran tidak valid!</p>";
        exit;
    }

    $valid_items = [];
    foreach ($_POST['items'] as $item) {
        $id_barang = intval($item['id_barang']);
        $jumlah = intval($item['jumlah']);

        if ($id_barang > 0 && $jumlah > 0) {
            $valid_items[] = $item;
        }
    }

    if (count($valid_items) === 0) {
        echo "<p style='color: red;'>Tidak ada barang yang valid untuk transaksi!</p>";
        exit;
    }

    foreach ($valid_items as $item) {
        $id_barang = intval($item['id_barang']);
        $jumlah = intval($item['jumlah']);
        $barang = mysqli_fetch_assoc(mysqli_query($link, "SELECT harga, stok FROM Barang WHERE id_barang = $id_barang"));

        if ($barang['stok'] < $jumlah) {
            echo "<p style='color: red;'>Stok barang tidak mencukupi untuk barang ID: $id_barang!</p>";
            continue;
        }

        $subtotal = $barang['harga'] * $jumlah;
        $total_harga += $subtotal;

        // Kurangi stok
        mysqli_query($link, "UPDATE Barang SET stok = stok - $jumlah WHERE id_barang = $id_barang");
    }

    // Simpan transaksi
    mysqli_query($link, "INSERT INTO Transaksi (tanggal, total_harga, id_admin, id_metode) 
                         VALUES ('$tanggal', $total_harga, $id_admin, $id_metode)");
    $id_transaksi = mysqli_insert_id($link);

    // Simpan detail transaksi
    foreach ($valid_items as $item) {
        $id_barang = intval($item['id_barang']);
        $jumlah = intval($item['jumlah']);
        $subtotal = mysqli_fetch_assoc(mysqli_query($link, "SELECT harga FROM Barang WHERE id_barang = $id_barang"))['harga'] * $jumlah;

        mysqli_query($link, "INSERT INTO DetailTransaksi (id_transaksi, id_barang, jumlah, subtotal) 
                             VALUES ($id_transaksi, $id_barang, $jumlah, $subtotal)");
    }

    echo "<p style='color: green;'>Transaksi berhasil disimpan!</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Transaksi Penjualan</title>
</head>
<body>
<header>
    <div class="header-left">
        <h1>Transaksi Penjualan</h1>
    </div>
    <nav class="header-right">
        <a href="dashboard.php">Dashboard</a>
        <a href="barang.php">Manajemen Barang</a>
        <a href="kategori.php">Manajemen Kategori</a>
        <a href="transaksi.php">Transaksi Penjualan</a>
        <a href="laporan.php">Laporan Penjualan</a>
        <a href="setting.php">Pengaturan</a>
        <a href="logout.php" style="color: red;">Logout</a>
    </nav>
</header>

<form method="POST" action="">
    <div id="item-container">
        <div class="item">
            <select name="items[0][id_barang]" class="barang-select" onchange="updateSubtotal(this)" required>
                <option value="">-- Pilih Barang --</option>
                <?php
                $barangResult = mysqli_query($link, "SELECT * FROM Barang");
                while ($barang = mysqli_fetch_assoc($barangResult)) {
                    echo "<option value='{$barang['id_barang']}' data-harga='{$barang['harga']}'>" . htmlspecialchars($barang['nama_barang']) . "</option>";
                }
                ?>
            </select>
            <input type="number" name="items[0][jumlah]" class="jumlah-input" placeholder="Jumlah" min="1" oninput="updateSubtotal(this)" required>
            <span class="subtotal">Subtotal: 0</span>
            <button type="button" class="hapus-item" onclick="hapusItem(this)">Hapus</button>
        </div>
    </div>

    <button type="button" onclick="addItem()">Tambah Barang</button><br><br>

    <h3>Total Transaksi: <span id="total-harga">0</span></h3>

    <label for="metode_pembayaran">Metode Pembayaran:</label>
    <select name="id_metode" id="metode_pembayaran" required>
        <option value="">-- Pilih Metode Pembayaran --</option>
        <?php
        $metodeResult = mysqli_query($link, "SELECT * FROM MetodePembayaran");
        while ($metode = mysqli_fetch_assoc($metodeResult)) {
            echo "<option value='{$metode['id_metode']}'>" . htmlspecialchars($metode['nama_metode']) . "</option>";
        }
        ?>
    </select><br><br>

    <button type="submit">Simpan Transaksi</button>
</form>

<script>
let itemIndex = 1;

function addItem() {
    const container = document.getElementById('item-container');
    const newItem = document.createElement('div');
    newItem.className = 'item';
    newItem.innerHTML = `
        <select name="items[${itemIndex}][id_barang]" class="barang-select" onchange="updateSubtotal(this)" required>
            <option value="">-- Pilih Barang --</option>
            <?php
            $barangResult = mysqli_query($link, "SELECT * FROM Barang");
            while ($barang = mysqli_fetch_assoc($barangResult)) {
                echo "<option value='{$barang['id_barang']}' data-harga='{$barang['harga']}'>" . htmlspecialchars($barang['nama_barang']) . "</option>";
            }
            ?>
        </select>
        <input type="number" name="items[${itemIndex}][jumlah]" class="jumlah-input" placeholder="Jumlah" min="1" oninput="updateSubtotal(this)" required>
        <span class="subtotal">Subtotal: 0</span>
        <button type="button" class="hapus-item" onclick="hapusItem(this)">Hapus</button>
    `;
    container.appendChild(newItem);
    itemIndex++;
}

function updateSubtotal(element) {
    const item = element.closest('.item');
    const barangSelect = item.querySelector('.barang-select');
    const jumlahInput = item.querySelector('.jumlah-input');
    const subtotalSpan = item.querySelector('.subtotal');

    const harga = parseFloat(barangSelect.options[barangSelect.selectedIndex]?.getAttribute('data-harga')) || 0;
    const jumlah = parseFloat(jumlahInput.value) || 0;
    const subtotal = harga * jumlah;

    subtotalSpan.textContent = `Subtotal: ${subtotal}`;
    updateTotalHarga();
}

function updateTotalHarga() {
    const subtotals = document.querySelectorAll('.subtotal');
    let total = 0;

    subtotals.forEach(sub => {
        const value = parseFloat(sub.textContent.replace('Subtotal: ', '')) || 0;
        total += value;
    });

    document.getElementById('total-harga').textContent = total;
}

function hapusItem(button) {
    const item = button.closest('.item');
    item.remove();
    updateTotalHarga();
}

function validateForm(event) {
    const items = document.querySelectorAll('.item');
    let valid = false;

    items.forEach(item => {
        const barangSelect = item.querySelector('.barang-select');
        const jumlahInput = item.querySelector('.jumlah-input');

        if (barangSelect.value && parseInt(jumlahInput.value) > 0) {
            valid = true;
        }
    });

    if (!valid) {
        alert("Setidaknya satu barang harus dipilih dengan jumlah yang valid!");
        event.preventDefault();
    }
}

document.querySelector('form').addEventListener('submit', validateForm);
</script>

<p><a href="dashboard.php">Kembali ke Dashboard</a></p>
</body>
</html>
