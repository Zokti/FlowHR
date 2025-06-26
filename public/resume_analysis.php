<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

// Получение списка резюме с расширенной информацией
$resumes = [];
$query = "SELECT 
            r.id as resume_id,
            r.title as resume_title,
            r.description as resume_description,
            r.visibility_duration,
            r.created_at as resume_created_at,
            r.is_published,
            u.name,
            u.email,
            u.phone,
            u.city,
            u.age,
            u.profile_completion,
            e.name as experience_name,
            s.salary_range,
            GROUP_CONCAT(DISTINCT sk.name) as skills,
            COUNT(DISTINCT a.id) as applications_count
          FROM resumes r
          JOIN users u ON r.user_id = u.id 
          LEFT JOIN experiences e ON r.experience_id = e.id
          LEFT JOIN salaries s ON r.salary_id = s.id
          LEFT JOIN resume_skills rs ON r.id = rs.resume_id
          LEFT JOIN skills sk ON rs.skill_id = sk.id
          LEFT JOIN applications a ON a.entity_id = r.id AND a.entity_type = 'resume'
          GROUP BY r.id, r.title, r.description, r.visibility_duration, r.created_at, 
                   r.is_published, u.name, u.email, u.phone, u.city, u.age, 
                   u.profile_completion, e.name, s.salary_range
          ORDER BY r.created_at DESC";

