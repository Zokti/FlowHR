<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/header.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    // Получение всех активных резюме
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.title,
            r.description,
            r.salary_id,
            r.created_at,
            r.experience_id,
            u.name,
            u.email,
            s.salary_range,
            e.name as experience_name,
            COUNT(DISTINCT a.id) as applications_count
        FROM resumes r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN salaries s ON r.salary_id = s.id
        LEFT JOIN experiences e ON r.experience_id = e.id
        LEFT JOIN applications a ON a.entity_id = r.id AND a.entity_type = 'resume'
        WHERE r.is_published = 1
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $resumes = $stmt->fetchAll();

    // Получение навыков для всех резюме
    $resumeIds = array_column($resumes, 'id');
    $skills = [];
    if (!empty($resumeIds)) {
        $placeholders = str_repeat('?,', count($resumeIds) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT rs.resume_id, s.name
            FROM resume_skills rs
            JOIN skills s ON rs.skill_id = s.id
            WHERE rs.resume_id IN ($placeholders)
        ");
        $stmt->execute($resumeIds);
        while ($row = $stmt->fetch()) {
            $skills[$row['resume_id']][] = $row['name'];
        }
    }

    // Получение вакансий HR
    $stmt = $pdo->prepare("
        SELECT j.*, s.salary_range, e.name as experience_name
        FROM jobs j
        LEFT JOIN salaries s ON j.salary_id = s.id
        LEFT JOIN experiences e ON j.experience_id = e.id
        WHERE j.hr_id = ? AND j.status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $jobs = $stmt->fetchAll();

} catch(Exception $e) {
    error_log($e->getMessage());
    die("Ошибка: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр резюме - FlowHR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        }

        body {
            margin-left: 250px;
            font-family: 'Inter', 'Arial', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .resume-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-title {
            color: var(--text-color);
            font-size: 36px;
            margin-bottom: 30px;
            text-align: center;
            padding: 20px 0;
            font-weight: 700;
            position: relative;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border-radius: 2px;
        }

        .resumes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-top: 2rem;
        }

        .resume-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .resume-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .resume-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .resume-card:hover::before {
            opacity: 1;
        }

        .resume-header {
            padding: 1.8rem;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            border-radius: 16px 16px 0 0;
            position: relative;
        }

        .resume-header:hover .expand-title-btn {
            opacity: 1;
            visibility: visible;
            bottom: -35px;
        }

        .resume-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin: 0;
            font-weight: 600;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
            position: relative;
            transition: all 0.3s ease;
        }

        .resume-title.expanded {
            -webkit-line-clamp: unset;
        }

        .expand-title-btn {
            opacity: 0;
            visibility: hidden;
            color: var(--primary-color);
            background: none;
            border: none;
            padding: 0.5rem 0;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-align: left;
            margin-top: 0.5rem;
            position: absolute;
            left: 0;
            bottom: -30px;
            background: var(--card-bg);
            padding: 0.5rem 1rem;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .expand-title-btn:hover {
            color: var(--primary-dark);
            background: var(--card-bg);
        }

        .expand-title-btn i {
            margin-left: 0.5rem;
            transition: transform 0.3s ease;
        }

        .resume-title.expanded + .expand-title-btn i {
            transform: rotate(180deg);
        }

        .resume-body {
            padding: 1.8rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            background: #FFFFFF;
            padding: 1rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.95rem;
            color: var(--text-color);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .meta-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .meta-item i {
            color: var(--primary-color);
            font-size: 1.2rem;
            width: 30px;
        }

        .resume-description {
            background: var(--card-bg);
            padding: 1.2rem;
            border-radius: 12px;
            line-height: 1.6;
            color: var(--text-color);
            margin: 0.5rem 0;
            border-left: 4px solid var(--primary-color);
            font-size: 0.95rem;
            max-height: 120px;
            overflow-y: auto;
            position: relative;
            transition: max-height 0.3s ease;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .resume-description.expanded {
            max-height: none;
        }

        .expand-description {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px;
            background: linear-gradient(to top, var(--card-bg), transparent);
            text-align: center;
            cursor: pointer;
            color: var(--primary-color);
            font-weight: 500;
            transition: all 0.3s ease;
            display: none;
        }

        .resume-description:not(.expanded) .expand-description {
            display: block;
        }

        .resume-description:hover {
            color: var(--primary-dark);
        }

        .skills-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: auto;
        }

        .skill-pill {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(255, 111, 97, 0.2);
        }

        .skill-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 111, 97, 0.3);
        }

        .actions-toolbar {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .btn {
            width: 100%;
            justify-content: center;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .search-bar {
            max-width: 600px;
            margin: 2rem auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1.2rem 1.8rem;
            padding-left: 3.5rem;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--card-bg);
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .no-resumes {
            text-align: center;
            padding: 3rem;
            background: #FFF8E1;
            border-radius: 15px;
            margin-top: 2rem;
        }

        .no-resumes i {
            font-size: 3rem;
            color: #FF8C42;
            margin-bottom: 1rem;
        }

        .no-resumes p {
            font-size: 1.2rem;
            color: #2C3E50;
            margin-bottom: 1rem;
        }

        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-dialog {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 1.75rem auto;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 30px;
            padding: 1.5rem;
            width: 100%;
            max-width: 750px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalOpen 0.3s ease-out;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            padding: 1.5rem;
            border-radius: 30px 30px 0 0;
            background: linear-gradient(to right, #f8f9fa, #ffffff);
        }

        .modal-title {
            font-size: 1.8rem;
            color: #2C3E50;
            font-weight: 600;
            margin: 0;
        }

        .btn-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 40px;
            height: 40px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #666;
            transition: all 0.3s ease;
            border-radius: 50%;
            z-index: 1;
        }

        .btn-close:hover {
            background: rgba(255, 111, 97, 0.1);
            color: var(--primary-color);
            transform: rotate(90deg);
        }

        .btn-close::before {
            content: '×';
            font-size: 2rem;
            line-height: 1;
            font-weight: 300;
        }

        .btn-close:hover::before {
            transform: scale(1.1);
        }

        .job-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin: 1rem 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .job-item {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .job-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .job-item.selected {
            border-color: var(--primary-color);
            background: rgba(255, 111, 97, 0.05);
        }

        .job-title {
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .job-salary {
            color: #FF8C42;
            font-weight: 500;
            font-size: 1rem;
        }

        .job-status {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.8rem;
        }

        .job-status i {
            color: #666;
        }

        .job-item.disabled {
            position: relative;
            filter: blur(2px);
            pointer-events: none;
        }

        .job-item.disabled::after {
            content: 'Вакансия уже предложена';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            z-index: 1;
        }

        .job-item.disabled .job-status {
            display: none;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #2C3E50;
            padding: 0.8rem 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-cancel:hover {
            background: #e9ecef;
            border-color: #dee2e6;
            transform: translateY(-2px);
        }

        .btn-submit {
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 140, 66, 0.3);
        }

        .form-group {
            margin: 1.2rem 0;
        }

        .form-label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            line-height: 1.4;
            transition: all 0.3s ease;
            background: var(--card-bg);
            resize: none;
            height: 120px;
            box-sizing: border-box;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 111, 97, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 20px;
            margin-bottom: 1rem;
            background: #FFF8E1;
            border-left: 4px solid #FF8C42;
        }

        .alert-danger {
            background: #fff5f5;
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary-color);
        }

        /* Стили для спиннера загрузки */
        .text-center {
            padding: 2rem;
            background: #ffffff;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .text-center p {
            margin-top: 1rem;
            color: #666;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 10px;
            }

            .modal {
                left: 0;
            }

            .modal-content {
                width: 95%;
                padding: 1rem;
            }
        }

        .btn-offer {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            width: auto;
            min-width: 180px;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(255, 111, 97, 0.2);
        }

        .btn-offer i {
            font-size: 1.1rem;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-offer:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 111, 97, 0.3);
        }

        .btn-offer:hover i {
            transform: scale(1.1);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .job-item {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .job-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .job-item.selected {
            border-color: var(--primary-color);
            background: rgba(255, 111, 97, 0.05);
        }

        .modal-actions {
            display: flex;
            gap: 0.8rem;
            margin-top: 1.5rem;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #2C3E50;
            padding: 8px 16px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-submit {
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
            box-shadow: 0 2px 8px rgba(255, 140, 66, 0.2);
        }

        .job-item.disabled {
            background: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.7;
            position: relative;
            filter: grayscale(0.5);
        }

        .job-item.disabled:hover {
            transform: none;
            box-shadow: none;
            border-color: transparent;
        }

        .job-item.disabled .job-status {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.8rem;
        }

        .job-item.disabled .job-status i {
            color: #666;
        }

        .job-item.disabled .job-title,
        .job-item.disabled .job-salary {
            color: #666;
        }

        .job-item.disabled::after {
            display: none;
        }

        /* Стили для модального окна подтверждения */
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1100;
            justify-content: center;
            align-items: center;
        }

        .confirm-modal.show {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        .confirm-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            position: relative;
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.3s ease-out forwards;
        }

        .confirm-icon {
            font-size: 3rem;
            color: #FF8C42;
            margin-bottom: 1rem;
        }

        .confirm-title {
            font-size: 1.5rem;
            color: #2C3E50;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .confirm-message {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .confirm-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .confirm-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            min-width: 120px;
        }

        .confirm-btn.cancel {
            background: #f8f9fa;
            color: #2C3E50;
            border: 1px solid #e9ecef;
        }

        .confirm-btn.cancel:hover {
            background: #e9ecef;
        }

        .confirm-btn.confirm {
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.2);
        }

        .confirm-btn.confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 140, 66, 0.3);
        }

        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        /* Добавляем стили для анализа */
        .analysis-section {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: #ffffff;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .analysis-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .analysis-section h6 {
            color: #2C3E50;
            font-weight: 600;
            margin-bottom: 1.2rem;
            font-size: 1.1rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .analysis-section h6::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            border-radius: 3px;
        }

        .score-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            margin: 0 auto;
            box-shadow: 0 8px 25px rgba(255, 111, 97, 0.3);
            transition: all 0.3s ease;
        }

        .score-circle:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(255, 111, 97, 0.4);
        }

        .score-value {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .score-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .analysis-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .analysis-item {
            margin-bottom: 1rem;
            padding: 1.2rem;
            background: #f8f9fa;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .analysis-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .analysis-item:last-child {
            margin-bottom: 0;
        }

        .analysis-criterion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
        }

        .criterion-name {
            font-weight: 600;
            color: #2C3E50;
            font-size: 1.05rem;
        }

        .criterion-score {
            font-weight: 600;
            color: var(--primary-color);
            background: rgba(255, 111, 97, 0.1);
            padding: 0.4rem 1rem;
            border-radius: 25px;
            font-size: 0.95rem;
        }

        .criterion-comment {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .resume-info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .resume-info-list li {
            margin-bottom: 0.8rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .resume-info-list li:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .resume-info-list li:last-child {
            margin-bottom: 0;
        }

        .resume-info-list strong {
            color: #2C3E50;
            margin-right: 0.5rem;
            font-weight: 600;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #2C3E50;
            padding: 0.8rem 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-cancel:hover {
            background: #e9ecef;
            border-color: #dee2e6;
            transform: translateY(-2px);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .alert {
            border-radius: 20px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .alert-danger {
            background: #fff5f5;
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary-color);
        }

        /* Стили для кастомизированного скролла */
        .modal-content {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background-color: rgba(255, 111, 97, 0.3);
            border-radius: 20px;
            border: 2px solid transparent;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 111, 97, 0.5);
        }

        /* Стили для скролла в описании резюме */
        .resume-description {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .resume-description::-webkit-scrollbar {
            width: 6px;
        }

        .resume-description::-webkit-scrollbar-track {
            background: transparent;
        }

        .resume-description::-webkit-scrollbar-thumb {
            background-color: rgba(255, 111, 97, 0.3);
            border-radius: 20px;
            border: 2px solid transparent;
        }

        .resume-description::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 111, 97, 0.5);
        }

        /* Стили для скролла в списке вакансий */
        .job-list {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .job-list::-webkit-scrollbar {
            width: 6px;
        }

        .job-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .job-list::-webkit-scrollbar-thumb {
            background-color: rgba(255, 111, 97, 0.3);
            border-radius: 20px;
            border: 2px solid transparent;
        }

        .job-list::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 111, 97, 0.5);
        }

        /* Стили для скролла в текстовом поле сообщения */
        .form-input {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 111, 97, 0.3) transparent;
        }

        .form-input::-webkit-scrollbar {
            width: 6px;
        }

        .form-input::-webkit-scrollbar-track {
            background: transparent;
        }

        .form-input::-webkit-scrollbar-thumb {
            background-color: rgba(255, 111, 97, 0.3);
            border-radius: 20px;
            border: 2px solid transparent;
        }

        .form-input::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 111, 97, 0.5);
        }

        /* Стили для анализа соответствия */
        .job-match {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .match-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .match-details {
            margin-top: 0.5rem;
        }

        .match-percentage {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .match-type {
            margin: 0.5rem 0;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            display: inline-block;
            font-size: 0.9rem;
        }

        .match-type.full {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .match-type.partial {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .match-skills {
            font-size: 0.9rem;
            color: #666;
        }

        .job-item.selected {
            border-color: var(--primary-color);
            background: rgba(255, 111, 97, 0.05);
        }
    </style>
</head>
<body>
    <div class="resume-container">
        <h1 class="page-title">Просмотр резюме</h1>

        <div class="search-bar">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Поиск по резюме...">
        </div>

        <?php if (!empty($resumes)): ?>
            <div class="resumes-grid">
                <?php foreach ($resumes as $resume): ?>
                    <div class="resume-card">
                        <div class="resume-header">
                            <h2 class="resume-title"><?= htmlspecialchars($resume['title']) ?></h2>
                        </div>

                        <div class="resume-body">
                            <div class="meta-grid">
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <span><?= htmlspecialchars($resume['name']) ?></span>
                                </div>
                                <?php if ($resume['salary_range']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span><?= htmlspecialchars($resume['salary_range']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($resume['experience_name']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?= htmlspecialchars($resume['experience_name']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($resume['description'])): ?>
                                <div class="resume-description">
                                    <?= nl2br(htmlspecialchars($resume['description'])) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($skills[$resume['id']])): ?>
                                <div class="skills-cloud">
                                    <?php foreach ($skills[$resume['id']] as $skill): ?>
                                        <span class="skill-pill"><?= htmlspecialchars($skill) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="actions-toolbar">
                                <button type="button" 
                                        class="btn btn-offer" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#analysisModal"
                                        data-resume-id="<?= $resume['id'] ?>"
                                        data-resume-title="<?= htmlspecialchars($resume['title']) ?>"
                                        data-resume-description="<?= htmlspecialchars($resume['description']) ?>">
                                    <i class="fas fa-chart-line"></i> 
                                    Анализ и предложение
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-resumes">
                <i class="fas fa-file-alt"></i>
                <p>Нет доступных резюме</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Модальное окно анализа резюме -->
    <div class="modal fade" id="analysisModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Анализ резюме</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="analysisContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                            <p class="mt-2">Анализируем резюме...</p>
                        </div>
                    </div>
                    <div class="modal-actions mt-4">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            Закрыть
                        </button>
                        <button type="button" class="btn btn-submit" id="showOfferForm" style="display: none;">
                            Предложить вакансию
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно предложения вакансии -->
    <div class="modal fade" id="offerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Предложить вакансию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="offerForm">
                        <input type="hidden" name="resume_id" id="resumeId">
                        
                        <div class="job-list">
                            <?php foreach ($jobs as $job): ?>
                                <div class="job-item" 
                                     data-job-id="<?= $job['id'] ?>"
                                     data-experience="<?= htmlspecialchars($job['experience_name'] ?? '') ?>">
                                    <div class="job-header">
                                    <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                                    <div class="job-salary"><?= htmlspecialchars($job['salary_range']) ?></div>
                                    </div>
                                    <div class="job-match">
                                        <div class="match-details">
                                            <div class="match-percentage"></div>
                                            <div class="match-type"></div>
                                            <div class="match-skills"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Сообщение кандидату</label>
                            <textarea 
                                class="form-input"
                                name="message" 
                                rows="4"
                                placeholder="Напишите, почему вы хотите предложить эту вакансию"
                                required
                            ></textarea>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-cancel" id="backToAnalysis">
                                <i class="fas fa-arrow-left"></i> Вернуться к анализу
                            </button>
                            <button type="submit" class="btn btn-submit">
                                Отправить предложение
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения -->
    <div class="confirm-modal" id="confirmModal">
        <div class="confirm-content">
            <button type="button" class="btn-close" id="closeConfirmModal"></button>
            <div class="confirm-icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <h3 class="confirm-title">Подтверждение действия</h3>
            <p class="confirm-message">Вы уверены, что хотите предложить эту вакансию кандидату?</p>
            <div class="confirm-buttons">
                <button class="confirm-btn cancel" id="cancelConfirm">Отмена</button>
                <button class="confirm-btn confirm" id="confirmAction">Подтвердить</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Добавляем полный текст заголовка как data-атрибут
        document.querySelectorAll('.resume-title').forEach(title => {
            title.setAttribute('data-full-title', title.textContent);
        });

        // Поиск по резюме
        const searchInput = document.querySelector('.search-input');
        const resumeCards = document.querySelectorAll('.resume-card');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            resumeCards.forEach(card => {
                const title = card.querySelector('.resume-title').textContent.toLowerCase();
                const description = card.querySelector('.resume-description')?.textContent.toLowerCase() || '';
                const skills = Array.from(card.querySelectorAll('.skill-pill'))
                    .map(skill => skill.textContent.toLowerCase());
                
                const matches = title.includes(searchTerm) || 
                              description.includes(searchTerm) ||
                              skills.some(skill => skill.includes(searchTerm));
                
                card.style.display = matches ? 'flex' : 'none';
            });
        });

        // Обработка выбора вакансии
        const jobItems = document.querySelectorAll('.job-item');
        let selectedJobId = null;

        jobItems.forEach(item => {
            item.addEventListener('click', function() {
                if (this.classList.contains('disabled')) {
                    return;
                }
                jobItems.forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                selectedJobId = this.dataset.jobId;
            });
        });

        // Обработка открытия модального окна анализа
        const analysisModal = document.getElementById('analysisModal');
        let currentResumeId = null;

        analysisModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            currentResumeId = button.getAttribute('data-resume-id');
            
            // Скрываем кнопку предложения вакансии
            document.getElementById('showOfferForm').style.display = 'none';
            
            // Показываем спиннер
            document.getElementById('analysisContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Анализируем резюме...</p>
                </div>
            `;

            // Получаем данные резюме
            const resumeCard = document.querySelector(`.resume-card [data-resume-id="${currentResumeId}"]`).closest('.resume-card');
            const title = resumeCard.querySelector('.resume-title').textContent;
            const description = resumeCard.querySelector('.resume-description')?.textContent || '';
            const experience = resumeCard.querySelector('.meta-item:nth-child(3) span')?.textContent || '';
            const salary = resumeCard.querySelector('.meta-item:nth-child(2) span')?.textContent || '';
            const skills = Array.from(resumeCard.querySelectorAll('.skill-pill')).map(skill => skill.textContent.trim());

            // Анализируем резюме
            const analysis = {
                score: 0,
                details: [],
                resume_info: {
                    title: title,
                    experience: experience,
                    salary: salary,
                    created_at: new Date().toLocaleDateString('ru-RU'),
                    skills_count: skills.length
                }
            };

            // Анализ описания
            const descriptionLength = description.length;
            if (descriptionLength > 500) {
                analysis.score += 40;
                analysis.details.push({
                    criterion: 'Описание резюме',
                    score: 40,
                    comment: 'Отличное описание с подробной информацией'
                });
            } else if (descriptionLength > 200) {
                analysis.score += 30;
                analysis.details.push({
                    criterion: 'Описание резюме',
                    score: 30,
                    comment: 'Хорошее описание, но можно добавить больше деталей'
                });
            } else {
                analysis.score += 20;
                analysis.details.push({
                    criterion: 'Описание резюме',
                    score: 20,
                    comment: 'Краткое описание, рекомендуется расширить'
                });
            }

            // Анализ навыков
            if (skills.length > 5) {
                analysis.score += 40;
                analysis.details.push({
                    criterion: 'Навыки',
                    score: 40,
                    comment: 'Отличный набор навыков'
                });
            } else if (skills.length > 2) {
                analysis.score += 30;
                analysis.details.push({
                    criterion: 'Навыки',
                    score: 30,
                    comment: 'Хороший набор навыков'
                });
            } else {
                analysis.score += 20;
                analysis.details.push({
                    criterion: 'Навыки',
                    score: 20,
                    comment: 'Рекомендуется добавить больше навыков'
                });
            }

            // Анализ опыта
            if (experience) {
                analysis.score += 20;
                analysis.details.push({
                    criterion: 'Опыт работы',
                    score: 20,
                    comment: `Опыт работы: ${experience}`
                });
            } else {
                analysis.details.push({
                    criterion: 'Опыт работы',
                    score: 0,
                    comment: 'Опыт работы не указан'
                });
            }

            // Отображаем результаты анализа
            document.getElementById('analysisContent').innerHTML = `
                <div class="analysis-section">
                    <h6 class="mb-3">Общая оценка</h6>
                    <div class="score-circle mb-3">
                        <span class="score-value">${analysis.score}</span>
                        <span class="score-label">из 100</span>
                        </div>
                </div>
                <div class="analysis-section">
                    <h6 class="mb-3">Детальный анализ</h6>
                    <ul class="analysis-list">
                        ${analysis.details.map(detail => `
                            <li class="analysis-item">
                                <div class="analysis-criterion">
                                    <span class="criterion-name">${detail.criterion}</span>
                                    <span class="criterion-score">${detail.score}%</span>
                                </div>
                                <p class="criterion-comment">${detail.comment}</p>
                            </li>
                        `).join('')}
                    </ul>
                </div>
                <div class="analysis-section">
                    <h6 class="mb-3">Информация о резюме</h6>
                    <ul class="resume-info-list">
                        <li><strong>Название:</strong> ${analysis.resume_info.title}</li>
                        <li><strong>Опыт:</strong> ${analysis.resume_info.experience || 'Не указан'}</li>
                        <li><strong>Зарплата:</strong> ${analysis.resume_info.salary || 'Не указана'}</li>
                        <li><strong>Дата создания:</strong> ${analysis.resume_info.created_at}</li>
                        <li><strong>Количество навыков:</strong> ${analysis.resume_info.skills_count}</li>
                    </ul>
                        </div>
                    `;

            // Показываем кнопку предложения вакансии
            document.getElementById('showOfferForm').style.display = 'block';
        });

        // Обработчик кнопки "Предложить вакансию"
        document.getElementById('showOfferForm').addEventListener('click', function() {
            // Закрываем модальное окно анализа
            const modal = bootstrap.Modal.getInstance(analysisModal);
                modal.hide();
                
            // Открываем модальное окно предложения вакансии
            const offerModal = new bootstrap.Modal(document.getElementById('offerModal'));
            offerModal.show();
        });

        // Обработчик открытия модального окна предложения вакансии
        document.getElementById('offerModal').addEventListener('show.bs.modal', function(event) {
            const resumeId = currentResumeId; // Используем сохраненный ID резюме
            document.getElementById('resumeId').value = resumeId;
            
            const resumeCard = document.querySelector(`.resume-card [data-resume-id="${resumeId}"]`).closest('.resume-card');
            const resumeExperience = resumeCard.querySelector('.meta-item:nth-child(3) span')?.textContent.trim() || '';
            const resumeSkills = Array.from(resumeCard.querySelectorAll('.skill-pill'))
                .map(skill => skill.textContent.trim());

            // Показываем результаты анализа для каждой вакансии
            document.querySelectorAll('.job-item').forEach(jobItem => {
                const jobExperience = jobItem.dataset.experience || '';
                const experienceMatch = resumeExperience === jobExperience;
                
                const matchDetails = jobItem.querySelector('.match-details');
                
                const matchType = matchDetails.querySelector('.match-type');
                matchType.textContent = experienceMatch ? 'Полное соответствие' : 'Частичное соответствие';
                matchType.className = `match-type ${experienceMatch ? 'full' : 'partial'}`;

                matchDetails.querySelector('.match-percentage').textContent = `${experienceMatch ? '100' : '50'}%`;

                const matchSkills = matchDetails.querySelector('.match-skills');
                matchSkills.innerHTML = resumeSkills.length > 0 ? 
                    `Навыки кандидата: <strong>${resumeSkills.join(', ')}</strong>` :
                    'Нет указанных навыков';
            });
        });

        // Обработчик выбора вакансии
        document.querySelectorAll('.job-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.job-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        // Обработчик отправки формы
        document.getElementById('offerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const selectedJob = document.querySelector('.job-item.selected');
            if (!selectedJob) {
                alert('Пожалуйста, выберите вакансию');
                return;
            }

            const formData = new FormData(this);
            formData.append('job_id', selectedJob.dataset.jobId);

            fetch('send_job_offer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Предложение успешно отправлено!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(() => {
                alert('Произошла ошибка при отправке предложения.');
            });
        });

        // Обработчик закрытия модального окна подтверждения
        document.getElementById('closeConfirmModal').addEventListener('click', function() {
            document.getElementById('confirmModal').classList.remove('show');
        });

        // Обработчик кнопки "Вернуться к анализу"
        document.getElementById('backToAnalysis').addEventListener('click', function() {
            // Закрываем модальное окно предложения вакансии
            const offerModal = bootstrap.Modal.getInstance(document.getElementById('offerModal'));
            offerModal.hide();
            
            // Открываем модальное окно анализа
            const analysisModal = new bootstrap.Modal(document.getElementById('analysisModal'));
            analysisModal.show();
        });
    });
    </script>
</body>
</html> 