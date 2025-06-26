<?php
session_start();
require '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];

// Количество откликов на все вакансии
$query = $pdo->query("SELECT COUNT(*) as total_applications FROM applications");
$total_applications = $query->fetch()['total_applications'];

// Количество откликов по статусам (для всех)
$query_pending = $pdo->query("SELECT COUNT(*) as pending FROM applications WHERE status = 'pending'");
$pending_count = $query_pending->fetch()['pending'];

$query_accepted = $pdo->query("SELECT COUNT(*) as accepted FROM applications WHERE status = 'accepted'");
$accepted_count = $query_accepted->fetch()['accepted'];

$query_rejected = $pdo->query("SELECT COUNT(*) as rejected FROM applications WHERE status = 'rejected'");
$rejected_count = $query_rejected->fetch()['rejected'];

// Топ 5 вакансий по количеству откликов
$top_jobs = $pdo->query("
    SELECT jobs.title, COUNT(applications.id) as count 
    FROM applications 
    JOIN jobs ON applications.job_id = jobs.id 
    GROUP BY jobs.id 
    ORDER BY count DESC 
    LIMIT 5
");

// Если HR, считаем количество его вакансий и откликов
$total_jobs = 0;
$hr_applications = 0;
$hr_pending = 0;
$hr_accepted = 0;
$hr_rejected = 0;
if ($user_role == 'HR') {
    // Количество вакансий HR
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_jobs FROM jobs WHERE hr_id = ?");
    $stmt->execute([$user_id]);
    $total_jobs = $stmt->fetch()['total_jobs'];

    // Количество откликов на вакансии HR
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as hr_applications 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ?
    ");
    $stmt->execute([$user_id]);
    $hr_applications = $stmt->fetch()['hr_applications'];

    // Количество откликов на вакансии HR по статусам
    $stmt_pending = $pdo->prepare("
        SELECT COUNT(*) as hr_pending 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ? AND applications.status = 'pending'
    ");
    $stmt_pending->execute([$user_id]);
    $hr_pending = $stmt_pending->fetch()['hr_pending'];

    $stmt_accepted = $pdo->prepare("
        SELECT COUNT(*) as hr_accepted 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ? AND applications.status = 'accepted'
    ");
    $stmt_accepted->execute([$user_id]);
    $hr_accepted = $stmt_accepted->fetch()['hr_accepted'];

    $stmt_rejected = $pdo->prepare("
        SELECT COUNT(*) as hr_rejected 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ? AND applications.status = 'rejected'
    ");
    $stmt_rejected->execute([$user_id]);
    $hr_rejected = $stmt_rejected->fetch()['hr_rejected'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #2C3E50; /* Теплый серый фон */
            color: #E0E0E0; /* Светло-серый текст */
            padding-top: 60px; /* Отступ для фиксированного header */
        }

        .container {
            background: linear-gradient(135deg, #FF6F61, #2C3E50); /* Градиентный фон */
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.4);
            margin-top: 20px;
        }

        h2, h3 {
            color: #E0E0E0; /* Светло-серый текст */
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1); /* Полупрозрачный фон для карточек */
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .stat-card h5 {
            margin-bottom: 15px;
            font-size: 18px;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }

        .btn {
            background-color: #FF6F61; /* Коралловый цвет для кнопок */
            border: none;
            color: #E0E0E0; /* Светло-серый текст */
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #FF3B2F; /* Темно-коралловый при наведении */
        }

        .list-group-item {
            background-color: rgba(255, 255, 255, 0.1); /* Полупрозрачный фон для элементов списка */
            color: #E0E0E0; /* Светло-серый текст */
            border: none;
            margin-bottom: 10px;
            border-radius: 10px;
        }

        .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.2); /* Эффект при наведении */
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h2>Добро пожаловать, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <p>Роль: <b><?php echo $user_role == 'HR' ? 'HR-специалист' : 'Кандидат'; ?></b></p>
        <a href="logout.php" class="btn btn-danger">Выйти</a>

        <hr>

        <?php if ($user_role == 'HR'): ?>
            <h4>Ваша статистика:</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Вакансий размещено</h5>
                        <p><?php echo $total_jobs; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Откликов на ваши вакансии</h5>
                        <p><?php echo $hr_applications; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Откликов на рассмотрении</h5>
                        <p><?php echo $hr_pending; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Принятых откликов</h5>
                        <p><?php echo $hr_accepted; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Отклоненных откликов</h5>
                        <p><?php echo $hr_rejected; ?></p>
                    </div>
                </div>
            </div>
            <a href="my_vacancies.php" class="btn btn-primary">Управление вакансиями</a>
            <a href="create_vacancies.php" class="btn btn-primary">Создание вакансии</a>
        <?php else: ?>
            <h4>Общая статистика:</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Всего откликов</h5>
                        <p><?php echo $total_applications; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Откликов на рассмотрении</h5>
                        <p><?php echo $pending_count; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Принятых откликов</h5>
                        <p><?php echo $accepted_count; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Отклоненных откликов</h5>
                        <p><?php echo $rejected_count; ?></p>
                    </div>
                </div>
            </div>

            <h5 class="mt-4">Топ вакансий по откликам:</h5>
            <ul class="list-group">
                <?php while ($row = $top_jobs->fetch()): ?>
                    <li class="list-group-item"><?php echo "{$row['title']} – {$row['count']} откликов"; ?></li>
                <?php endwhile; ?>
            </ul>

            <a href="jobs.php" class="btn btn-success mt-3">Просмотр вакансий</a>
        <?php endif; ?>
    </div>
</body>
</html>