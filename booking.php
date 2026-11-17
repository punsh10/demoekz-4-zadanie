<?php
require 'config.php';

$errors = [];
$ok = isset($_GET['ok']);

$roomId = (int)($_POST['room_id'] ?? $_GET['room_id'] ?? 0);

$old = [
    'first_name' => '',
    'last_name' => '',
    'phone' => '',
    'email' => '',
    'check_in' => '',
    'check_out' => ''
];

// Получаем занятые даты для выбранного номера (одобренные брони)
$busyDates = [];
if ($roomId > 0) {
    $stmt = $mysqli->prepare(
        "SELECT check_in, check_out FROM bookings 
         WHERE room_id = ? AND status = 'approved'"
    );
    $stmt->bind_param('i', $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['check_in']);
        $end = new DateTime($row['check_out']);
        $current = clone $start;
        while ($current < $end) {
            $busyDates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $v) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (!preg_match('/^[А-ЯЁа-яё][А-ЯЁа-яё\s\.\-]*$/u', $old['first_name'])) {
        $errors['first_name'] = 'Имя: только русские буквы, пробелы, точка и тире.';
    }
    if (!preg_match('/^[А-ЯЁа-яё][А-ЯЁа-яё\s\.\-]*$/u', $old['last_name'])) {
        $errors['last_name'] = 'Фамилия: только русские буквы, пробелы, точка и тире.';
    }

    if (!preg_match('/^\+7\s*\(\d{3}\)\s*\d{3}-\d{2}-\d{2}$/', $old['phone'])) {
        $errors['phone'] = 'Телефон в формате +7(999)123-45-67 или +7 (999) 123-45-67.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный e-mail.';
    }

    $ci = null;
    $co = null;
    
    $d_in = DateTime::createFromFormat('d.m.Y', $old['check_in']);
    if (!$d_in || $d_in->format('d.m.Y') !== $old['check_in']) {
        $errors['check_in'] = 'Дата заезда: формат ДД.ММ.ГГГГ.';
    } else {
        $ci = $d_in->format('Y-m-d');
    }

    $d_out = DateTime::createFromFormat('d.m.Y', $old['check_out']);
    if (!$d_out || $d_out->format('d.m.Y') !== $old['check_out']) {
        $errors['check_out'] = 'Дата выезда: формат ДД.ММ.ГГГГ.';
    } else {
        $co = $d_out->format('Y-m-d');
    }

    if ($ci && $co) {
        $today = (new DateTime('today'))->format('Y-m-d');
        if ($ci < $today) {
            $errors['check_in'] = 'Дата заезда не может быть в прошлом.';
        }
        if ($co <= $ci) {
            $errors['check_out'] = 'Дата выезда должна быть позже даты заезда.';
        }
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("SELECT id FROM rooms WHERE id = ?");
        $stmt->bind_param('i', $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            $errors['room'] = 'Номер не выбран или не существует.';
        }
    }

    if (!$errors) {
        $stmt = $mysqli->prepare(
            "SELECT id FROM bookings
             WHERE room_id = ? AND status = 'approved'
               AND check_in < ? AND check_out > ?"
        );
        $stmt->bind_param('iss', $roomId, $co, $ci);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            $errors['dates'] = 'На эти даты номер уже занят. Выберите другие даты.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $mysqli->prepare(
                "INSERT INTO bookings (room_id, first_name, last_name, phone, email, check_in, check_out)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('issssss', $roomId, $old['first_name'], $old['last_name'], $old['phone'], $old['email'], $ci, $co);
            $stmt->execute();
            header('Location: booking.php?room_id=' . $roomId . '&ok=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            $errors['db'] = 'Не удалось сохранить заявку: ' . $e->getMessage();
        }
    }
}

