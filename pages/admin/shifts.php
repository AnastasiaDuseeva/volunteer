<?php
require_once 'auth_check.php';
$db = getDB();

// Получаем event_id из URL: shifts.php?event_id=3
$eventId = intval($_GET['event_id'] ?? 0);
if (!$eventId) {
    header('Location: events.php');
    exit;
}

// Загружаем мероприятие — нам нужно его название для заголовка
$eventStmt = $db->prepare("SELECT id, title FROM events WHERE id = ?");
$eventStmt->execute([$eventId]);
$event = $eventStmt->fetch();
if (!$event) {
    header('Location: events.php');
    exit;
}

// ─── Обработка POST-запросов ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Удаление одной смены
    if ($action === 'delete_one' && !empty($_POST['id'])) {
        $db->prepare("DELETE FROM shifts WHERE id = ? AND event_id = ?")
           ->execute([intval($_POST['id']), $eventId]);
    }

    // Удаление нескольких смен
    if ($action === 'delete_many' && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        // Добавляем event_id в конец массива для условия AND event_id = ?
        $params = array_merge($ids, [$eventId]);
        $db->prepare("DELETE FROM shifts WHERE id IN ($ph) AND event_id = ?")
           ->execute($params);
    }

    // Редактирование смены — сохранение изменений
    if ($action === 'edit_save' && !empty($_POST['id'])) {
        $shiftId    = intval($_POST['id']);
        $shiftDate  = trim($_POST['shift_date'] ?? '');
        $timeStart  = trim($_POST['time_start']  ?? '');
        $timeEnd    = trim($_POST['time_end']    ?? '');
        $capacity   = intval($_POST['capacity']  ?? 0);
        $status     = in_array($_POST['status'] ?? '', ['ACTIVE','CANCELED'])
                      ? $_POST['status'] : 'ACTIVE';

        if ($shiftDate && $timeStart && $timeEnd && $capacity > 0) {
            $db->prepare("
                UPDATE shifts
                SET shift_date = ?, time_start = ?, time_end = ?, capacity = ?, status = ?
                WHERE id = ? AND event_id = ?
            ")->execute([$shiftDate, $timeStart, $timeEnd, $capacity, $status, $shiftId, $eventId]);
        }
    }

    header("Location: shifts.php?event_id=$eventId");
    exit;
}

// ─── Какую смену редактируем? ─────────────────────────────────────────
// Если в URL есть ?edit=5 — показываем форму редактирования для смены 5
$editId = intval($_GET['edit'] ?? 0);
$editShift = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM shifts WHERE id = ? AND event_id = ?");
    $stmt->execute([$editId, $eventId]);
    $editShift = $stmt->fetch();
}

