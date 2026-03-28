<?php
session_start();
require_once 'config.php';
include 'profile.php';
// ─── Parameters ──────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// ─── Volunteers query ─────────────────────────────────────────────────────────
$volunteers = [];
$total_volunteers = 0;

$like = '%' . $search . '%';

$countSql = "SELECT COUNT(*) FROM volunteer_profiles vp
             JOIN users u ON u.id = vp.user_id
             WHERE u.role = 'VOLUNTEER' AND u.is_active = 1
             AND (CONCAT(vp.last_name,' ',vp.first_name,' ',IFNULL(vp.middle_name,'')) LIKE :s
                  OR vp.city LIKE :s2)";
$stmt = $pdo->prepare($countSql);
$stmt->execute([':s' => $like, ':s2' => $like]);
$total_volunteers = (int)$stmt->fetchColumn();

$sql = "SELECT vp.user_id, vp.last_name, vp.first_name, vp.middle_name,
               vp.birth_date, vp.city, vp.phone
        FROM volunteer_profiles vp
        JOIN users u ON u.id = vp.user_id
        WHERE u.role = 'VOLUNTEER' AND u.is_active = 1
        AND (CONCAT(vp.last_name,' ',vp.first_name,' ',IFNULL(vp.middle_name,'')) LIKE :s
             OR vp.city LIKE :s2)
        ORDER BY vp.last_name, vp.first_name
        LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':s',   $like, PDO::PARAM_STR);
$stmt->bindValue(':s2',  $like, PDO::PARAM_STR);
$stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$volunteers = $stmt->fetchAll();

// ─── Helpers ──────────────────────────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$total_pages = (int)ceil($total_volunteers / $limit);

function pageUrl(int $p, string $search): string {
    $q = http_build_query(['search' => $search, 'page' => $p]);
    return '?' . $q;
}

// Проверка авторизации
$isLoggedIn = isLoggedIn();
$userName = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Волонтёры — Поможем вместе</title>
    <link rel="stylesheet" href="../css/list_of_events.css">
    <link rel="stylesheet" href="../css/style_header_footer.css">
    <link rel="stylesheet" href="../css/profile.css"> 
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
            <a href="../pages/index.php" class="logo">
                <div class="logo-icon">
                    <img src="../img/log_main.png" alt="Login" width="47" height="47">
                </div>
                <span class="logo-text">Поможем<br>вместе</span>
            </a>
            <nav class="button-header">
                <a href="../pages/events.php">Мероприятия</a>
                <a href="../pages/volunteers.php">Волонтеры</a>
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

        <!-- ══════════════ MAIN ══════════════ -->
        <main>

            <!-- ПОИСК -->
            <form method="GET" action="" class="search-wrap">
                <input type="hidden" name="page" value="1">
                <input
                    class="search-input"
                    type="text"
                    name="search"
                    value="<?= h($search) ?>"
                    placeholder="Поиск по имени или городу..."
                    autocomplete="off"
                >
                <span class="search-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </span>
            </form>

            <!-- ══════ СПИСОК ВОЛОНТЁРОВ ══════ -->
            <div class="card-list">
                <?php if (empty($volunteers)): ?>
                    <div class="empty-state">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <p>Волонтёры не найдены</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($volunteers as $v):
                        $fullName = h($v['last_name']) . ' ' . h($v['first_name'])
                                . ($v['middle_name'] ? ' ' . h($v['middle_name']) : '');
                    ?>
                    <a href="volunteer.php?id=<?= (int)$v['user_id'] ?>" class="volunteer-card">
                        <div class="vol-info">
                            <div class="vol-name"><?= $fullName ?></div>
                            <?php if ($v['city']): ?>
                            <div class="vol-city">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                г. <?= h($v['city']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
        </div>

        <!-- ══════ ПАГИНАЦИЯ ══════ -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= pageUrl($page - 1, $search) ?>" class="page-btn" title="Назад">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </a>
            <?php else: ?>
                <button class="page-btn" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end   = min($total_pages, $page + 2);
            if ($start > 1) echo '<span class="page-btn" style="border:none;cursor:default;">…</span>';
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="<?= pageUrl($i, $search) ?>"
                class="page-btn <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor;
            if ($end < $total_pages) echo '<span class="page-btn" style="border:none;cursor:default;">…</span>';
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="<?= pageUrl($page + 1, $search) ?>" class="page-btn" title="Вперёд">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            <?php else: ?>
                <button class="page-btn" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    `</main>
</div>
<footer>
    <a href="privacy.php">Политика конфиденциальности</a>
    <a href="terms.php">Политика использования</a>
    <a href="requisites.php">Реквизиты</a>
    <a href="mailto:info@gmail.com">info@gmail.com</a>
</footer>
</body>
</html>
