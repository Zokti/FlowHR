<?php
require __DIR__ . '/../includes/config.php';

// Инициализация переменных
$resumes = [];
$skills = [];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Требуется авторизация");
    }
    $currentUserId = $_SESSION['user_id'];

    // Основной запрос для получения резюме
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.title,
            r.description,
            r.visibility_duration,
            r.created_at,
            r.is_published,
            COUNT(rr.id) AS responses_count,
            DATEDIFF(
                DATE_ADD(r.created_at, INTERVAL r.visibility_duration DAY), 
                NOW()
            ) AS days_left
        FROM resumes r
        LEFT JOIN resume_responses rr ON r.id = rr.resume_id
        WHERE r.user_id = ? AND r.is_published = 1
        GROUP BY r.id
    ");
    
    $stmt->execute([$currentUserId]);
    $resumes = $stmt->fetchAll();

    // Получение навыков
    if (!empty($resumes)) {
        $resumeIds = array_column($resumes, 'id');
        $placeholders = str_repeat('?,', count($resumeIds) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT rs.resume_id, s.name AS skill 
            FROM resume_skills rs
            LEFT JOIN skills s ON rs.skill_id = s.id
            WHERE rs.resume_id IN ($placeholders)
        ");
        $stmt->execute($resumeIds);
        
        while ($row = $stmt->fetch()) {
            $skills[$row['resume_id']][] = $row['skill'];
        }
    }

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Ошибка базы данных: " . $e->getMessage());
} catch(Exception $e) {
    error_log("General error: " . $e->getMessage());
    die($e->getMessage());
}

