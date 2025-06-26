<?php
session_start();
require '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HR') {
    header("Location: login.php");
    exit();
}

$hr_id = $_SESSION['user_id'];

// Получаем общее количество вакансий
$query = "SELECT COUNT(*) as total_jobs FROM jobs WHERE hr_id = :hr_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$total_jobs = $stmt->fetch(PDO::FETCH_ASSOC)['total_jobs'];

// Получаем общее количество откликов
$query = "SELECT COUNT(*) as total_applications FROM applications WHERE hr_id = :hr_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$total_applications = $stmt->fetch(PDO::FETCH_ASSOC)['total_applications'];

// Получаем количество откликов в ожидании
$query = "SELECT COUNT(*) as pending FROM applications WHERE hr_id = :hr_id AND status = 'pending'";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$pending_applications = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

// Получаем количество кандидатов на собеседовании
$query = "SELECT COUNT(*) as interview FROM applications WHERE hr_id = :hr_id AND status = 'interview'";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$interview_applications = $stmt->fetch(PDO::FETCH_ASSOC)['interview'];

// Получаем количество принятых откликов
$query = "SELECT COUNT(*) as hired FROM applications WHERE hr_id = :hr_id AND status = 'hired'";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$hired_applications = $stmt->fetch(PDO::FETCH_ASSOC)['hired'];

// Получаем количество отклоненных откликов
$query = "SELECT COUNT(*) as rejected FROM applications WHERE hr_id = :hr_id AND status = 'rejected'";
$stmt = $pdo->prepare($query);
$stmt->execute(['hr_id' => $hr_id]);
$rejected_applications = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];

// Получаем статистику по вакансиям
$query = "SELECT j.title, COUNT(a.id) as applications_count 
          FROM jobs j 
          LEFT JOIN applications a ON j.id = a.job_id 
          WHERE j.hr_id = :hr_id 
          GROUP BY j.id";
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
            --chart-color-1: #FF6F61;
            --chart-color-2: #4ECDC4;
            --chart-color-3: #FFD166;
            --chart-color-4: #EF476F;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            padding-top: 0;
            line-height: 1.6;
            margin-left: 250px;
        }

        .container {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin: 30px;
            border: 1px solid var(--border-color);
            max-width: calc(100% - 60px);
        }

        h2 {
            color: var(--text-color);
            margin-bottom: 40px;
            font-weight: 700;
            font-size: 32px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover:before {
            transform: scaleX(1);
        }

        .stat-card h3 {
            margin-top: 0;
            font-size: 16px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-weight: 600;
            line-height: 1.4;
        }

        .stat-card p {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            color: var(--primary-color);
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .stats-section {
            margin-bottom: 40px;
        }

        .analytics-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .analytics-section h3 {
            color: var(--text-color);
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        .chart-container {
            margin-top: 0;
            background: transparent;
            box-shadow: none;
            border: none;
            padding: 0;
        }

        .chart-container:hover {
            box-shadow: none;
        }

        #applicationsChart {
            max-height: 400px;
            width: 100% !important;
        }

        #jobsChart {
            max-height: 500px;
            width: 100% !important;
        }

        .icon {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 24px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .icon {
            transform: scale(1.1);
        }

        /* Мобильная адаптация */
        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding-top: 20px;
            }

            .container {
                padding: 20px;
                margin: 15px;
                border-radius: 20px;
            }

            h2 {
                font-size: 26px;
                margin-bottom: 30px;
            }

            .stat-card {
                height: 160px;
                padding: 20px;
            }

            .stat-card h3 {
                font-size: 14px;
                margin-bottom: 15px;
            }

            .stat-card p {
                font-size: 28px;
            }

            .analytics-section {
                padding: 20px;
            }

            .analytics-section h3 {
                font-size: 20px;
                margin-bottom: 20px;
            }

            #applicationsChart, #jobsChart {
                max-height: 350px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h2><i class="fas fa-chart-line icon"></i>Аналитика вакансий</h2>

        <!-- Секция со статистикой -->
        <div class="stats-section">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><i class="fas fa-briefcase icon"></i>Общее количество ваших вакансий</h3>
                        <p><?php echo $total_jobs; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><i class="fas fa-envelope-open-text icon"></i>Все отклики на ваши вакансии</h3>
                        <p><?php echo $total_applications; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><i class="fas fa-check-circle icon"></i>Принятые на работу</h3>
                        <p><?php echo $hired_applications; ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><i class="fas fa-user-tie icon"></i>На собеседовании</h3>
                        <p><?php echo $pending_applications; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Секция с аналитикой -->
        <div class="analytics-section">
            <h3><i class="fas fa-chart-pie icon"></i>Статистика откликов</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-container">
                        <canvas id="applicationsChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <canvas id="jobsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // График для откликов
        const ctxApplications = document.getElementById('applicationsChart').getContext('2d');
        new Chart(ctxApplications, {
            type: 'doughnut',
            data: {
                labels: ['На собеседовании', 'Ожидают', 'Приняты', 'Отклонены'],
                datasets: [{
                    data: [
                        <?php echo $interview_applications; ?>, 
                        <?php echo $pending_applications; ?>, 
                        <?php echo $hired_applications; ?>, 
                        <?php echo $rejected_applications; ?>
                    ],
                    backgroundColor: [
                        '#FF6F61',
                        '#4ECDC4',
                        '#FFD166',
                        '#EF476F'
                    ],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'var(--text-color)',
                            font: {
                                size: 14,
                                weight: '500'
                            },
                            padding: 20,
                            generateLabels: function(chart) {
                                const datasets = chart.data.datasets;
                                return chart.data.labels.map(function(label, i) {
                                    const value = datasets[0].data[i];
                                    return {
                                        text: `${label} (${value})`,
                                        fillStyle: datasets[0].backgroundColor[i],
                                        strokeStyle: datasets[0].backgroundColor[i],
                                        lineWidth: 2,
                                        hidden: isNaN(value) || value === 0,
                                        index: i
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value}`;
                            }
                        }
                    }
                },
                cutout: '60%'
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
                    backgroundColor: '#FF6F61',
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: 'var(--text-color)',
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'var(--text-color)',
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            maxRotation: 45,
                            minRotation: 45,
                            padding: 10
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>