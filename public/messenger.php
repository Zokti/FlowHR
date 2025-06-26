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
        a.id AS application_id,
        a.job_id,
        j.title AS job_title,
        salaries.salary_range AS salary,
        CASE 
            WHEN a.hr_id = ? THEN a.candidate_id
            ELSE a.hr_id
        END AS interlocutor_id,
        u.name AS interlocutor_name,
        u.avatar AS interlocutor_avatar,
        a.status AS status,
        a.entity_type,
        a.created_at AS created_at
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN salaries ON j.salary_id = salaries.id
    JOIN users u ON (
        CASE 
            WHEN a.hr_id = ? THEN a.candidate_id = u.id
            ELSE a.hr_id = u.id
        END
    )
    WHERE a.hr_id = ? OR a.candidate_id = ?
    GROUP BY 
        a.id,
        a.job_id,
        j.title,
        salaries.salary_range,
        interlocutor_id,
        u.name,
        u.avatar,
        a.status,
        a.entity_type,
        a.created_at
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Добавим отладочную информацию
error_log("User ID: " . $user_id);
error_log("Number of chats found: " . count($chats));
foreach ($chats as $chat) {
    error_log("Chat ID: " . $chat['application_id'] . ", Type: " . $chat['entity_type'] . ", Job ID: " . $chat['job_id']);
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Метатег для адаптивного дизайна -->
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

        /* Медиа-запрос для мобильных устройств */
        @media (max-width: 768px) {
            body {
                margin-left: 0; /* Убираем отступ для бокового меню */
                padding: 10px; /* Уменьшаем отступы */
            }

            .container {
                padding: 10px; /* Уменьшаем отступы внутри контейнера */
            }

            h2 {
                font-size: 28px; /* Уменьшаем размер заголовка */
                margin: 20px 0; /* Уменьшаем отступы */
            }

            .chat-item {
                flex-direction: column; /* Меняем направление на вертикальное */
                align-items: flex-start; /* Выравниваем элементы по левому краю */
                padding: 15px; /* Уменьшаем отступы внутри карточки */
            }

            .chat-item img {
                width: 50px; /* Уменьшаем размер аватара */
                height: 50px;
                margin-right: 0; /* Убираем отступ справа */
                margin-bottom: 10px; /* Добавляем отступ снизу */
            }

            .chat-item .info {
                width: 100%; /* Занимаем всю ширину */
            }

            .chat-item .info h5 {
                font-size: 18px; /* Уменьшаем размер текста */
            }

            .chat-item .info p {
                font-size: 14px; /* Уменьшаем размер текста */
            }

            .chat-item .btn {
                width: 100%; /* Кнопка занимает всю ширину */
                margin-left: 0; /* Убираем отступ слева */
                margin-top: 10px; /* Добавляем отступ сверху */
                text-align: center; /* Выравниваем текст по центру */
            }

            .alert-info {
                padding: 15px; /* Уменьшаем отступы */
                font-size: 14px; /* Уменьшаем размер текста */
            }
        }

        .chat-card {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .chat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FF6F61;
        }

        .chat-title {
            font-size: 1.2rem;
            color: #2C3E50;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-title i {
            color: #FF6F61;
        }

        .chat-content {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .chat-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #FF6F61;
            flex-shrink: 0;
        }

        .chat-details {
            flex-grow: 1;
        }

        .chat-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 0.95rem;
        }

        .info-item i {
            color: #FF6F61;
            width: 20px;
            text-align: center;
        }

        .chat-type {
            background: #FFF8E1;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #FF6F61;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .chat-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-chat {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 150px;
            height: 40px;
        }

        .btn-chat:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.3);
            background: linear-gradient(135deg, #FF3B2F, #FF6F61);
            color: white;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending {
            background: #FFF8E1;
            color: #FFC107;
        }

        .status-interview {
            background: #E3F2FD;
            color: #2196F3;
        }

        .status-hired {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .status-rejected {
            background: #FFEBEE;
            color: #DC3545;
        }

        @media (max-width: 768px) {
            .chat-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .chat-info {
                grid-template-columns: 1fr;
            }

            .chat-actions {
                flex-direction: column;
            }

            .btn-chat {
                width: 100%;
            }
        }
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
                    <div class="chat-card">
                        <div class="chat-header">
                            <h3 class="chat-title">
                                <i class="fas fa-briefcase"></i>
                                <?php echo htmlspecialchars($chat['job_title']); ?>
                            </h3>
                            <span class="status-badge status-<?php echo $chat['status']; ?>">
                                <?php 
                                switch($chat['status']) {
                                    case 'pending':
                                        echo '<i class="fas fa-hourglass-half"></i> На рассмотрении';
                                        break;
                                    case 'interview':
                                        echo '<i class="fas fa-handshake"></i> Собеседование';
                                        break;
                                    case 'hired':
                                        echo '<i class="fas fa-user-tie"></i> Принят на работу';
                                        break;
                                    case 'rejected':
                                        echo '<i class="fas fa-times-circle"></i> Отклонено';
                                        break;
                                }
                                ?>
                            </span>
                        </div>

                        <div class="chat-content">
                            <img src="<?php echo $chat['interlocutor_avatar'] ? '../uploads/avatars/' . $chat['interlocutor_avatar'] : '../assets/img/default-avatar.png'; ?>" 
                                 alt="Avatar" 
                                 class="chat-avatar">
                            <div class="chat-details">
                                <div class="chat-info">
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo htmlspecialchars($chat['interlocutor_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?php echo date('d.m.Y H:i', strtotime($chat['created_at'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="chat-type">
                                            <?php if ($chat['entity_type'] === 'vacancy'): ?>
                                                <i class="fas fa-briefcase"></i> Отклик на вакансию
                                            <?php else: ?>
                                                <i class="fas fa-user-tie"></i> Предложение по резюме
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="chat-actions">
                            <a href="chat.php?application_id=<?php echo $chat['application_id']; ?>" class="btn-chat">
                                <i class="fas fa-comment-dots"></i>
                                Открыть чат
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>