require __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои резюме - FlowHR</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #FF6F61;
            --primary-dark: #FF8C42;
            --text-color: #2C3E50;
            --border-color: #E9ECEF;
            --bg-color: #F8F9FA;
            --card-bg: #FFFFFF;
            --success-color: #28a745;
            --warning-color: #FFC107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            margin-left: 250px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .resumes-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            color: var(--text-color);
            font-size: 32px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-title i {
            color: var(--primary-color);
            font-size: 32px;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
        }

        .page-title:hover i {
            transform: scale(1.2) rotate(15deg);
            animation: none;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .page-title:after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 0;
            border-radius: 2px;
        }

        .resume-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
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
            padding: 1.5rem;
            background: #FFFFFF;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .resume-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin: 0;
            font-weight: 600;
        }

        .status-indicator {
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 6px rgba(255, 111, 97, 0.2);
        }

        .resume-body {
            padding: 2rem;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            background: #FFFFFF;
            padding: 1rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
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
            background: #FFFFFF;
            padding: 1.5rem;
            border-radius: 10px;
            line-height: 1.7;
            color: var(--text-color);
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .resume-description::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30px;
            background: linear-gradient(transparent, #FFFFFF);
            pointer-events: none;
        }

        .skills-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            padding: 1rem 0;
        }

        .skill-pill {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .skill-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .actions-toolbar {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-confirm {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 6px rgba(255, 111, 97, 0.2);
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 111, 97, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color), #c82333);
            color: white;
            box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2);
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(220, 53, 69, 0.3);
        }

        .no-resumes {
            text-align: center;
            padding: 3rem;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-top: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        .no-resumes i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .no-resumes p {
            font-size: 1.2rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
        }

        /* Стили для модальных окон */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-dialog {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 1.75rem auto;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: var(--card-bg);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            position: relative;
        }

        .modal-header h5 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-body {
            padding: 25px;
            background: var(--card-bg);
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            background: var(--card-bg);
            border-radius: 0 0 15px 15px;
        }

        .btn-close {
            color: white;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .btn-cancel {
            background: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: var(--border-color);
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .resume-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .meta-grid {
                grid-template-columns: 1fr;
            }

            .actions-toolbar {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .modal {
                left: 0;
            }
        }

        .resume-card:nth-child(1) { animation-delay: 0.1s; }
        .resume-card:nth-child(2) { animation-delay: 0.2s; }
        .resume-card:nth-child(3) { animation-delay: 0.3s; }
        .resume-card:nth-child(4) { animation-delay: 0.4s; }
        .resume-card:nth-child(5) { animation-delay: 0.5s; }

        .filter-section {
            background: #FFFFFF;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border-radius: 2px;
        }

        .search-section {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: #FFFFFF;
            color: var(--text-color);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.1);
            outline: none;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .filter-item {
            position: relative;
        }

        .filter-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 14px;
        }

        .filter-label i {
            color: var(--primary-color);
            margin-right: 8px;
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: #FFFFFF;
            color: var(--text-color);
            font-size: 14px;
            transition: all 0.3s ease;
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23FF6F61' d='M6 8.825L1.175 4 2.05 3.125 6 7.075 9.95 3.125 10.825 4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .filter-select:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.1);
            outline: none;
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        .btn-toggle-description {
            display: none; /* По умолчанию скрыта */
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            padding: 5px 10px;
            margin-top: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-toggle-description:hover {
            color: var(--primary-dark);
        }

        .btn-toggle-description i {
            transition: transform 0.3s ease;
        }

        .btn-toggle-description.expanded i {
            transform: rotate(180deg);
        }

        .description-content {
            max-height: 100px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .description-content.expanded {
            max-height: none !important;
        }
    </style>
</head>
<body>
    <div class="resumes-container">
        <h1 class="page-title">
            <i class="fas fa-file-alt"></i>
            Мои резюме
        </h1>

        <!-- Фильтры -->
        <div class="filter-section">
            <div class="search-section">
                <input type="text" 
                       class="search-input" 
                       placeholder="Поиск по названию резюме..."
                       id="searchInput">
            </div>
            <div class="filter-grid">
                <div class="filter-item">
                    <label class="filter-label">
                        <i class="fas fa-briefcase"></i>
                        Опыт работы
                    </label>
                    <select class="filter-select" id="experienceFilter">
                        <option value="">Все</option>
                        <?php foreach ($experiences as $experience): ?>
                            <option value="<?= $experience['id'] ?>">
                                <?= htmlspecialchars($experience['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label class="filter-label">
                        <i class="fas fa-money-bill-wave"></i>
                        Зарплата
                    </label>
                    <select class="filter-select" id="salaryFilter">
                        <option value="">Все</option>
                        <?php foreach ($salaries as $salary): ?>
                            <option value="<?= $salary['id'] ?>">
                                <?= htmlspecialchars($salary['salary_range']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Модальное окно редактирования -->
        <div class="modal fade" id="editResumeModal" tabindex="-1" aria-labelledby="editResumeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editResumeModalLabel">
                            <i class="fas fa-edit"></i>
                            Редактирование резюме
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editResumeForm">
                            <input type="hidden" name="resume_id" id="editResumeId">
                            <div class="mb-3">
                                <label for="editTitle" class="form-label">Название</label>
                                <input type="text" class="form-control" id="editTitle" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="editDescription" class="form-label">Описание</label>
                                <textarea class="form-control" id="editDescription" name="description" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editSkills" class="form-label">Навыки</label>
                                <select class="form-control" id="editSkills" name="skills[]" multiple required>
                                    <?php foreach ($skills as $skill): ?>
                                        <option value="<?= $skill['id'] ?>"><?= htmlspecialchars($skill['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" form="editResumeForm" class="btn-submit">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно удаления -->
        <div class="modal fade" id="deleteResumeModal" tabindex="-1" aria-labelledby="deleteResumeModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteResumeModalLabel">
                            <i class="fas fa-trash-alt"></i>
                            Удаление резюме
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Вы уверены, что хотите удалить это резюме? Это действие нельзя будет отменить.</p>
                        <form id="deleteResumeForm">
                            <input type="hidden" name="resume_id" id="deleteResumeId">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" form="deleteResumeForm" class="btn-submit">Удалить</button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($resumes)): ?>
            <?php foreach ($resumes as $resume): ?>
                <div class="resume-card" 
                     data-resume-id="<?= $resume['id'] ?>"
                     data-experience="<?= $resume['experience_id'] ?>"
                     data-salary="<?= $resume['salary_id'] ?>">
                    <div class="resume-header">
                        <h2 class="resume-title"><?= htmlspecialchars($resume['title']) ?></h2>
                        <div class="status-indicator">
                            <i class="fas fa-circle"></i>
                            <?= $resume['days_left'] > 0 ? "Активно {$resume['days_left']} дней" : "В архиве" ?>
                        </div>
                    </div>

                    <div class="resume-body">
                        <div class="meta-grid">
                            <div class="meta-item">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Создано: <?= date('d.m.Y', strtotime($resume['created_at'])) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-eye"></i>
                                <span>Видимость: <?= $resume['visibility_duration'] ?> дней</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-briefcase"></i>
                                <span>Откликов: <?= $resume['responses_count'] ?></span>
                            </div>
                        </div>

                        <?php if (!empty($resume['description'])): ?>
                            <div class="resume-description">
                                <div class="description-content" style="max-height: 100px; overflow: hidden;">
                                    <?= nl2br(htmlspecialchars($resume['description'])) ?>
                                </div>
                                <?php if (strlen($resume['description']) > 200): ?>
                                    <button class="btn-toggle-description" onclick="toggleDescription(this)">
                                        <i class="fas fa-chevron-down"></i> Развернуть
                                    </button>
                                <?php endif; ?>
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
                            <button type="button" class="btn btn-confirm edit-resume-btn" data-bs-toggle="modal" data-bs-target="#editResumeModal" data-resume-id="<?= $resume['id'] ?>" data-resume-title="<?= htmlspecialchars($resume['title']) ?>" data-resume-description="<?= htmlspecialchars($resume['description']) ?>">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                            <button type="button" class="btn btn-delete delete-resume-btn" data-bs-toggle="modal" data-bs-target="#deleteResumeModal" data-resume-id="<?= $resume['id'] ?>">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-resumes">
                <p>У вас пока нет активных резюме</p>
                <a href="create_resume.php" class="btn btn-confirm">
                    <i class="fas fa-plus"></i> Создать новое резюме
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- jQuery (необходим для некоторых функций Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация всех модальных окон
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                new bootstrap.Modal(modal);
            });

            // Обработчик открытия модального окна редактирования
            document.querySelectorAll('.edit-resume-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    var resumeId = this.getAttribute('data-resume-id');
                    var title = this.closest('.resume-card').querySelector('.resume-title').textContent;
                    var description = this.closest('.resume-card').querySelector('.resume-description').textContent;
                    var skills = Array.from(this.closest('.resume-card').querySelectorAll('.skill-pill'))
                        .map(skill => skill.getAttribute('data-skill-id'));

                    document.getElementById('editResumeId').value = resumeId;
                    document.getElementById('editTitle').value = title;
                    document.getElementById('editDescription').value = description;
                    
                    var skillsSelect = document.getElementById('editSkills');
                    Array.from(skillsSelect.options).forEach(function(option) {
                        option.selected = skills.includes(option.value);
                    });

                    var editModal = new bootstrap.Modal(document.getElementById('editResumeModal'));
                    editModal.show();
                });
            });

            // Обработчик открытия модального окна удаления
            document.querySelectorAll('.delete-resume-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    var resumeId = this.getAttribute('data-resume-id');
                    document.getElementById('deleteResumeId').value = resumeId;
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteResumeModal'));
                    deleteModal.show();
                });
            });

            // Обработчик отправки формы редактирования
            document.getElementById('editResumeForm').addEventListener('submit', function(e) {
                e.preventDefault();
                // Здесь будет код отправки формы
                console.log('Отправка формы редактирования');
            });

            // Обработчик отправки формы удаления
            document.getElementById('deleteResumeForm').addEventListener('submit', function(e) {
                e.preventDefault();
                // Здесь будет код отправки формы
                console.log('Отправка формы удаления');
            });

            // Функционал фильтрации
            const searchInput = document.getElementById('searchInput');
            const experienceFilter = document.getElementById('experienceFilter');
            const salaryFilter = document.getElementById('salaryFilter');
            const resumeCards = document.querySelectorAll('.resume-card');

            function filterResumes() {
                const searchTerm = searchInput.value.toLowerCase();
                const experienceValue = experienceFilter.value;
                const salaryValue = salaryFilter.value;

                resumeCards.forEach(card => {
                    const title = card.querySelector('.resume-title').textContent.toLowerCase();
                    const experience = card.dataset.experience;
                    const salary = card.dataset.salary;

                    const matchesSearch = title.includes(searchTerm);
                    const matchesExperience = !experienceValue || experience === experienceValue;
                    const matchesSalary = !salaryValue || salary === salaryValue;

                    card.style.display = matchesSearch && matchesExperience && matchesSalary ? 'block' : 'none';
                });
            }

            searchInput.addEventListener('input', filterResumes);
            experienceFilter.addEventListener('change', filterResumes);
            salaryFilter.addEventListener('change', filterResumes);

            // Проверка высоты описания при загрузке
            document.querySelectorAll('.description-content').forEach(content => {
                if (content.scrollHeight > 100) {
                    content.nextElementSibling.style.display = 'flex';
                }
            });
        });

        // Функция для сворачивания/разворачивания описания
        function toggleDescription(button) {
            const content = button.previousElementSibling;
            const isExpanded = content.classList.contains('expanded');
            
            if (isExpanded) {
                content.classList.remove('expanded');
                content.style.maxHeight = '100px';
                button.innerHTML = '<i class="fas fa-chevron-down"></i> Развернуть';
                button.classList.remove('expanded');
            } else {
                content.classList.add('expanded');
                content.style.maxHeight = content.scrollHeight + 'px';
                button.innerHTML = '<i class="fas fa-chevron-up"></i> Свернуть';
                button.classList.add('expanded');
            }
        }
    </script>
</body>
</html>