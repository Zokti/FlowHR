<?php
require __DIR__ . '/../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Проверка роли пользователя
if ($_SESSION['role'] !== 'HR') {
    header('Location: /dashboard.php');
    exit;
}

// Получение списка навыков
$stmt = $pdo->query("SELECT * FROM skills ORDER BY name");
$skills = $stmt->fetchAll();

// Получение списка опыта работы
$stmt = $pdo->query("SELECT * FROM experiences ORDER BY name");
$experiences = $stmt->fetchAll();

// Получение списка зарплат
$stmt = $pdo->query("SELECT * FROM salaries ORDER BY salary_range");
$salaries = $stmt->fetchAll();

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Вставка вакансии
        $stmt = $pdo->prepare("
            INSERT INTO jobs (
                hr_id,
                title,
                description,
                experience_id,
                salary_id,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['experience_id'],
            $_POST['salary_id']
        ]);

        $job_id = $pdo->lastInsertId();

        // Вставка навыков
        if (!empty($_POST['skills'])) {
            $stmt = $pdo->prepare("
                INSERT INTO job_skills (job_id, skill_id) 
                VALUES (?, ?)
            ");

            foreach ($_POST['skills'] as $skill_id) {
                $stmt->execute([$job_id, $skill_id]);
            }
        }

        $pdo->commit();
        
        // Перенаправление на страницу вакансий с сообщением об успехе
        $_SESSION['success_message'] = 'Вакансия успешно создана';
        header('Location: http://flowhr/public/my_vacancies.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Произошла ошибка при создании вакансии: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать вакансию - FlowHR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
            margin-left: 250px;
            padding: 20px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .vacancy-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .page-title {
            color: #2C3E50;
            font-size: 32px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .page-title:after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #FF8C75, #FF6B4A);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 0;
            border-radius: 2px;
        }

        .form-section {
            background: #FFF9F7;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 140, 117, 0.1);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            box-shadow: 0 5px 15px rgba(255, 140, 117, 0.1);
            transform: translateY(-2px);
        }

        .section-title {
            color: #2D3748;
            font-size: 1.2rem;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 140, 117, 0.1);
        }

        .section-title i {
            color: #FF8C75;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-group::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 140, 117, 0.1), transparent);
        }

        .form-group:last-child::after {
            display: none;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-weight: 600;
            color: #2C3E50;
            font-size: 1rem;
        }

        .form-label i {
            color: #FF8C75;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #FF8C75;
            border-radius: 10px;
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.3s ease;
            background: white;
            resize: none;
            box-sizing: border-box;
            margin-top: 5px;
        }

        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 140, 117, 0.15);
            border-color: #FF6B4A;
        }

        .form-input::placeholder {
            color: #A0AEC0;
            transition: all 0.3s ease;
        }

        .form-input:focus::placeholder {
            opacity: 0.7;
            transform: translateX(5px);
        }

        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #FF8C75;
            border-radius: 10px;
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.3s ease;
            background: white;
            color: #2C3E50;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23FF8C75' d='M6 8.825L1.175 4 2.05 3.125 6 7.075 9.95 3.125 10.825 4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
            margin-top: 5px;
        }

        .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 140, 117, 0.15);
            border-color: #FF6B4A;
        }

        .skills-container {
            position: relative;
            margin-top: 15px;
        }

        .skills-container::before {
            content: 'Выберите требуемые навыки';
            position: absolute;
            top: -25px;
            left: 0;
            font-size: 0.9rem;
            color: #718096;
            font-style: italic;
            background: #FFF9F7;
            padding: 0 10px;
        }

        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .skill-tag {
            background: white;
            border: 2px solid #FF8C75;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 0.95rem;
            color: #2C3E50;
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
            border-color: #FF6B4A;
        }

        .skill-tag.selected {
            background: linear-gradient(135deg, #FF8C75, #FF6B4A);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(255, 140, 117, 0.15);
        }

        .skill-tag.selected:hover {
            background: linear-gradient(135deg, #FF6B4A, #FF8C75);
            box-shadow: 0 6px 15px rgba(255, 140, 117, 0.2);
        }

        .btn-submit {
            background: linear-gradient(135deg, #FF8C75, #FF6B4A);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 140, 117, 0.15);
            width: 100%;
            max-width: 300px;
            margin: 30px auto 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .btn-submit:hover::before {
            transform: translateX(100%);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 140, 117, 0.2);
            background: linear-gradient(135deg, #FF6B4A, #FF8C75);
        }

        .error-alert {
            background: #FFF5F2;
            border: 1px solid #FF8C75;
            color: #C62828;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-alert i {
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .vacancy-container {
                padding: 20px;
                margin: 10px auto;
            }

            .page-title {
                font-size: 24px;
            }

            .form-section {
                padding: 15px;
            }

            .btn-submit {
                padding: 12px 24px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="vacancy-container">
        <h1 class="page-title">Создание вакансии</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-file-alt"></i>
                    Основная информация
                </h2>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-heading"></i>
                        Название вакансии
                    </label>
                    <input type="text" 
                           class="form-input" 
                           name="title" 
                           placeholder="Например: Senior Python Developer"
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i>
                        Описание
                    </label>
                    <textarea class="form-input" 
                              name="description" 
                              placeholder="Опишите требования к кандидату и условия работы"
                              required></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-tools"></i>
                    Требования
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-briefcase"></i>
                            Опыт работы
                        </label>
                        <select class="form-select" name="experience_id" required>
                            <option value="">Выберите опыт работы</option>
                            <?php foreach ($experiences as $experience): ?>
                                <option value="<?php echo $experience['id']; ?>">
                                    <?php echo htmlspecialchars($experience['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave"></i>
                            Зарплата
                        </label>
                        <select class="form-select" name="salary_id" required>
                            <option value="">Выберите зарплату</option>
                            <?php foreach ($salaries as $salary): ?>
                                <option value="<?php echo $salary['id']; ?>">
                                    <?php echo htmlspecialchars($salary['salary_range']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="skills-container">
                    <div class="skills-grid">
                        <?php foreach ($skills as $skill): ?>
                            <div class="skill-tag" 
                                 onclick="toggleSkill(this, <?php echo $skill['id']; ?>)"
                                 data-skill-id="<?php echo $skill['id']; ?>">
                                <i class="fas fa-check"></i>
                                <?php echo htmlspecialchars($skill['name']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i>
                Опубликовать вакансию
            </button>
        </form>
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
            const container = document.createElement('div');
            container.id = 'skills-inputs-container';
            document.querySelector('form').appendChild(container);
            return container;
        }

        // Добавляем валидацию формы
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const description = document.querySelector('textarea[name="description"]').value.trim();
            const experience = document.querySelector('select[name="experience_id"]').value;
            const salary = document.querySelector('select[name="salary_id"]').value;
            const skills = document.querySelectorAll('.skill-tag.selected');

            if (!title) {
                e.preventDefault();
                alert('Пожалуйста, введите название вакансии');
                return;
            }

            if (!description) {
                e.preventDefault();
                alert('Пожалуйста, введите описание вакансии');
                return;
            }

            if (!experience) {
                e.preventDefault();
                alert('Пожалуйста, выберите требуемый опыт');
                return;
            }

            if (!salary) {
                e.preventDefault();
                alert('Пожалуйста, выберите зарплату');
                return;
            }

            if (skills.length === 0) {
                e.preventDefault();
                alert('Пожалуйста, выберите хотя бы один навык');
                return;
            }
        });
    </script>
</body>
</html>