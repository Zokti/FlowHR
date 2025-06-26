<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь и является ли он модератором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    header("Location: login.php");
    exit();
}

// Получаем список ролей и пользователей для фильтрации
$roles_query = "SELECT DISTINCT role FROM users";
$roles_result = $pdo->query($roles_query);
$roles = $roles_result->fetchAll(PDO::FETCH_COLUMN);

$users_query = "SELECT id, name, role FROM users";
$users_result = $pdo->query($users_query);
$users = $users_result->fetchAll();

// Фильтры
$selected_role = $_GET['role'] ?? '';
$selected_user_id = $_GET['user_id'] ?? '';

// Запрос для получения сообщений с фильтрами
$messages_query = "SELECT m.id, m.content, m.created_at, u.name AS sender_name, u.role AS sender_role 
                   FROM messages m
                   JOIN users u ON m.user_id = u.id
                   WHERE 1=1";

if ($selected_role) {
    $messages_query .= " AND u.role = :role";
}
if ($selected_user_id) {
    $messages_query .= " AND m.user_id = :user_id";
}

$messages_query .= " ORDER BY m.created_at DESC";

$stmt = $pdo->prepare($messages_query);

if ($selected_role) {
    $stmt->bindParam(':role', $selected_role);
}
if ($selected_user_id) {
    $stmt->bindParam(':user_id', $selected_user_id);
}

$stmt->execute();
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Модерация сообщений</title>
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
            margin-top: 20px;
            width: 100%;
            table-layout: fixed;
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
            white-space: nowrap;
        }

        .table th:nth-child(1) { width: 5%; }  /* ID */
        .table th:nth-child(2) { width: 15%; } /* Отправитель */
        .table th:nth-child(3) { width: 15%; } /* Получатель */
        .table th:nth-child(4) { width: 30%; } /* Сообщение */
        .table th:nth-child(5) { width: 15%; } /* Дата */
        .table th:nth-child(6) { width: 20%; } /* Действия */

        .table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid #F8F9FA;
            transition: all 0.3s ease;
            color: #2C3E50;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table td:last-child {
            white-space: normal;
        }

        .text-success {
            color: #28a745 !important;
            font-weight: 600;
            padding: 8px 16px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 8px;
            display: inline-block;
        }

        .text-danger {
            color: #dc3545 !important;
            font-weight: 600;
            padding: 8px 16px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 8px;
            display: inline-block;
        }

        .actions-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .btn-action {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
            width: 100%;
            justify-content: center;
        }

        .btn-action i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .btn-action:hover {
            background: #FF3B2F;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .btn-action:hover i {
            transform: scale(1.2);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-delete {
            background: #dc3545;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }

        .btn-delete:hover {
            background: #c82333;
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
        }

        /* Стили для модальных окон */
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
            font-size: 24px;
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
            cursor: pointer;
            z-index: 1;
        }

        .btn-close:before {
            content: '×';
            font-size: 28px;
            line-height: 1;
            font-weight: 300;
        }

        .btn-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
            opacity: 1;
        }

        .btn-close:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(255,255,255,0.2);
        }

        .modal-body {
            padding: 35px;
            background: #F8F9FA;
        }

        .modal-footer {
            padding: 25px 35px;
            background: #F8F9FA;
            border-top: 1px solid rgba(44, 62, 80, 0.1);
            display: flex;
            justify-content: center;
            gap: 15px;
            align-items: center;
        }

        .btn-save {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 12px 35px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
            min-width: 180px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-save:hover {
            background: #FF3B2F;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .btn-save:active {
            transform: translateY(0);
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

            .btn-action {
                width: 100%;
                margin-bottom: 10px;
            }

            .modal-dialog {
                margin: 10px;
            }
        }

        .filters {
            background: #FFFFFF;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.05);
            margin-bottom: 30px;
        }

        .filters form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filters .form-select {
            padding: 12px 20px;
            border: 2px solid #F8F9FA;
            border-radius: 12px;
            font-size: 1rem;
            color: #2C3E50;
            background-color: #FFFFFF;
            min-width: 200px;
            transition: all 0.3s ease;
        }

        .filters .form-select:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
        }

        .btn-apply {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 12px 25px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .btn-apply:hover {
            background: #FF3B2F;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .btn-reset {
            background: #F8F9FA;
            color: #2C3E50;
            padding: 12px 25px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            border: 2px solid #E9ECEF;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-reset:hover {
            background: #E9ECEF;
            transform: translateY(-2px);
        }

        .messages-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .message {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }

        .message:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(44, 62, 80, 0.1);
        }

        .message .sender {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: #2C3E50;
            font-weight: 600;
        }

        .message .sender i {
            color: #FF6F61;
            font-size: 1.2rem;
        }

        .message .role {
            color: #6C757D;
            font-weight: 500;
            font-size: 0.9rem;
            background: #F8F9FA;
            padding: 4px 12px;
            border-radius: 8px;
        }

        .message .content {
            background: #F8F9FA;
            padding: 20px;
            border-radius: 15px;
            margin: 15px 0;
            font-size: 1.1rem;
            line-height: 1.6;
            color: #2C3E50;
        }

        .message .content i {
            color: #FF6F61;
            margin-right: 10px;
        }

        .message .timestamp {
            color: #6C757D;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
        }

        .message .timestamp i {
            color: #FF6F61;
        }

        .message .actions {
            position: absolute;
            top: 25px;
            right: 25px;
        }

        .btn-delete {
            background: #dc3545;
            color: #FFFFFF;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
        }

        .btn-delete i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .btn-delete:hover i {
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .filters form {
                flex-direction: column;
                align-items: stretch;
            }

            .filters .form-select {
                width: 100%;
            }

            .message .actions {
                position: static;
                margin-top: 15px;
            }

            .btn-delete {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-comments"></i> Модерация сообщений</h1>

        <!-- Фильтры -->
        <div class="filters">
            <form method="GET" action="">
                <select name="role" class="form-select">
                    <option value="">Все роли</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars($role) ?>" <?= $selected_role === $role ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="user_id" class="form-select">
                    <option value="">Все пользователи</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['id']) ?>" <?= $selected_user_id == $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-apply">
                    <i class="fas fa-filter"></i> Применить фильтры
                </button>

                <a href="moderate_messages.php" class="btn-reset">
                    <i class="fas fa-times"></i> Сбросить фильтры
                </a>
            </form>
        </div>

        <!-- Список сообщений -->
        <div class="messages-list">
            <?php if (empty($messages)): ?>
                <p>Сообщений не найдено.</p>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message">
                        <div class="sender">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($message['sender_name']) ?>
                            <span class="role"><?= htmlspecialchars($message['sender_role']) ?></span>
                        </div>
                        <div class="content">
                            <i class="fas fa-comment"></i> <?= htmlspecialchars($message['content']) ?>
                        </div>
                        <div class="timestamp">
                            <i class="fas fa-clock"></i> <?= htmlspecialchars($message['created_at']) ?>
                        </div>
                        <div class="actions">
                            <button class="btn-delete" onclick="deleteMessage(<?= $message['id'] ?>)">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Функция для удаления сообщения
    function deleteMessage(messageId) {
        if (confirm('Вы уверены, что хотите удалить это сообщение?')) {
            fetch('moderator_actions.php?action=delete_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json' // Указываем тип содержимого
                },
                body: JSON.stringify({ message_id: messageId }) // Отправляем данные в формате JSON
            })
            .then(response => {
                if (response.ok) {
                    location.reload(); // Перезагружаем страницу после успешного удаления
                } else {
                    console.error('Ошибка при удалении сообщения');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
            });
        }
    }
    </script>
</body>
</html>