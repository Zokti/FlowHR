<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/header.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    // Получение всех активных вакансий HR специалиста
    $stmt = $pdo->prepare("
        SELECT j.*, s.salary_range, e.name as experience_name,
               GROUP_CONCAT(DISTINCT sk.name) as skills
        FROM jobs j
        LEFT JOIN salaries s ON j.salary_id = s.id
        LEFT JOIN experiences e ON j.experience_id = e.id
        LEFT JOIN job_skills js ON j.id = js.job_id
        LEFT JOIN skills sk ON js.skill_id = sk.id
        WHERE j.hr_id = ? AND j.status = 'active'
        GROUP BY j.id
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
    <title>Автоматический подбор персонала - FlowHR</title>
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
        }

        .container {
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

        .filter-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FF6F61, #FF4F3F);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .filter-item {
            position: relative;
        }

        .filter-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-label i {
            color: #FF6F61;
        }

        .filter-select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1rem;
            color: #333;
            background-color: #FFFFFF;
            transition: all 0.3s ease;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .filter-select:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.1);
            outline: none;
        }

        .filter-select:hover {
            border-color: #FF6F61;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        }

        .filter-item::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 20px;
            top: 45px;
            color: #FF6F61;
            pointer-events: none;
        }

        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .job-card {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .job-card::before {
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

        .job-card:hover::before {
            opacity: 1;
        }

        .job-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .job-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .job-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
        }

        .job-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .job-content {
            flex-grow: 1;
        }

        .job-info {
            margin-bottom: 15px;
        }

        .job-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .job-info-item i {
            color: var(--primary-color);
            width: 20px;
        }

        .job-description {
            background: var(--bg-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.95rem;
            line-height: 1.5;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .job-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .skill-tag {
            background: rgba(255, 111, 97, 0.1);
            color: var(--primary-color);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-match {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-match:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 97, 0.2);
        }

        .btn-match i {
            font-size: 1.1rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 250px;
            width: calc(100% - 250px);
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 20px;
            color: #666;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #FF6F61;
        }

        .modal h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 10px;
        }

        .modal p {
            color: #666;
            margin-bottom: 20px;
        }

        .match-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .match-option {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px 20px;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            text-align: center;
        }

        .match-option:hover {
            border-color: #FF6F61;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .match-option.selected {
            border-color: #FF6F61;
            background-color: rgba(255, 111, 97, 0.05);
        }

        .match-option.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #f5f5f5;
        }

        .match-option.disabled:hover {
            border-color: #e0e0e0;
            transform: none;
            box-shadow: none;
        }

        .match-option i {
            font-size: 24px;
            color: #FF6F61;
        }

        .match-option h3 {
            font-size: 1.1rem;
            color: #333;
            margin: 0;
        }

        .match-option p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
            line-height: 1.4;
        }

        .match-count {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 111, 97, 0.1);
            color: #FF6F61;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .match-count i {
            font-size: 1rem;
            color: #FF6F61;
        }

        .no-candidates-message {
            text-align: center;
            color: #666;
            padding: 25px;
            background: #f5f5f5;
            border-radius: 12px;
            margin: 20px 0;
            display: none;
        }

        .no-candidates-message.show {
            display: block;
        }

        .no-candidates-message i {
            font-size: 24px;
            color: #666;
            margin-bottom: 10px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
            border: none;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6F61, #FF4F3F);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #FF4F3F, #FF3F2F);
        }

        .btn-primary:disabled {
            background: #FFB3AD;
            cursor: not-allowed;
        }

        .search-section {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1rem;
            color: #333;
            background-color: #FFFFFF;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .search-input:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.1);
            outline: none;
        }

        .search-input:hover {
            border-color: #FF6F61;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 10px;
            }

            .container {
                padding: 10px;
            }

            .filter-section {
                padding: 20px;
            }
            
            .filter-select {
                padding: 12px 15px;
            }

            .job-card {
                margin: 0 10px;
            }

            .modal {
                left: 0;
                width: 100%;
            }
            
            .match-options {
                grid-template-columns: 1fr;
            }
        }

        .toast.success {
            background: linear-gradient(135deg, #FF6F61, #FF4F3F);
        }

        .toast.error {
            background: linear-gradient(135deg, #FF4F3F, #FF3F2F);
        }

        .toast.info {
            background: linear-gradient(135deg, #FF6F61, #FF4F3F);
        }

        .success-details {
            position: fixed;
            top: 50%;
            left: 55%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 90%;
            width: 400px;
        }

        .success-details.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        .success-details .success-icon {
            text-align: center;
            margin-bottom: 1rem;
        }

        .success-details .success-icon i {
            font-size: 3rem;
            color: #28a745;
        }

        .success-details .success-content {
            text-align: center;
        }

        .success-details h3 {
            margin: 0 0 1rem;
            color: #2c3e50;
        }

        .success-details p {
            margin: 0.5rem 0;
            color: #666;
        }

        @media (max-width: 768px) {
            .success-details {
                left: 50%;
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-user-plus"></i>
            Автоматический подбор персонала
        </h1>

        <div class="filter-section">
            <div class="search-section">
                <input type="text" class="search-input" id="searchInput" placeholder="Поиск по названию вакансии...">
            </div>
            <div class="filter-grid">
                <div class="filter-item">
                    <label class="filter-label">
                        <i class="fas fa-briefcase"></i>
                        Опыт работы
                    </label>
                    <select class="filter-select" id="experienceFilter">
                        <option value="">Все</option>
                        <option value="1">Без опыта</option>
                        <option value="2">До 1 года</option>
                        <option value="3">1-3 года</option>
                        <option value="4">3-5 лет</option>
                        <option value="5">Более 5 лет</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label class="filter-label">
                        <i class="fas fa-money-bill-wave"></i>
                        Зарплата
                    </label>
                    <select class="filter-select" id="salaryFilter">
                        <option value="">Все</option>
                        <option value="1">До 50 000 ₽</option>
                        <option value="2">50 000 - 100 000 ₽</option>
                        <option value="3">100 000 - 150 000 ₽</option>
                        <option value="4">150 000 - 200 000 ₽</option>
                        <option value="5">Более 200 000 ₽</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="jobs-grid">
            <?php foreach ($jobs as $job): ?>
                <div class="job-card" data-experience="<?= $job['experience_id'] ?>" data-salary="<?= $job['salary_id'] ?>">
                    <div class="job-header">
                        <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                        <div class="job-meta">
                            <span><i class="fas fa-money-bill-wave"></i> <?= htmlspecialchars($job['salary_range']) ?></span>
                            <span><i class="fas fa-briefcase"></i> <?= htmlspecialchars($job['experience_name']) ?></span>
                        </div>
                    </div>
                    <div class="job-body">
                        <div class="job-content">
                            <div class="job-info">
                                <div class="job-info-item">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?= htmlspecialchars($job['experience_name']) ?></span>
                                </div>
                                <div class="job-info-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span><?= htmlspecialchars($job['salary_range']) ?></span>
                                </div>
                            </div>
                            <div class="job-description">
                                <?= nl2br(htmlspecialchars($job['description'])) ?>
                            </div>
                            <div class="job-skills">
                                <?php 
                                $skills = explode(',', $job['skills']);
                                foreach ($skills as $skill): 
                                    if (!empty(trim($skill))):
                                ?>
                                    <div class="skill-tag">
                                        <i class="fas fa-check"></i>
                                        <?= htmlspecialchars(trim($skill)) ?>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <button class="btn-match" onclick="openModal(<?= $job['id'] ?>)">
                            <i class="fas fa-user-plus"></i>
                            Подобрать кандидатов
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Модальное окно подбора кандидатов -->
    <div id="matchModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            <h2>Подбор кандидатов</h2>
            <p>Выберите тип соответствия для подбора кандидатов:</p>
            
            <div class="no-candidates-message">
                <i class="fas fa-info-circle"></i>
                <p>Подходящих кандидатов не найдено</p>
            </div>
            
            <div class="match-options">
                <div class="match-option" onclick="selectMatchOption('100')" data-value="100">
                    <div class="match-count">
                        <i class="fas fa-users"></i>
                        <span class="full-match-count">0</span>
                    </div>
                    <i class="fas fa-check-circle"></i>
                    <h3>100% соответствие</h3>
                    <p>Кандидаты, у которых есть все необходимые навыки</p>
                </div>
                <div class="match-option" onclick="selectMatchOption('50')" data-value="50">
                    <div class="match-count">
                        <i class="fas fa-users"></i>
                        <span class="partial-match-count">0</span>
                    </div>
                    <i class="fas fa-check"></i>
                    <h3>50% соответствие</h3>
                    <p>Кандидаты, у которых есть хотя бы половина необходимых навыков</p>
                </div>
            </div>
            
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                    Отмена
                </button>
                <button class="btn btn-primary" onclick="startMatching()" id="confirmButton" disabled>
                    <i class="fas fa-spinner fa-spin" style="display: none;"></i>
                    Начать подбор
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentJobId = null;
        let selectedMatchType = null;

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.classList.add('show'), 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        function updateMatchCounts(jobId) {
            fetch(`api/get_match_counts.php?job_id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Match counts data:', data);
                    
                    const fullMatch = parseInt(data.full_match) || 0;
                    const partialMatch = parseInt(data.partial_match) || 0;
                    
                    document.querySelector('.full-match-count').textContent = fullMatch;
                    document.querySelector('.partial-match-count').textContent = partialMatch;
                    
                    const hasCandidates = fullMatch > 0 || partialMatch > 0;
                    document.querySelector('.no-candidates-message').classList.toggle('show', !hasCandidates);
                    
                    document.querySelectorAll('.match-option').forEach(option => {
                        const value = option.dataset.value;
                        const count = value === '100' ? fullMatch : partialMatch;
                        option.classList.toggle('disabled', count === 0);
                        
                        const countElement = option.querySelector('.match-count span');
                        countElement.title = `Найдено кандидатов: ${count}`;
                    });
                    
                    document.getElementById('confirmButton').disabled = !hasCandidates;
                    
                    if (!hasCandidates) {
                        showToast('Подходящих кандидатов не найдено', 'info');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Ошибка при получении количества кандидатов', 'error');
                });
        }

        function selectMatchOption(value) {
            const option = document.querySelector(`.match-option[data-value="${value}"]`);
            if (option.classList.contains('disabled')) {
                showToast('Нет кандидатов с таким уровнем соответствия', 'warning');
                return;
            }
            
            document.querySelectorAll('.match-option').forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
            selectedMatchType = value;
            document.getElementById('confirmButton').disabled = false;
            
            const count = value === '100' 
                ? document.querySelector('.full-match-count').textContent 
                : document.querySelector('.partial-match-count').textContent;
            showToast(`Выбрано ${count} кандидатов с ${value}% соответствием`, 'info');
        }

        function startMatching() {
            if (!selectedMatchType) {
                showToast('Пожалуйста, выберите тип соответствия', 'error');
                return;
            }

            if (!currentJobId) {
                showToast('Ошибка: не выбран ID вакансии', 'error');
                return;
            }

            const button = document.getElementById('confirmButton');
            const spinner = button.querySelector('.fa-spinner');
            button.disabled = true;
            spinner.style.display = 'inline-block';

            console.log('Starting matching with:', { jobId: currentJobId, matchType: selectedMatchType });

            fetch('api/match_candidates.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    job_id: currentJobId,
                    match_type: selectedMatchType
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Matching response:', data);
                
                if (data.success) {
                    const matchTypeText = selectedMatchType === '100' ? '100%' : '50%';
                    const message = `Успешно отправлено ${data.offers_sent} предложений кандидатам с ${matchTypeText} соответствием навыков`;
                    showToast(message, 'success');
                    
                    // Показываем детальное сообщение
                    const detailMessage = document.createElement('div');
                    detailMessage.className = 'success-details';
                    detailMessage.innerHTML = `
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="success-content">
                            <h3>Подбор кандидатов завершен</h3>
                            <p>${message}</p>
                            <p>Всего найдено подходящих кандидатов: ${data.matched_count}</p>
                        </div>
                    `;
                    document.body.appendChild(detailMessage);
                    
                    // Анимация появления
                    setTimeout(() => detailMessage.classList.add('show'), 100);
                    
                    // Закрываем модальное окно и обновляем страницу через 3 секунды
                    setTimeout(() => {
                        detailMessage.classList.remove('show');
                        setTimeout(() => {
                            detailMessage.remove();
                            closeModal();
                            location.reload();
                        }, 300);
                    }, 3000);
                } else {
                    showToast(data.message || 'Произошла ошибка при подборе кандидатов', 'error');
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка сети при подборе кандидатов', 'error');
                button.disabled = false;
            })
            .finally(() => {
                spinner.style.display = 'none';
            });
        }

        function openModal(jobId) {
            currentJobId = jobId;
            selectedMatchType = null;
            document.getElementById('confirmButton').disabled = true;
            document.querySelectorAll('.match-option').forEach(opt => opt.classList.remove('selected'));
            document.querySelector('.no-candidates-message').classList.remove('show');
            updateMatchCounts(jobId);
            document.getElementById('matchModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('matchModal').style.display = 'none';
            currentJobId = null;
            selectedMatchType = null;
        }

        // Функция фильтрации вакансий
        function filterJobs() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const experienceFilter = document.getElementById('experienceFilter').value;
            const salaryFilter = document.getElementById('salaryFilter').value;

            document.querySelectorAll('.job-card').forEach(card => {
                const title = card.querySelector('.job-title').textContent.toLowerCase();
                const experience = card.dataset.experience;
                const salary = card.dataset.salary;

                const matchesSearch = title.includes(searchText);
                const matchesExperience = !experienceFilter || experience === experienceFilter;
                const matchesSalary = !salaryFilter || salary === salaryFilter;

                card.style.display = matchesSearch && matchesExperience && matchesSalary ? 'block' : 'none';
            });
        }

        // Добавляем обработчики событий для фильтров
        document.getElementById('searchInput').addEventListener('input', filterJobs);
        document.getElementById('experienceFilter').addEventListener('change', filterJobs);
        document.getElementById('salaryFilter').addEventListener('change', filterJobs);
    </script>
</body>
</html> 