<?php
session_start();
require_once 'config.php';
include 'profile_org.php';

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$isLoggedIn = $currentUserId > 0;
$userName = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : '';

if ($eventId <= 0) {
    die('Мероприятие не найдено.');
}

$sqlEvent = "
    SELECT
        e.id,
        e.title,
        e.description,
        e.full_description,
        e.volunteers_tasks,
        e.city,
        e.location,
        e.image_path,
        e.start_date,
        e.end_date,
        e.required_volunteers,
        e.recruitment_status,
        c.name AS category_name
    FROM events e
    LEFT JOIN event_categories c ON e.category_id = c.id
    WHERE e.id = :id
    LIMIT 1
";
$stmtEvent = $pdo->prepare($sqlEvent);
$stmtEvent->execute([':id' => $eventId]);
$event = $stmtEvent->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die('Мероприятие не найдено.');
}

// Получение смен + считает сколько людей записано на каждую смену и записан ли текущий пользователь
$sqlShifts = "
    SELECT
        s.id,
        s.shift_date,
        s.time_start,
        s.time_end,
        s.capacity,
        s.status,
        COALESCE(rc.registered_count, 0) AS registered_count,
        CASE
            WHEN :check_user_id > 0 AND EXISTS (
                SELECT 1
                FROM registrations r_user
                WHERE r_user.shift_id = s.id
                  AND r_user.volunteer_user_id = :current_user_id
                  AND r_user.status = 'ACTIVE'
            ) THEN 1
            ELSE 0
        END AS is_registered
    FROM shifts s
    LEFT JOIN (
        SELECT
            shift_id,
            COUNT(*) AS registered_count
        FROM registrations
        WHERE status = 'ACTIVE'
        GROUP BY shift_id
    ) rc ON rc.shift_id = s.id
    WHERE s.event_id = :event_id
    ORDER BY s.shift_date ASC, s.time_start ASC
";
$stmtShifts = $pdo->prepare($sqlShifts);
$stmtShifts->execute([
    ':event_id'       => $eventId,
    ':check_user_id'  => $currentUserId,
    ':current_user_id'=> $currentUserId,
]);
$shifts = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);

$totalRegistered = 0;
$totalCapacity   = 0;

// ── Блок организатора: волонтёры по сменам ──────────────────────────
$isOrganizer = $isLoggedIn && ($_SESSION['user_role'] ?? '') === 'ORGANIZER';

$stmtOwner = $pdo->prepare("SELECT organizer_id FROM events WHERE id = :id");
$stmtOwner->execute([':id' => $eventId]);
$isOwner = (int)$stmtOwner->fetchColumn() === $currentUserId && $isOrganizer;

