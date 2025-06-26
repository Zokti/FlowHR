<?php


session_start();
require '../includes/config.php';
ob_start(); // Включаем буферизацию вывода

// Проверяем, авторизован ли пользователь и является ли он HR
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] != 'HR') {
    header("Location: access_denied.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем данные для выпадающих списков и чекбоксов
$experiences = $pdo->query("SELECT * FROM experiences")->fetchAll();
$salaries = $pdo->query("SELECT * FROM salaries")->fetchAll();
$skills = $pdo->query("SELECT * FROM skills")->fetchAll();

// Обработка AJAX-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        try {
            switch ($_POST['action']) {
                case 'edit':
                    $job_id = $_POST['job_id'];
                    $title = $_POST['title'];
                    $description = $_POST['description'];
                    $experience_id = $_POST['experience_id'];
                    $salary_id = $_POST['salary_id'];
                    $skill_ids = isset($_POST['skills']) ? $_POST['skills'] : [];

                    $pdo->beginTransaction();

                    // Обновляем основную информацию о вакансии
                    $update_query = "
                        UPDATE jobs 
                        SET title = ?, description = ?, experience_id = ?, salary_id = ?
                        WHERE id = ? AND hr_id = ?
                    ";
                    $stmt = $pdo->prepare($update_query);
                    $result = $stmt->execute([$title, $description, $experience_id, $salary_id, $job_id, $user_id]);
                    
                    if ($result) {
                        // Удаляем старые навыки
                        $stmt = $pdo->prepare("DELETE FROM job_skills WHERE job_id = ?");
                        $stmt->execute([$job_id]);

                        // Добавляем новые навыки
                        if (!empty($skill_ids)) {
                            $stmt = $pdo->prepare("INSERT INTO job_skills (job_id, skill_id) VALUES (?, ?)");
                            foreach ($skill_ids as $skill_id) {
                                $stmt->execute([$job_id, $skill_id]);
                            }
                        }

                        $pdo->commit();
                        echo json_encode(['success' => true, 'message' => 'Вакансия успешно обновлена']);
                    } else {
                        $pdo->rollBack();
                        throw new Exception('Ошибка при обновлении вакансии');
                    }
                    break;

                case 'close':
                    $job_id = $_POST['job_id'];
                    $update_query = "UPDATE jobs SET status = 'closed' WHERE id = ? AND hr_id = ?";
                    $stmt = $pdo->prepare($update_query);
                    $result = $stmt->execute([$job_id, $user_id]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Вакансия успешно закрыта']);
                    } else {
                        throw new Exception('Ошибка при закрытии вакансии');
                    }
                    break;

                case 'delete':
                    $job_id = $_POST['job_id'];
                    $delete_query = "DELETE FROM jobs WHERE id = ? AND hr_id = ?";
                    $stmt = $pdo->prepare($delete_query);
                    $result = $stmt->execute([$job_id, $user_id]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Вакансия успешно удалена']);
                    } else {
                        throw new Exception('Ошибка при удалении вакансии');
                    }
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

// Подключаем header.php только для обычных запросов
require_once '../includes/header.php';

// Получаем вакансии с навыками
$jobs_query = "
    SELECT 
        j.id, 
        j.title, 
        j.description, 
        j.status, 
        j.experience_id,
        j.salary_id,
        e.name as experience,
        s.salary_range,
        COUNT(DISTINCT a.id) as applications_count,
        GROUP_CONCAT(DISTINCT sk.name) as skills
    FROM jobs j
    LEFT JOIN experiences e ON j.experience_id = e.id
    LEFT JOIN salaries s ON j.salary_id = s.id
    LEFT JOIN applications a ON j.id = a.job_id
    LEFT JOIN job_skills js ON j.id = js.job_id
    LEFT JOIN skills sk ON js.skill_id = sk.id
    WHERE j.hr_id = ?
    GROUP BY j.id
    ORDER BY j.created_at DESC
";
$jobs_stmt = $pdo->prepare($jobs_query);
$jobs_stmt->execute([$user_id]);
$jobs = $jobs_stmt->fetchAll();

ob_end_flush(); // Отправляем буферизированный вывод
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои вакансии</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #FF8C75;
            --primary-dark: #FF6B4A;
            --text-color: #2C3E50;
            --text-light: #718096;
            --bg-light: #F8FAFC;
            --border-color: #E2E8F0;
            --success-color: #48BB78;
            --error-color: #F56565;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--bg-light);
            margin-left: 250px;
            padding: 20px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 140, 117, 0.1);
        }

        .page-title {
            color: var(--text-color);
            font-size: 32px;
            margin: 0;
            position: relative;
            padding-bottom: 15px;
        }

        .page-title:after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            position: absolute;
            left: 0;
            bottom: 0;
            border-radius: 2px;
        }

        .create-vacancy-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .create-vacancy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 140, 117, 0.2);
            color: white;
        }

        .vacancy-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 140, 117, 0.1);
            position: relative;
            overflow: hidden;
        }

        .vacancy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255, 140, 117, 0.15);
        }

        .vacancy-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-dark));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .vacancy-card:hover::before {
            opacity: 1;
        }

        .vacancy-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .vacancy-title {
            color: #2C3E50;
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }

        .applications-count {
            background: #F0F7FF;
            color: #007AFF;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .applications-count i {
            font-size: 0.9rem;
        }

        .vacancy-status-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .vacancy-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .vacancy-status i {
            font-size: 0.9rem;
        }

        .status-active {
            background: #E3FCEF;
            color: #00A76F;
        }

        .status-inactive {
            background: #FFF5F2;
            color: #FF6B4A;
        }

        .vacancy-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
        }

        .info-item i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .vacancy-description {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 20px;
            padding: 15px;
            background: #FFF9F7;
            border-radius: 10px;
            border: 1px solid rgba(255, 140, 117, 0.1);
        }

        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .skill-tag {
            background: #FFF8E1;
            border: 2px solid var(--primary-color);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.9rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .skill-tag i {
            color: var(--primary-color);
            font-size: 0.8rem;
        }

        .skill-tag:hover {
            background: #FFF5F2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 111, 97, 0.1);
        }

        .vacancy-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid rgba(255, 140, 117, 0.1);
        }

        .action-button {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            background: white;
        }

        .edit-button {
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .edit-button:hover {
            background: #FFF5F2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 97, 0.15);
        }

        .close-button {
            color: var(--error-color);
            border: 2px solid var(--error-color);
        }

        .close-button:hover {
            background: #FFF5F5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.15);
        }

        .applications-count {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 140, 117, 0.1);
            color: var(--primary-color);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Модальные окна */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal.show {
            opacity: 1;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        .modal.show .modal-content {
            transform: translate(-50%, -50%) scale(1);
        }

        .modal-header {
            padding: 25px 30px;
            background: linear-gradient(to right, #FFF8E1, #FFF5F2);
            border-bottom: 1px solid rgba(255, 140, 117, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            flex-shrink: 0;
        }

        .modal-title {
            font-size: 1.8rem;
            color: var(--text-color);
            font-weight: 600;
            margin: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .close-modal {
            background: white;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 8px;
            transition: all 0.3s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .close-modal:hover {
            background: #FFF5F2;
            color: var(--primary-color);
            transform: rotate(90deg);
            box-shadow: 0 4px 12px rgba(255, 111, 97, 0.2);
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px 0;
        }

        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        .form-group {
            margin-bottom: 25px;
            padding: 0 30px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-light);
        }

        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 140, 117, 0.1);
            outline: none;
            background: white;
        }

        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-height: 200px;
            overflow-y: auto;
            padding: 5px 15px 5px 5px;
            margin: 0 15px;
        }

        .skills-grid::-webkit-scrollbar {
            width: 8px;
        }

        .skills-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .skills-grid::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        .skills-grid::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        .skill-tag {
            background: white;
            border: 2px solid var(--primary-color);
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 0.95rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            user-select: none;
        }

        .skill-tag:hover {
            background: #FFF5F2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 140, 117, 0.1);
            border-color: var(--primary-dark);
        }

        .skill-tag.selected {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(255, 140, 117, 0.15);
        }

        .skill-tag.selected:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            box-shadow: 0 6px 15px rgba(255, 140, 117, 0.2);
        }

        .skill-tag i {
            color: inherit;
            font-size: 0.9rem;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .skill-tag.selected i {
            opacity: 1;
        }

        .modal-footer {
            padding: 20px 30px;
            background: var(--bg-light);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-shrink: 0;
        }

        .modal-button {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .cancel-button {
            background: var(--border-color);
            color: var(--text-light);
        }

        .cancel-button:hover {
            background: #CBD5E1;
        }

        .save-button {
            background: var(--primary-color);
            color: white;
        }

        .save-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 97, 0.2);
        }

        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Стили для модального окна подтверждения */
        .confirm-message {
            text-align: center;
            padding: 20px;
        }

        .confirm-message i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .confirm-message p {
            font-size: 1.1rem;
            color: var(--text-color);
            margin: 0;
        }

        /* Стили для уведомлений */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            background: linear-gradient(135deg, var(--success-color), #38A169);
            color: white;
        }

        .notification.error {
            background: linear-gradient(135deg, var(--error-color), #E53E3E);
            color: white;
        }

        .notification i {
            font-size: 1.2rem;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .page-title {
                font-size: 24px;
            }

            .create-vacancy-btn {
                width: 100%;
                justify-content: center;
            }

            .vacancy-card {
                padding: 15px;
            }

            .vacancy-header {
                flex-direction: column;
                gap: 10px;
            }

            .vacancy-actions {
                flex-direction: column;
            }

            .action-button {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                width: 95%;
                max-height: 90vh;
            }

            .modal-header {
                padding: 20px;
            }

            .modal-title {
                font-size: 1.5rem;
            }

            .form-group {
                padding: 0 20px;
            }

            .modal-footer {
                padding: 15px 20px;
                flex-direction: column;
            }

            .modal-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1 class="page-title">Мои вакансии</h1>
        <a href="create_vacancy.php" class="create-vacancy-btn">
            <i class="fas fa-plus"></i>
            Создать вакансию
        </a>
    </div>

    <?php if (empty($jobs)): ?>
        <div class="no-vacancies">
            <p>У вас пока нет созданных вакансий</p>
            <a href="create_vacancy.php" class="btn-create">Создать вакансию</a>
        </div>
    <?php else: ?>
        <?php foreach ($jobs as $job): ?>
            <div class="vacancy-card" data-vacancy-id="<?= $job['id'] ?>">
                <div class="vacancy-header">
                    <h2 class="vacancy-title"><?= htmlspecialchars($job['title']) ?></h2>
                    <div class="applications-count">
                        <i class="fas fa-users"></i>
                        <?= $job['applications_count'] ?> откликов
                    </div>
                </div>

                <div class="vacancy-info">
                    <div class="info-item">
                        <i class="fas fa-briefcase"></i>
                        <span>Опыт: <?= htmlspecialchars($job['experience']) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Зарплата: <?= htmlspecialchars($job['salary_range']) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Создана: <?= date('d.m.Y', strtotime($job['created_at'])) ?></span>
                    </div>
                </div>

                <div class="vacancy-description">
                    <?= nl2br(htmlspecialchars($job['description'])) ?>
                </div>

                <div class="skills-container">
                    <?php
                    $skill_names = explode(',', $job['skills']);
                    foreach ($skill_names as $skill_name): 
                        if (!empty($skill_name)):
                    ?>
                        <div class="skill-tag">
                            <i class="fas fa-check"></i>
                            <?= htmlspecialchars($skill_name) ?>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>

                <div class="vacancy-actions">
                    <button class="action-button edit-button" onclick="openEditModal(<?= $job['id'] ?>)">
                        <i class="fas fa-edit"></i>
                        Редактировать
                    </button>
                    <?php if ($job['status'] === 'active'): ?>
                        <button class="action-button close-button" onclick="closeVacancy(<?= $job['id'] ?>)">
                            <i class="fas fa-times"></i>
                            Закрыть вакансию
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Редактирование вакансии</h3>
                <button class="close-modal" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="job_id" id="editVacancyId">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-heading"></i>
                            Название вакансии
                        </label>
                        <input type="text" class="form-input" name="title" id="editTitle" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-align-left"></i>
                            Описание
                        </label>
                        <textarea class="form-input" name="description" id="editDescription" rows="5" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-clock"></i>
                            Опыт работы
                        </label>
                        <select class="form-input" name="experience_id" id="editExperience" required>
                            <?php foreach ($experiences as $experience): ?>
                                <option value="<?= $experience['id'] ?>"><?= htmlspecialchars($experience['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave"></i>
                            Зарплата
                        </label>
                        <select class="form-input" name="salary_id" id="editSalary" required>
                            <?php foreach ($salaries as $salary): ?>
                                <option value="<?= $salary['id'] ?>"><?= htmlspecialchars($salary['salary_range']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tools"></i>
                            Навыки
                        </label>
                        <div class="skills-grid">
                            <?php foreach ($skills as $skill): ?>
                                <div class="skill-tag" 
                                     onclick="toggleSkill(this, <?= $skill['id'] ?>)"
                                     data-skill-id="<?= $skill['id'] ?>">
                                    <i class="fas fa-check"></i>
                                    <?= htmlspecialchars($skill['name']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="modal-button cancel-button" onclick="closeEditModal()">
                            <i class="fas fa-times"></i> Отмена
                        </button>
                        <button type="submit" name="edit_job" class="modal-button save-button">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения -->
    <div id="confirmModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Подтверждение</h3>
                <button class="close-modal" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="confirm-message">
                    <i class="fas fa-question-circle"></i>
                    <p>Вы уверены, что хотите сохранить изменения?</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-button cancel-button" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i> Отмена
                </button>
                <button type="button" class="modal-button save-button" id="confirmSaveButton">
                    <i class="fas fa-save"></i> Сохранить
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleSkill(element, skillId) {
            element.classList.toggle('selected');
            
            const hiddenInputsContainer = document.getElementById('skills-inputs-container') || createHiddenInputsContainer();
            const existingInput = hiddenInputsContainer.querySelector(`input[value="${skillId}"]`);

            if (element.classList.contains('selected')) {
                if (!existingInput) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'skills[]';
                    input.value = skillId;
                    hiddenInputsContainer.appendChild(input);
                }
            } else {
                if (existingInput) {
                    existingInput.remove();
                }
            }
        }

        function createHiddenInputsContainer() {
            const container = document.getElementById('skills-inputs-container') || document.createElement('div');
            container.id = 'skills-inputs-container';
            if (!container.parentElement) {
                document.querySelector('form').appendChild(container);
            }
            return container;
        }

        function showConfirmModal() {
            const modal = document.getElementById('confirmModal');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirmModal');
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
                if (type === 'success') {
                    window.location.reload();
                }
            }, 3000);
        }

        function openEditModal(vacancyId) {
            const modal = document.getElementById('editModal');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('show'), 10);
            
            // Получаем данные вакансии из карточки
            const vacancyCard = document.querySelector(`[data-vacancy-id="${vacancyId}"]`);
            const title = vacancyCard.querySelector('.vacancy-title').textContent;
            const description = vacancyCard.querySelector('.vacancy-description').textContent.trim();
            const experience = vacancyCard.querySelector('.info-item:nth-child(1) span').textContent.split(': ')[1];
            const salary = vacancyCard.querySelector('.info-item:nth-child(2) span').textContent.split(': ')[1];
            
            // Заполняем форму данными
            document.getElementById('editVacancyId').value = vacancyId;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = description.replace(/\n\s+/g, '\n');
            
            // Устанавливаем значения в селектах
            const experienceSelect = document.getElementById('editExperience');
            const salarySelect = document.getElementById('editSalary');
            
            Array.from(experienceSelect.options).forEach(option => {
                if (option.text === experience) {
                    experienceSelect.value = option.value;
                }
            });
            
            Array.from(salarySelect.options).forEach(option => {
                if (option.text === salary) {
                    salarySelect.value = option.value;
                }
            });

            // Очищаем предыдущие выбранные навыки
            document.querySelectorAll('.skill-tag').forEach(tag => {
                tag.classList.remove('selected');
            });
            const container = document.getElementById('skills-inputs-container');
            if (container) {
                container.innerHTML = '';
            }

            // Получаем и устанавливаем навыки
            const skillTags = vacancyCard.querySelectorAll('.skill-tag');
            const selectedSkills = Array.from(skillTags).map(tag => tag.textContent.trim());
            
            // Отмечаем выбранные навыки
            document.querySelectorAll('.skill-tag').forEach(tag => {
                const skillName = tag.textContent.trim();
                if (selectedSkills.includes(skillName)) {
                    tag.classList.add('selected');
                    const skillId = tag.getAttribute('data-skill-id');
                    const hiddenInputsContainer = createHiddenInputsContainer();
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'skills[]';
                    input.value = skillId;
                    hiddenInputsContainer.appendChild(input);
                }
            });
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            const confirmModal = document.getElementById('confirmModal');
            if (event.target === modal) {
                closeEditModal();
            }
            if (event.target === confirmModal) {
                closeConfirmModal();
            }
        }

        // Обработка отправки формы
        document.getElementById('editForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Проверяем, есть ли выбранные навыки
            const selectedSkills = document.querySelectorAll('.skill-tag.selected');
            if (selectedSkills.length === 0) {
                showNotification('Пожалуйста, выберите хотя бы один навык', 'error');
                return;
            }

            // Показываем модальное окно подтверждения
            showConfirmModal();
        });

        // Обработка подтверждения сохранения
        document.getElementById('confirmSaveButton').addEventListener('click', function() {
            const form = document.getElementById('editForm');
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.classList.add('loading');
            
            // Отправляем форму
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка сети');
                }
                return response.json();
            })
            .then(data => {
                closeConfirmModal();
                if (data.success) {
                    showNotification(data.message);
                } else {
                    throw new Error(data.message || 'Ошибка при сохранении');
                }
            })
            .catch(error => {
                showNotification(error.message, 'error');
                submitButton.classList.remove('loading');
            });
        });

        function closeVacancy(vacancyId) {
            if (confirm('Вы уверены, что хотите закрыть эту вакансию?')) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=close&job_id=${vacancyId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message);
                    } else {
                        throw new Error(data.message || 'Ошибка при закрытии вакансии');
                    }
                })
                .catch(error => {
                    showNotification(error.message, 'error');
                });
            }
        }
    </script>
</body>
</html>