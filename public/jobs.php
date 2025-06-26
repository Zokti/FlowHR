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
    SELECT 
        j.id, 
        j.title, 
        j.description, 
        j.status, 
        s.salary_range, 
        e.name AS experience,
        j.created_at,
        u.name as company_name
    FROM jobs j
    JOIN salaries s ON j.salary_id = s.id
    JOIN experiences e ON j.experience_id = e.id
    JOIN users u ON j.hr_id = u.id
    WHERE j.status = 'active'
    ORDER BY j.created_at DESC
";
$jobs_stmt = $pdo->query($jobs_query);
$jobs = $jobs_stmt->fetchAll();

// Получаем список всех навыков
$skills_query = "SELECT id, name FROM skills";
$skills_result = $pdo->query($skills_query);
$skills = $skills_result->fetchAll(PDO::FETCH_KEY_PAIR); // Ключ - ID навыка, значение - название

// Обработка отклика на вакансию
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    try {
        $job_id = $_POST['job_id'];
        $user_id = $_SESSION['user_id'];

        // Проверяем, не откликался ли уже пользователь
        $check_query = "SELECT * FROM applications WHERE entity_id = ? AND entity_type = 'vacancy' AND candidate_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$job_id, $user_id]);

        if ($check_stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Вы уже откликались на эту вакансию']);
            exit();
        }

        // Получаем ID HR-менеджера
        $job_query = "SELECT hr_id FROM jobs WHERE id = ?";
        $job_stmt = $pdo->prepare($job_query);
        $job_stmt->execute([$job_id]);
        $job = $job_stmt->fetch();

        if (!$job) {
            echo json_encode(['success' => false, 'message' => 'Вакансия не найдена']);
            exit();
        }

        // Создаем отклик
        $insert_query = "INSERT INTO applications (job_id, hr_id, candidate_id, entity_type, entity_id, status, created_at) 
                        VALUES (?, ?, ?, 'vacancy', ?, 'pending', NOW())";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute([$job_id, $job['hr_id'], $user_id, $job_id]);

        echo json_encode(['success' => true, 'message' => 'Отклик успешно отправлен!']);
    } catch (PDOException $e) {
        error_log("Ошибка при отклике на вакансию: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Произошла ошибка при отправке отклика']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вакансии - FlowHR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            font-family: 'Arial', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
            margin-left: 250px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: var(--text-color);
            text-align: center;
            margin: 40px 0;
            font-size: 36px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        h1 i {
            color: var(--primary-color);
            font-size: 32px;
        }

        .job-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            animation: slideUp 0.5s ease-out;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .job-title {
            color: var(--text-color);
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .company-name {
            color: var(--primary-color);
            font-size: 16px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .company-name i {
            font-size: 18px;
        }

        .job-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--bg-color);
            border-radius: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: var(--card-bg);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .detail-item i {
            color: var(--primary-color);
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        .detail-text {
            color: var(--text-color);
            font-size: 16px;
            font-weight: 500;
        }

        .job-description {
            color: var(--text-color);
            line-height: 1.7;
            margin-bottom: 20px;
            padding: 20px;
            background: var(--bg-color);
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }

        .job-description::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30px;
            background: linear-gradient(transparent, var(--bg-color));
            pointer-events: none;
        }

        .btn-apply {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .btn-apply i {
            font-size: 18px;
        }

        .confirm-modal {
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

        .confirm-modal.show {
            display: flex;
        }

        .confirm-content {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
            transform: translateY(20px);
            opacity: 0;
            animation: modalOpen 0.3s ease-out forwards;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        @keyframes modalOpen {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .confirm-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .confirm-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .confirm-message {
            color: var(--text-color);
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
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            min-width: 120px;
        }

        .confirm-btn.cancel {
            background: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .confirm-btn.cancel:hover {
            background: var(--border-color);
        }

        .confirm-btn.confirm {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .confirm-btn.confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .confirm-modal {
                left: 0;
            }

            .job-details {
                grid-template-columns: 1fr;
            }

            .job-header {
                flex-direction: column;
                gap: 15px;
            }

            .btn-apply {
                width: 100%;
                justify-content: center;
            }
        }

        .job-card:nth-child(1) { animation-delay: 0.1s; }
        .job-card:nth-child(2) { animation-delay: 0.2s; }
        .job-card:nth-child(3) { animation-delay: 0.3s; }
        .job-card:nth-child(4) { animation-delay: 0.4s; }
        .job-card:nth-child(5) { animation-delay: 0.5s; }

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

        .search-container {
            margin-bottom: 30px;
            background: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .search-box {
            position: relative;
            margin-bottom: 15px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 18px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.1);
        }

        .filters {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-select {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            color: var(--text-color);
            background: var(--card-bg);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.1);
        }

        .clear-filters-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clear-filters-btn:hover {
            background: var(--border-color);
        }

        .clear-filters-btn i {
            font-size: 14px;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            background: var(--card-bg);
            border-radius: 15px;
            margin-top: 20px;
        }

        .no-results i {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .no-results p {
            color: var(--text-color);
            font-size: 18px;
            margin: 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="jobs-container">
            <h1 class="page-title">
                <i class="fas fa-briefcase"></i>
                Доступные вакансии
            </h1>

            <!-- Поиск и фильтры -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Поиск по названию вакансии...">
                </div>
                <div class="filters">
                    <select id="experienceFilter" class="filter-select">
                        <option value="">Все уровни опыта</option>
                        <option value="Нет опыта">Нет опыта</option>
                        <option value="От 1 до 3 лет">От 1 до 3 лет</option>
                        <option value="От 3 до 5 лет">От 3 до 5 лет</option>
                        <option value="Более 5 лет">Более 5 лет</option>
                    </select>
                    <button id="clearFilters" class="clear-filters-btn">
                        <i class="fas fa-times"></i>
                        Очистить фильтры
                    </button>
                </div>
            </div>

            <div id="jobsList">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card" id="job-<?php echo $job['id']; ?>" 
                         data-title="<?php echo htmlspecialchars($job['title']); ?>"
                         data-company="<?php echo htmlspecialchars($job['company_name']); ?>"
                         data-description="<?php echo htmlspecialchars($job['description']); ?>"
                         data-experience="<?php echo htmlspecialchars($job['experience']); ?>"
                         data-salary="<?php echo htmlspecialchars($job['salary_range']); ?>">
                        <div class="job-header">
                            <div>
                                <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="company-name">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="job-details">
                            <div class="detail-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span class="detail-text"><?php echo htmlspecialchars($job['salary_range']); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-briefcase"></i>
                                <span class="detail-text"><?php echo htmlspecialchars($job['experience']); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="detail-text"><?php echo date('d.m.Y', strtotime($job['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="job-description">
                            <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        </div>

                        <button class="btn-apply" onclick="confirmApplication(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title']); ?>')">
                            <i class="fas fa-paper-plane"></i>
                            Откликнуться
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения -->
    <div class="confirm-modal" id="confirmModal">
        <div class="confirm-content">
            <div class="confirm-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <h3 class="confirm-title">Подтверждение отклика</h3>
            <p class="confirm-message">Вы уверены, что хотите откликнуться на вакансию "<span id="jobTitle"></span>"?</p>
            <div class="confirm-buttons">
                <button class="confirm-btn cancel" onclick="closeConfirmModal()">Отмена</button>
                <button class="confirm-btn confirm" onclick="submitApplication()">Подтвердить</button>
            </div>
        </div>
    </div>

    <script>
    let selectedJobId = null;
    const confirmModal = document.getElementById('confirmModal');

    function confirmApplication(jobId, jobTitle) {
        selectedJobId = jobId;
        document.getElementById('jobTitle').textContent = jobTitle;
        confirmModal.classList.add('show');
    }

    function closeConfirmModal() {
        confirmModal.classList.remove('show');
        selectedJobId = null;
    }

    function submitApplication() {
        if (!selectedJobId) return;

        const formData = new FormData();
        formData.append('job_id', selectedJobId);

        fetch('jobs.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Показываем сообщение об успехе
                const confirmContent = document.querySelector('.confirm-content');
                confirmContent.innerHTML = `
                    <div class="confirm-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="confirm-title">Успешно!</h3>
                    <p class="confirm-message">${data.message}</p>
                    <div class="confirm-buttons">
                        <button class="confirm-btn cancel" onclick="closeConfirmModal()">Закрыть</button>
                    </div>
                `;
                
                // Отключаем кнопку отклика
                const applyButton = document.querySelector(`#job-${selectedJobId} .btn-apply`);
                applyButton.disabled = true;
                applyButton.innerHTML = '<i class="fas fa-check"></i> Отклик отправлен';
            } else {
                // Показываем сообщение об ошибке
                const confirmContent = document.querySelector('.confirm-content');
                confirmContent.innerHTML = `
                    <div class="confirm-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3 class="confirm-title">Ошибка</h3>
                    <p class="confirm-message">${data.message}</p>
                    <div class="confirm-buttons">
                        <button class="confirm-btn cancel" onclick="closeConfirmModal()">Закрыть</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            const confirmContent = document.querySelector('.confirm-content');
            confirmContent.innerHTML = `
                <div class="confirm-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3 class="confirm-title">Ошибка</h3>
                <p class="confirm-message">Произошла ошибка при отправке отклика</p>
                <div class="confirm-buttons">
                    <button class="confirm-btn cancel" onclick="closeConfirmModal()">Закрыть</button>
                </div>
            `;
        });
    }

    // Закрытие модального окна при клике вне его
    confirmModal.addEventListener('click', (e) => {
        if (e.target === confirmModal) {
            closeConfirmModal();
        }
    });

    // Функция поиска и фильтрации
    function filterJobs() {
        const searchText = document.getElementById('searchInput').value.toLowerCase();
        const experienceFilter = document.getElementById('experienceFilter').value;
        const jobCards = document.querySelectorAll('.job-card');
        let hasResults = false;

        jobCards.forEach(card => {
            const title = card.dataset.title.toLowerCase();
            const experience = card.dataset.experience;

            const matchesSearch = title.includes(searchText);
            const matchesExperience = !experienceFilter || experience === experienceFilter;

            if (matchesSearch && matchesExperience) {
                card.style.display = 'block';
                hasResults = true;
            } else {
                card.style.display = 'none';
            }
        });

        // Показываем сообщение, если нет результатов
        let noResults = document.querySelector('.no-results');
        if (!hasResults) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.innerHTML = `
                    <i class="fas fa-search"></i>
                    <p>По вашему запросу ничего не найдено</p>
                `;
                document.getElementById('jobsList').appendChild(noResults);
            }
        } else if (noResults) {
            noResults.remove();
        }
    }

    // Функция очистки фильтров
    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('experienceFilter').value = '';
        filterJobs();
    }

    // Добавляем обработчики событий
    document.getElementById('searchInput').addEventListener('input', filterJobs);
    document.getElementById('experienceFilter').addEventListener('change', filterJobs);
    document.getElementById('clearFilters').addEventListener('click', clearFilters);
    </script>
</body>
</html>