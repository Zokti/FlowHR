<?php
session_start();
require '../includes/config.php';
require '../includes/header.php';

// Проверка авторизации и роли HR
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HR') {
    header("Location: login.php");
    exit();
}

$hr_id = $_SESSION['user_id'];

// Обработка предложения вакансии
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'], $_POST['job_id'])) {
    $candidate_id = $_POST['candidate_id'];
    $job_id = $_POST['job_id'];

    // Проверяем, существует ли вакансия
    $query = "SELECT id FROM jobs WHERE id = :job_id AND user_id = :hr_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['job_id' => $job_id, 'hr_id' => $hr_id]);
    $job = $stmt->fetch();

    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Вакансия не найдена']);
        exit();
    }

    // Проверяем, не было ли уже предложения
    $query = "SELECT id FROM applications WHERE job_id = :job_id AND user_id = :candidate_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['job_id' => $job_id, 'candidate_id' => $candidate_id]);
    $application = $stmt->fetch();

    if ($application) {
        echo json_encode(['success' => false, 'message' => 'Вакансия уже предложена']);
        exit();
    }

    // Добавляем предложение
    $query = "INSERT INTO applications (job_id, user_id, status) VALUES (:job_id, :user_id, 'pending')";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['job_id' => $job_id, 'user_id' => $candidate_id]);

    echo json_encode(['success' => true]);
    exit();
}

// Получаем всех кандидатов и их результаты тестов
$query_candidates = "
    SELECT 
        users.id AS candidate_id,
        users.name AS candidate_name,
        users.avatar AS candidate_avatar,
        results.score,
        results.completed_at,
        tests.title AS test_name
    FROM users
    LEFT JOIN results ON users.id = results.user_id
    LEFT JOIN tests ON results.test_id = tests.id
    WHERE users.role = 'candidate'
    ORDER BY users.id, results.completed_at DESC
";
$stmt_candidates = $pdo->prepare($query_candidates);
$stmt_candidates->execute();
$candidates = $stmt_candidates->fetchAll(PDO::FETCH_GROUP);

