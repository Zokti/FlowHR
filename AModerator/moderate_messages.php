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
        }

        .filters {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters select, .filters button {
            margin-right: 10px;
        }

        .btn-apply, .btn-reset {
    padding: 6px 12px; /* Уменьшаем вертикальный padding */
    font-size: 14px; /* Уменьшаем размер шрифта */
    display: flex;
    align-items: center; /* Выравниваем иконку и текст по вертикали */
    gap: 6px; /* Расстояние между иконкой и текстом */
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-apply {
    background: #28a745; /* Зеленый цвет для кнопки "Применить фильтры" */
    color: #FFFFFF;
    border: none;
}

.btn-apply:hover {
    background: #218838; /* Темно-зеленый при наведении */
}

.btn-reset {
    background: #dc3545; /* Красный цвет для кнопки "Сбросить фильтры" */
    color: #FFFFFF;
    border: none;
    text-decoration: none; /* Убираем подчеркивание */
}

.btn-reset:hover {
    background: #c82333; /* Темно-красный при наведении */
}

        .messages-list {
            background: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            padding: 20px;
        }

        .message {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #E0E0E0;
            border-radius: 10px;
            background: #F9F9F9;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .message:hover {
            transform: translateY(-5px); /* Анимация поднятия */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); /* Тень при наведении */
        }

        .message .sender {
            font-weight: 500; /* Менее жирный текст */
            color: #2C3E50;
        }

        .message .role {
            color: #666666;
            font-size: 0.9em;
            font-weight: 400; /* Менее жирный текст */
        }

        .message .content {
            margin-top: 10px;
            font-weight: 400; /* Менее жирный текст */
        }

        .message .actions {
            margin-top: 10px;
        }

        .btn-delete {
            background: #dc3545;
            color: #FFFFFF;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .timestamp {
            margin-top: 10px;
            font-size: 0.9em;
            color: #666666;
            font-weight: 400; /* Менее жирный текст */
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-comments"></i> Модерация сообщений</h1>

        <!-- Фильтры -->
        <div class="filters">
    <form method="GET" action="" class="d-flex align-items-center gap-2">
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
                            <span class="role">(<?= htmlspecialchars($message['sender_role']) ?>)</span>
                        </div>
                        <div class="content">
                            <i class="fas fa-comment"></i> <?= htmlspecialchars($message['content']) ?>
                        </div>
                        <div class="actions">
                            <button class="btn-delete" onclick="deleteMessage(<?= $message['id'] ?>)">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </div>
                        <div class="timestamp">
                            <i class="fas fa-clock"></i> <?= htmlspecialchars($message['created_at']) ?>
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