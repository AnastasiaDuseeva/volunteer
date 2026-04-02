<?php
require_once 'auth_check.php';
$db = getDB();

// ─── Удаление ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_one' && !empty($_POST['id'])) {
        $db->prepare("DELETE FROM events WHERE id = ?")->execute([intval($_POST['id'])]);
    }
    if ($_POST['action'] === 'delete_many' && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM events WHERE id IN ($ph)")->execute($ids);
    }
    header('Location: events.php');
    exit;
}

// ─── Список мероприятий ────────────────────────────────────────────────
// event_categories.id — первичный ключ категорий (не category_id!)
// registrations.volunteer_user_id — id волонтёра
// registrations не имеет reg_id — используем COUNT(r.id)
$events = $db->query("
    SELECT
        e.id,
        e.title,
        e.city,
        e.start_date,
        e.end_date,
        e.required_volunteers,
        e.recruitment_status,
        ec.name AS category_name,
        COALESCE(
            (SELECT COUNT(*)
             FROM registrations r
             JOIN shifts s ON s.id = r.shift_id
             WHERE s.event_id = e.id AND r.status = 'ACTIVE'),
        0) AS registered_total
    FROM events e
    LEFT JOIN event_categories ec ON e.category_id = ec.id
    ORDER BY e.start_date DESC
")->fetchAll();

$cities     = $db->query("SELECT DISTINCT city FROM events WHERE city IS NOT NULL ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
$categories = $db->query("SELECT id, name FROM event_categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мероприятия — Администратор</title>
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

    <a href="index.php" class="back-link">
        <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Назад в панель управления
    </a>

    <?php if (isset($_GET['created'])): ?>
    <div style="background:#E7F7EE;border:1px solid #3DAA6A;border-radius:10px;padding:12px 18px;margin-bottom:20px;color:#2d7a4f;font-weight:600;">
        ✓ Мероприятие успешно создано
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
    <div style="background:#E7F7EE;border:1px solid #3DAA6A;border-radius:10px;padding:12px 18px;margin-bottom:20px;color:#2d7a4f;font-weight:600;">
        ✓ Мероприятие успешно обновлено
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['shifts_added'])): ?>
    <div style="background:#E7F7EE;border:1px solid #3DAA6A;border-radius:10px;padding:12px 18px;margin-bottom:20px;color:#2d7a4f;font-weight:600;">
        ✓ Добавлено смен: <?= intval($_GET['shifts_added']) ?>
    </div>
    <?php endif; ?>

    <div class="list-toolbar">
        <span class="toolbar-title">Мероприятия</span>

        <input type="text" class="search-input" id="searchInput"
               placeholder="Поиск по названию..." oninput="filterTable()">

        <select class="filter-select" id="filterCity" onchange="filterTable()">
            <option value="">Все города</option>
            <?php foreach ($cities as $city): ?>
                <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
            <?php endforeach; ?>
        </select>

        <select class="filter-select" id="filterCategory" onchange="filterTable()">
            <option value="">Все категории</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select class="filter-select" id="filterStatus" onchange="filterTable()">
            <option value="">Все статусы</option>
            <option value="Открыт">Открыт</option>
            <option value="Закрыт">Закрыт</option>
            <option value="Проект">Проект</option>
            <option value="Завершён">Завершён</option>
        </select>

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

        <a href="event_create.php" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить мероприятие
        </a>
    </div>

    <div class="list-table-wrap">
        <table class="list-table" id="eventsTable">
            <thead>
                <tr>
                    <th id="thCheckbox" style="display:none;">
                        <input type="checkbox" class="row-checkbox" id="checkAll" onchange="toggleAll(this)">
                    </th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Город</th>
                    <th>Даты</th>
                    <th>Статус</th>
                    <th>Волонтёры</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="eventsTableBody">

            <?php if (empty($events)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:#888;">
                        Мероприятий пока нет. <a href="event_create.php" style="color:#4A7FC1;">Создать первое</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($events as $ev): ?>
                <?php
                    $dateStart  = $ev['start_date'] ? date('d.m.Y', strtotime($ev['start_date'])) : '—';
                    $dateEnd    = $ev['end_date']   ? date('d.m.Y', strtotime($ev['end_date']))   : '';
                    $dateStr    = $dateEnd ? "$dateStart — $dateEnd" : $dateStart;
                    $badgeClass = match($ev['recruitment_status']) {
                        'Открыт'   => 'badge-open',
                        'Закрыт', 'Завершён' => 'badge-closed',
                        default    => 'badge-active',
                    };
                ?>
                <tr data-title="<?= htmlspecialchars(mb_strtolower($ev['title'])) ?>"
                    data-city="<?= htmlspecialchars($ev['city'] ?? '') ?>"
                    data-category="<?= htmlspecialchars($ev['category_name'] ?? '') ?>"
                    data-status="<?= htmlspecialchars($ev['recruitment_status']) ?>">

                    <td id="tdCheckbox_<?= $ev['id'] ?>" style="display:none;">
                        <input type="checkbox" class="row-checkbox row-select"
                               value="<?= $ev['id'] ?>" onchange="updateDeleteBtn()">
                    </td>
                    <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                    <td><?= htmlspecialchars($ev['category_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($ev['city'] ?? '—') ?></td>
                    <td style="white-space:nowrap;"><?= $dateStr ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($ev['recruitment_status']) ?></span></td>
                    <td><?= $ev['registered_total'] ?> / <?= $ev['required_volunteers'] ?></td>

                    <td class="actions-cell">
                        <button class="btn-dots" onclick="toggleDropdown(this)" aria-label="Действия">
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <circle cx="12" cy="5"  r="1.2" fill="currentColor"/>
                                <circle cx="12" cy="12" r="1.2" fill="currentColor"/>
                                <circle cx="12" cy="19" r="1.2" fill="currentColor"/>
                            </svg>
                        </button>
                        <div class="dropdown-menu">
                            <a href="event_edit.php?id=<?= $ev['id'] ?>" class="dropdown-item">
                                <svg viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Редактировать
                            </a>
                            <a href="shifts.php?event_id=<?= $ev['id'] ?>" class="dropdown-item">
                                <svg viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Управление сменами
                            </a>
                            <a href="shift_create.php?event_id=<?= $ev['id'] ?>" class="dropdown-item">
                                <svg viewBox="0 0 24 24">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Добавить смену
                            </a>
                            <button class="dropdown-item danger"
                                    onclick="openDeleteModal(false, <?= $ev['id'] ?>, '<?= htmlspecialchars(addslashes($ev['title'])) ?>')">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                </svg>
                                Удалить мероприятие
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

<form id="deleteForm" method="post" action="events.php" style="display:none;">
    <input type="hidden" name="action" id="deleteAction">
    <input type="hidden" name="id" id="deleteId">
</form>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">
            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/>
            </svg>
        </div>
        <p class="modal-title" id="modalTitle">Удалить мероприятие?</p>
        <p class="modal-text" id="modalText">Это действие необратимо.</p>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeDeleteModal()">Отмена</button>
            <button class="btn-modal-confirm" onclick="confirmDelete()">Удалить</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('click',function(e){
    if(!e.target.closest('.actions-cell')){
        document.querySelectorAll('.dropdown-menu.open').forEach(function(m){
            m.classList.remove('open');m.style.top='';m.style.left='';m.style.visibility='';
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
let selectMode=false;
function toggleSelectMode(){selectMode=!selectMode;const btn=document.getElementById('btnSelectMode');document.getElementById('thCheckbox').style.display=selectMode?'':'none';document.querySelectorAll('[id^="tdCheckbox_"]').forEach(td=>{td.style.display=selectMode?'':'none';});if(!selectMode){document.querySelectorAll('.row-select').forEach(cb=>cb.checked=false);document.getElementById('checkAll').checked=false;document.getElementById('btnDeleteSelected').classList.remove('visible');btn.style.cssText='';}else{btn.style.background='#EBF2FB';btn.style.borderColor='#4A7FC1';btn.style.color='#2C5F9A';}}
function toggleAll(masterCb){document.querySelectorAll('.row-select').forEach(cb=>{if(cb.closest('tr').style.display!=='none')cb.checked=masterCb.checked;});updateDeleteBtn();}
function updateDeleteBtn(){const checked=document.querySelectorAll('.row-select:checked').length;const btn=document.getElementById('btnDeleteSelected');if(checked>0){btn.classList.add('visible');btn.innerHTML=`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg> Удалить выбранные (${checked})`;}else btn.classList.remove('visible');}
function filterTable(){const search=document.getElementById('searchInput').value.toLowerCase();const city=document.getElementById('filterCity').value;const cat=document.getElementById('filterCategory').value;const status=document.getElementById('filterStatus').value;document.querySelectorAll('#eventsTableBody tr[data-title]').forEach(row=>{const ok=(row.dataset.title||'').includes(search)&&(city===''||(row.dataset.city||'')===city)&&(cat===''||(row.dataset.category||'')===cat)&&(status===''||(row.dataset.status||'')===status);row.style.display=ok?'':'none';});}
let deleteTarget=null;
function openDeleteModal(isBulk,id=null,name=''){document.querySelectorAll('.dropdown-menu.open').forEach(m=>m.classList.remove('open'));deleteTarget=isBulk?'bulk':id;if(isBulk){const c=document.querySelectorAll('.row-select:checked').length;document.getElementById('modalTitle').textContent=`Удалить ${c} мероприятий?`;document.getElementById('modalText').textContent='Все смены и записи волонтёров будут удалены безвозвратно.';}else{document.getElementById('modalTitle').textContent='Удалить мероприятие?';document.getElementById('modalText').textContent=`«${name}» и все его смены будут удалены безвозвратно.`;}document.getElementById('deleteModal').classList.add('open');}
function closeDeleteModal(){document.getElementById('deleteModal').classList.remove('open');deleteTarget=null;}
function confirmDelete(){const form=document.getElementById('deleteForm');if(deleteTarget==='bulk'){document.getElementById('deleteAction').value='delete_many';form.querySelectorAll('input[name="ids[]"]').forEach(el=>el.remove());document.querySelectorAll('.row-select:checked').forEach(cb=>{const i=document.createElement('input');i.type='hidden';i.name='ids[]';i.value=cb.value;form.appendChild(i);});}else{document.getElementById('deleteAction').value='delete_one';document.getElementById('deleteId').value=deleteTarget;}form.submit();}
document.getElementById('deleteModal').addEventListener('click',function(e){if(e.target===this)closeDeleteModal();});
</script>
</body>
</html>