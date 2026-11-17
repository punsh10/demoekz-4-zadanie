<?php
require 'config.php';

$categories = ['Стандарт', 'Студия', 'Люкс'];
$selected = $_GET['category'] ?? '';
if (!in_array($selected, $categories, true)) $selected = '';

if ($selected !== '') {
    $stmt = $mysqli->prepare("SELECT * FROM rooms WHERE category = ? ORDER BY price");
    $stmt->bind_param('s', $selected);
} else {
    $stmt = $mysqli->prepare("SELECT * FROM rooms ORDER BY price");
}
$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог - Сладкие сны</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div>
        <h1>Отель «Сладкие сны»</h1>
        <p>Ваш идеальный отдых начинается здесь!</p>
    </div>
    <nav>
        <a href="index.php">Главная</a>
        <a href="catalog.php">Каталог</a>
    </nav>
</header>

<div class="container">
    <h2>Каталог номеров</h2>

    <div class="filters">
        <form method="get" style="display:flex; gap:10px; align-items:center;">
            <label>Категория:</label>
            <select name="category">
                <option value="">Все</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c ?>" <?= $selected === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn" style="width:auto;">Применить</button>
            <a href="catalog.php" class="btn" style="width:auto; background:#888;">Сбросить</a>
        </form>
    </div>

    <div class="grid">
        <?php if (!$rooms): ?>
            <p>Номера не найдены.</p>
        <?php endif; ?>
        <?php foreach ($rooms as $r): ?>
        <div class="card">
            <img src="<?= htmlspecialchars($r['image']) ?>" alt="Номер">
            <h3><?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['category']) ?>)</h3>
            <p>Цена: <?= (int)$r['price'] ?> руб/сутки</p>
            <p>Площадь: <?= (int)$r['area'] ?> кв.м, Мест: <?= (int)$r['capacity'] ?><?= $r['amenities'] ? ', ' . htmlspecialchars($r['amenities']) : '' ?></p>
            <a href="booking.php?room_id=<?= (int)$r['id'] ?>" class="btn">Забронировать</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<footer><p>Адрес: г. Москва, ул. Пушкина, д. 1 | Круглосуточно</p></footer>
</body>
</html>
