<?php
// Включаем вывод ошибок для отладки (после сдачи задания эти 2 строки можно удалить)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// ЗАМЕНИ ЭТИ ДАННЫЕ НА СВОИ ИЗ ПАНЕЛИ INFINITYFREE
define('DB_HOST', 'sql101.infinityfree.com'); 
define('DB_USER', 'if0_43461626');             
define('DB_PASS', 'matvei1509'); 
define('DB_NAME', 'if0_43461626_hostel');       

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset('utf8mb4');

if ($mysqli->connect_error) {
    die('Ошибка подключения к БД: ' . $mysqli->connect_error);
}
?>