$rooms = $mysqli->query("SELECT id, name, price FROM rooms ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Бронирование - Сладкие сны</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .flatpickr-day.disabled {
            background: #ffebee !important;
            color: #c62828 !important;
            text-decoration: line-through;
        }
        .flatpickr-day.disabled:hover {
            background: #ffebee !important;
        }
    </style>
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
    <h2 style="text-align:center;">Бронирование номера</h2>

    <?php if ($ok): ?>
        <p class="success">Заявка успешно отправлена! Администратор рассмотрит её, и мы свяжемся с вами.</p>
    <?php endif; ?>

    <?php if (isset($errors['db'])): ?>
        <p class="error"><?= htmlspecialchars($errors['db']) ?></p>
    <?php endif; ?>

    <form method="post" id="bookingForm">
        <div class="form-group">
            <label>Номер *</label>
            <select name="room_id" id="roomSelect">
                <option value="">— выберите —</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $roomId == $r['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['name']) ?> (<?= (int)$r['price'] ?> руб/сутки)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['room'])): ?><p class="error"><?= $errors['room'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Имя *</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($old['first_name']) ?>">
            <?php if (isset($errors['first_name'])): ?><p class="error"><?= $errors['first_name'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Фамилия *</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($old['last_name']) ?>">
            <?php if (isset($errors['last_name'])): ?><p class="error"><?= $errors['last_name'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Телефон *</label>
            <input type="text" name="phone" placeholder="+7(999)123-45-67" value="<?= htmlspecialchars($old['phone']) ?>">
            <?php if (isset($errors['phone'])): ?><p class="error"><?= $errors['phone'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label>E-mail *</label>
            <input type="text" name="email" placeholder="example@mail.ru" value="<?= htmlspecialchars($old['email']) ?>">
            <?php if (isset($errors['email'])): ?><p class="error"><?= $errors['email'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Дата заезда * (ДД.ММ.ГГГГ)</label>
            <input type="text" name="check_in" id="checkIn" placeholder="12.12.2026" value="<?= htmlspecialchars($old['check_in']) ?>" required>
            <?php if (isset($errors['check_in'])): ?><p class="error"><?= $errors['check_in'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Дата выезда * (ДД.ММ.ГГГГ)</label>
            <input type="text" name="check_out" id="checkOut" placeholder="15.12.2026" value="<?= htmlspecialchars($old['check_out']) ?>" required>
            <?php if (isset($errors['check_out'])): ?><p class="error"><?= $errors['check_out'] ?></p><?php endif; ?>
            <?php if (isset($errors['dates'])): ?><p class="error"><?= $errors['dates'] ?></p><?php endif; ?>
        </div>

        <button type="submit" class="btn">Отправить заявку</button>
    </form>
</div>

<footer>
    <p>Адрес: г. Москва, ул. Пушкина, д. 1 | Круглосуточно</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
<script>
    const busyDates = <?= json_encode($busyDates) ?>;
    const today = new Date().toISOString().split('T')[0];

    const checkInPicker = flatpickr("#checkIn", {
        locale: "ru",
        dateFormat: "d.m.Y",
        minDate: today,
        disable: busyDates,
        onChange: function(selectedDates) {
            if (selectedDates[0]) {
                const nextDay = new Date(selectedDates[0]);
                nextDay.setDate(nextDay.getDate() + 1);
                checkOutPicker.set('minDate', nextDay);
            }
        }
    });

    const checkOutPicker = flatpickr("#checkOut", {
        locale: "ru",
        dateFormat: "d.m.Y",
        minDate: today,
        disable: busyDates,
        onChange: function(selectedDates) {
            if (selectedDates[0] && checkInPicker.selectedDates[0]) {
                if (selectedDates[0] <= checkInPicker.selectedDates[0]) {
                    const nextDay = new Date(checkInPicker.selectedDates[0]);
                    nextDay.setDate(nextDay.getDate() + 1);
                    checkOutPicker.setDate(nextDay);
                }
            }
        }
    });

    document.getElementById('roomSelect').addEventListener('change', function() {
        const roomId = this.value;
        if (roomId) {
            window.location.href = 'booking.php?room_id=' + roomId;
        }
    });
</script>
</body>
</html>
