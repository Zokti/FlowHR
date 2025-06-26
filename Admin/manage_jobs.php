<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь и является ли он админом
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Получаем список всех вакансий
$query = "
    SELECT j.*, 
           e.name as experience_name,
           s.salary_range as salary_amount,
           u.name as hr_name
    FROM jobs j
    LEFT JOIN experiences e ON j.experience_id = e.id
    LEFT JOIN salaries s ON j.salary_id = s.id
    LEFT JOIN users u ON j.hr_id = u.id
    ORDER BY j.created_at DESC
";
$jobs_result = $pdo->query($query);
$jobs = $jobs_result->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление вакансиями</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #F8F9FA;
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        /* Глобальные стили для backdrop */
        .modal-backdrop {
            background: rgba(44, 62, 80, 0.8) !important;
            backdrop-filter: blur(8px) !important;
            animation: backdropFadeIn 0.3s ease !important;
        }

        .modal-backdrop.fade {
            opacity: 0;
        }

        .modal-backdrop.show {
            opacity: 1;
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

        h1 {
            color: #2C3E50;
            text-align: center;
            margin: 0 0 40px;
            font-size: 42px;
            font-weight: 800;
            position: relative;
            padding-bottom: 20px;
            animation: slideDown 0.5s ease;
        }

        h1:after {
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

        h1 i {
            margin-right: 15px;
            color: #FF6F61;
            animation: rotateIn 0.5s ease;
        }

        .table {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.05);
            border: none;
            overflow: hidden;
            margin-top: 30px;
        }

        .table th {
            background: #F8F9FA;
            color: #2C3E50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            padding: 20px;
            border-bottom: 2px solid #F8F9FA;
        }

        .table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid #F8F9FA;
            transition: all 0.3s ease;
            color: #2C3E50;
        }

        .table tr:hover td {
            background: #F8F9FA;
            transform: scale(1.01);
        }

        .table td:last-child {
            min-width: 300px;
        }

        /* Стили для описания вакансии */
        .job-description {
            max-width: 300px;
            max-height: 80px;
            overflow: hidden;
            position: relative;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
        }

        .job-description.full {
            max-height: none;
            -webkit-line-clamp: unset;
        }

        .job-description::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 50px;
            height: 20px;
            background: linear-gradient(to right, transparent, #FFFFFF);
            pointer-events: none;
        }

        .job-description.full::after {
            display: none;
        }

        .show-more-btn {
            background: none;
            border: none;
            color: #FF6F61;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 2px 0;
            margin-top: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .show-more-btn:hover {
            color: #FF3B2F;
            text-decoration: underline;
        }

        .description-container {
            display: flex;
            flex-direction: column;
        }

        .actions-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 280px;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.1);
        }

        .btn-action i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .btn-action:hover i {
            transform: scale(1.2);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.2);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-delete {
            background: #FF3B2F;
            color: #FFFFFF;
        }

        .btn-status {
            background: #28a745;
            color: #FFFFFF;
        }

        .btn-status.closed {
            background: #FF6F61;
        }

        .text-success {
            background: #28a745;
            color: #FFFFFF;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }

        .text-danger {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        /* Стили для модального окна */
        .modal-content {
            border: none;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(44, 62, 80, 0.15);
            overflow: hidden;
            animation: modalSlideIn 0.4s ease;
            position: relative;
        }

        .modal-header {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 30px;
            border: none;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0.5), rgba(255,255,255,0.1));
        }

        .modal-title {
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(44, 62, 80, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            color: #FFFFFF;
        }

        .btn-close {
            position: absolute;
            right: 20px;
            top: 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
            color: #FFFFFF;
            font-size: 20px;
            opacity: 0.8;
        }

        .btn-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
            opacity: 1;
        }

        .modal-body {
            padding: 35px;
            background: #F8F9FA;
        }

        .form-label {
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .form-control, .form-select {
            padding: 15px 20px;
            border: 2px solid #F8F9FA;
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #FFFFFF;
            width: 100%;
            color: #2C3E50;
        }

        .form-control:focus, .form-select:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
            transform: translateY(-2px);
        }

        .modal-footer {
            padding: 25px 35px;
            background: #FFFFFF;
            border-top: 1px solid #F8F9FA;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn-primary {
            background: #FF6F61;
            border: none;
            padding: 15px 40px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #FFFFFF;
            transition: all 0.3s ease;
            min-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .btn-primary:hover {
            background: #FF3B2F;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 111, 97, 0.3);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-primary i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-primary:hover i {
            transform: scale(1.2);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 15px 40px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #FFFFFF;
            transition: all 0.3s ease;
            min-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }

        /* Анимации */
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

        @keyframes rotateIn {
            from {
                opacity: 0;
                transform: rotate(-180deg);
            }
            to {
                opacity: 1;
                transform: rotate(0);
            }
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes iconPulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        @keyframes iconFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        @keyframes backdropFadeIn {
            from {
                opacity: 0;
                backdrop-filter: blur(0px);
            }
            to {
                opacity: 1;
                backdrop-filter: blur(8px);
            }
        }

        @keyframes backdropFadeOut {
            from {
                opacity: 1;
                backdrop-filter: blur(8px);
            }
            to {
                opacity: 0;
                backdrop-filter: blur(0px);
            }
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .container {
                padding: 20px;
            }

            h1 {
                font-size: 32px;
            }

            .table {
                display: block;
                overflow-x: auto;
            }

            .actions-container {
                min-width: 100%;
            }

            .btn-action {
                width: 100%;
                padding: 8px 16px;
            }

            .table td:last-child {
                min-width: 100%;
            }

            .job-description {
                max-width: 100%;
                -webkit-line-clamp: 2;
            }

            .show-more-btn {
                font-size: 0.9rem;
                padding: 5px 0;
            }

            /* Адаптивность для модального окна описания */
            .description-modal .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
            }

            .description-modal .modal-header {
                padding: 25px 20px;
            }

            .description-modal .modal-title {
                font-size: 24px;
            }

            .description-modal .modal-title i {
                font-size: 28px;
            }

            .description-modal .modal-body {
                padding: 25px 20px;
            }

            .description-modal .job-title-display {
                font-size: 20px;
                padding: 15px 20px;
            }

            .description-modal .job-description-display {
                padding: 20px;
                font-size: 15px;
                max-height: 300px;
            }

            .description-modal .modal-footer {
                padding: 20px;
            }

            .description-modal .btn-secondary {
                padding: 12px 30px;
                font-size: 1rem;
                min-width: 150px;
            }
        }

        /* Стили для красивого модального окна описания */
        .description-modal .modal-content {
            border: none;
            border-radius: 25px;
            box-shadow: 0 30px 60px rgba(44, 62, 80, 0.2);
            overflow: hidden;
            animation: descriptionModalSlideIn 0.5s ease;
            background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
        }

        /* Затемнение фона модального окна */
        .description-modal + .modal-backdrop {
            background: rgba(44, 62, 80, 0.8);
            backdrop-filter: blur(8px);
            animation: backdropFadeIn 0.3s ease;
        }

        .description-modal.show + .modal-backdrop {
            background: rgba(44, 62, 80, 0.8);
        }

        .description-modal .modal-header {
            background: linear-gradient(135deg, #FF6F61 0%, #FF3B2F 100%);
            color: #FFFFFF;
            padding: 35px 40px;
            border: none;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .description-modal .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .description-modal .modal-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0.6), rgba(255,255,255,0.2));
        }

        .description-modal .modal-title {
            font-size: 32px;
            font-weight: 800;
            text-shadow: 0 2px 8px rgba(44, 62, 80, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 0;
            color: #FFFFFF;
            position: relative;
            z-index: 1;
        }

        .description-modal .modal-title i {
            font-size: 36px;
            animation: iconFloat 3s ease-in-out infinite;
        }

        .description-modal .btn-close {
            position: absolute;
            right: 25px;
            top: 25px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.2);
            color: #FFFFFF;
            font-size: 22px;
            opacity: 0.9;
            backdrop-filter: blur(10px);
            z-index: 2;
        }

        .description-modal .btn-close:hover {
            background: rgba(255,255,255,0.25);
            transform: rotate(90deg) scale(1.1);
            opacity: 1;
            border-color: rgba(255,255,255,0.4);
        }

        .description-modal .modal-body {
            padding: 40px;
            background: #FFFFFF;
            position: relative;
        }

        .description-modal .modal-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #E9ECEF, transparent);
        }

        .description-modal .job-title-display {
            font-size: 24px;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 25px;
            padding: 20px 25px;
            background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
            border-radius: 15px;
            border-left: 5px solid #FF6F61;
            position: relative;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.05);
        }

        .description-modal .job-title-display::before {
            content: '📋';
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            background: #FF6F61;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(255, 111, 97, 0.3);
        }

        .description-modal .job-description-display {
            background: linear-gradient(135deg, #F8F9FA 0%, #FFFFFF 100%);
            border: 2px solid #E9ECEF;
            border-radius: 20px;
            padding: 30px;
            font-size: 16px;
            line-height: 1.8;
            color: #2C3E50;
            position: relative;
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.08);
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }

        .description-modal .job-description-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F, #FF6F61);
            border-radius: 20px 20px 0 0;
        }

        .description-modal .job-description-display::after {
            content: '📄';
            position: absolute;
            top: -10px;
            right: 20px;
            background: #FF6F61;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            box-shadow: 0 4px 10px rgba(255, 111, 97, 0.3);
        }

        .description-modal .job-description-display::-webkit-scrollbar {
            width: 8px;
        }

        .description-modal .job-description-display::-webkit-scrollbar-track {
            background: #F1F3F4;
            border-radius: 10px;
        }

        .description-modal .job-description-display::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            border-radius: 10px;
        }

        .description-modal .job-description-display::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #FF3B2F, #FF6F61);
        }

        .description-modal .modal-footer {
            padding: 30px 40px;
            background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
            border-top: 1px solid #E9ECEF;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .description-modal .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            border: none;
            padding: 15px 40px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #FFFFFF;
            transition: all 0.3s ease;
            min-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
            position: relative;
            overflow: hidden;
        }

        .description-modal .btn-secondary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .description-modal .btn-secondary:hover::before {
            left: 100%;
        }

        .description-modal .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }

        /* Анимации для модального окна */
        @keyframes descriptionModalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-briefcase"></i>Управление вакансиями</h1>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Статус</th>
                    <th>Опыт</th>
                    <th>Зарплата</th>
                    <th>HR</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?= htmlspecialchars($job['id']) ?></td>
                        <td><?= htmlspecialchars($job['title']) ?></td>
                        <td>
                            <div class="description-container">
                                <div class="job-description" data-job-id="<?= $job['id'] ?>">
                                    <?= htmlspecialchars($job['description']) ?>
                                </div>
                                <button class="show-more-btn" data-job-id="<?= $job['id'] ?>">Подробнее</button>
                            </div>
                        </td>
                        <td>
                            <?php if ($job['status'] === 'active'): ?>
                                <span class="text-success">Активна</span>
                            <?php else: ?>
                                <span class="text-danger">Закрыта</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($job['experience_name']) ?></td>
                        <td><?= htmlspecialchars($job['salary_amount']) ?></td>
                        <td><?= htmlspecialchars($job['hr_name']) ?></td>
                        <td><?= htmlspecialchars($job['created_at']) ?></td>
                        <td>
                            <div class="actions-container">
                                <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editJobModal" data-job-id="<?= $job['id'] ?>" data-job-title="<?= htmlspecialchars($job['title']) ?>" data-job-description="<?= htmlspecialchars($job['description']) ?>" data-job-status="<?= htmlspecialchars($job['status']) ?>">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteJobModal" data-job-id="<?= $job['id'] ?>">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                                <button class="btn-action btn-status <?= $job['status'] === 'active' ? 'active' : 'closed' ?>" 
                                        onclick="toggleJobStatus(<?= $job['id'] ?>, '<?= $job['status'] ?>')">
                                    <i class="fas <?= $job['status'] === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                    <?= $job['status'] === 'active' ? 'Закрыть' : 'Открыть' ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно редактирования вакансии -->
    <div class="modal fade" id="editJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать вакансию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editJobForm">
                        <input type="hidden" name="job_id" id="editJobId">
                        <div class="mb-3">
                            <label for="editJobTitle" class="form-label">Название</label>
                            <input type="text" class="form-control" id="editJobTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="editJobDescription" class="form-label">Описание</label>
                            <textarea class="form-control" id="editJobDescription" name="description" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editJobStatus" class="form-label">Статус</label>
                            <select class="form-select" id="editJobStatus" name="status" required>
                                <option value="active">Активна</option>
                                <option value="closed">Закрыта</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно удаления вакансии -->
    <div class="modal fade" id="deleteJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить вакансию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить эту вакансию?</p>
                    <form id="deleteJobForm">
                        <input type="hidden" name="job_id" id="deleteJobId">
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно просмотра описания вакансии -->
    <div class="modal fade description-modal" id="viewDescriptionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-alt"></i> Описание вакансии</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="job-title-display" id="modalJobTitle"></div>
                    <div class="job-description-display" id="modalJobDescription"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Заполнение данных в модальном окне редактирования
        const editJobModal = document.getElementById('editJobModal');
        editJobModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const jobId = button.getAttribute('data-job-id');
            const jobTitle = button.getAttribute('data-job-title');
            const jobDescription = button.getAttribute('data-job-description');
            const jobStatus = button.getAttribute('data-job-status');

            document.getElementById('editJobId').value = jobId;
            document.getElementById('editJobTitle').value = jobTitle;
            document.getElementById('editJobDescription').value = jobDescription;
            document.getElementById('editJobStatus').value = jobStatus;
        });

        // Заполнение данных в модальном окне удаления
        const deleteJobModal = document.getElementById('deleteJobModal');
        deleteJobModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const jobId = button.getAttribute('data-job-id');
            document.getElementById('deleteJobId').value = jobId;
        });

        // Обработка формы редактирования
        document.getElementById('editJobForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch('admin_actions.php?action=edit_job', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });

        // Обработка формы удаления
        document.getElementById('deleteJobForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch('admin_actions.php?action=delete_job', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });

        // Изменение статуса вакансии
        function toggleJobStatus(jobId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'closed' : 'active';

            fetch('admin_actions.php?action=toggle_job_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ job_id: jobId, status: newStatus }),
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        }

        // Обработка кнопки "Подробнее"
        const showMoreButtons = document.querySelectorAll('.show-more-btn');
        showMoreButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const jobId = this.getAttribute('data-job-id');
                const row = this.closest('tr');
                const jobTitle = row.querySelector('td:nth-child(2)').textContent.trim();
                const jobDescription = row.querySelector('.job-description').textContent.trim();
                
                // Заполняем модальное окно
                document.getElementById('modalJobTitle').textContent = jobTitle;
                document.getElementById('modalJobDescription').textContent = jobDescription;
                
                // Открываем модальное окно с красивой анимацией и backdrop
                const modal = new bootstrap.Modal(document.getElementById('viewDescriptionModal'), {
                    backdrop: true,
                    keyboard: true
                });
                modal.show();
                
                // Добавляем небольшую задержку для плавной анимации
                setTimeout(() => {
                    const modalElement = document.getElementById('viewDescriptionModal');
                    modalElement.classList.add('show');
                    
                    // Принудительно создаем backdrop если его нет
                    if (!document.querySelector('.modal-backdrop')) {
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        backdrop.style.cssText = `
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100vw;
                            height: 100vh;
                            background: rgba(44, 62, 80, 0.8);
                            backdrop-filter: blur(8px);
                            z-index: 1040;
                            animation: backdropFadeIn 0.3s ease;
                        `;
                        document.body.appendChild(backdrop);
                    }
                }, 100);
            });
        });

        // Добавляем обработчик для закрытия модального окна по клику вне его
        document.getElementById('viewDescriptionModal').addEventListener('click', function(event) {
            if (event.target === this) {
                const modal = bootstrap.Modal.getInstance(this);
                modal.hide();
            }
        });
    </script>
</body>
</html>