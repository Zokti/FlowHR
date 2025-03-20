<?php
session_start();
require '../includes/config.php';
require_once '../includes/header.php';

// Проверяем, авторизован ли пользователь и является ли он HR
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HR') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Обработка изменения статуса отклика
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'];
    $status = $_POST['status'];

    $update_query = "UPDATE applications SET status = ? WHERE id = ? AND job_id IN (SELECT id FROM jobs WHERE user_id = ?)";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$status, $application_id, $user_id]);
}

// Получаем все отклики на вакансии текущего HR
$applications_query = "
    SELECT a.id AS application_id, a.status, a.applied_at, 
           u.name AS candidate_name, u.id AS candidate_id, 
           j.title AS job_title, j.id AS job_id
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN jobs j ON a.job_id = j.id
    WHERE j.user_id = ?
    ORDER BY a.applied_at DESC
";
$applications_stmt = $pdo->prepare($applications_query);
$applications_stmt->execute([$user_id]);
$applications = $applications_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отклики на вакансии</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Font Awesome -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #FFFFFF; /* Белый фон страницы */
            padding: 20px;
            margin-left: 250px; /* Отступ для бокового меню */
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #FF6F61; /* Коралловый цвет для заголовка */
            text-align: center; /* Заголовок по центру */
            margin: 40px 0; /* Отступ сверху и снизу */
            font-size: 36px; /* Увеличенный размер текста */
            font-weight: bold; /* Жирный шрифт */
            animation: fadeInUp 0.5s ease-in-out; /* Анимация появления */
        }
        h1 i {
            margin-right: 10px; /* Отступ для иконки */
        }
        .application-card {
            background: #FFFFFF; /* Фон карточки */
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Тень карточки */
            border: 1px solid #E0E0E0; /* Граница карточки */
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Анимация */
            animation: fadeInUp 0.8s ease-in-out; /* Анимация появления */
        }
        .application-card:hover {
            transform: translateY(-5px); /* Поднимаем карточку при наведении */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); /* Увеличиваем тень */
        }
        .application-card h5 {
            color: #2C3E50; /* Основной текст */
            margin-top: 0;
        }
        .application-card p {
            color: #666666; /* Второстепенный текст */
            margin: 10px 0;
        }
        .status-pending {
            color: #FFC107; /* Желтый для статуса "на рассмотрении" */
        }
        .status-accepted {
            color: #28A745; /* Зеленый для статуса "принят" */
        }
        .status-rejected {
            color: #DC3545; /* Красный для статуса "отклонен" */
        }
        .btn-primary {
            background-color: #FF6F61; /* Коралловый цвет */
            color: #FFFFFF; /* Белый текст */
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #FF3B2F; /* Темно-коралловый при наведении */
        }
        .btn-primary i {
            margin-right: 8px; /* Отступ для иконки */
        }
        .btn-chat {
            background-color: #FF6F61; /* Коралловый цвет, как у кнопки "Обновить статус" */
            color: #FFFFFF; /* Белый текст */
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            text-decoration: none; /* Убираем подчеркивание у ссылки */
            display: inline-block; /* Чтобы ссылка вела себя как кнопка */
        }
        .btn-chat:hover {
            background-color: #FF3B2F; /* Темно-коралловый при наведении */
        }
        .btn-chat i {
            margin-right: 8px; /* Отступ для иконки */
        }
        .form-select {
            border-radius: 5px;
            border: 1px solid #E0E0E0; /* Граница для выпадающего списка */
            padding: 8px;
            margin-right: 10px;
            transition: border-color 0.3s ease;
            padding-right: 30px; /* Увеличиваем отступ справа для стрелки */
            background: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232C3E50'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e") no-repeat right 8px center; /* Кастомная стрелка */
            background-size: 12px; /* Размер стрелки */
            appearance: none; /* Убираем стандартную стрелку */
        }
        .form-select:focus {
            border-color: #FF6F61; /* Акцентный цвет при фокусе */
            box-shadow: 0 0 5px rgba(255, 111, 97, 0.5); /* Тень при фокусе */
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
        .application-card:nth-child(1) { animation-delay: 0.1s; }
        .application-card:nth-child(2) { animation-delay: 0.2s; }
        .application-card:nth-child(3) { animation-delay: 0.3s; }
        .application-card:nth-child(4) { animation-delay: 0.4s; }
        .application-card:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-comments"></i>Отклики на вакансии</h1>

        <?php if (empty($applications)): ?>
            <p>Нет откликов на ваши вакансии.</p>
        <?php else: ?>
            <?php foreach ($applications as $application): ?>
                <div class="application-card">
                    <h5><i class="fas fa-briefcase"></i> Вакансия: <?php echo htmlspecialchars($application['job_title']); ?></h5>
                    <p><i class="fas fa-user"></i> <strong>Кандидат:</strong> <?php echo htmlspecialchars($application['candidate_name']); ?></p>
                    <p><i class="fas fa-info-circle"></i> <strong>Статус:</strong> 
                        <span class="status-<?php echo $application['status']; ?>">
                            <?php 
                            echo $application['status'] == 'pending' ? '<i class="fas fa-hourglass-half"></i> На рассмотрении' : 
                                 ($application['status'] == 'accepted' ? '<i class="fas fa-check-circle"></i> Принят' : '<i class="fas fa-times-circle"></i> Отклонен'); 
                            ?>
                        </span>
                    </p>
                    <p><i class="fas fa-calendar-alt"></i> <strong>Дата отклика:</strong> <?php echo htmlspecialchars($application['applied_at']); ?></p>

                    <!-- Форма для изменения статуса -->
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                        <select name="status" class="form-select" style="width: auto; display: inline-block;">
                            <option value="pending" <?php echo $application['status'] == 'pending' ? 'selected' : ''; ?>><i class="fas fa-hourglass-half"></i> На рассмотрении</option>
                            <option value="accepted" <?php echo $application['status'] == 'accepted' ? 'selected' : ''; ?>><i class="fas fa-check-circle"></i> Принять</option>
                            <option value="rejected" <?php echo $application['status'] == 'rejected' ? 'selected' : ''; ?>><i class="fas fa-times-circle"></i> Отклонить</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Обновить статус</button>
                    </form>

                    <!-- Кнопка чата -->
                    <a href="chat.php?application_id=<?php echo $application['application_id']; ?>" class="btn btn-chat"><i class="fas fa-comment-dots"></i> Чат</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>