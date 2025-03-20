<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем ID теста из URL
$test_id = $_GET['test_id'];

// Получаем результат теста
$user_id = $_SESSION['user_id'];
$result_query = "SELECT score, time_taken FROM results WHERE test_id = ? AND user_id = ? ORDER BY completed_at DESC LIMIT 1";
$result_stmt = $pdo->prepare($result_query);
$result_stmt->execute([$test_id, $user_id]);
$result = $result_stmt->fetch();

// Получаем информацию о тесте
$test_query = "SELECT title FROM tests WHERE id = ?";
$test_stmt = $pdo->prepare($test_query);
$test_stmt->execute([$test_id]);
$test = $test_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты теста</title>
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

        .result-container {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease-in-out;
        }

        .result-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .result-container h2 {
            color: #2C3E50;
            margin-top: 0;
            display: flex;
            align-items: center;
        }

        .result-container h2 i {
            color: #FF6F61;
            margin-right: 10px;
        }

        .result-container p {
            color: #666666;
            margin: 10px 0;
            display: flex;
            align-items: center;
        }

        .result-container p i {
            color: #FF6F61;
            margin-right: 10px;
        }

        .btn-back {
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

        .btn-back i {
            margin-right: 8px;
        }

        .btn-back:hover {
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

        /* Убираем подчеркивание у всех ссылок */
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="result-container">
            <h2><i class="fas fa-clipboard-check"></i>Результаты теста: <?php echo htmlspecialchars($test['title']); ?></h2>
            <p><i class="fas fa-star"></i><strong>Ваш результат:</strong> <?php echo $result['score']; ?> баллов</p>
            <p><i class="fas fa-clock"></i><strong>Время выполнения:</strong> <?php echo $result['time_taken']; ?> секунд</p>
            <button class="btn-back" onclick="window.location.href='tests.php'">
                <i class="fas fa-arrow-left"></i> Вернуться к тестам
            </button>
        </div>
    </div>
</body>
</html>