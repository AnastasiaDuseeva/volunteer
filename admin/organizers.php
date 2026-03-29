<?php
require_once 'auth_check.php';
$db = getDB();

// ─── Удаление ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_one' && !empty($_POST['id'])) {
        $db->prepare("DELETE FROM users WHERE id = ? AND role = 'ORGANIZER'")->execute([intval($_POST['id'])]);
    }
    if ($_POST['action'] === 'delete_many' && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ($ph) AND role = 'ORGANIZER'")->execute($ids);
    }
    header('Location: organizers.php');
    exit;
}

// ─── Список организаторов ──────────────────────────────────────────────
// users.id — первичный ключ
// organizer_profiles: contact_phone, contact_email (не phone/email!)
$organizers = $db->query("
    SELECT
        u.id AS user_id,
        u.email,
        op.org_name,
        op.inn,
        op.kpp,
        op.legal_address,
        op.contact_phone,
        op.contact_email
    FROM users u
    LEFT JOIN organizer_profiles op ON op.user_id = u.id
    WHERE u.role = 'ORGANIZER'
    ORDER BY op.org_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Организаторы — Администратор</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">
    <a href="index.php" class="admin-logo">
        <img src="../img/log_main.png" alt="Логотип">
        <span class="admin-logo-text">Поможем<br>вместе</span>
    </a>
    <span class="admin-badge">Администратор</span>
    <span class="admin-header-name"><?= htmlspecialchars($admin['name']) ?></span>
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

    <?php if (isset($_GET['success'])): ?>
    <div style="background:#E7F7EE;border:1px solid #3DAA6A;border-radius:10px;padding:12px 18px;margin-bottom:20px;color:#2d7a4f;font-weight:600;">
        ✓ Организатор успешно добавлен
    </div>
    <?php endif; ?>

    <div class="list-toolbar">
        <span class="toolbar-title">
            Организаторы
            <span style="font-size:14px;font-weight:400;color:#888;margin-left:6px;">(<?= count($organizers) ?>)</span>
        </span>

        <input type="text" class="search-input" id="searchInput"
               placeholder="Поиск по названию или ИНН..." oninput="filterTable()">

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

        <a href="organizer_add.php" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить организатора
        </a>
    </div>

    <div class="list-table-wrap">
        <table class="list-table" id="organizersTable">
            <thead>
                <tr>
                    <th id="thCheckbox" style="display:none;">
                        <input type="checkbox" class="row-checkbox" id="checkAll" onchange="toggleAll(this)">
                    </th>
                    <th>Наименование</th>
                    <th>ИНН</th>
                    <th>КПП</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="organizersTableBody">

            <?php if (empty($organizers)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:#888;">
                        Организаторов пока нет. <a href="organizer_add.php" style="color:#4A7FC1;">Добавить первого</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($organizers as $org): ?>
                <?php $searchData = mb_strtolower(($org['org_name'] ?? '') . ' ' . ($org['inn'] ?? '')); ?>
                <tr data-search="<?= htmlspecialchars($searchData) ?>">
                    <td id="tdCheckbox_<?= $org['user_id'] ?>" style="display:none;">
                        <input type="checkbox" class="row-checkbox row-select"
                               value="<?= $org['user_id'] ?>" onchange="updateDeleteBtn()">
                    </td>
                    <td><strong><?= htmlspecialchars($org['org_name'] ?? '— нет профиля —') ?></strong></td>
                    <td><?= htmlspecialchars($org['inn'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($org['kpp'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($org['contact_phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($org['contact_email'] ?? $org['email']) ?></td>
                    <td class="actions-cell">
                        <button class="btn-dots" onclick="toggleDropdown(this)" aria-label="Действия">
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <circle cx="12" cy="5"  r="1.2" fill="currentColor"/>
                                <circle cx="12" cy="12" r="1.2" fill="currentColor"/>
                                <circle cx="12" cy="19" r="1.2" fill="currentColor"/>
                            </svg>
                        </button>
                        <div class="dropdown-menu">
                            <a href="organizer_edit.php?id=<?= $org['user_id'] ?>" class="dropdown-item">
                                <svg viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Редактировать
                            </a>
                            <button class="dropdown-item danger"
                                    onclick="openDeleteModal(false, <?= $org['user_id'] ?>, '<?= htmlspecialchars(addslashes($org['org_name'] ?? $org['email'])) ?>')">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                </svg>
                                Удалить организатора
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

<form id="deleteForm" method="post" action="organizers.php" style="display:none;">
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
        <p class="modal-title" id="modalTitle">Удалить организатора?</p>
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
function filterTable(){const search=document.getElementById('searchInput').value.toLowerCase();document.querySelectorAll('#organizersTableBody tr[data-search]').forEach(row=>{row.style.display=(row.dataset.search||'').includes(search)?'':'none';});}
let deleteTarget=null;
function openDeleteModal(isBulk,id=null,name=''){document.querySelectorAll('.dropdown-menu.open').forEach(m=>m.classList.remove('open'));deleteTarget=isBulk?'bulk':id;if(isBulk){const c=document.querySelectorAll('.row-select:checked').length;document.getElementById('modalTitle').textContent=`Удалить ${c} организаторов?`;document.getElementById('modalText').textContent='Аккаунты будут удалены безвозвратно.';}else{document.getElementById('modalTitle').textContent='Удалить организатора?';document.getElementById('modalText').textContent=`«${name}» будет удалён безвозвратно.`;}document.getElementById('deleteModal').classList.add('open');}
function closeDeleteModal(){document.getElementById('deleteModal').classList.remove('open');deleteTarget=null;}
function confirmDelete(){const form=document.getElementById('deleteForm');if(deleteTarget==='bulk'){document.getElementById('deleteAction').value='delete_many';form.querySelectorAll('input[name="ids[]"]').forEach(el=>el.remove());document.querySelectorAll('.row-select:checked').forEach(cb=>{const i=document.createElement('input');i.type='hidden';i.name='ids[]';i.value=cb.value;form.appendChild(i);});}else{document.getElementById('deleteAction').value='delete_one';document.getElementById('deleteId').value=deleteTarget;}form.submit();}
document.getElementById('deleteModal').addEventListener('click',function(e){if(e.target===this)closeDeleteModal();});
</script>
</body>
</html>