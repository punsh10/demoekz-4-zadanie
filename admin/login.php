<?php
require '../config.php';
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $stmt = $mysqli->prepare("SELECT id, password_hash FROM admins WHERE login = ?");
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if ($admin && password_verify($pass, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: index.php'); exit;
    }
    $error = 'Неверный логин или пароль.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Вход - Админ</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
    <h2>Вход для администратора</h2>
    <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
    <form method="post">
        <div class="form-group"><label>Логин</label><input type="text" name="login" required></div>
        <div class="form-group"><label>Пароль</label><input type="password" name="password" required></div>
        <button type="submit" class="btn">Войти</button>
        <a href="../index.php" class="btn" style="background:#888;">Назад</a>
    </form>
</div>
</body>
</html>
