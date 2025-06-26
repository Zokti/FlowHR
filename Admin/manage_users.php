<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь и является ли он админом
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Получаем список всех пользователей
$users_query = "SELECT id, name, email, login, role, is_blocked, blocked_until FROM users";
$users_result = $pdo->query($users_query);
$users = $users_result->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #F8F9FA;
            color: #2C3E50;
            padding: 30px;
            margin-left: 250px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
            background: #FFFFFF;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(44, 62, 80, 0.08);
            animation: fadeIn 0.5s ease;
        }

        h1 {
            color: #2C3E50;
            text-align: center;
            margin: 0 0 40px;
            font-size: 42px;
            font-weight: 800;
            position: relative;
            padding-bottom: 20px;
            animation: slideDown 0.5s ease;
        }

        h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 4px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            border-radius: 2px;
        }

        h1 i {
            margin-right: 15px;
            color: #FF6F61;
            animation: rotateIn 0.5s ease;
        }

        .table {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.05);
            border: none;
            overflow: hidden;
            margin-top: 30px;
        }

        .table th {
            background: #F8F9FA;
            color: #2C3E50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            padding: 20px;
            border-bottom: 2px solid #F8F9FA;
        }

        .table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid #F8F9FA;
            transition: all 0.3s ease;
            color: #2C3E50;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table td:last-child {
            max-width: none;
            min-width: 450px;
        }

        .table tr:hover td {
            background: #F8F9FA;
            transform: scale(1.01);
        }

        .actions-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 180px;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.1);
        }

        .btn-action i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .btn-action:hover i {
            transform: scale(1.2);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.2);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-delete {
            background: #FF3B2F;
            color: #FFFFFF;
        }

        .btn-block {
            background: #FF6F61;
            color: #FFFFFF;
        }

        .btn-unblock {
            background: #28a745;
            color: #FFFFFF;
        }

        .status-badge {
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: left;
            min-width: 180px;
            max-width: 180px;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            white-space: normal;
            word-wrap: break-word;
        }

        .status-active {
            background: #28a745;
            color: #FFFFFF;
            border: 2px solid #28a745;
            flex-wrap: wrap;
        }

        .status-blocked {
            background: #FF6F61;
            color: #FFFFFF;
            border: 2px solid #FF6F61;
            flex-direction: column;
            align-items: flex-start;
            padding: 8px 12px;
            min-width: 180px;
            max-width: 180px;
            white-space: normal;
            word-wrap: break-word;
        }

        .status-blocked .blocked-info {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-top: 2px;
            padding-left: 24px;
            white-space: normal;
            word-wrap: break-word;
        }

        .status-blocked i {
            font-size: 1rem;
            margin-right: 6px;
        }

        .status-active i {
            font-size: 1rem;
        }

        .modal-content {
            border: none;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(44, 62, 80, 0.15);
            overflow: hidden;
            animation: modalSlideIn 0.4s ease;
        }

        .modal-header {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 30px;
            border: none;
            position: relative;
        }

        .modal-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0.5), rgba(255,255,255,0.1));
        }

        .modal-title {
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(44, 62, 80, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .btn-close {
            position: absolute;
            right: 20px;
            top: 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
            color: #FFFFFF;
            font-size: 20px;
            opacity: 0.8;
        }

        .btn-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
            opacity: 1;
        }

        .modal-body {
            padding: 35px;
            background: #F8F9FA;
        }

        .form-label {
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .form-control, .form-select {
            padding: 15px 20px;
            border: 2px solid #F8F9FA;
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #FFFFFF;
            width: 100%;
            color: #2C3E50;
        }

        .form-control:focus, .form-select:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
            transform: translateY(-2px);
        }

        .modal-footer {
            padding: 25px 35px;
            background: #FFFFFF;
            border-top: 1px solid #F8F9FA;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn-primary {
            background: #FF6F61;
            border: none;
            padding: 15px 40px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #FFFFFF;
            transition: all 0.3s ease;
            min-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .btn-primary:hover {
            background: #FF3B2F;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 111, 97, 0.3);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-primary i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-primary:hover i {
            transform: scale(1.2);
        }

        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes rotateIn {
            from {
                opacity: 0;
                transform: rotate(-180deg);
            }
            to {
                opacity: 1;
                transform: rotate(0);
            }
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .container {
                padding: 20px;
            }

            h1 {
                font-size: 32px;
            }

            .table {
                display: block;
                overflow-x: auto;
            }

            .actions-container {
                min-width: 100%;
            }

            .table td:last-child {
                min-width: 100%;
            }

            .btn-action {
                width: 100%;
                padding: 8px 16px;
            }

            .status-badge {
                min-width: 100%;
            }

            .status-blocked {
                align-items: center;
            }

            .status-blocked .blocked-info {
                padding-left: 0;
                text-align: center;
            }
        }

        /* Стили для уведомлений */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .toast {
            background: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
            min-width: 300px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.hide {
            opacity: 0;
            transform: translateX(100%);
        }

        .toast-header {
            background: #FF6F61;
            color: #FFFFFF;
            border-radius: 10px 10px 0 0;
            padding: 12px 15px;
            display: flex;
            align-items: center;
        }

        .toast-header i {
            margin-right: 8px;
        }

        .toast-body {
            padding: 15px;
            color: #2C3E50;
            font-weight: 500;
        }

        .toast-success .toast-header {
            background: #28a745;
        }

        .toast-warning .toast-header {
            background: #ffc107;
        }

        .toast-danger .toast-header {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Контейнер для уведомлений -->
    <div class="toast-container"></div>

    <div class="container">
        <h1><i class="fas fa-users-cog"></i>Управление пользователями</h1>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Логин</th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['login']) ?></td>
                        <td>
                            <span class="badge bg-primary"><?= htmlspecialchars($user['role']) ?></span>
                        </td>
                        <td>
                            <?php if ($user['is_blocked']): ?>
                                <span class="status-badge status-blocked">
                                    <div>
                                        <i class="fas fa-lock"></i> Пользователь заблокирован
                                    </div>
                                    <div class="blocked-info">
                                        До: <?= htmlspecialchars($user['blocked_until']) ?>
                                    </div>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-active">
                                    <i class="fas fa-check-circle"></i> Пользователь активен
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-container">
                                <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                        data-user-id="<?= $user['id'] ?>" 
                                        data-user-name="<?= htmlspecialchars($user['name']) ?>" 
                                        data-user-email="<?= htmlspecialchars($user['email']) ?>" 
                                        data-user-role="<?= htmlspecialchars($user['role']) ?>">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteUserModal" 
                                        data-user-id="<?= $user['id'] ?>">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                                <?php if ($user['is_blocked']): ?>
                                    <button class="btn-action btn-unblock" data-bs-toggle="modal" data-bs-target="#unblockUserModal" 
                                            data-user-id="<?= $user['id'] ?>">
                                        <i class="fas fa-unlock"></i> Разблокировать
                                    </button>
                                <?php else: ?>
                                    <button class="btn-action btn-block" data-bs-toggle="modal" data-bs-target="#blockUserModal" 
                                            data-user-id="<?= $user['id'] ?>">
                                        <i class="fas fa-lock"></i> Заблокировать
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно редактирования пользователя -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label for="editUserName" class="form-label">Имя</label>
                            <input type="text" class="form-control" id="editUserName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editUserEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editUserEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editUserRole" class="form-label">Роль</label>
                            <select class="form-select" id="editUserRole" name="role" required>
                                <option value="candidate">Кандидат</option>
                                <option value="HR">HR</option>
                                <option value="moderator">Модератор</option>
                                <option value="admin">Админ</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-action">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно удаления пользователя -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-4">Вы уверены, что хотите удалить этого пользователя? Это действие нельзя будет отменить.</p>
                    <form id="deleteUserForm">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" class="btn-action btn-delete">Удалить пользователя</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно блокировки пользователя -->
    <div class="modal fade" id="blockUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-lock"></i> Заблокировать пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="blockUserForm">
                        <input type="hidden" name="user_id" id="blockUserId">
                        <div class="mb-3">
                            <label for="blockDuration" class="form-label">Выберите срок блокировки</label>
                            <select class="form-select" id="blockDuration" name="duration" required>
                                <option value="1 hour">1 час</option>
                                <option value="12 hours">12 часов</option>
                                <option value="1 day">1 день</option>
                                <option value="1 week">1 неделя</option>
                                <option value="1 month">1 месяц</option>
                                <option value="forever">Вечная блокировка</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-action btn-block">Заблокировать пользователя</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно разблокировки пользователя -->
    <div class="modal fade" id="unblockUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-unlock"></i> Разблокировать пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-4">Вы уверены, что хотите разблокировать этого пользователя?</p>
                    <form id="unblockUserForm">
                        <input type="hidden" name="user_id" id="unblockUserId">
                        <button type="submit" class="btn-action btn-unblock">Разблокировать пользователя</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Обработка модальных окон
        const editUserModal = document.getElementById('editUserModal');
        const deleteUserModal = document.getElementById('deleteUserModal');
        const blockUserModal = document.getElementById('blockUserModal');
        const unblockUserModal = document.getElementById('unblockUserModal');

        // Заполнение данных в модальном окне редактирования
        editUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            const userEmail = button.getAttribute('data-user-email');
            const userRole = button.getAttribute('data-user-role');

            document.getElementById('editUserId').value = userId;
            document.getElementById('editUserName').value = userName;
            document.getElementById('editUserEmail').value = userEmail;
            document.getElementById('editUserRole').value = userRole;
        });

        deleteUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            document.getElementById('deleteUserId').value = userId;
        });

        blockUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            document.getElementById('blockUserId').value = userId;
        });

        unblockUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            document.getElementById('unblockUserId').value = userId;
        });

        // Функция для показа уведомлений
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            let icon = 'check-circle';
            if (type === 'warning') icon = 'exclamation-triangle';
            if (type === 'danger') icon = 'times-circle';

            toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas fa-${icon}"></i>
                    <strong class="me-auto">Уведомление</strong>
                    <button type="button" class="btn-close" onclick="hideToast(this.parentElement.parentElement)"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;

            toastContainer.appendChild(toast);
            
            // Показываем уведомление
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Удаляем уведомление через 8 секунд
            setTimeout(() => {
                hideToast(toast);
            }, 8000);
        }

        // Функция для скрытия уведомления
        function hideToast(toast) {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 500);
        }

        // Функция для обработки ответа от сервера
        async function handleResponse(response, successMessage, errorMessage, type = 'success') {
            if (response.ok) {
                // Закрываем модальное окно
                const modal = bootstrap.Modal.getInstance(document.querySelector('.modal.show'));
                if (modal) {
                    modal.hide();
                }
                
                // Показываем уведомление об успехе
                showToast(successMessage, type);
                
                // Перезагружаем страницу через большую задержку
                setTimeout(() => {
                    location.reload();
                }, 3000);
            } else {
                showToast(errorMessage || 'Произошла ошибка', 'danger');
            }
        }

        // Обновляем обработчики форм
        document.getElementById('editUserForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('admin_actions.php?action=edit_user', {
                    method: 'POST',
                    body: formData
                });
                await handleResponse(
                    response,
                    'Пользователь успешно отредактирован',
                    'Ошибка при редактировании пользователя'
                );
            } catch (error) {
                showToast('Произошла ошибка при отправке запроса', 'danger');
            }
        });

        document.getElementById('deleteUserForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('admin_actions.php?action=delete_user', {
                    method: 'POST',
                    body: formData
                });
                await handleResponse(
                    response,
                    'Пользователь успешно удален',
                    'Ошибка при удалении пользователя',
                    'danger'
                );
            } catch (error) {
                showToast('Произошла ошибка при отправке запроса', 'danger');
            }
        });

        document.getElementById('blockUserForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('admin_actions.php?action=block_user', {
                    method: 'POST',
                    body: formData
                });
                await handleResponse(
                    response,
                    'Пользователь успешно заблокирован',
                    'Ошибка при блокировке пользователя',
                    'warning'
                );
            } catch (error) {
                showToast('Произошла ошибка при отправке запроса', 'danger');
            }
        });

        document.getElementById('unblockUserForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('admin_actions.php?action=unblock_user', {
                    method: 'POST',
                    body: formData
                });
                await handleResponse(
                    response,
                    'Пользователь успешно разблокирован',
                    'Ошибка при разблокировке пользователя'
                );
            } catch (error) {
                showToast('Произошла ошибка при отправке запроса', 'danger');
            }
        });
    </script>
</body>
</html>