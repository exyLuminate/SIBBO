<?php
session_start();
require_once "config.php";

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($link, $_POST['username']);
    $password = mysqli_real_escape_string($link, $_POST['password']);

    $sql = "SELECT * FROM Admin WHERE username = '$username' AND password = MD5('$password')";
    $result = mysqli_query($link, $sql);

    if (mysqli_num_rows($result) === 1) {
        $admin = mysqli_fetch_assoc($result);
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $admin['username'];  // simpan username dari DB
        $_SESSION['id_admin'] = $admin['id_admin'];  // simpan id_admin dari DB
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login Admin</title></head>
<body>
    <h2>Login Admin</h2>
    <form method="POST" action="">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
