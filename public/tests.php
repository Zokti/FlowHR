<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем список тестов
$tests_query = "SELECT * FROM tests";
$tests_result = $pdo->query($tests_query);
$tests = $tests_result->fetchAll();

// Получаем результаты тестов пользователя
$user_id = $_SESSION['user_id'];
$results_query = "SELECT test_id, score, time_taken FROM results WHERE user_id = ?";
$results_stmt = $pdo->prepare($results_query);
$results_stmt->execute([$user_id]);
$results = $results_stmt->fetchAll(PDO::FETCH_ASSOC); // Используем FETCH_ASSOC вместо FETCH_KEY_PAIR

// Преобразуем результаты в удобный формат
$user_results = [];
foreach ($results as $result) {
    $user_results[$result['test_id']] = [
        'score' => $result['score'],
        'time_taken' => $result['time_taken']
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступные тесты</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #F8F9FA;
            color: #2C3E50;
            padding: 20px;
            margin-left: 250px;
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
            color: #FF6F61;
        }

        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .test-card {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #E9ECEF;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .test-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .test-card:hover::before {
            opacity: 1;
        }

        .test-card h3 {
            color: #2C3E50;
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .test-details {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #6C757D;
        }

        .test-details i {
            color: #FF6F61;
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .test-details p {
            margin: 0;
            font-size: 14px;
        }

        .test-details strong {
            color: #2C3E50;
            font-weight: 600;
        }

        .test-result {
            background: #F8F9FA;
            border-radius: 15px;
            padding: 15px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .test-result i {
            color: #28a745;
            font-size: 20px;
        }

        .test-result p {
            margin: 0;
            color: #2C3E50;
            font-size: 14px;
        }

        .test-result strong {
            color: #28a745;
            font-weight: 600;
        }

        .progress-container {
            margin-top: 15px;
            background: #E9ECEF;
            border-radius: 10px;
            height: 6px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .btn-start {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
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
        }

        .btn-start i {
            margin-right: 8px;
            font-size: 16px;
        }

        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
            color: #FFFFFF;
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

        .test-card {
            animation: fadeInUp 0.5s ease-in-out;
        }

        .test-card:nth-child(1) { animation-delay: 0.1s; }
        .test-card:nth-child(2) { animation-delay: 0.2s; }
        .test-card:nth-child(3) { animation-delay: 0.3s; }
        .test-card:nth-child(4) { animation-delay: 0.4s; }
        .test-card:nth-child(5) { animation-delay: 0.5s; }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .tests-grid {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 28px;
                margin: 20px 0;
            }

            .test-card {
                padding: 20px;
            }
        }

        .test-warning {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            border: none;
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
            animation: fadeInUp 0.5s ease-in-out;
        }

        .test-warning i {
            font-size: 24px;
        }

        .test-warning span {
            font-size: 16px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .test-warning {
                padding: 12px 15px;
                margin-bottom: 20px;
            }

            .test-warning i {
                font-size: 20px;
            }

            .test-warning span {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-clipboard-list"></i>Доступные тесты</h1>

        <div class="alert alert-info test-warning">
            <i class="fas fa-info-circle"></i>
            <span>Внимание! Каждый тест можно пройти только один раз. Пожалуйста, будьте внимательны при выборе ответов.</span>
        </div>

        <div class="tests-grid">
            <?php foreach ($tests as $test): ?>
                <div class="test-card">
                    <h3><?php echo htmlspecialchars($test['title']); ?></h3>

                    <div class="test-details">
                        <i class="fas fa-question-circle"></i>
                        <p><strong>Количество вопросов:</strong> 10</p>
                    </div>

                    <div class="test-details">
                        <i class="fas fa-clock"></i>
                        <p><strong>Время на прохождение:</strong> <?php echo $test['time_limit'] / 60; ?> минут</p>
                    </div>

                    <?php if (isset($user_results[$test['id']])): ?>
                        <div class="test-result">
                            <i class="fas fa-check-circle"></i>
                            <p>Вы прошли этот тест на <strong><?php echo $user_results[$test['id']]['score']; ?> баллов</strong></p>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $user_results[$test['id']]['score']; ?>%"></div>
                        </div>
                    <?php else: ?>
                        <a href="test.php?id=<?php echo $test['id']; ?>" class="btn-start">
                            <i class="fas fa-play"></i> Начать тест
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>