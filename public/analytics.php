<?php
session_start();
require '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HR') {
    header("Location: login.php");
    exit();
}

$hr_id = $_SESSION['user_id'];

// Получаем данные для аналитики
$query = "SELECT COUNT(*) as total_jobs FROM jobs WHERE user_id = :hr_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$total_jobs = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total_applications FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.user_id = :hr_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$total_applications = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as pending FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.user_id = :hr_id AND a.status = 'pending'";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$pending_applications = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as accepted FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.user_id = :hr_id AND a.status = 'accepted'";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$accepted_applications = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as rejected FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.user_id = :hr_id AND a.status = 'rejected'";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$rejected_applications = $stmt->fetchColumn();

// Получаем данные для графика по вакансиям
$query = "SELECT j.title, COUNT(a.id) as applications_count FROM jobs j LEFT JOIN applications a ON j.id = a.job_id WHERE j.user_id = :hr_id GROUP BY j.id";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$jobs_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jobs_labels = [];
$jobs_applications = [];
foreach ($jobs_data as $job) {
    $jobs_labels[] = $job['title'];
    $jobs_applications[] = $job['applications_count'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Подключаем Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #FFFFFF; /* Белый фон страницы */
            color: #2C3E50; /* Темно-серый текст */
            padding-top: 60px;
        }

        .container {
            background: #FFFFFF; /* Белый фон контейнера */
            padding: 30px;
            border-radius: 20px; /* Закругленные углы */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Легкая тень */
            margin-top: 20px;
            border: 1px solid #E0E0E0; /* Обводка контейнера */
        }

        h2 {
            color: #2C3E50; /* Темно-серый заголовок */
            margin-bottom: 20px;
        }

        .stat-card {
            background: #FFFFFF; /* Белый фон карточек */
            border: 1px solid #E0E0E0; /* Серая граница */
            padding: 20px;
            border-radius: 15px; /* Закругленные углы */
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px); /* Поднимаем карточку при наведении */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); /* Увеличиваем тень */
        }

        .stat-card h3 {
            margin-top: 0;
            font-size: 20px;
            color: #2C3E50; /* Темно-серый текст */
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #FF6F61; /* Коралловый акцент */
        }

        canvas {
            background: #FFFFFF; /* Белый фон графиков */
            border: 1px solid #E0E0E0; /* Серая граница */
            border-radius: 15px; /* Закругленные углы */
            padding: 20px;
            margin-top: 20px;
        }

        .btn-primary {
            background-color: #FF6F61; /* Коралловый цвет для кнопок */
            border: none;
            color: #FFFFFF; /* Белый текст */
            border-radius: 10px; /* Закругленные углы */
            padding: 10px 20px;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #FF3B2F; /* Темно-коралловый при наведении */
        }

        .icon {
            margin-right: 10px;
            color: #FF6F61; /* Коралловый цвет иконок */
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h2>Аналитика вакансий</h2>

        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <h3><i class="fas fa-briefcase icon"></i>Общее количество ваших вакансий:</h3>
                    <p><?php echo $total_jobs; ?> вакансий</p>
                </div>

                <div class="stat-card">
                    <h3><i class="fas fa-envelope-open-text icon"></i>Все отклики на ваши вакансии:</h3>
                    <p><?php echo $total_applications; ?> откликов</p>
                </div>

                <div class="stat-card">
                    <h3><i class="fas fa-check-circle icon"></i>Количество принятых откликов:</h3>
                    <p><?php echo $accepted_applications; ?> принятых</p>
                </div>

                <div class="stat-card">
                    <h3><i class="fas fa-hourglass-half icon"></i>Количество откликов в ожидании:</h3>
                    <p><?php echo $pending_applications; ?> в ожидании</p>
                </div>

                <div class="stat-card">
                    <h3><i class="fas fa-times-circle icon"></i>Количество отклоненных откликов:</h3>
                    <p><?php echo $rejected_applications; ?> отклоненных</p>
                </div>
            </div>

            <div class="col-md-6">
                <h3><i class="fas fa-chart-pie icon"></i>Визуальная аналитика откликов</h3>
                <canvas id="applicationsChart"></canvas>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h3><i class="fas fa-chart-bar icon"></i>Аналитика по каждой вакансии</h3>
                <canvas id="jobsChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // График для откликов
        const ctxApplications = document.getElementById('applicationsChart').getContext('2d');
        new Chart(ctxApplications, {
            type: 'doughnut',
            data: {
                labels: ['Принятые', 'Ожидают рассмотрения', 'Отклоненные'],
                datasets: [{
                    data: [<?php echo $accepted_applications; ?>, <?php echo $pending_applications; ?>, <?php echo $rejected_applications; ?>],
                    backgroundColor: ['#28a745', '#FFC107', '#dc3545'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#2C3E50' /* Темно-серый текст */
                        }
                    }
                }
            }
        });

        // График для вакансий
        const ctxJobs = document.getElementById('jobsChart').getContext('2d');
        new Chart(ctxJobs, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($jobs_labels); ?>,
                datasets: [{
                    label: 'Количество откликов',
                    data: <?php echo json_encode($jobs_applications); ?>,
                    backgroundColor: '#FF6F61', /* Коралловый цвет */
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#2C3E50' /* Темно-серый текст */
                        }
                    },
                    x: {
                        ticks: {
                            color: '#2C3E50' /* Темно-серый текст */
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>