<?php
require_once 'auth_check.php';
$db = getDB();

// ─── Удаление ──────────────────────────────────────────────────────────
// users.id — первичный ключ (не user_id!)
// volunteer_profiles.user_id — внешний ключ к users.id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_one' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $db->prepare("DELETE FROM users WHERE id = ? AND role = 'VOLUNTEER'")->execute([$id]);
    }
    if ($_POST['action'] === 'delete_many' && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ($ph) AND role = 'VOLUNTEER'")->execute($ids);
    }
    header('Location: volunteers.php');
    exit;
}

// ─── Список волонтёров ─────────────────────────────────────────────────
// users.id — это то что раньше я называла user_id
// volunteer_profiles.user_id ссылается на users.id
// registrations.volunteer_user_id ссылается на users.id
// registrations не имеет поля reg_id — считаем через COUNT(r.id)
$volunteers = $db->query("
    SELECT
        u.id AS user_id,
        u.email,
        u.created_at,
        CONCAT_WS(' ', vp.last_name, vp.first_name, vp.middle_name) AS full_name,
        vp.city,
        vp.phone,
        vp.birth_date,
        COUNT(r.id) AS events_count
    FROM users u
    LEFT JOIN volunteer_profiles vp ON vp.user_id = u.id
    LEFT JOIN registrations r ON r.volunteer_user_id = u.id AND r.status = 'ACTIVE'
    WHERE u.role = 'VOLUNTEER'
    GROUP BY u.id
    ORDER BY vp.last_name, vp.first_name
")->fetchAll();

$cities = $db->query("
    SELECT DISTINCT city FROM volunteer_profiles
    WHERE city IS NOT NULL ORDER BY city
")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Волонтёры — Администратор</title>
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

    <a href="index.php" class="back-link">
        <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Назад в панель управления
    </a>

    <div class="list-toolbar">
        <span class="toolbar-title">
            Волонтёры
            <span style="font-size:14px;font-weight:400;color:#888;margin-left:6px;">(<?= count($volunteers) ?>)</span>
        </span>

        <input type="text" class="search-input" id="searchInput"
               placeholder="Поиск по ФИО или email..." oninput="filterTable()">

        <select class="filter-select" id="filterCity" onchange="filterTable()">
            <option value="">Все города</option>
            <?php foreach ($cities as $city): ?>
                <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
            <?php endforeach; ?>
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
            Удалить выбранных
        </button>
    </div>

    <div class="list-table-wrap">
        <table class="list-table" id="volunteersTable">
            <thead>
                <tr>
                    <th id="thCheckbox" style="display:none;">
                        <input type="checkbox" class="row-checkbox" id="checkAll" onchange="toggleAll(this)">
                    </th>
                    <th>ФИО</th>
                    <th>Город</th>
                    <th>Дата рождения</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th style="text-align:center;">Записей</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="volunteersTableBody">

            <?php if (empty($volunteers)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:#888;">Волонтёров пока нет.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($volunteers as $v): ?>
                <?php
                    $birthdate  = $v['birth_date'] ? date('d.m.Y', strtotime($v['birth_date'])) : '—';
                    $searchData = mb_strtolower(($v['full_name'] ?? '') . ' ' . $v['email']);
                ?>
                <tr data-search="<?= htmlspecialchars($searchData) ?>"
                    data-city="<?= htmlspecialchars($v['city'] ?? '') ?>">

                    <td id="tdCheckbox_<?= $v['user_id'] ?>" style="display:none;">
                        <input type="checkbox" class="row-checkbox row-select"
                               value="<?= $v['user_id'] ?>" onchange="updateDeleteBtn()">
                    </td>
                    <td><strong><?= htmlspecialchars($v['full_name'] ?: '— нет профиля —') ?></strong></td>
                    <td><?= htmlspecialchars($v['city'] ?? '—') ?></td>
                    <td><?= $birthdate ?></td>
                    <td><?= htmlspecialchars($v['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($v['email']) ?></td>
                    <td style="text-align:center;">
                        <?php if ($v['events_count'] > 0): ?>
                            <span class="badge badge-active"><?= $v['events_count'] ?></span>
                        <?php else: ?>
                            <span style="color:#888;">0</span>
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
                            <a href="volunteer_profile.php?id=<?= $v['user_id'] ?>" class="dropdown-item">
                                <svg viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Просмотр профиля
                            </a>
                            <button class="dropdown-item danger"
                                    onclick="openDeleteModal(false, <?= $v['user_id'] ?>, '<?= htmlspecialchars(addslashes($v['full_name'] ?? $v['email'])) ?>')">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                </svg>
                                Удалить волонтёра
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

<form id="deleteForm" method="post" action="volunteers.php" style="display:none;">
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
        <p class="modal-title" id="modalTitle">Удалить волонтёра?</p>
        <p class="modal-text" id="modalText">Аккаунт будет удалён безвозвратно.</p>
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
function updateDeleteBtn(){const checked=document.querySelectorAll('.row-select:checked').length;const btn=document.getElementById('btnDeleteSelected');if(checked>0){btn.classList.add('visible');btn.innerHTML=`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg> Удалить выбранных (${checked})`;}else btn.classList.remove('visible');}
function filterTable(){const search=document.getElementById('searchInput').value.toLowerCase();const city=document.getElementById('filterCity').value;document.querySelectorAll('#volunteersTableBody tr[data-search]').forEach(row=>{const ok=(row.dataset.search||'').includes(search)&&(city===''||(row.dataset.city||'')===city);row.style.display=ok?'':'none';});}
let deleteTarget=null;
function openDeleteModal(isBulk,id=null,name=''){document.querySelectorAll('.dropdown-menu.open').forEach(m=>m.classList.remove('open'));deleteTarget=isBulk?'bulk':id;if(isBulk){const c=document.querySelectorAll('.row-select:checked').length;document.getElementById('modalTitle').textContent=`Удалить ${c} волонтёров?`;document.getElementById('modalText').textContent='Аккаунты и все записи будут удалены безвозвратно.';}else{document.getElementById('modalTitle').textContent='Удалить волонтёра?';document.getElementById('modalText').textContent=`Аккаунт «${name}» будет удалён безвозвратно.`;}document.getElementById('deleteModal').classList.add('open');}
function closeDeleteModal(){document.getElementById('deleteModal').classList.remove('open');deleteTarget=null;}
function confirmDelete(){const form=document.getElementById('deleteForm');if(deleteTarget==='bulk'){document.getElementById('deleteAction').value='delete_many';form.querySelectorAll('input[name="ids[]"]').forEach(el=>el.remove());document.querySelectorAll('.row-select:checked').forEach(cb=>{const i=document.createElement('input');i.type='hidden';i.name='ids[]';i.value=cb.value;form.appendChild(i);});}else{document.getElementById('deleteAction').value='delete_one';document.getElementById('deleteId').value=deleteTarget;}form.submit();}
document.getElementById('deleteModal').addEventListener('click',function(e){if(e.target===this)closeDeleteModal();});
</script>
</body>
</html>