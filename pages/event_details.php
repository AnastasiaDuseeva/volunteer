<?php
session_start();
require_once 'config.php';
include 'profile.php';

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$isLoggedIn = $currentUserId > 0;
// Имя пользователя (если залогинен)
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
//Получение смен + считает сколько людей записано на каждую смену и записан ли текущий пользователь
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
    ':event_id' => $eventId,
    ':check_user_id' => $currentUserId,
    ':current_user_id' => $currentUserId,
]);
$shifts = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);

$totalRegistered = 0;
$totalCapacity = 0;

foreach ($shifts as $shift) {
    $totalRegistered += (int)$shift['registered_count'];
    $totalCapacity += (int)$shift['capacity'];
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
    if (empty($startDate)) {
        return 'Дата не указана';
    }

    $start = date('d.m.Y', strtotime($startDate));

    if (empty($endDate) || $startDate === $endDate) {
        return $start;
    }

    $end = date('d.m.Y', strtotime($endDate));
    return $start . ' – ' . $end;
}

function formatShiftDateTime($date, $timeStart, $timeEnd)
{
    $formattedDate = date('d.m.Y', strtotime($date));
    $formattedStart = substr($timeStart, 0, 5);
    $formattedEnd = substr($timeEnd, 0, 5);

    return $formattedDate . '  ' . $formattedStart . '-' . $formattedEnd;
}

function isShiftPast($date, $timeEnd)
{
    $endDateTime = strtotime($date . ' ' . $timeEnd);
    return $endDateTime < time();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детальная страница мероприятия</title>
    <link rel="stylesheet" href="../css/style_header_footer.css">
    <link rel="stylesheet" href="../css/event_details.css">
    <link rel="stylesheet" href="../css/profile.css">
</head>
<body>
        <header>
            <a href="../pages/index.php" class="logo">
                <div class="logo-icon" >
                        <img src="../img/log_main.png" alt="Login" width="47" height="47">
                </div>
                <span class="logo-text">Поможем<br>вместе</span>
            </a>

            <nav class="button-header">
                <a href="../pages/events.php">Мероприятия</a>
                <a href="../pages/list_of_val.php">Волонтеры</a>
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
                        <img src="../<?php echo htmlspecialchars(!empty($event['image_path']) ? $event['image_path'] : 'img/events/default.jpg'); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>


                    <?php if ($isLoggedIn): ?>
                        <form method="post" action="../pages/event_detail_check_write.php" id="shiftForm">
                            <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">

                            <div class="event-dates">
                                <?php if (!empty($shifts)): ?>
                                    <?php foreach ($shifts as $shift): ?>
                                        <?php
                                        $registeredCount = (int)$shift['registered_count'];
                                        $capacity = (int)$shift['capacity'];
                                        $isRegistered = (int)$shift['is_registered'] === 1;
                                        $isActiveShift = $shift['status'] === 'ACTIVE';
                                        $isPast = isShiftPast($shift['shift_date'], $shift['time_end']);
                                        $hasPlaces = $registeredCount < $capacity;
                                        $canRegister = !$isRegistered && $isActiveShift && !$isPast && $hasPlaces;
                                        $canCancel = $isRegistered;
                                        $isDisabled = !$canRegister && !$canCancel;
                                        ?>
                                        <label class="date-item<?php echo $isRegistered ? ' is-registered' : ''; ?><?php echo $isDisabled ? ' is-disabled' : ''; ?>">
                                            <input
                                                type="checkbox"
                                                name="shift_ids[]"
                                                value="<?php echo (int)$shift['id']; ?>"
                                                class="shift-checkbox"
                                                data-registered="<?php echo $isRegistered ? '1' : '0'; ?>"
                                                <?php echo $isDisabled ? 'disabled' : ''; ?>
                                            >
                                            <span><?php echo htmlspecialchars(formatShiftDateTime($shift['shift_date'], $shift['time_start'], $shift['time_end'])); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>Смены пока не добавлены.</p>
                                <?php endif; ?>
                            </div>

                            <div class="shift-actions">
                                <button type="submit" name="action" value="register" class="signup-btn" id="registerBtn" hidden>
                                    Записаться
                                </button>
                                <button type="submit" name="action" value="cancel" class="cancel-btn" id="cancelBtn" hidden>
                                    Отменить запись
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="event-dates">
                            <?php if (!empty($shifts)): ?>
                                <?php foreach ($shifts as $shift): ?>
                                    <label class="date-item is-disabled">
                                        <input type="checkbox" disabled>
                                        <span><?php echo htmlspecialchars(formatShiftDateTime($shift['shift_date'], $shift['time_start'], $shift['time_end'])); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Смены пока не добавлены.</p>
                            <?php endif; ?>
                        </div>
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
                            <span>г. <?php echo htmlspecialchars($event['city']);?></span>
                        </div>

                        <div class="info-item">
                            <img src="../img/icon/people_icon.png" alt="">
                            <span><?php echo (int)$totalRegistered; ?>/<?php echo (int)$totalCapacity; ?></span>
                        </div>
                    </div>

                    <div class="full-description">
                        <h2 class="visually-hidden">Полное описание</h2>
                        <div class="content-box-inner">
                            <p>
                                <?php
                                echo nl2br(htmlspecialchars(
                                    !empty($event['full_description'])
                                        ? $event['full_description']
                                        : $event['description']
                                ));
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="requirements-box">
                        <h2 class="visually-hidden">Задачи волонтера, требования</h2>
                        <div class="content-box-inner">
                            <?php if (!empty($tasks)): ?>
                                <?php foreach ($tasks as $task): ?>
                                    <?php echo htmlspecialchars($task); ?><br>
                                <?php endforeach; ?>
                            <?php else: ?>
                                Задачи волонтёров пока не указаны.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <?php if ($isLoggedIn): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.shift-checkbox');
            const registerBtn = document.getElementById('registerBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            function updateButtons() {
                let hasNewShifts = false;
                let hasRegisteredShifts = false;

                checkboxes.forEach(function (checkbox) {
                    if (checkbox.checked) {
                        const isRegistered = checkbox.dataset.registered === '1';

                        if (isRegistered) {
                            hasRegisteredShifts = true;
                        } else {
                            hasNewShifts = true;
                        }
                    }
                });

                registerBtn.hidden = !hasNewShifts;
                cancelBtn.hidden = !hasRegisteredShifts;
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateButtons);
            });

            updateButtons();
        });
        </script>
    <?php endif; ?>
</body>
<footer>
    <a href="../pages/privacy.php">Политика конфиденциальности</a>
    <a href="../pages/terms.php">Политика использования</a>
    <a href="../pages/requisites.php">Реквизиты</a>
    <a href="mailto:info@gmail.com">info@gmail.com</a>
</footer>
</html>