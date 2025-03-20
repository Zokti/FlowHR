<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем все отклики текущего пользователя
$stmt = $pdo->prepare("
    SELECT 
        applications.id AS application_id,
        applications.status AS application_status,
        applications.applied_at AS applied_at,
        jobs.id AS job_id,
        jobs.title AS job_title,
        jobs.description AS job_description,
        salaries.salary_range AS salary,
        experiences.name AS experience,
        GROUP_CONCAT(skills.name SEPARATOR ', ') AS skills
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    JOIN salaries ON jobs.salary_id = salaries.id
    JOIN experiences ON jobs.experience_id = experiences.id
    JOIN skills ON FIND_IN_SET(skills.id, jobs.skill_ids) > 0
    WHERE applications.user_id = ?
    GROUP BY applications.id
    ORDER BY applications.applied_at DESC
");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои отклики</title>
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

        .alert-info {
            background: #FFF8E1;
            color: #2C3E50;
            border: 1px solid #E0E0E0;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .application-card {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease-in-out;
        }

        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .application-card h5 {
            color: #2C3E50;
            margin-top: 0;
        }

        .application-card p {
            color: #666666;
            margin: 10px 0;
        }

        .application-card strong {
            color: #2C3E50;
        }

        .application-card .application-details {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .application-card .application-details i {
            color: #FF6F61;
            margin-right: 10px;
        }

        .status-pending {
            color: #FFC107;
        }

        .status-accepted {
            color: #28a745;
        }

        .status-rejected {
            color: #dc3545;
        }

        .btn-chat {
            background: #FF6F61;
            color: #FFFFFF;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-chat i {
            margin-right: 8px;
        }

        .btn-chat:hover {
            background: #FF3B2F;
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
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Мои отклики</h1>

        <?php if (empty($applications)): ?>
            <div class="alert alert-info">Вы ещё не откликались на вакансии.</div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <h5><?= htmlspecialchars($application['job_title']) ?></h5>
                        <p><?= htmlspecialchars($application['job_description']) ?></p>

                        <div class="application-details">
                            <i class="fas fa-money-bill-wave"></i>
                            <p><strong>Зарплата:</strong> <?= htmlspecialchars($application['salary']) ?></p>
                        </div>

                        <div class="application-details">
                            <i class="fas fa-user-tie"></i>
                            <p><strong>Требуемый опыт:</strong> <?= htmlspecialchars($application['experience']) ?></p>
                        </div>

                        <div class="application-details">
                            <i class="fas fa-tools"></i>
                            <p><strong>Навыки:</strong> <?= htmlspecialchars($application['skills']) ?></p>
                        </div>

                        <div class="application-details">
                            <i class="fas fa-info-circle"></i>
                            <p><strong>Статус отклика:</strong> 
                                <span class="status-<?= htmlspecialchars($application['application_status']) ?>">
                                    <?php 
                                    echo $application['application_status'] == 'pending' ? 'На рассмотрении' : 
                                         ($application['application_status'] == 'accepted' ? 'Принят' : 'Отклонен'); 
                                    ?>
                                </span>
                            </p>
                        </div>

                        <div class="application-details">
                            <i class="fas fa-calendar-alt"></i>
                            <p><strong>Дата подачи отклика:</strong> <?= htmlspecialchars($application['applied_at']) ?></p>
                        </div>

                        <!-- Кнопка для перехода в чат с HR -->
                        <form action="chat.php" method="GET">
                            <input type="hidden" name="application_id" value="<?= $application['application_id'] ?>">
                            <button type="submit" class="btn-chat">
                                <i class="fas fa-comment-dots"></i> Перейти в чат
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>