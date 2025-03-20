<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    die("Чат не найден.");
}

// Получаем информацию о чате (собеседник, вакансия)
$stmt = $pdo->prepare("
    SELECT 
        applications.id AS application_id,
        j.title AS job_title,
        u1.id AS user1_id,
        u1.name AS user1_name,
        u1.avatar AS user1_avatar,
        u2.id AS user2_id,
        u2.name AS user2_name,
        u2.avatar AS user2_avatar
    FROM applications
    JOIN jobs j ON applications.job_id = j.id
    JOIN users u1 ON applications.user_id = u1.id
    JOIN users u2 ON j.user_id = u2.id
    WHERE applications.id = ?
");
$stmt->execute([$application_id]);
$chat_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chat_info) {
    die("Чат не найден.");
}

// Определяем собеседника
$interlocutor_id = ($chat_info['user1_id'] == $user_id) ? $chat_info['user2_id'] : $chat_info['user1_id'];
$interlocutor_name = ($chat_info['user1_id'] == $user_id) ? $chat_info['user2_name'] : $chat_info['user1_name'];
$interlocutor_avatar = ($chat_info['user1_id'] == $user_id) ? $chat_info['user2_avatar'] : $chat_info['user1_avatar'];

// Получаем сообщения для этого чата
$stmt = $pdo->prepare("
    SELECT 
        messages.id,
        messages.user_id,
        messages.content,
        messages.created_at,
        users.name AS sender_name,
        users.avatar AS sender_avatar
    FROM messages
    JOIN users ON messages.user_id = users.id
    WHERE messages.application_id = ?
    ORDER BY messages.created_at ASC
");
$stmt->execute([$application_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message_content = trim($_POST['message']);

    if (!empty($message_content)) {
        $stmt = $pdo->prepare("
            INSERT INTO messages (application_id, user_id, content)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$application_id, $user_id, $message_content]);

        // Перенаправляем, чтобы избежать повторной отправки формы
        header("Location: chat.php?application_id=$application_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Чат</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #F5F5F5; /* Светло-серый фон */
            color: #2C3E50; /* Темно-серый текст */
            padding: 20px;
            margin-left: 250px; /* Отступ для бокового меню */
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #6c757d; /* Серый цвет кнопки */
            color: #FFFFFF; /* Белый текст */
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.95em;
            text-decoration: none; /* Убираем подчеркивание */
        }

        .back-button:hover {
            background: #5a6268; /* Темно-серый при наведении */
        }

        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            background: #FFFFFF; /* Белый фон контейнера */
            border-radius: 15px; /* Закругленные углы */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Легкая тень */
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 80vh; /* Высота контейнера */
            position: relative; /* Для позиционирования кнопки */
        }

        .chat-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #E0E0E0; /* Светло-серая граница */
        }

        .chat-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #FF6F61; /* Коралловая рамка */
            margin-bottom: 10px;
        }

        .chat-header h3 {
            margin: 10px 0 5px;
            color: #2C3E50; /* Темно-серый текст */
            font-size: 1.5em;
        }

        .chat-header p {
            color: #666666; /* Серый текст */
            font-size: 0.9em;
        }

        .messages {
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 20px;
            padding: 10px;
            background: #F9F9F9; /* Светлый фон для сообщений */
            border-radius: 10px;
            border: 1px solid #E0E0E0; /* Светло-серая граница */
        }

        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 10px;
            max-width: 70%;
            position: relative;
        }

        .message.sender {
            background: #FF6F61; /* Коралловый фон для сообщений отправителя */
            color: #FFFFFF; /* Белый текст */
            margin-left: auto;
        }

        .message.receiver {
            background: #FFFFFF; /* Белый фон для сообщений получателя */
            color: #2C3E50; /* Темно-серый текст */
            margin-right: auto;
            border: 1px solid #E0E0E0; /* Светло-серая граница */
        }

        .message p {
            margin: 0;
            font-size: 0.95em;
        }

        .message small {
            display: block;
            margin-top: 5px;
            color: #666666; /* Серый текст */
            font-size: 0.8em;
            text-align: right;
        }

        .chat-input {
            display: flex;
            gap: 10px;
            padding: 10px;
            background: #FFFFFF; /* Белый фон */
            border-radius: 10px;
            border: 1px solid #E0E0E0; /* Светло-серая граница */
        }

        .chat-input textarea {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #E0E0E0; /* Светло-серая граница */
            border-radius: 5px;
            resize: none;
            font-size: 0.95em;
        }

        .chat-input button {
            background: #FF6F61; /* Коралловый цвет кнопки */
            color: #FFFFFF; /* Белый текст */
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.95em;
        }

        .chat-input button:hover {
            background: #FF3B2F; /* Темно-коралловый при наведении */
        }

        /* Анимация для новых сообщений */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message {
            animation: fadeIn 0.3s ease-in-out;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="chat-container">
        <!-- Кнопка "Вернуться в мессенджер" -->
        <a href="messenger.php" class="back-button">Вернуться в мессенджер</a>

        <!-- Заголовок чата -->
        <div class="chat-header">
            <img src="../uploads/avatars/<?= htmlspecialchars($interlocutor_avatar) ?>" alt="Аватар">
            <h3><?= htmlspecialchars($interlocutor_name) ?></h3>
            <p>Вакансия: <?= htmlspecialchars($chat_info['job_title']) ?></p>
        </div>

        <!-- Сообщения -->
        <div class="messages">
            <?php foreach ($messages as $message): ?>
                <div class="message <?= ($message['user_id'] == $user_id) ? 'sender' : 'receiver' ?>">
                    <p><?= htmlspecialchars($message['content']) ?></p>
                    <small><?= date('d.m.Y H:i', strtotime($message['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Форма отправки сообщения -->
        <form method="POST" class="chat-input">
            <textarea name="message" placeholder="Введите сообщение..." required></textarea>
            <button type="submit">Отправить</button>
        </form>
    </div>

    <script>
        // Отправка сообщения по нажатию Enter
        document.querySelector('textarea').addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.querySelector('form').submit();
            }
        });

        // Прокрутка вниз при загрузке страницы
        const messagesContainer = document.querySelector('.messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    </script>
</body>
</html>