$volunteersPerShift = [];
if ($isOwner) {
    foreach ($shifts as $shift) {
        $stmtVol = $pdo->prepare("
            SELECT vp.last_name, vp.first_name, vp.middle_name,
                   vp.phone, vp.email_public, r.registered_at, r.id AS reg_id
            FROM registrations r
            JOIN volunteer_profiles vp ON vp.user_id = r.volunteer_user_id
            WHERE r.shift_id = :sid AND r.status = 'ACTIVE'
            ORDER BY vp.last_name
        ");
        $stmtVol->execute([':sid' => $shift['id']]);
        $volunteersPerShift[$shift['id']] = $stmtVol->fetchAll(PDO::FETCH_ASSOC);
    }
}

foreach ($shifts as $shift) {
    $totalRegistered += (int)$shift['registered_count'];
    $totalCapacity   += (int)$shift['capacity'];
}

$tasks = [];
if (!empty($event['volunteers_tasks'])) {
    $decodedTasks = json_decode($event['volunteers_tasks'], true);
    if (is_array($decodedTasks)) {
        $tasks = $decodedTasks;
    }
}

function formatEventPeriod($startDate, $endDate)
{
    if (empty($startDate)) return 'Дата не указана';
    $start = date('d.m.Y', strtotime($startDate));
    if (empty($endDate) || $startDate === $endDate) return $start;
    return $start . ' – ' . date('d.m.Y', strtotime($endDate));
}

function formatShiftDateTime($date, $timeStart, $timeEnd)
{
    return date('d.m.Y', strtotime($date)) . '  ' . substr($timeStart, 0, 5) . '-' . substr($timeEnd, 0, 5);
}

function isShiftPast($date, $timeEnd)
{
    return strtotime($date . ' ' . $timeEnd) < time();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детальная страница мероприятия</title>
    <link rel="stylesheet" href="../css/style_header_footer.css">
    <link rel="stylesheet" href="../css/org_event_det.css">
    <link rel="stylesheet" href="../css/event_details.css">
    <link rel="stylesheet" href="../css/profile_org.css">
    <link rel="icon" type="image/png" href="../favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../favicon/favicon.svg" />
    <link rel="shortcut icon" href="../favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png" />
    <link rel="manifest" href="../favicon/site.webmanifest" />
</head>
<body>

    <header>
        <a href="../pages/index_org.php" class="logo">
            <div class="logo-icon">
                <img src="../img/log_main.png" alt="Login" width="47" height="47">
            </div>
            <span class="logo-text">Поможем<br>вместе</span>
        </a>
        <nav class="button-header">
            <a href="../pages/events_org.php">Мероприятия</a>
            <a href="../pages/list_val_org.php">Волонтеры</a>
        </nav>
        <div class="header-actions">
            <button class="btn-icon" title="Поиск" onclick="window.location.href='search.php'">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>
            <?php if ($isLoggedIn): ?>
                <button type="button" class="btn-login" id="profileMenuOpen">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <?= $userName ?>
                </button>
            <?php else: ?>
                <a href="../pages/login.php" class="btn-login">
                    <img src="../img/log_main.png" alt="Login" width="20" height="20">
                    Войти
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="page">
        <main>
            <a href="../pages/events.php" class="back-link">← Назад</a>

            <section class="event-details">
                <aside class="event-left">
                    <div class="event-photo">
                        <img src="../<?php echo htmlspecialchars(!empty($event['image_path']) ? $event['image_path'] : 'img/events/default.jpg'); ?>"
                             alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>

                    <!-- ── Список смен (только просмотр) ── -->
                    <div class="event-dates">
                        <?php if (!empty($shifts)): ?>
                            <?php foreach ($shifts as $shift): ?>
                                <?php
                                $registeredCount = (int)$shift['registered_count'];
                                $capacity        = (int)$shift['capacity'];
                                $isRegistered    = (int)$shift['is_registered'] === 1;
                                $isActiveShift   = $shift['status'] === 'ACTIVE';
                                $isPast          = isShiftPast($shift['shift_date'], $shift['time_end']);
                                $hasPlaces       = $registeredCount < $capacity;
                                $canRegister     = !$isRegistered && $isActiveShift && !$isPast && $hasPlaces;
                                $canCancel       = $isRegistered;
                                $isDisabled      = !$canRegister && !$canCancel;
                                ?>
                                <div class="date-item<?= $isRegistered ? ' is-registered' : '' ?><?= $isDisabled ? ' is-disabled' : '' ?>">
                                    <span><?= htmlspecialchars(formatShiftDateTime($shift['shift_date'], $shift['time_start'], $shift['time_end'])) ?></span>
                                    <span class="shift-spots"><?= $registeredCount ?>/<?= $capacity ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Смены пока не добавлены.</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isLoggedIn): ?>
                        <a href="../pages/login.php" class="signup-register">Войти, чтобы записаться</a>
                    <?php endif; ?>
                </aside>

                <div class="event-main-info">
                    <h1 class="event-details-title">
                        <?php echo htmlspecialchars($event['title']); ?>
                    </h1>

                    <div class="event-info-row">
                        <div class="info-item">
                            <img src="../img/icon/calendar.png" alt="">
                            <span><?php echo htmlspecialchars(formatEventPeriod($event['start_date'], $event['end_date'])); ?></span>
                        </div>
                        <div class="info-item">
                            <img src="../img/icon/geoloc.png" alt="">
                            <span>г. <?php echo htmlspecialchars($event['city']); ?></span>
                        </div>
                        <div class="info-item">
                            <img src="../img/icon/people_icon.png" alt="">
                            <span><?= (int)$totalRegistered ?>/<?= (int)$totalCapacity ?></span>
                        </div>
                    </div>

                    <div class="full-description">
                        <h2 class="visually-hidden">Полное описание</h2>
                        <div class="content-box-inner">
                            <p><?php echo nl2br(htmlspecialchars(
                                !empty($event['full_description'])
                                    ? $event['full_description']
                                    : $event['description']
                            )); ?></p>
                        </div>
                    </div>

                    <div class="requirements-box">
                        <h2 class="visually-hidden">Задачи волонтера, требования</h2>
                        <div class="content-box-inner">
                            <?php if (!empty($tasks)): ?>
                                <?php foreach ($tasks as $task): ?>
                                    <?= htmlspecialchars($task) ?><br>
                                <?php endforeach; ?>
                            <?php else: ?>
                                Задачи волонтёров пока не указаны.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ══════ ПАНЕЛЬ ОРГАНИЗАТОРА ══════ -->
            <?php if ($isOwner): ?>
            <section class="organizer-panel">
                <h2 class="organizer-panel__title">Управление сменами</h2>

                <?php foreach ($shifts as $shift): ?>
                <div class="shift-block">
                    <div class="shift-block__header">
                        <div class="shift-block__info">
                            <strong><?= htmlspecialchars(formatShiftDateTime($shift['shift_date'], $shift['time_start'], $shift['time_end'])) ?></strong>
                            <span class="shift-status shift-status--<?= strtolower($shift['status']) ?>">
                                <?= $shift['status'] === 'ACTIVE' ? 'Активна' : 'Отменена' ?>
                            </span>
                            <span class="shift-capacity">
                                <?= (int)$shift['registered_count'] ?> / <?= (int)$shift['capacity'] ?> чел.
                            </span>
                        </div>
                        <div class="shift-block__actions">
                            <!-- Редактировать -->
                            <button class="btn-edit" onclick="toggleEditForm(<?= (int)$shift['id'] ?>)">
                                Редактировать
                            </button>
                            <!-- Отменить / Активировать -->
                            <?php if ($shift['status'] === 'ACTIVE'): ?>
                            <form method="POST" action="shift_manage.php" style="display:inline">
                                <input type="hidden" name="action"   value="cancel">
                                <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
                                <input type="hidden" name="shift_id" value="<?= (int)$shift['id'] ?>">
                                <button type="submit" class="btn-cancel-shift"
                                    onclick="return confirm('Отменить смену? Все записи останутся в БД.')">
                                    ✕ Отменить смену
                                </button>
                            </form>
                            <?php else: ?>
                                <?php if (!isShiftPast($shift['shift_date'], $shift['time_end'])): ?>
                                <form method="POST" action="shift_manage.php" style="display:inline">
                                    <input type="hidden" name="action"   value="activate">
                                    <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
                                    <input type="hidden" name="shift_id" value="<?= (int)$shift['id'] ?>">
                                    <button type="submit" class="btn-activate-shift"
                                        onclick="return confirm('Активировать смену?')">
                                        ✓ Активировать
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span style="font-size:13px; color:#B0B5C0;">Смена прошла</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Форма редактирования (скрыта по умолчанию) -->
                    <form method="POST" action="shift_manage.php"
                          class="edit-shift-form" id="editForm-<?= (int)$shift['id'] ?>" style="display:none">
                        <input type="hidden" name="action"   value="edit">
                        <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
                        <input type="hidden" name="shift_id" value="<?= (int)$shift['id'] ?>">
                        <div class="edit-shift-grid">
                            <label>Дата
                                <input type="date" name="shift_date"
                                       value="<?= htmlspecialchars($shift['shift_date']) ?>" required>
                            </label>
                            <label>Начало
                                <input type="time" name="time_start"
                                       value="<?= htmlspecialchars(substr($shift['time_start'], 0, 5)) ?>" required>
                            </label>
                            <label>Конец
                                <input type="time" name="time_end"
                                       value="<?= htmlspecialchars(substr($shift['time_end'], 0, 5)) ?>" required>
                            </label>
                            <label>Вместимость
                                <input type="number" name="capacity" min="1"
                                       value="<?= (int)$shift['capacity'] ?>" required>
                            </label>
                        </div>
                        <button type="submit" class="btn-save">Сохранить</button>
                        <button type="button" class="btn-cancel-edit"
                                onclick="toggleEditForm(<?= (int)$shift['id'] ?>)">Отмена</button>
                    </form>

                    <!-- Список волонтёров на эту смену -->
                    <?php $vols = $volunteersPerShift[$shift['id']] ?? []; ?>
                    <?php if (!empty($vols)): ?>
                    <div class="volunteers-list">
                        <h4>Записаны (<?= count($vols) ?>):</h4>
                        <table class="vol-table">
                            <thead>
                                <tr>
                                    <th>ФИО</th>
                                    <th>Телефон</th>
                                    <th>Email</th>
                                    <th>Дата записи</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vols as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['last_name'] . ' ' . $v['first_name'] . ' ' . $v['middle_name']) ?></td>
                                    <td><?= htmlspecialchars($v['phone'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($v['email_public'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars(date('d.m.Y', strtotime($v['registered_at']))) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="no-volunteers">Нет записавшихся волонтёров</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- ── Добавить новую смену ── -->
                <div class="add-shift-block">
                    <h3>Добавить смену</h3>
                    <form method="POST" action="shift_manage.php" class="edit-shift-grid">
                        <input type="hidden" name="action"   value="add">
                        <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
                        <label>Дата
                            <input type="date" name="shift_date" required>
                        </label>
                        <label>Начало
                            <input type="time" name="time_start" required>
                        </label>
                        <label>Конец
                            <input type="time" name="time_end" required>
                        </label>
                        <label>Вместимость
                            <input type="number" name="capacity" min="1" value="10" required>
                        </label>
                        <button type="submit" class="btn-save">Добавить</button>
                    </form>
                </div>
            </section>

            <script>
            function toggleEditForm(shiftId) {
                const form = document.getElementById('editForm-' + shiftId);
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }
            </script>
            <?php endif; ?>

        </main>
    </div>

</body>
<footer>
    <a href="privacy.php">Политика конфиденциальности</a>
    <a href="terms.php">Политика использования</a>
    <a href="requisites.php">Реквизиты</a>
    <a href="mailto:info@gmail.com">info@gmail.com</a>
</footer>
</html>