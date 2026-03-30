<?php
session_start();
require_once 'config.php';
include 'profile.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$userName = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : '';

$sqlMyShifts = "
    SELECT
        s.id AS shift_id,
        s.event_id,
        s.shift_date,
        s.time_start,
        s.time_end,
        e.title AS event_title,
        e.description
    FROM registrations r
    INNER JOIN shifts s ON s.id = r.shift_id
    INNER JOIN events e ON e.id = s.event_id
    WHERE r.volunteer_user_id = :user_id
      AND r.status = 'ACTIVE'
    ORDER BY s.shift_date ASC, s.time_start ASC
";

$stmtMyShifts = $pdo->prepare($sqlMyShifts);
$stmtMyShifts->execute([':user_id' => $currentUserId]);
$allShifts = $stmtMyShifts->fetchAll(PDO::FETCH_ASSOC);

$currentShifts = [];
$pastShifts = [];

$now = new DateTime();

foreach ($allShifts as $shift) {
    $shiftEnd = new DateTime($shift['shift_date'] . ' ' . $shift['time_end']);

    if ($shiftEnd < $now) {
        $pastShifts[] = $shift;
    } else {
        $currentShifts[] = $shift;
    }
}

function formatShiftDate($date)
{
    if (empty($date)) {
        return 'Не указано';
    }

    return date('d.m.Y', strtotime($date));
}

function formatShiftTime($time)
{
    if (empty($time)) {
        return '';
    }

    return date('H:i', strtotime($time));
}

function shortenText($text, $length = 120)
{
    $text = trim((string)$text);

    if ($text === '') {
        return 'Описание отсутствует.';
    }

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . '...';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои смены и мероприятия</title>
    <link rel="stylesheet" href="../css/my_event.css">
    <link rel="stylesheet" href="../css/style_header_footer.css">
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

<div class="my-events-page">
    <div class="my-events-container">
        <div class="my-events-header">
            <h1>Мои смены</h1>
        </div>

        <div class="my-events-columns">
            <!-- Левая колонка -->
            <section class="events-column">
                <div class="events-column-header">
                    <h2>Предстоящие и текущие</h2>
                    <span class="events-count"><?php echo count($currentShifts); ?></span>
                </div>

                <?php if (!empty($currentShifts)): ?>
                    <div class="events-list">
                        <?php foreach ($currentShifts as $shift): ?>
                            <article class="event-card">
                                <div class="event-card-top">
                                    <span class="event-badge event-badge-upcoming">Актуально</span>
                                </div>

                                <h3 class="event-title">
                                    <?php echo htmlspecialchars($shift['event_title']); ?>
                                </h3>

                                <p class="event-description">
                                    <?php echo htmlspecialchars(shortenText($shift['description'] ?? '')); ?>
                                </p>

                                <div class="event-meta">
                                    <p><strong>Дата смены:</strong> <?php echo htmlspecialchars(formatShiftDate($shift['shift_date'])); ?></p>
                                    <p><strong>Время:</strong> 
                                        <?php echo htmlspecialchars(formatShiftTime($shift['time_start'])); ?>
                                        —
                                        <?php echo htmlspecialchars(formatShiftTime($shift['time_end'])); ?></p>
                                </div>

                                <div class="event-actions">
                                    <a href="event_details.php?id=<?php echo (int)$shift['event_id']; ?>" class="event-link">
                                        Подробнее
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-events">
                        <p>У вас пока нет текущих или будущих смен.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Правая колонка -->
            <section class="events-column">
                <div class="events-column-header">
                    <h2>Прошедшие</h2>
                    <span class="events-count"><?php echo count($pastShifts); ?></span>
                </div>

                <?php if (!empty($pastShifts)): ?>
                    <div class="events-list">
                        <?php foreach ($pastShifts as $shift): ?>
                            <article class="event-card">
                                <div class="event-card-top">
                                    <span class="event-badge event-badge-past">Завершено</span>
                                </div>

                                <h3 class="event-title">
                                    <?php echo htmlspecialchars($shift['event_title']); ?>
                                </h3>

                                <p class="event-description">
                                    <?php echo htmlspecialchars(shortenText($shift['description'] ?? '')); ?>
                                </p>

                                <div class="event-meta">
                                    <p><strong>Дата смены:</strong> <?php echo htmlspecialchars(formatShiftDate($shift['shift_date'])); ?></p>
                                    <p><strong>Время:</strong> 
                                        <?php echo htmlspecialchars(formatShiftTime($shift['time_start'])); ?>
                                        —
                                        <?php echo htmlspecialchars(formatShiftTime($shift['time_end'])); ?>
                                    </p>
                                </div>

                                <div class="event-actions">
                                    <a href="event_details.php?id=<?php echo (int)$shift['event_id']; ?>" class="event-link">
                                        Подробнее
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-events">
                        <p>Прошедших смен пока нет.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<footer>
    <a href="../pages/privacy.php">Политика конфиденциальности</a>
    <a href="../pages/terms.php">Политика использования</a>
    <a href="../pages/requisites.php">Реквизиты</a>
    <a href="mailto:info@gmail.com">info@gmail.com</a>
</footer>

</body>
</html>