// ─── Список смен мероприятия ──────────────────────────────────────────
// Считаем зарегистрированных по каждой смене
$shifts = $db->prepare("
    SELECT
        s.id,
        s.shift_date,
        s.time_start,
        s.time_end,
        s.capacity,
        s.status,
        COUNT(r.id) AS registered_count
    FROM shifts s
    LEFT JOIN registrations r ON r.shift_id = s.id AND r.status = 'ACTIVE'
    WHERE s.event_id = ?
    GROUP BY s.id
    ORDER BY s.shift_date, s.time_start
");
$shifts->execute([$eventId]);
$shifts = $shifts->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Смены — <?= htmlspecialchars($event['title']) ?></title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">
    <a href="index.php" class="admin-logo">
        <img src="../img/log_main.png" alt="Логотип">
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
        Назад к мероприятиям
    </a>

    <!-- Название мероприятия как подзаголовок -->
    <div style="margin-bottom:24px;">
        <h1 class="page-heading" style="margin-bottom:4px;">Смены</h1>
        <p style="font-size:15px;color:#888;">
            <?= htmlspecialchars($event['title']) ?>
        </p>
    </div>

    <!-- ─── ФОРМА РЕДАКТИРОВАНИЯ (показывается если выбрана смена) ─── -->
    <?php if ($editShift): ?>
    <div class="form-card" style="margin-bottom:28px;">
        <h2 class="form-title" style="font-size:16px;">Редактировать смену</h2>

        <form action="" method="post">
            <input type="hidden" name="action" value="edit_save">
            <input type="hidden" name="id" value="<?= $editShift['id'] ?>">

            <!-- Заголовки колонок -->
            <div style="display:grid;grid-template-columns:170px 130px 130px 110px 150px;gap:14px;margin-bottom:6px;">
                <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Дата</span>
                <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Начало</span>
                <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Окончание</span>
                <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Мест</span>
                <span style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.04em;">Статус</span>
            </div>

            <div style="display:grid;grid-template-columns:170px 130px 130px 110px 150px;gap:14px;margin-bottom:20px;">
                <input type="date" name="shift_date" class="form-input"
                       value="<?= htmlspecialchars($editShift['shift_date']) ?>" required>

                <!-- time_start в БД хранится как TIME (HH:MM:SS), для input type=time нужно HH:MM -->
                <input type="time" name="time_start" class="form-input"
                       value="<?= substr($editShift['time_start'], 0, 5) ?>" required>

                <input type="time" name="time_end" class="form-input"
                       value="<?= substr($editShift['time_end'], 0, 5) ?>" required>

                <input type="number" name="capacity" class="form-input"
                       value="<?= $editShift['capacity'] ?>" min="1" max="9999" required>

                <select name="status" class="form-select">
                    <option value="ACTIVE"   <?= $editShift['status'] === 'ACTIVE'   ? 'selected' : '' ?>>Активна</option>
                    <option value="CANCELED" <?= $editShift['status'] === 'CANCELED' ? 'selected' : '' ?>>Отменена</option>
                </select>
            </div>

            <div class="form-actions" style="padding-top:0;border-top:none;margin-top:0;">
                <button type="submit" class="btn-submit">Сохранить</button>
                <a href="shifts.php?event_id=<?= $eventId ?>" class="btn-cancel">Отмена</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ─── ПАНЕЛЬ ИНСТРУМЕНТОВ ─── -->
    <div class="list-toolbar">
        <span class="toolbar-title">
            Список смен
            <span style="font-size:14px;font-weight:400;color:#888;margin-left:6px;">(<?= count($shifts) ?>)</span>
        </span>

        <button class="btn-secondary" id="btnSelectMode" onclick="toggleSelectMode()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 11 12 14 22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            Выбрать несколько
        </button>

        <button class="btn-delete-selected" id="btnDeleteSelected" onclick="openDeleteModal(true)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/>
            </svg>
            Удалить выбранные
        </button>

        <!-- Кнопка «Добавить смену» — переходим на shift_create с предвыбранным мероприятием -->
        <a href="shift_create.php?event_id=<?= $eventId ?>" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить смену
        </a>
    </div>

    <!-- ─── ТАБЛИЦА СМЕН ─── -->
    <div class="list-table-wrap">
        <table class="list-table" id="shiftsTable">
            <thead>
                <tr>
                    <th id="thCheckbox" style="display:none;">
                        <input type="checkbox" class="row-checkbox" id="checkAll" onchange="toggleAll(this)">
                    </th>
                    <th>Дата</th>
                    <th>Начало</th>
                    <th>Окончание</th>
                    <th style="text-align:center;">Записано</th>
                    <th style="text-align:center;">Мест всего</th>
                    <th style="text-align:center;">Свободно</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="shiftsTableBody">

            <?php if (empty($shifts)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:#888;">
                        Смен пока нет.
                        <a href="shift_create.php?event_id=<?= $eventId ?>" style="color:#4A7FC1;">
                            Добавить первую
                        </a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($shifts as $shift): ?>
                <?php
                    $freePlaces  = $shift['capacity'] - $shift['registered_count'];
                    $freePlaces  = max(0, $freePlaces);
                    $isCanceled  = $shift['status'] === 'CANCELED';
                    $rowStyle    = $isCanceled ? 'opacity:.6;' : '';
                ?>
                <tr style="<?= $rowStyle ?>">

                    <td id="tdCheckbox_<?= $shift['id'] ?>" style="display:none;">
                        <input type="checkbox" class="row-checkbox row-select"
                               value="<?= $shift['id'] ?>" onchange="updateDeleteBtn()">
                    </td>

                    <td><strong><?= date('d.m.Y', strtotime($shift['shift_date'])) ?></strong></td>

                    <!-- Обрезаем секунды из формата HH:MM:SS → HH:MM -->
                    <td><?= substr($shift['time_start'], 0, 5) ?></td>
                    <td><?= substr($shift['time_end'],   0, 5) ?></td>

                    <td style="text-align:center;">
                        <?php if ($shift['registered_count'] > 0): ?>
                            <span class="badge badge-active"><?= $shift['registered_count'] ?></span>
                        <?php else: ?>
                            <span style="color:#888;">0</span>
                        <?php endif; ?>
                    </td>

                    <td style="text-align:center;"><?= $shift['capacity'] ?></td>

                    <td style="text-align:center;">
                        <?php if ($freePlaces === 0): ?>
                            <span class="badge badge-closed">0</span>
                        <?php else: ?>
                            <span class="badge badge-open"><?= $freePlaces ?></span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($isCanceled): ?>
                            <span class="badge badge-closed">Отменена</span>
                        <?php else: ?>
                            <span class="badge badge-open">Активна</span>
                        <?php endif; ?>
                    </td>

                    <td class="actions-cell">
                        <button class="btn-dots" onclick="toggleDropdown(this)" aria-label="Действия">
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <circle cx="12" cy="5"  r="1.2" fill="currentColor"/>
                                <circle cx="12" cy="12" r="1.2" fill="currentColor"/>
                                <circle cx="12" cy="19" r="1.2" fill="currentColor"/>
                            </svg>
                        </button>
                        <div class="dropdown-menu">
                            <!-- Редактировать — открывает форму наверху страницы -->
                            <a href="shifts.php?event_id=<?= $eventId ?>&edit=<?= $shift['id'] ?>"
                               class="dropdown-item">
                                <svg viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Редактировать
                            </a>
                            <button class="dropdown-item danger"
                                    onclick="openDeleteModal(false, <?= $shift['id'] ?>,
                                    '<?= date('d.m.Y', strtotime($shift['shift_date'])) . ' ' . substr($shift['time_start'],0,5) . '–' . substr($shift['time_end'],0,5) ?>')">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                </svg>
                                Удалить смену
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            </tbody>
        </table>
    </div>

</main>

<footer class="admin-footer">
    <a href="../pages/privacy.php">Политика конфиденциальности</a>
    <a href="../pages/terms.php">Политика использования</a>
    <a href="mailto:info@pomozhem.ru">info@pomozhem.ru</a>

</footer>

<!-- Скрытая форма для удаления -->
<form id="deleteForm" method="post" action="shifts.php?event_id=<?= $eventId ?>" style="display:none;">
    <input type="hidden" name="action" id="deleteAction">
    <input type="hidden" name="id" id="deleteId">
</form>

<!-- Модальное окно подтверждения удаления -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">
            <svg viewBox="0 0 24 24">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/>
            </svg>
        </div>
        <p class="modal-title" id="modalTitle">Удалить смену?</p>
        <p class="modal-text" id="modalText">
            Все записи волонтёров на эту смену тоже будут удалены.
        </p>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()">Отмена</button>
            <button class="btn-modal-confirm" onclick="confirmDelete()">Удалить</button>
        </div>
    </div>
</div>

<script>
/* ─── Дропдаун с правильным позиционированием ─── */
document.addEventListener('click', function(e) {
    if (!e.target.closest('.actions-cell')) {
        document.querySelectorAll('.dropdown-menu.open').forEach(function(m) {
            m.classList.remove('open');
            m.style.top = '';
            m.style.left = '';
            m.style.visibility = '';
        });
    }
});

function toggleDropdown(btn){
    var menu=btn.nextElementSibling;
    var isOpen=menu.classList.contains('open');
    
    // Закрываем ВСЕ открытые меню
    document.querySelectorAll('.dropdown-menu.open').forEach(function(m){
        m.classList.remove('open');
    });
    
    // Если это меню было закрыто, открываем его
    if(!isOpen){
        menu.style.visibility='hidden';
        menu.style.display='block';
        var menuW=menu.offsetWidth;
        var menuH=menu.offsetHeight;
        menu.style.display='';
        
        var rect=btn.getBoundingClientRect();
        var top=rect.bottom+4;
        var left=rect.right-menuW;
        
        // Проверяем, влезает ли меню вниз
        if(top+menuH>window.innerHeight-8){
            top=rect.top-menuH-4;
        }
        
        // Проверяем, не выходит ли меню влево
        if(left<8)left=8;
        
        // Если справа выходит за границы, сдвигаем влево
        if(left+menuW>window.innerWidth-8){
            left=window.innerWidth-menuW-8;
        }
        
        menu.style.position='fixed';
        menu.style.top=top+'px';
        menu.style.left=left+'px';
        menu.style.visibility='visible';
        menu.classList.add('open');
    }
}

/* ─── Режим выбора нескольких ─── */
let selectMode = false;

function toggleSelectMode() {
    selectMode = !selectMode;
    var btn = document.getElementById('btnSelectMode');
    document.getElementById('thCheckbox').style.display = selectMode ? '' : 'none';
    document.querySelectorAll('[id^="tdCheckbox_"]').forEach(function(td) {
        td.style.display = selectMode ? '' : 'none';
    });
    if (!selectMode) {
        document.querySelectorAll('.row-select').forEach(function(cb) { cb.checked = false; });
        document.getElementById('checkAll').checked = false;
        document.getElementById('btnDeleteSelected').classList.remove('visible');
        btn.style.cssText = '';
    } else {
        btn.style.background = '#EBF2FB';
        btn.style.borderColor = '#4A7FC1';
        btn.style.color = '#2C5F9A';
    }
}

function toggleAll(masterCb) {
    document.querySelectorAll('.row-select').forEach(function(cb) {
        if (cb.closest('tr').style.display !== 'none') cb.checked = masterCb.checked;
    });
    updateDeleteBtn();
}

function updateDeleteBtn() {
    var checked = document.querySelectorAll('.row-select:checked').length;
    var btn = document.getElementById('btnDeleteSelected');
    if (checked > 0) {
        btn.classList.add('visible');
        btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg> Удалить выбранные (' + checked + ')';
    } else {
        btn.classList.remove('visible');
    }
}

/* ─── Модальное окно удаления ─── */
var deleteTarget = null;

function openDeleteModal(isBulk, id, name) {
    document.querySelectorAll('.dropdown-menu.open').forEach(function(m) {
        m.classList.remove('open');
        m.style.top = '';
        m.style.left = '';
    });
    deleteTarget = isBulk ? 'bulk' : id;

    if (isBulk) {
        var c = document.querySelectorAll('.row-select:checked').length;
        document.getElementById('modalTitle').textContent = 'Удалить ' + c + ' смен?';
        document.getElementById('modalText').textContent  = 'Все записи волонтёров на эти смены тоже будут удалены.';
    } else {
        document.getElementById('modalTitle').textContent = 'Удалить смену?';
        document.getElementById('modalText').textContent  = 'Смена «' + name + '» и все записи волонтёров на неё будут удалены.';
    }
    document.getElementById('deleteModal').classList.add('open');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
    deleteTarget = null;
}

function confirmDelete() {
    var form = document.getElementById('deleteForm');
    if (deleteTarget === 'bulk') {
        document.getElementById('deleteAction').value = 'delete_many';
        form.querySelectorAll('input[name="ids[]"]').forEach(function(el) { el.remove(); });
        document.querySelectorAll('.row-select:checked').forEach(function(cb) {
            var i = document.createElement('input');
            i.type = 'hidden'; i.name = 'ids[]'; i.value = cb.value;
            form.appendChild(i);
        });
    } else {
        document.getElementById('deleteAction').value = 'delete_one';
        document.getElementById('deleteId').value = deleteTarget;
    }
    form.submit();
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// Если открыта форма редактирования — прокручиваем к ней
<?php if ($editShift): ?>
document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
<?php endif; ?>
</script>

</body>
</html>
