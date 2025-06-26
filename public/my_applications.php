<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем отклики пользователя
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        j.title as job_title,
        s.salary_range,
        u.name as hr_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN salaries s ON j.salary_id = s.id
    JOIN users u ON a.hr_id = u.id
    WHERE a.candidate_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои отклики</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6F61;
            --primary-dark: #FF3B2F;
            --text-color: #2C3E50;
            --bg-color: #F8F9FA;
            --card-bg: #FFFFFF;
            --border-color: #E0E0E0;
            --success-color: #28a745;
            --warning-color: #FFC107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
            margin-left: 250px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: var(--text-color);
            text-align: center;
            margin: 40px 0;
            font-size: 36px;
            font-weight: bold;
            animation: fadeInUp 0.5s ease-in-out;
        }

        h1 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .applications-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .application-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.5s ease-in-out;
            display: flex;
            flex-direction: column;
        }

        .application-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .application-card:hover::before {
            opacity: 1;
        }

        .application-card .card-content {
            flex: 1;
        }

        .application-card .card-footer {
            margin-top: auto;
            padding-top: 20px;
        }

        .application-card h5 {
            color: var(--text-color);
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .application-details {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #6C757D;
        }

        .application-details i {
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .application-details p {
            margin: 0;
            font-size: 14px;
        }

        .application-details strong {
            color: var(--text-color);
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 10px;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .status-accepted {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .btn-chat {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #FFFFFF;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
            width: 100%;
            height: 48px;
            min-height: 48px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-chat i {
            margin-right: 8px;
            font-size: 16px;
            flex-shrink: 0;
        }

        .btn-chat span {
            flex: 1;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .empty-state p {
            color: #6C757D;
            font-size: 18px;
            margin-bottom: 20px;
        }

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

        .application-card:nth-child(1) { animation-delay: 0.1s; }
        .application-card:nth-child(2) { animation-delay: 0.2s; }
        .application-card:nth-child(3) { animation-delay: 0.3s; }
        .application-card:nth-child(4) { animation-delay: 0.4s; }
        .application-card:nth-child(5) { animation-delay: 0.5s; }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .applications-list {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 28px;
                margin: 20px 0;
            }

            .application-card {
                padding: 20px;
            }
        }

        .btn-back {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #FFFFFF;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 30px;
            height: 48px;
            min-height: 48px;
            width: 100%;
            max-width: 300px;
            margin: 0 auto 30px;
        }

        .btn-back i {
            margin-right: 8px;
            font-size: 16px;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
            color: #FFFFFF;
        }

        .header-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            text-align: center;
        }

        .header-actions h1 {
            margin: 0 0 20px 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="header-actions">
            <h1><i class="fas fa-clipboard-list"></i>Мои отклики</h1>
            <a href="jobs.php" class="btn-back">
                <i class="fas fa-search"></i>
                Продолжить выбор вакансий
            </a>
        </div>

        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>У вас пока нет откликов на вакансии</p>
                <a href="jobs.php" class="btn-chat">
                    <i class="fas fa-search"></i> Найти вакансии
                </a>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <div class="card-content">
                            <h5><?= htmlspecialchars($application['job_title']) ?></h5>

                            <div class="application-details">
                                <i class="fas fa-money-bill-wave"></i>
                                <p><strong>Зарплата:</strong> <?= htmlspecialchars($application['salary_range']) ?></p>
                            </div>

                            <div class="application-details">
                                <i class="fas fa-user-tie"></i>
                                <p><strong>HR:</strong> <?= htmlspecialchars($application['hr_name']) ?></p>
                            </div>

                            <div class="application-details">
                                <i class="fas fa-calendar-alt"></i>
                                <p><strong>Дата отклика:</strong> <?= date('d.m.Y H:i', strtotime($application['created_at'])) ?></p>
                            </div>

                            <div class="status-badge status-<?= htmlspecialchars($application['status']) ?>">
                                <i class="fas <?= 
                                    $application['status'] == 'pending' ? 'fa-hourglass-half' : 
                                    ($application['status'] == 'accepted' ? 'fa-check-circle' : 'fa-times-circle') 
                                ?>"></i>
                                <span style="margin-left: 8px;">
                                    <?= 
                                        $application['status'] == 'pending' ? 'На рассмотрении' : 
                                        ($application['status'] == 'accepted' ? 'Принят' : 'Отклонен') 
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="card-footer">
                            <a href="chat.php?application_id=<?= $application['id'] ?>" class="btn-chat">
                                <i class="fas fa-comment-dots"></i>
                                <span>Перейти в чат</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>