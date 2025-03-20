<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем список откликов с информацией о собеседнике
$stmt = $pdo->prepare("
    SELECT 
        applications.id AS application_id,
        j.title AS job_title,
        salaries.salary_range AS salary,
        IF(applications.user_id = ?, j.user_id, applications.user_id) AS interlocutor_id,
        u.name AS interlocutor_name,
        u.avatar AS interlocutor_avatar
    FROM applications
    JOIN jobs j ON applications.job_id = j.id
    JOIN salaries ON j.salary_id = salaries.id
    JOIN users u ON (applications.user_id = ? AND j.user_id = u.id) OR (j.user_id = ? AND applications.user_id = u.id)
    WHERE applications.user_id = ? OR j.user_id = ?
    GROUP BY 
        applications.id, 
        j.title, 
        salaries.salary_range, 
        interlocutor_id, 
        u.name, 
        u.avatar
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мессенджер</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #FFFFFF; /* Белый фон */
            padding: 20px;
            color: #2C3E50; /* Темно-серый текст */
            margin-left: 250px; /* Отступ для бокового меню */
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h2 {
            color: #FF6F61; /* Коралловый цвет, как у кнопок */
            text-align: center; /* Заголовок по центру */
            margin: 40px 0; /* Отступ сверху и снизу */
            font-size: 36px; /* Увеличенный размер текста */
            font-weight: bold; /* Жирный шрифт */
            animation: fadeInUp 0.5s ease-in-out; /* Анимация появления */
        }

        .chat-list {
            margin-top: 20px;
        }

        .chat-item {
            background: #FFFFFF; /* Белый фон карточек */
            border-radius: 15px; /* Закругленные углы */
            padding: 20px; /* Увеличенный отступ внутри карточки */
            margin-bottom: 15px; /* Отступ между карточками */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Легкая тень */
            display: flex;
            align-items: center;
            border: 1px solid #E0E0E0; /* Светло-серая граница */
            transition: transform 0.2s, box-shadow 0.2s;
            animation: fadeInUp 0.8s ease-in-out; /* Анимация появления */
        }

        .chat-item:hover {
            transform: translateY(-5px); /* Эффект при наведении */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); /* Тень при наведении */
        }

        .chat-item img {
            width: 60px; /* Увеличенный размер аватара */
            height: 60px;
            border-radius: 50%;
            margin-right: 20px; /* Увеличенный отступ */
        }

        .chat-item .info {
            flex-grow: 1;
        }

        .chat-item .info h5 {
            margin: 0;
            font-size: 20px; /* Увеличенный размер текста */
            color: #2C3E50; /* Темно-серый текст */
        }

        .chat-item .info p {
            margin: 5px 0;
            color: #666666; /* Серый текст */
            font-size: 16px; /* Увеличенный размер текста */
        }

        .chat-item .btn {
            margin-left: auto;
            background: #FF6F61; /* Коралловый цвет кнопки */
            color: #FFFFFF; /* Белый текст */
            border: none;
            border-radius: 10px; /* Закругленные углы */
            padding: 10px 20px; /* Увеличенный отступ внутри кнопки */
            font-size: 16px; /* Увеличенный размер текста */
            transition: background 0.2s;
        }

        .chat-item .btn:hover {
            background: #FF3B2F; /* Темно-коралловый при наведении */
        }

        .alert-info {
            background: #FFF8E1; /* Очень светлый бежевый фон */
            color: #2C3E50; /* Темно-серый текст */
            border: 1px solid #E0E0E0; /* Светло-серая граница */
            border-radius: 15px; /* Закругленные углы */
            padding: 20px; /* Увеличенный отступ */
            margin-bottom: 20px;
            text-align: center; /* Текст по центру */
            animation: fadeInUp 0.5s ease-in-out; /* Анимация появления */
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

        /* Задержка для анимации карточек */
        .chat-item:nth-child(1) { animation-delay: 0.1s; }
        .chat-item:nth-child(2) { animation-delay: 0.2s; }
        .chat-item:nth-child(3) { animation-delay: 0.3s; }
        .chat-item:nth-child(4) { animation-delay: 0.4s; }
        .chat-item:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h2>Мессенджер</h2>

        <?php if (empty($chats)): ?>
            <div class="alert alert-info">У вас нет активных чатов.</div>
        <?php else: ?>
            <div class="chat-list">
                <?php foreach ($chats as $chat): ?>
                    <div class="chat-item">
                        <img src="../uploads/avatars/<?= htmlspecialchars($chat['interlocutor_avatar']) ?>" alt="Аватар">
                        <div class="info">
                            <h5><?= htmlspecialchars($chat['interlocutor_name']) ?></h5>
                            <p><strong>Вакансия:</strong> <?= htmlspecialchars($chat['job_title']) ?></p>
                            <p><strong>Зарплата:</strong> <?= htmlspecialchars($chat['salary']) ?></p>
                        </div>
                        <a href="chat.php?application_id=<?= $chat['application_id'] ?>" class="btn btn-primary">Перейти в чат</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>