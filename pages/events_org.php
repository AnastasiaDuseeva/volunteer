<?php
session_start();
require_once 'config.php';
requireRole('ORGANIZER');
$userId = (int)$_SESSION['user_id'];
include 'profile_org.php';

// Проверка авторизации
$isLoggedIn = isLoggedIn();

// Имя пользователя (если залогинен)
$userName = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : '';


$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filterDate = isset($_GET['date']) ? trim($_GET['date']) : '';

$statuses = ['Проект', 'Открыт', 'Закрыт', 'Завершён'];

if (!in_array($status, $statuses, true)) {
    $status = '';
}

$sqlCities = "
    SELECT DISTINCT city
    FROM events
    WHERE city IS NOT NULL AND city <> ''
    ORDER BY city ASC
";
$stmtCities = $pdo->query($sqlCities);
$cities = $stmtCities->fetchAll();

$sqlCategories = "
    SELECT id, name
    FROM event_categories
    ORDER BY name ASC
";
$stmtCategories = $pdo->query($sqlCategories);
$categories = $stmtCategories->fetchAll();
$hasActiveFilters = ($city !== '' || $categoryId > 0 || $status !== '' || $filterDate !== '');

$sql = "
    SELECT 
        e.id,
        e.title,
        e.description,
        e.city,
        e.location,
        e.image_path,
        e.start_date,
        e.end_date,
        e.recruitment_status,
        c.name AS category_name
    FROM events e
    LEFT JOIN event_categories c ON e.category_id = c.id
    WHERE e.created_by = :user_id
";
$params = [];

if ($city !== '') {
    $sql .= " AND e.city = :city";
    $params[':city'] = $city;
}

if ($categoryId > 0) {
    $sql .= " AND e.category_id = :category_id";
    $params[':category_id'] = $categoryId;
}

if ($status !== '') {
    $sql .= " AND e.recruitment_status = :status";
    $params[':status'] = $status;
}

if ($filterDate !== '') {
    $sql .= " AND e.start_date IS NOT NULL AND e.end_date IS NOT NULL
              AND e.start_date <= :filter_date
              AND e.end_date >= :filter_date";
    $params[':filter_date'] = $filterDate;
}

$sql .= " ORDER BY e.start_date ASC, e.created_at DESC";

$stmt = $pdo->prepare($sql);
$params[':user_id'] = $userId;
$stmt->execute($params);
$events = $stmt->fetchAll();

function formatEventDate(?string $startDate, ?string $endDate): string
{
    if (empty($startDate)) {
        return 'Дата не указана';
    }

    $start = date('d.m.Y', strtotime($startDate));
    $end = date('d.m.Y', strtotime($endDate));
    return $start . ' – ' . $end;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мероприятия</title>
    <link rel="stylesheet" href="../css/style_header_footer.css">
    <link rel="stylesheet" href="../css/events.css">
    <link rel="stylesheet" href="../css/profile_org.css">
    <link rel="icon" type="image/png" href="../favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../favicon/favicon.svg" />
    <link rel="shortcut icon" href="../favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png" />
    <link rel="manifest" href="../favicon/site.webmanifest" />

</head>
<body>
<div class="page">
        <!-- ══════════════ HEADER ══════════════ -->
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
                    <a href="login.php" class="btn-login"> 
                        <img src="img/log_main.png" alt="Login" width="20" height="20">
                        Войти
                    </a>
                <?php endif; ?>
    </div>
    </header>
       

        <main class="events-layout">
            <aside class="filters">
                <form class="filters-form" action="#" method="get">
                    <div class="filter-group">
                        <select name="city" class="filter-select">
                            <option value="">Город</option>
                            <?php foreach ($cities as $cityItem): ?>
                                <option
                                    value="<?php echo htmlspecialchars($cityItem['city']); ?>"
                                    <?php echo ($city === $cityItem['city']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($cityItem['city']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <select name="category" class="filter-select">
                        <option value="">Категория</option>
                            <?php foreach ($categories as $categoryItem): ?>
                                    <option
                                        value="<?php echo (int)$categoryItem['id']; ?>"
                                        <?php echo ($categoryId === (int)$categoryItem['id']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($categoryItem['name']); ?>
                                    </option>
                            <?php endforeach; ?> 
                        </select>
                    </div>

                    <div class="filter-group">
                        <select name="status" class="filter-select">
                            <option value="">Статус</option>
                            <?php foreach ($statuses as $statusItem): ?>
                                <option
                                    value="<?php echo htmlspecialchars($statusItem); ?>"
                                    <?php echo ($status === $statusItem) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($statusItem); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                    <input
                            type="date"
                            name="date"
                            id="date"
                            class="date-input"
                            value="<?php echo htmlspecialchars($filterDate); ?>"
                        >
                    </div>
                
                    <div class="filter-actions">
                        <button type="submit" class="find-btn">Найти</button>
                        <?php if ($hasActiveFilters): ?>
                            <button type="button" class="find-btn" onclick="window.location.href='events.php'">Сбросить</button>
                        <?php endif; ?>
                    </div>
                </form>
            </aside>

           <div class="events-list">
           <?php foreach ($events as $event):?>
                <article class="event-card">
                    <a href="event_details.php?id=<?=(int)$event['id']?>" class="event-image">
                        <img src="../<?=htmlspecialchars($event['image_path'])?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    </a>

                        <div class="event-main">
                            <h2 class="event-title">
                                <a href="event_details_org.php?id=<?=(int)$event['id']?>"><?= htmlspecialchars($event['title']) ?></a>
                            </h2>
                            
                            <p class="event-text">
                                <?= htmlspecialchars($event['description']) ?>
                            </p>
                        </div>

                        <div class="event-meta">
                            <div class="meta-item">
                                <img src="../img/icon/geoloc.png" alt="Город">
                                <span>г. <?= htmlspecialchars($event['city']) ?></span>
                            </div>
                            <div class="meta-item">
                                <img src="../img/icon/calendar.png" alt="Дата">
                                <span><?= htmlspecialchars(formatEventDate($event['start_date'], $event['end_date'])) ?></span>
                            </div>
                        </div>

                        <div class="event-footer">
                            <span class="tag"><?= htmlspecialchars($event['category_name']) ?></span>
                            <span class="status-badge"><?= htmlspecialchars($event['recruitment_status']) ?></span>
                        </div>
                </article>
                <?php endforeach; ?>
            </div>

        </main>
    </div>
<footer>
    <a href="privacy.php">Политика конфиденциальности</a>
    <a href="terms.php">Политика использования</a>
    <a href="requisites.php">Реквизиты</a>
    <a href="mailto:info@gmail.com">info@gmail.com</a>
</footer>
</body>
</html>