try {
    $stmt = $pdo->query($query);
    $resumes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching resumes: " . $e->getMessage());
    $resumes = [];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анализ резюме - FlowHR</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            animation: fadeIn 0.5s ease-in-out;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-title {
            color: var(--text-color);
            font-size: 36px;
            margin: 2rem 0;
            text-align: center;
            padding: 20px 0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-title i {
            color: var(--primary-color);
            font-size: 32px;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 2px;
        }

        .resume-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
            border: 1px solid var(--border-color);
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
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 25px;
            border-radius: 20px 20px 0 0;
        }

        .resume-body {
            padding: 25px;
            background: var(--card-bg);
        }

        .resume-info {
            margin-bottom: 20px;
            padding: 15px;
            background: var(--bg-color);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .resume-info strong {
            color: var(--text-color);
            display: inline-block;
            width: 180px;
            font-weight: 600;
        }

        .resume-description {
            background: var(--bg-color);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid var(--primary-color);
            border: 1px solid var(--border-color);
        }

        .resume-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 5px;
            max-height: 100px;
            overflow-y: auto;
            padding: 5px;
            background: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .resume-skills::-webkit-scrollbar {
            width: 6px;
        }

        .resume-skills::-webkit-scrollbar-track {
            background: var(--bg-color);
            border-radius: 3px;
        }

        .resume-skills::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .resume-skills::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        .skill-badge {
            background: var(--card-bg);
            color: var(--text-color);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .skill-badge:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .analysis-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .quality-assessment {
            text-align: center;
            margin: 25px 0;
            padding: 20px;
            background: var(--bg-color);
            border-radius: 10px;
            border: 1px solid var(--border-color);
            position: relative;
        }

        .quality-assessment h4 {
            margin-bottom: 20px;
            color: var(--text-color);
            font-weight: 600;
            font-size: 1.3rem;
        }

        .progress {
            height: 25px;
            border-radius: 12px;
            background-color: var(--bg-color);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            margin: 15px 0;
        }

        .progress-bar {
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9em;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .progress-percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .analysis-details {
            margin-top: 25px;
            padding: 20px;
            background: var(--bg-color);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .analysis-details h5 {
            color: var(--text-color);
            margin-bottom: 20px;
            font-weight: 600;
        }

        .job-suggestion-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: var(--card-bg);
            padding: 20px;
        }

        .job-suggestion-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }

        .job-suggestion-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .job-suggestion-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
        }

        .job-suggestion-info {
            margin: 15px 0;
            padding: 15px;
            background: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .job-suggestion-description {
            margin: 15px 0;
            padding: 15px;
            background: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            line-height: 1.6;
        }

        .job-suggestion-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-propose {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-propose:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
        }

        .btn-propose:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .match-badge {
            font-size: 0.9em;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .match-badge i {
            font-size: 0.9em;
        }

        .match-badge.full {
            background-color: var(--success-color);
            color: white;
        }

        .match-badge.partial {
            background-color: var(--warning-color);
            color: var(--text-color);
        }

        .match-badge.none {
            background-color: var(--danger-color);
            color: white;
        }

        .resume-analysis-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .resume-analysis-title i {
            color: var(--primary-color);
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: var(--bg-color);
            font-weight: 600;
            color: var(--text-color);
            padding: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .table td {
            vertical-align: middle;
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            max-width: 200px;
        }

        .table td.skills-cell {
            min-width: 200px;
            max-width: 250px;
        }

        .table tbody tr:hover {
            background-color: var(--bg-color);
        }

        .badge {
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
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

        .modal-header h5 i {
            font-size: 1.3rem;
        }

        .modal-body {
            padding: 25px;
            background: var(--card-bg);
        }

        .propose-job-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-label i {
            color: var(--primary-color);
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 111, 97, 0.25);
        }

        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-cancel {
            background: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #e9ecef;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .success-modal .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }

        .success-modal .modal-body {
            padding: 40px 20px;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--success-color), #2ecc71);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease-out;
        }

        .success-icon i {
            font-size: 40px;
            color: white;
        }

        .success-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 15px;
            animation: slideUp 0.5s ease-out 0.2s both;
        }

        .success-message {
            color: #666;
            margin-bottom: 25px;
            animation: slideUp 0.5s ease-out 0.3s both;
        }

        .success-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            animation: slideUp 0.5s ease-out 0.4s both;
        }

        .btn-success-modal {
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success-modal.primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
        }

        .btn-success-modal.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
        }

        .btn-success-modal.secondary {
            background: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-success-modal.secondary:hover {
            background: #e9ecef;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .skills-container {
            position: relative;
            display: inline-block;
        }

        .skills-preview {
            display: flex;
            gap: 5px;
            align-items: center;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 15px;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .skills-preview:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .skills-preview i {
            font-size: 0.8em;
            transition: transform 0.3s ease;
        }

        .skills-preview.active i {
            transform: rotate(180deg);
        }

        .skills-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 10px;
            min-width: 200px;
            max-width: 300px;
            display: none;
            margin-top: 5px;
        }

        .skills-dropdown.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
            padding: 5px;
        }

        .skills-list::-webkit-scrollbar {
            width: 4px;
        }

        .skills-list::-webkit-scrollbar-track {
            background: var(--bg-color);
        }

        .skills-list::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 2px;
        }

        .skill-badge {
            background: var(--bg-color);
            color: var(--text-color);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .skill-badge:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="page-title">
            <i class="fas fa-file-alt"></i>
            Анализ резюме
        </h1>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card resume-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Название резюме</th>
                                        <th>Кандидат</th>
                                        <th>Опыт</th>
                                        <th>Желаемая оплата труда</th>
                                        <th>Навыки</th>
                                        <th>Срок действия</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumes as $resume): 
                                        $createdAt = new DateTime($resume['resume_created_at']);
                                        $expiresAt = (clone $createdAt)->modify('+' . $resume['visibility_duration'] . ' days');
                                        $now = new DateTime();
                                        $daysLeft = $now->diff($expiresAt)->days;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($resume['resume_title']); ?></td>
                                        <td><?php echo htmlspecialchars($resume['name']); ?></td>
                                        <td><?php echo htmlspecialchars($resume['experience_name']); ?></td>
                                        <td><?php echo htmlspecialchars($resume['salary_range']); ?></td>
                                        <td>
                                            <div class="skills-container">
                                                <div class="skills-preview" onclick="toggleSkills(this)">
                                                    <span>Навыки</span>
                                                    <i class="fas fa-chevron-down"></i>
                                                </div>
                                                <div class="skills-dropdown">
                                                    <div class="skills-list">
                                                        <?php 
                                                        $skills = explode(',', $resume['skills']);
                                                        foreach ($skills as $skill): 
                                                            $skill = trim($skill);
                                                            if (!empty($skill)):
                                                        ?>
                                                            <span class="skill-badge"><?php echo htmlspecialchars($skill); ?></span>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($daysLeft > 0): ?>
                                                <span class="badge bg-success">Осталось <?php echo $daysLeft; ?> дней</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Истекло</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($resume['is_published']): ?>
                                                <span class="badge bg-success">Опубликовано</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Черновик</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="analyzeResume(<?php echo $resume['resume_id']; ?>)">
                                                Анализ резюме
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для анализа резюме -->
    <div class="modal fade" id="resumeAnalysisModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Анализ резюме</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="resumeAnalysisResults"></div>
                    <div class="mt-4">
                        <h5>Предложить вакансии</h5>
                        <div id="jobSuggestions" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для предложения вакансии -->
    <div class="modal fade" id="proposeJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane"></i>
                        Предложить вакансию
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="proposeJobForm" class="propose-job-form">
                        <input type="hidden" id="proposeJobId" name="job_id">
                        <input type="hidden" id="proposeResumeId" name="resume_id">
                        <div class="form-group">
                            <label for="proposeMessage" class="form-label">
                                <i class="fas fa-comment-alt"></i>
                                Сообщение кандидату
                            </label>
                            <textarea class="form-control" id="proposeMessage" name="message" rows="4" 
                                    placeholder="Напишите сообщение кандидату (необязательно)"></textarea>
                            <small class="form-text">
                                <i class="fas fa-info-circle"></i>
                                Вы можете оставить сообщение пустым, если хотите предложить вакансию без дополнительного текста
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                    <button type="button" class="btn btn-submit" onclick="submitProposeJob()">
                        <i class="fas fa-paper-plane"></i> Отправить предложение
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для анализа кандидата -->
    <div class="modal fade" id="candidateAnalysisModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Анализ кандидата</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="candidateAnalysisResults"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно успешного предложения вакансии -->
    <div class="modal fade success-modal" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 class="success-title">Вакансия успешно предложена</h3>
                    <p class="success-message">Кандидат получит уведомление о вашем предложении</p>
                    <div class="success-actions">
                        <button type="button" class="btn btn-success-modal secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Закрыть
                        </button>
                        <button type="button" class="btn btn-success-modal primary" onclick="closeSuccessModal()">
                            <i class="fas fa-check"></i> Продолжить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentResumeId = null;

    function analyzeResume(resumeId) {
        currentResumeId = resumeId;
        console.log('Analyzing resume:', resumeId);
        fetch(`../api/analyze_resume.php?id=${resumeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                console.log('Analysis data:', data);
                const modal = new bootstrap.Modal(document.getElementById('resumeAnalysisModal'));
                document.getElementById('resumeAnalysisResults').innerHTML = formatResumeAnalysis(data);
                loadJobSuggestions(resumeId);
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при анализе резюме: ' + error.message);
            });
    }

    function loadJobSuggestions(resumeId) {
        fetch(`../api/get_job_suggestions.php?resume_id=${resumeId}`)
            .then(response => response.json())
            .then(suggestions => {
                const suggestionsHtml = suggestions.map(job => `
                    <div class="job-suggestion-card">
                        <div class="job-suggestion-header">
                            <h5 class="job-suggestion-title">${job.title}</h5>
                            <span class="match-badge ${job.match_type}">
                                <i class="fas ${getMatchIcon(job.match_type)}"></i>
                                ${getMatchBadgeText(job.match_type)}
                            </span>
                        </div>
                        <div class="job-suggestion-info">
                            <p><strong>Опыт:</strong> ${job.experience}</p>
                            <p><strong>Зарплата:</strong> ${job.salary}</p>
                        </div>
                        <div class="job-suggestion-description">
                            ${job.description}
                        </div>
                        <div class="job-suggestion-actions">
                            ${job.is_proposed ? 
                                '<span class="badge bg-info">Предложено</span>' :
                                `<button class="btn btn-propose" onclick="showProposeJobModal(${job.id})">
                                    <i class="fas fa-paper-plane"></i> Предложить
                                </button>`
                            }
                        </div>
                    </div>
                `).join('');
                
                document.getElementById('jobSuggestions').innerHTML = suggestionsHtml;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('jobSuggestions').innerHTML = 
                    '<div class="alert alert-danger">Ошибка при загрузке предложений</div>';
            });
    }

    function getMatchIcon(matchType) {
        switch(matchType) {
            case 'full':
                return 'fa-check-circle';
            case 'partial':
                return 'fa-adjust';
            default:
                return 'fa-times-circle';
        }
    }

    function getMatchBadgeText(matchType) {
        switch(matchType) {
            case 'full':
                return 'Полное совпадение';
            case 'partial':
                return 'Частичное совпадение';
            default:
                return 'Нет совпадений';
        }
    }

    function showProposeJobModal(jobId) {
        document.getElementById('proposeJobId').value = jobId;
        document.getElementById('proposeResumeId').value = currentResumeId;
        const modal = new bootstrap.Modal(document.getElementById('proposeJobModal'));
        modal.show();
    }

    function submitProposeJob() {
        const formData = {
            job_id: document.getElementById('proposeJobId').value,
            resume_id: document.getElementById('proposeResumeId').value,
            message: document.getElementById('proposeMessage').value.trim()
        };

        fetch('../api/propose_job.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            // Закрываем модальное окно предложения
            bootstrap.Modal.getInstance(document.getElementById('proposeJobModal')).hide();
            // Показываем модальное окно успеха
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
            // Обновляем список предложений
            loadJobSuggestions(currentResumeId);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при предложении вакансии: ' + error.message);
        });
    }

    function closeSuccessModal() {
        bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
    }

    function analyzeCandidate(candidateId) {
        console.log('Analyzing candidate:', candidateId);
        fetch(`../api/analyze_candidate.php?id=${candidateId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                console.log('Analysis data:', data);
                const modal = new bootstrap.Modal(document.getElementById('candidateAnalysisModal'));
                document.getElementById('candidateAnalysisResults').innerHTML = formatCandidateAnalysis(data);
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при анализе кандидата: ' + error.message);
            });
    }

    function formatResumeAnalysis(data) {
        let qualityLevel = '';
        let qualityClass = '';
        
        if (data.score >= 80) {
            qualityLevel = 'Отличное резюме';
            qualityClass = 'success';
        } else if (data.score >= 60) {
            qualityLevel = 'Хорошее резюме';
            qualityClass = 'info';
        } else if (data.score >= 40) {
            qualityLevel = 'Среднее резюме';
            qualityClass = 'warning';
        } else {
            qualityLevel = 'Требует доработки';
            qualityClass = 'danger';
        }

        return `
            <div class="analysis-section">
                <h3 class="resume-analysis-title">
                    <i class="fas fa-chart-line"></i>
                    Оценка резюме
                </h3>
                <div class="resume-header">
                    <h4>${data.resume_info.title}</h4>
                </div>
                <div class="resume-body">
                    <div class="resume-info">
                        <p><strong>Опыт работы:</strong> ${data.resume_info.experience}</p>
                        <p><strong>Желаемая оплата труда:</strong> ${data.resume_info.salary}</p>
                        <p><strong>Дата создания:</strong> ${data.resume_info.created_at}</p>
                    </div>
                    <div class="resume-description">
                        <h6>Описание</h6>
                        <p>${data.resume_info.description}</p>
                    </div>
                    <div class="resume-skills">
                        <h6>Навыки</h6>
                        ${data.resume_info.skills.split(',').map(skill => 
                            `<span class="skill-badge">${skill.trim()}</span>`
                        ).join('')}
                    </div>
                </div>

                <div class="quality-assessment">
                    <h4 class="text-${qualityClass}">${qualityLevel}</h4>
                    <div class="progress">
                        <div class="progress-bar bg-${qualityClass}" role="progressbar" 
                             style="width: ${data.score}%" 
                             aria-valuenow="${data.score}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                        <span class="progress-percentage">${data.score}%</span>
                    </div>
                </div>

                <div class="analysis-details">
                    <h5>Детальный анализ:</h5>
                    ${data.details.map(detail => `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>${detail.criterion}</strong>
                                <span class="badge bg-${getScoreColor(detail.score)}">${detail.score}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-${getScoreColor(detail.score)}" 
                                     role="progressbar" 
                                     style="width: ${detail.score}%">
                                </div>
                                <span class="progress-percentage">${detail.score}%</span>
                            </div>
                            <p class="text-muted small mb-0">${detail.comment}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function getScoreColor(score) {
        if (score >= 80) return 'success';
        if (score >= 60) return 'info';
        if (score >= 40) return 'warning';
        return 'danger';
    }

    function formatCandidateAnalysis(data) {
        return `
            <div class="analysis-section">
                <h6>Анализ кандидата</h6>
                <div class="progress mb-3">
                    <div class="progress-bar bg-info" role="progressbar" style="width: ${data.score}%"></div>
                </div>
                <div class="analysis-details">
                    ${data.details.map(detail => `
                        <div class="mb-2">
                            <strong>${detail.criterion}:</strong> ${detail.score}/10
                            <p class="text-muted small">${detail.comment}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function toggleSkills(element) {
        const dropdown = element.nextElementSibling;
        const isActive = element.classList.contains('active');
        
        // Закрываем все открытые выпадающие списки
        document.querySelectorAll('.skills-preview.active').forEach(el => {
            if (el !== element) {
                el.classList.remove('active');
                el.nextElementSibling.classList.remove('show');
            }
        });

        // Переключаем текущий выпадающий список
        element.classList.toggle('active');
        dropdown.classList.toggle('show');

        // Закрываем выпадающий список при клике вне его
        if (!isActive) {
            document.addEventListener('click', function closeDropdown(e) {
                if (!element.contains(e.target) && !dropdown.contains(e.target)) {
                    element.classList.remove('active');
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }
    }
    </script>
</body>
</html> 