<?php
session_start();
require '../includes/config.php';

// Проверяем, передан ли test_id в URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Тест не выбран.");
}

$test_id = $_GET['id'];

// Получаем информацию о тесте
$test_query = "SELECT * FROM tests WHERE id = ?";
$test_stmt = $pdo->prepare($test_query);
$test_stmt->execute([$test_id]);
$test = $test_stmt->fetch();

if (!$test) {
    die("Тест не найден.");
}

// Получаем вопросы для теста
$questions_query = "SELECT * FROM questions WHERE test_id = ?";
$questions_stmt = $pdo->prepare($questions_query);
$questions_stmt->execute([$test_id]);
$questions = $questions_stmt->fetchAll();

if (!$questions) {
    die("Вопросы для этого теста отсутствуют.");
}

// Получаем ответы для каждого вопроса
foreach ($questions as $key => $question) {
    $answers_query = "SELECT * FROM answers WHERE question_id = ?";
    $answers_stmt = $pdo->prepare($answers_query);
    $answers_stmt->execute([$question['id']]);
    $questions[$key]['answers'] = $answers_stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($test['title']); ?></title>
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

        .test-container {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease-in-out;
        }

        .test-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .timer {
            font-size: 1.5em;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .timer i {
            margin-right: 10px;
        }

        .question {
            background: #FFFFFF;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .question:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .question h4 {
            color: #2C3E50;
            margin-top: 0;
        }

        .question label {
            display: block;
            margin-bottom: 10px;
            color: #666666;
            cursor: pointer;
        }

        .question input[type="radio"] {
            margin-right: 10px;
        }

        .btn-finish {
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

        .btn-finish i {
            margin-right: 8px;
        }

        .btn-finish:hover {
            background: #FF3B2F;
        }

        .btn-back {
            background: #6c757d;
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
            background: #5a6268;
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
        <div class="test-container">
            <h2><?php echo htmlspecialchars($test['title']); ?></h2>

            <div class="timer">
                <i class="fas fa-clock"></i>
                Оставшееся время: <span id="timer"><?php echo $test['time_limit'] / 60; ?>:00</span>
            </div>

            <form id="test-form" action="submit_test.php" method="POST">
                <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
                <input type="hidden" name="time_taken" id="time_taken" value="">

                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $question): ?>
                        <div class="question">
                            <h4><?php echo htmlspecialchars($question['question_text']); ?></h4>
                            <?php foreach ($question['answers'] as $answer): ?>
                                <label>
                                    <input type="radio" name="q<?php echo $question['id']; ?>" value="<?php echo $answer['id']; ?>">
                                    <?php echo htmlspecialchars($answer['answer_text']); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Вопросы для этого теста отсутствуют.</p>
                <?php endif; ?>

                <button type="submit" class="btn-finish">
                    <i class="fas fa-check"></i> Завершить тест
                </button>
                <button type="button" class="btn-back" onclick="window.location.href='tests.php'">
                    <i class="fas fa-arrow-left"></i> Вернуться к тестам
                </button>
            </form>
        </div>
    </div>

    <script>
        // Таймер
        let timeLeft = <?php echo $test['time_limit']; ?>;
        const timerElement = document.getElementById('timer');
        const timeTakenInput = document.getElementById('time_taken');

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                finishTest();
            } else {
                timeLeft--;
            }
        }

        const timerInterval = setInterval(updateTimer, 1000);

        function finishTest() {
            clearInterval(timerInterval);
            timeTakenInput.value = <?php echo $test['time_limit']; ?> - timeLeft;
            document.getElementById('test-form').submit();
        }
    </script>
</body>
</html>