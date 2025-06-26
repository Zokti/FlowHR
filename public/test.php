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
            background: #F8F9FA;
            color: #2C3E50;
            padding: 30px;
            margin-left: 250px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
            background: #FFFFFF;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(44, 62, 80, 0.08);
            animation: fadeIn 0.5s ease;
        }

        h2 {
            color: #2C3E50;
            text-align: center;
            margin: 0 0 40px;
            font-size: 36px;
            font-weight: 800;
            position: relative;
            padding-bottom: 20px;
            animation: slideDown 0.5s ease;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 4px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            border-radius: 2px;
        }

        .timer-container {
            background: #FFFFFF;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.05);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .timer {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            color: #2C3E50;
            font-weight: 600;
        }

        .timer i {
            color: #FF6F61;
            font-size: 1.4rem;
        }

        .timer span {
            background: #F8F9FA;
            padding: 8px 16px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 1.4rem;
            color: #FF6F61;
            font-weight: 700;
        }

        .progress-container {
            flex-grow: 1;
            margin: 0 20px;
            height: 8px;
            background: #F8F9FA;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            border-radius: 4px;
            transition: width 1s linear;
        }

        .question {
            background: #FFFFFF;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.05);
            margin-bottom: 30px;
            animation: fadeInUp 0.5s ease;
        }

        .question h4 {
            color: #2C3E50;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #F8F9FA;
        }

        .answer-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .answer-option {
            position: relative;
            padding: 15px 20px;
            background: #F8F9FA;
            border: 2px solid #E9ECEF;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .answer-option:hover {
            background: #E9ECEF;
            transform: translateX(5px);
        }

        .answer-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .answer-option label {
            display: block;
            padding-left: 30px;
            cursor: pointer;
            font-size: 1.1rem;
            color: #2C3E50;
        }

        .answer-option label:before {
            content: '';
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border: 2px solid #FF6F61;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .answer-option input[type="radio"]:checked + label:before {
            background: #FF6F61;
            box-shadow: inset 0 0 0 4px #FFFFFF;
        }

        .buttons-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        .btn-finish {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .btn-finish:hover {
            background: #FF3B2F;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .btn-back {
            background: #6C757D;
            color: #FFFFFF;
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
        }

        .btn-back:hover {
            background: #5A6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .container {
                padding: 20px;
            }

            .timer-container {
                flex-direction: column;
                gap: 15px;
            }

            .progress-container {
                width: 100%;
                margin: 10px 0;
            }

            .buttons-container {
                flex-direction: column;
            }

            .btn-finish, .btn-back {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h2><?php echo htmlspecialchars($test['title']); ?></h2>

        <div class="timer-container">
            <div class="timer">
                <i class="fas fa-clock"></i>
                Оставшееся время: <span id="timer"><?php echo $test['time_limit'] / 60; ?>:00</span>
            </div>
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
        </div>

        <form id="test-form" action="submit_test.php" method="POST">
            <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
            <input type="hidden" name="time_taken" id="time_taken" value="">

            <?php if (!empty($questions)): ?>
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question">
                        <h4>Вопрос <?php echo $index + 1; ?>: <?php echo htmlspecialchars($question['question_text']); ?></h4>
                        <div class="answer-options">
                            <?php foreach ($question['answers'] as $answer): ?>
                                <div class="answer-option">
                                    <input type="radio" name="q<?php echo $question['id']; ?>" 
                                           id="q<?php echo $question['id']; ?>_a<?php echo $answer['id']; ?>" 
                                           value="<?php echo $answer['id']; ?>" required>
                                    <label for="q<?php echo $question['id']; ?>_a<?php echo $answer['id']; ?>">
                                        <?php echo htmlspecialchars($answer['answer_text']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Вопросы для этого теста отсутствуют.</p>
            <?php endif; ?>

            <div class="buttons-container">
                <button type="submit" class="btn-finish">
                    <i class="fas fa-check"></i> Завершить тест
                </button>
                <button type="button" class="btn-back" onclick="window.location.href='tests.php'">
                    <i class="fas fa-arrow-left"></i> Вернуться к тестам
                </button>
            </div>
        </form>
    </div>

    <script>
        // Таймер
        let timeLeft = <?php echo $test['time_limit']; ?>;
        const timerElement = document.getElementById('timer');
        const timeTakenInput = document.getElementById('time_taken');
        const progressBar = document.getElementById('progress-bar');
        const totalTime = timeLeft;
        let timerInterval;

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Обновляем прогресс-бар
            const progress = (timeLeft / totalTime) * 100;
            progressBar.style.width = `${progress}%`;

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                document.getElementById('test-form').submit();
            } else {
                timeLeft--;
                timeTakenInput.value = totalTime - timeLeft;
            }
        }

        timerInterval = setInterval(updateTimer, 1000);
        updateTimer();

        // Подтверждение при уходе со страницы
        window.onbeforeunload = function() {
            return "Вы уверены, что хотите покинуть страницу? Ваши ответы не будут сохранены.";
        };

        // Убираем предупреждение при отправке формы
        document.getElementById('test-form').onsubmit = function() {
            window.onbeforeunload = null;
        };
    </script>
</body>
</html>