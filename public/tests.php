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

        .test-card {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease-in-out;
        }

        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .test-card h3 {
            color: #2C3E50;
            margin-top: 0;
        }

        .test-card p {
            color: #666666;
            margin: 10px 0;
        }

        .test-card strong {
            color: #2C3E50;
        }

        .test-card .test-details {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .test-card .test-details i {
            color: #FF6F61;
            margin-right: 10px;
        }

        .btn-start {
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
            text-decoration: none; /* Убираем подчеркивание */
        }

        .btn-start i {
            margin-right: 8px;
        }

        .btn-start:hover {
            background: #FF3B2F;
        }

        .test-result {
            color: #28a745;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .test-result i {
            color: #28a745;
            margin-right: 10px;
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
        .test-card:nth-child(1) { animation-delay: 0.1s; }
        .test-card:nth-child(2) { animation-delay: 0.2s; }
        .test-card:nth-child(3) { animation-delay: 0.3s; }
        .test-card:nth-child(4) { animation-delay: 0.4s; }
        .test-card:nth-child(5) { animation-delay: 0.5s; }

        /* Убираем подчеркивание у всех ссылок */
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-clipboard-list"></i>Доступные тесты</h1>

        <?php foreach ($tests as $test): ?>
            <div class="test-card">
                <h3><?php echo htmlspecialchars($test['title']); ?></h3>

                <div class="test-details">
                    <i class="fas fa-question-circle"></i>
                    <p><strong>Количество вопросов:</strong> 10</p> <!-- Фиксированное значение -->
                </div>

                <div class="test-details">
                    <i class="fas fa-clock"></i>
                    <p><strong>Время на прохождение:</strong> <?php echo $test['time_limit'] / 60; ?> минут</p>
                </div>

                <?php if (isset($user_results[$test['id']])): ?>
                    <div class="test-result">
                        <i class="fas fa-check-circle"></i>
                        <p>Вы прошли этот тест на <strong><?php echo $user_results[$test['id']]['score']; ?> баллов</strong> за <strong><?php echo $user_results[$test['id']]['time_taken']; ?> секунд</strong>.</p>
                    </div>
                <?php else: ?>
                    <a href="test.php?id=<?php echo $test['id']; ?>" class="btn-start">
                        <i class="fas fa-play"></i> Начать тест
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>