// Получаем все вакансии HR
$query_jobs = "SELECT id, title FROM jobs WHERE user_id = :hr_id";
$stmt_jobs = $pdo->prepare($query_jobs);
$stmt_jobs->execute(['hr_id' => $hr_id]);
$jobs = $stmt_jobs->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список кандидатов</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #FFFFFF;
            padding: 20px;
            margin-left: 250px; /* Отступ для бокового меню */
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #FF6F61;
            text-align: center;
            margin: 40px 0;
            font-size: 36px;
            font-weight: bold;
            animation: fadeInUp 0.5s ease-in-out;
        }
        h1 i {
            margin-right: 10px;
        }
        .candidate-card {
            background: #FFFFFF;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease-in-out;
        }
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        .candidate-card h5 {
            color: #2C3E50;
            margin-top: 0;
        }
        .candidate-card p {
            color: #666666;
            margin: 10px 0;
        }
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .btn-primary {
            background-color: #FF6F61;
            color: #FFFFFF;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #FF3B2F;
        }
        .btn-primary i {
            margin-right: 8px;
        }
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #E0E0E0;
        }
        .test-item:last-child {
            border-bottom: none;
        }
        .test-item i {
            color: #FF6F61;
            margin-right: 10px;
        }
        .test-name {
            flex-grow: 1;
        }
        .test-score {
            color: #2C3E50;
            font-weight: bold;
        }
        .modal-content {
            background: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            background: #FF6F61;
            color: #FFFFFF;
            border-radius: 15px 15px 0 0;
        }
        .modal-title {
            font-size: 18px;
        }
        .modal-body {
            padding: 20px;
        }
        .form-select {
            border-radius: 5px;
            border: 1px solid #E0E0E0;
            padding: 8px;
            margin-bottom: 10px;
            transition: border-color 0.3s ease;
        }
        .form-select:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 5px rgba(255, 111, 97, 0.5);
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
        .candidate-card:nth-child(1) { animation-delay: 0.1s; }
        .candidate-card:nth-child(2) { animation-delay: 0.2s; }
        .candidate-card:nth-child(3) { animation-delay: 0.3s; }
        .candidate-card:nth-child(4) { animation-delay: 0.4s; }
        .candidate-card:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-users"></i>Список кандидатов</h1>

        <?php if (empty($candidates)): ?>
            <p>Нет доступных кандидатов.</p>
        <?php else: ?>
            <?php foreach ($candidates as $candidate_id => $tests): ?>
                <?php
                // Проверяем, какие вакансии уже предложены кандидату
                $query_applied_jobs = "
                    SELECT job_id FROM applications
                    WHERE user_id = :candidate_id AND job_id IN (
                        SELECT id FROM jobs WHERE user_id = :hr_id
                    )
                ";
                $stmt_applied = $pdo->prepare($query_applied_jobs);
                $stmt_applied->execute(['candidate_id' => $candidate_id, 'hr_id' => $hr_id]);
                $applied_jobs = $stmt_applied->fetchAll(PDO::FETCH_COLUMN);

                // Фильтруем вакансии, которые еще не предложены
                $available_jobs = array_filter($jobs, function($job) use ($applied_jobs) {
                    return !in_array($job['id'], $applied_jobs);
                });
                ?>
                <div class="candidate-card">
                    <div class="d-flex align-items-center">
                        <img src="../uploads/avatars/<?php echo htmlspecialchars($tests[0]['candidate_avatar'] ?? 'default_avatar.jpg'); ?>" class="avatar">
                        <h5><?php echo htmlspecialchars($tests[0]['candidate_name']); ?></h5>
                    </div>
                    <div>
                        <?php if (empty($tests[0]['test_name'])): ?>
                            <p>Нет пройденных тестов</p>
                        <?php else: ?>
                            <?php foreach ($tests as $test): ?>
                                <div class="test-item">
                                    <div class="test-name">
                                        <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($test['test_name']); ?>
                                    </div>
                                    <div class="test-score">
                                        <?php echo htmlspecialchars($test['score']); ?> баллов
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <?php if (!empty($available_jobs)): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#offerModal" data-candidate-id="<?php echo $candidate_id; ?>">
                                <i class="fas fa-handshake"></i> Предложить вакансию
                            </button>
                        <?php else: ?>
                            <span style="color: #FF6F61;">Все вакансии предложены</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Модальное окно для предложения вакансии -->
    <div class="modal fade" id="offerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Предложить вакансию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="offerForm">
                        <input type="hidden" name="candidate_id" id="candidateId">
                        <div class="mb-3">
                            <label for="jobSelect" class="form-label">Выберите вакансию:</label>
                            <select class="form-select" id="jobSelect" name="job_id" required>
                                <!-- Вакансии будут добавлены через JavaScript -->
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Предложить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Обработка открытия модального окна
        document.addEventListener('DOMContentLoaded', function() {
            const offerModal = document.getElementById('offerModal');
            const jobSelect = document.getElementById('jobSelect');

            offerModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // Кнопка, которая открыла модальное окно
                const candidateId = button.getAttribute('data-candidate-id');
                document.getElementById('candidateId').value = candidateId;

                // Очищаем список вакансий
                jobSelect.innerHTML = '';

                // Добавляем доступные вакансии
                fetch('get_available_jobs.php?candidate_id=' + candidateId)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(job => {
                            const option = document.createElement('option');
                            option.value = job.id;
                            option.textContent = job.title;
                            jobSelect.appendChild(option);
                        });
                    });
            });

            // Обработка отправки формы
            document.getElementById('offerForm').addEventListener('submit', function(event) {
                event.preventDefault();

                const formData = new FormData(this);
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Вакансия успешно предложена!');
                        location.reload(); // Перезагружаем страницу
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            });
        });
    </script>
</body>
</html>