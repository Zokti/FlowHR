<?php
// Отключаем кэширование
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

ob_start();
require __DIR__ . '/../includes/config.php';

// Проверка авторизации и прав доступа
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    header("Location: ../auth.php");
    ob_end_clean();
    exit;
}

// Инициализация переменных
$errors = [];
$success = [];
$users = [];
$search = '';
$role_filter = '';

// Обработка поиска и фильтрации
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}
if (isset($_GET['role'])) {
    $role_filter = $_GET['role'];
}

// Обработка действий с пользователями
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        try {
            switch ($action) {
                case 'block':
                    $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success[] = "Пользователь успешно заблокирован";
                    break;

                case 'unblock':
                    $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success[] = "Пользователь успешно разблокирован";
                    break;

                case 'delete':
                    // Проверяем, не является ли пользователь последним модератором
                    if ($_SESSION['role'] === 'moderator') {
                        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user_role = $stmt->fetchColumn();

                        if ($user_role === 'moderator') {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'moderator'");
                            $stmt->execute();
                            $moderator_count = $stmt->fetchColumn();

                            if ($moderator_count <= 1) {
                                $errors[] = "Невозможно удалить последнего модератора";
                                break;
                            }
                        }
                    }

                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success[] = "Пользователь успешно удален";
                    break;

                case 'change_role':
                    $new_role = $_POST['new_role'] ?? '';
                    if (in_array($new_role, ['user', 'HR', 'moderator'])) {
                        // Проверяем, не является ли пользователь последним модератором
                        if ($_SESSION['role'] === 'moderator' && $new_role !== 'moderator') {
                            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $current_role = $stmt->fetchColumn();

                            if ($current_role === 'moderator') {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'moderator'");
                                $stmt->execute();
                                $moderator_count = $stmt->fetchColumn();

                                if ($moderator_count <= 1) {
                                    $errors[] = "Невозможно изменить роль последнего модератора";
                                    break;
                                }
                            }
                        }

                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$new_role, $user_id]);
                        $success[] = "Роль пользователя успешно изменена";
                    } else {
                        $errors[] = "Некорректная роль";
                    }
                    break;
            }
        } catch (PDOException $e) {
            $errors[] = "Ошибка при выполнении операции: " . $e->getMessage();
        }
    }
}

// Получение списка пользователей с учетом поиска и фильтрации
try {
    $query = "SELECT * FROM users WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (name LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($role_filter) {
        $query .= " AND role = ?";
        $params[] = $role_filter;
    }

    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Ошибка при получении списка пользователей: " . $e->getMessage();
}

