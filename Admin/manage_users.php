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
            background: #FFFFFF;
            color: #2C3E50;
            padding: 20px;
            margin-left: 250px; /* Отступ для бокового меню */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #2C3E50;
            text-align: center;
            margin: 40px 0;
            font-size: 36px;
            font-weight: bold;
            animation: fadeInUp 0.5s ease-in-out;
        }

        h1 i {
            margin-right: 10px;
        }

        .table {
            background: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
        }

        .table th, .table td {
            vertical-align: middle;
        }

        .btn-action {
    background: #FF6F61;
    color: #FFFFFF;
    border: none;
    padding: 8px 16px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px; /* Горизонтальный отступ между кнопками */
    margin-bottom: 10px; /* Вертикальный отступ между кнопками */
}

.btn-action:last-child {
    margin-right: 0; /* Убираем горизонтальный отступ у последней кнопки */
    margin-bottom: 0; /* Убираем вертикальный отступ у последней кнопки */
}

        .btn-action i {
            margin-right: 8px;
        }

        .btn-action:hover {
            background: #FF3B2F;
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .btn-block {
            background: #ffc107;
        }

        .btn-block:hover {
            background: #e0a800;
        }

        .btn-unblock {
            background: #28a745;
        }

        .btn-unblock:hover {
            background: #218838;
        }

        /* Стили для модальных окон */
        .modal-header {
            background: #FF6F61;
            color: #FFFFFF;
            border-radius: 15px 15px 0 0;
        }

        .modal-title {
            font-size: 18px;
        }

        .modal-body {
            padding: 20px;
        }

        .form-select, .form-control {
            border-radius: 5px;
            border: 1px solid #E0E0E0;
            padding: 8px;
            margin-bottom: 10px;
            transition: border-color 0.3s ease;
        }

        .form-select:focus, .form-control:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 5px rgba(255, 111, 97, 0.5);
        }

        /* Анимация появления */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Убираем подчеркивание у всех ссылок */
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

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
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <?php if ($user['is_blocked']): ?>
                                <span class="text-danger">Заблокирован до <?= htmlspecialchars($user['blocked_until']) ?></span>
                            <?php else: ?>
                                <span class="text-success">Активен</span>
                            <?php endif; ?>
                        </td>
                        <td>
    <div class="d-flex flex-wrap"> <!-- Добавляем flex-контейнер для кнопок -->
        <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>" data-user-email="<?= htmlspecialchars($user['email']) ?>" data-user-role="<?= htmlspecialchars($user['role']) ?>">
            <i class="fas fa-edit"></i> Редактировать
        </button>
        <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-user-id="<?= $user['id'] ?>">
            <i class="fas fa-trash"></i> Удалить
        </button>
        <?php if ($user['is_blocked']): ?>
            <button class="btn-action btn-unblock" data-bs-toggle="modal" data-bs-target="#unblockUserModal" data-user-id="<?= $user['id'] ?>">
                <i class="fas fa-unlock"></i> Разблокировать
            </button>
        <?php else: ?>
            <button class="btn-action btn-block" data-bs-toggle="modal" data-bs-target="#blockUserModal" data-user-id="<?= $user['id'] ?>">
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
                        <button type="submit" class="btn btn-primary">Сохранить</button>
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
                    <p>Вы уверены, что хотите удалить этого пользователя?</p>
                    <form id="deleteUserForm">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">Удалить</button>
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
                        <button type="submit" class="btn btn-warning">Заблокировать</button>
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
                    <p>Вы уверены, что хотите разблокировать этого пользователя?</p>
                    <form id="unblockUserForm">
                        <input type="hidden" name="user_id" id="unblockUserId">
                        <button type="submit" class="btn btn-success">Разблокировать</button>
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

        // Обработка форм
        document.getElementById('editUserForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            fetch('admin_actions.php?action=edit_user', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });

        document.getElementById('deleteUserForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            fetch('admin_actions.php?action=delete_user', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });

        document.getElementById('blockUserForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            fetch('admin_actions.php?action=block_user', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });

        document.getElementById('unblockUserForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            fetch('admin_actions.php?action=unblock_user', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>