<?php
require 'config.php';

$mysqli->query("DELETE FROM admins WHERE login = 'hotel123'");

$login = 'hotel123';
$pass = 'adminHotel';
$hash = password_hash($pass, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("INSERT INTO admins (login, password_hash) VALUES (?, ?)");
$stmt->bind_param('ss', $login, $hash);

if ($stmt->execute()) {
    echo "Администратор создан!<br>";
    echo "Логин: <strong>$login</strong><br>";
    echo "Пароль: <strong>$pass</strong><br><br>";
    echo "<span style='color:red;'><strong>ВАЖНО: УДАЛИТЕ ЭТОТ ФАЙЛ (setup_admin.php) ЧЕРЕЗ ФАЙЛОВЫЙ МЕНЕДЖЕР!</strong></span>";
} else {
    echo "Ошибка: " . $stmt->error;
}
?>
