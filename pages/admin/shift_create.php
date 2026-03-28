<?php
require_once 'auth_check.php';
$db = getDB();

$errors  = [];
$success = false;
$selectedEvent = intval($_GET['event_id'] ?? 0); // можно передать event_id через URL из карточки мероприятия

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $eventId    = intval($_POST['event_id'] ?? 0);
    $shiftDates = $_POST['shift_date'] ?? [];
    $timeStarts = $_POST['time_start'] ?? [];
    $timeEnds   = $_POST['time_end']   ?? [];
    $capacities = $_POST['capacity']   ?? [];

    if (!$eventId) {
        $errors['event_id'] = 'Выберите мероприятие';
    }

    if (empty($shiftDates) || !array_filter($shiftDates)) {
        $errors['shifts'] = 'Добавьте хотя бы одну смену';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO shifts (event_id, shift_date, time_start, time_end, capacity, status)
            VALUES (?, ?, ?, ?, ?, 'ACTIVE')
        ");

        $inserted = 0;
        for ($i = 0; $i < count($shiftDates); $i++) {
            $date     = trim($shiftDates[$i] ?? '');
            $start    = trim($timeStarts[$i]  ?? '');
            $end      = trim($timeEnds[$i]    ?? '');
            $capacity = intval($capacities[$i] ?? 0);

            // Пропускаем пустые строки
            if (empty($date) || empty($start) || empty($end) || $capacity < 1) continue;

            $stmt->execute([$eventId, $date, $start, $end, $capacity]);
            $inserted++;
        }

        if ($inserted > 0) {
            header('Location: events.php?shifts_added=' . $inserted);
            exit;
        } else {
            $errors['shifts'] = 'Ни одна смена не была добавлена. Проверь заполнение полей.';
        }
    }
}

// Мероприятия для выпадающего списка — берём из БД!
$events = $db->query("
    SELECT id, title, start_date
    FROM events
    ORDER BY start_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать смены — Администратор</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<header class="admin-header">
    <a href="index.php" class="admin-logo">
        <img src="../assets/img/log_main.png" alt="Логотип">
        <span class="admin-logo-text">Поможем<br>вместе</span>
    </a>
    <span class="admin-badge">Администратор</span>
    <span class="admin-header-name"><?= htmlspecialchars($admin['email']) ?></span>
    <div class="admin-header-right">
        <a href="../logout.php" class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Выйти
        </a>
    </div>
</header>

<main class="admin-main">

    <a href="events.php" class="back-link">
        <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Назад к списку мероприятий
    </a>

    <div class="form-card">
        <h1 class="form-title">Создать смены</h1>

        <form action="" method="post" id="createShiftForm">

            <!-- Мероприятие — список из БД -->
            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label" for="event_id">
                        Мероприятие <span class="required">*</span>
                    </label>
                    <select id="event_id" name="event_id" class="form-select" required
                            style="<?= isset($errors['event_id']) ? 'border-color:#E05252' : '' ?>">
                        <option value="">Выберите мероприятие</option>
                        <?php foreach ($events as $e): ?>
                            <option value="<?= $e['id'] ?>"
                                <?= ($selectedEvent === $e['id'] || (isset($_POST['event_id']) && intval($_POST['event_id']) === $e['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['title']) ?>
                                <?php if ($e['start_date']): ?>
                                    (<?= date('d.m.Y', strtotime($e['start_date'])) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['event_id'])): ?>
                        <span style="color:#E05252;font-size:12px;"><?= $errors['event_id'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Смены -->
            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label">Смены <span class="required">*</span></label>
                    <p style="font-size:13px;color:#888;margin-bottom:12px;">
                        Одна строка = одна смена. Можно добавить несколько сразу.
                    </p>

                    <?php if (isset($errors['shifts'])): ?>
                        <div style="color:#E05252;font-size:13px;margin-bottom:10px;"><?= $errors['shifts'] ?></div>
                    <?php endif; ?>

                    <div style="display:grid;grid-template-columns:160px 1fr 1fr 120px 36px;gap:10px;margin-bottom:4px;">
                        <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Дата</span>
                        <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Начало</span>
                        <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Окончание</span>
                        <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Мест</span>
                        <span></span>
                    </div>

                    <div class="shifts-rows" id="shiftsRows">
                        <div class="shift-row">
                            <input type="date" name="shift_date[]" class="form-input" required>
                            <input type="time" name="time_start[]" class="form-input" value="09:00" required>
                            <input type="time" name="time_end[]"   class="form-input" value="18:00" required>
                            <input type="number" name="capacity[]" class="form-input" placeholder="20" min="1" value="20" required>
                            <button type="button" class="btn-remove-shift" onclick="removeShift(this)">
                                <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="button" class="btn-add-task" onclick="addShiftRow()" style="margin-top:10px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Добавить ещё смену
                    </button>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Добавить смены</button>
                <a href="events.php" class="btn-cancel">Отмена</a>
            </div>

        </form>
    </div>

</main>

<footer class="admin-footer">
    <a href="../pages/privacy.php">Политика конфиденциальности</a>
    <a href="../pages/terms.php">Политика использования</a>
    <a href="mailto:info@pomozhem.ru">info@pomozhem.ru</a>
</footer>

<script>
function addShiftRow() {
    const rows = document.getElementById('shiftsRows');
    const row  = document.createElement('div');
    row.className = 'shift-row';
    row.innerHTML = `
        <input type="date" name="shift_date[]" class="form-input" required>
        <input type="time" name="time_start[]" class="form-input" value="09:00" required>
        <input type="time" name="time_end[]"   class="form-input" value="18:00" required>
        <input type="number" name="capacity[]" class="form-input" placeholder="20" min="1" value="20" required>
        <button type="button" class="btn-remove-shift" onclick="removeShift(this)">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>`;
    rows.appendChild(row);
    row.querySelector('input[type="date"]').focus();
}
function removeShift(btn) {
    const rows = document.getElementById('shiftsRows').querySelectorAll('.shift-row');
    if (rows.length > 1) btn.closest('.shift-row').remove();
}
</script>
</body>
</html>