require __DIR__ . '/../includes/header.php';
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - FlowHR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Основные стили */
        body {
            font-family: 'Arial', sans-serif;
            background: #f0f2f5;
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        .users-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            position: relative;
        }

        .page-title {
            color: #1a1a1a;
            font-size: 36px;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            padding-bottom: 20px;
            font-weight: 800;
        }

        .page-title:after {
            content: '';
            display: block;
            width: 100px;
            height: 5px;
            background: linear-gradient(90deg, #FF6F61, #FF8E53);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 0;
            border-radius: 3px;
        }

        .filters {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
            background: #ffffff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 16px 25px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-input:focus {
            outline: none;
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
            background: #fff;
        }

        .role-select {
            padding: 16px 25px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1rem;
            min-width: 180px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8f9fa;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23FF6F61' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 20px;
        }

        .role-select:focus {
            outline: none;
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
            background: #fff;
        }

        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .users-table th,
        .users-table td {
            padding: 20px 25px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .users-table th {
            background: #f8f9fa;
            color: #1a1a1a;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .users-table tr:hover {
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #FF6F61;
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.2);
            transition: all 0.3s ease;
        }

        .users-table tr:hover .user-avatar {
            transform: scale(1.1);
            border-color: #FF8E53;
        }

        .user-name {
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 1.1rem;
        }

        .user-role {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .role-user { 
            background: #e3f2fd; 
            color: #1976d2;
        }
        .role-hr { 
            background: #e8f5e9; 
            color: #2e7d32;
        }
        .role-moderator { 
            background: #fff3e0; 
            color: #e65100;
        }

        .user-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-active { 
            background: #e8f5e9; 
            color: #2e7d32;
        }
        .status-blocked { 
            background: #ffebee; 
            color: #c62828;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: nowrap;
            justify-content: flex-start;
            align-items: center;
        }

        .btn-action {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #FF6F61;
            color: white;
            font-weight: 600;
            white-space: nowrap;
            min-width: 140px;
            justify-content: center;
        }

        .btn-action:hover {
            background: #FF8E53;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-action i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .btn-action:hover i {
            transform: scale(1.2);
        }

        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            animation: slideIn 0.5s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert i {
            font-size: 1.5rem;
        }

        .alert-success {
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
            color: #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            border: 1px solid #ef9a9a;
            color: #c62828;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Стили для модальных окон */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
        }

        .modal-window {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            padding: 40px;
            border-radius: 20px;
            min-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 1001;
        }

        .modal-overlay.active .modal-window {
            transform: translate(-50%, -50%) scale(1);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-title {
            font-size: 28px;
            color: #1a1a1a;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .modal-subtitle {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 400px;
            margin: 0 auto;
        }

        .modal-body {
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
        }

        .modal-footer {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .modal-btn {
            padding: 15px 35px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 180px;
            justify-content: center;
        }

        .modal-btn-cancel {
            background: white;
            border: 2px solid #FF6F61;
            color: #FF6F61;
        }

        .modal-btn-cancel:hover {
            background: #FFF8E1;
            border-color: #FF8E53;
            color: #FF8E53;
        }

        .modal-btn-confirm {
            background: #FF6F61;
            border: none;
            color: white;
        }

        .modal-btn-confirm:hover {
            background: #FF8E53;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
        }

        .modal-btn:hover {
            transform: translateY(-2px);
        }

        .modal-btn:active {
            transform: translateY(0);
        }

        .modal-select {
            width: 100%;
            padding: 15px 25px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1.1rem;
            color: #1a1a1a;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 25px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23FF6F61' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 25px center;
            background-size: 20px;
        }

        .modal-select:hover {
            border-color: #FF6F61;
        }

        .modal-select:focus {
            outline: none;
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
        }
    </style>
</head>
<body>
    <div class="users-container">
        <h1 class="page-title">Управление пользователями</h1>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php foreach ($success as $message): ?>
                    <p><?= htmlspecialchars($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" class="search-form" style="display: flex; gap: 15px; flex: 1;">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Поиск по имени или email"
                       value="<?= htmlspecialchars($search) ?>">
                
                <select name="role" class="role-select">
                    <option value="">Все роли</option>
                    <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Пользователь</option>
                    <option value="HR" <?= $role_filter === 'HR' ? 'selected' : '' ?>>HR-специалист</option>
                    <option value="moderator" <?= $role_filter === 'moderator' ? 'selected' : '' ?>>Модератор</option>
                </select>

                <button type="submit" class="btn-action">
                    <i class="fas fa-search"></i> Поиск
                </button>
            </form>
        </div>

        <table class="users-table">
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-name">
                                <img src="<?= htmlspecialchars($user['avatar'] ?: '../assets/img/default-avatar.png') ?>" 
                                     alt="Avatar" 
                                     class="user-avatar">
                                <?= htmlspecialchars($user['name']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="user-role role-<?= strtolower($user['role']) ?>">
                                <?= htmlspecialchars($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="user-status <?= $user['is_blocked'] ? 'status-blocked' : 'status-active' ?>">
                                <?= $user['is_blocked'] ? 'Заблокирован' : 'Активен' ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action" 
                                        onclick="showEditModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['role']) ?>')">
                                    <i class="fas fa-edit"></i> Изменить роль
                                </button>
                                
                                <?php if ($user['is_blocked']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="unblock">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn-action">
                                            <i class="fas fa-unlock"></i> Разблокировать
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="block">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn-action">
                                            <i class="fas fa-lock"></i> Заблокировать
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <button class="btn-action" 
                                        onclick="showDeleteModal(<?= $user['id'] ?>)">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно изменения роли -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-window">
            <div class="modal-header">
                <h3 class="modal-title">Изменение роли пользователя</h3>
                <p class="modal-subtitle">Выберите новую роль для пользователя. Это действие может повлиять на доступные функции.</p>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="change_role">
                    <input type="hidden" name="user_id" id="editUserId">
                    
                    <select name="new_role" class="modal-select">
                        <option value="user">Пользователь</option>
                        <option value="HR">HR-специалист</option>
                        <option value="moderator">Модератор</option>
                    </select>

                    <div class="modal-footer">
                        <button type="button" class="modal-btn modal-btn-cancel" onclick="hideEditModal()">
                            <i class="fas fa-times"></i> Отмена
                        </button>
                        <button type="submit" class="modal-btn modal-btn-confirm">
                            <i class="fas fa-check"></i> Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно удаления -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-window">
            <div class="modal-header">
                <h3 class="modal-title">Подтверждение удаления</h3>
                <p class="modal-subtitle">Вы уверены, что хотите удалить этого пользователя? Это действие нельзя будет отменить.</p>
            </div>
            <div class="modal-body">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    
                    <div class="modal-footer">
                        <button type="button" class="modal-btn modal-btn-cancel" onclick="hideDeleteModal()">
                            <i class="fas fa-times"></i> Отмена
                        </button>
                        <button type="submit" class="modal-btn modal-btn-confirm">
                            <i class="fas fa-trash"></i> Удалить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showEditModal(userId, currentRole) {
            const modal = document.getElementById('editModal');
            const form = document.getElementById('editForm');
            const roleSelect = form.querySelector('select[name="new_role"]');
            
            document.getElementById('editUserId').value = userId;
            roleSelect.value = currentRole;
            
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('active'), 10);
        }

        function hideEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        function showDeleteModal(userId) {
            const modal = document.getElementById('deleteModal');
            document.getElementById('deleteUserId').value = userId;
            
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('active'), 10);
        }

        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        // Закрытие модальных окон при клике вне их области
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === editModal) {
                hideEditModal();
            }
            if (event.target === deleteModal) {
                hideDeleteModal();
            }
        }
    </script>
</body>
</html> 