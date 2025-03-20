<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем все доступные вакансии
$jobs_query = "
    SELECT j.id, j.title, j.description, j.status, s.salary_range, e.name AS experience, j.skill_ids
    FROM jobs j
    JOIN salaries s ON j.salary_id = s.id
    JOIN experiences e ON j.experience_id = e.id
    WHERE j.status = 'active'
";
$jobs_stmt = $pdo->query($jobs_query);
$jobs = $jobs_stmt->fetchAll();

// Получаем список всех навыков
$skills_query = "SELECT id, name FROM skills";
$skills_result = $pdo->query($skills_query);
$skills = $skills_result->fetchAll(PDO::FETCH_KEY_PAIR); // Ключ - ID навыка, значение - название

// Обработка отклика на вакансию
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = $_POST['job_id'];
    $user_id = $_SESSION['user_id'];

    // Проверяем, не откликался ли уже пользователь на эту вакансию
    $check_query = "SELECT * FROM applications WHERE job_id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$job_id, $user_id]);

    if ($check_stmt->rowCount() === 0) {
        // Добавляем отклик
        $insert_query = "INSERT INTO applications (job_id, user_id, status) VALUES (?, ?, 'pending')";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute([$job_id, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Отклик успешно отправлен!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Вы уже откликались на эту вакансию.']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вакансии</title>
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

        .job-card {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease-in-out;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .job-card h3 {
            color: #2C3E50;
            margin-top: 0;
        }

        .job-card p {
            color: #666666;
            margin: 10px 0;
        }

        .job-card strong {
            color: #2C3E50;
        }

        .job-card .job-details {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .job-card .job-details i {
            color: #FF6F61;
            margin-right: 10px;
        }

        .btn-apply {
            background-color: #FF6F61;
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

        .btn-apply i {
            margin-right: 8px;
        }

        .btn-apply:hover {
            background-color: #FF3B2F;
        }

        .btn-apply:disabled {
            background-color: #E0E0E0;
            cursor: not-allowed;
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
        .job-card:nth-child(1) { animation-delay: 0.1s; }
        .job-card:nth-child(2) { animation-delay: 0.2s; }
        .job-card:nth-child(3) { animation-delay: 0.3s; }
        .job-card:nth-child(4) { animation-delay: 0.4s; }
        .job-card:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Доступные вакансии</h1>

        <?php foreach ($jobs as $job): ?>
            <?php
            // Преобразуем skill_ids в массив названий навыков
            $skill_ids = explode(',', $job['skill_ids']);
            $job_skills = array_map(function ($skill_id) use ($skills) {
                return $skills[$skill_id] ?? 'Неизвестный навык';
            }, $skill_ids);
            ?>
            <div class="job-card" id="job-<?php echo $job['id']; ?>">
                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                <p><?php echo htmlspecialchars($job['description']); ?></p>

                <div class="job-details">
                    <i class="fas fa-money-bill-wave"></i>
                    <p><strong>Зарплата:</strong> <?php echo htmlspecialchars($job['salary_range']); ?></p>
                </div>

                <div class="job-details">
                    <i class="fas fa-user-tie"></i>
                    <p><strong>Опыт работы:</strong> <?php echo htmlspecialchars($job['experience']); ?></p>
                </div>

                <div class="job-details">
                    <i class="fas fa-tools"></i>
                    <p><strong>Навыки:</strong> <?php echo implode(', ', $job_skills); ?></p>
                </div>

                <button class="btn-apply" onclick="applyForJob(<?php echo $job['id']; ?>)">
                    <i class="fas fa-handshake"></i> Откликнуться
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function applyForJob(jobId) {
            $.ajax({
                url: 'jobs.php',
                method: 'POST',
                data: { job_id: jobId },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert(result.message);
                        // Делаем кнопку неактивной после успешного отклика
                        $(`#job-${jobId} .btn-apply`).html('<i class="fas fa-check"></i> Отклик отправлен').prop('disabled', true);
                    } else {
                        alert(result.message);
                    }
                }
            });
        }
    </script>
</body>
</html>