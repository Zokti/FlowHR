<?php
ob_start();
require __DIR__ . '/../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth.php");
    ob_end_clean();
    exit;
}

// Инициализация данных
$errors = [];
$formData = [
    'title' => '',
    'description' => '',
    'visibility_duration' => 7,
    'salary_id' => '',
    'experience_id' => '',
    'skills' => []
];

$skills = [];
$salaries = [];
$experiences = [];

// Загрузка справочников
try {
    $stmt = $pdo->query("SELECT * FROM skills ORDER BY name");
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM salaries ORDER BY id");
    $salaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM experiences ORDER BY id");
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "Ошибка загрузки справочников: " . $e->getMessage();
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация данных
    $formData['title'] = trim($_POST['title'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['visibility_duration'] = (int)($_POST['visibility_duration'] ?? 7);
    $formData['salary_id'] = (int)($_POST['salary_id'] ?? 0);
    $formData['experience_id'] = (int)($_POST['experience_id'] ?? 0);
    
    // Обработка навыков
    $formData['skills'] = array_map('intval', $_POST['skills'] ?? []);

    // Валидация
    if (empty($formData['title'])) {
        $errors[] = "Название резюме обязательно";
    }

    if (!in_array($formData['visibility_duration'], [7, 14, 30])) {
        $errors[] = "Некорректный срок публикации";
    }

    if (empty($formData['salary_id'])) {
        $errors[] = "Выберите зарплату";
    }

    if (empty($formData['experience_id'])) {
        $errors[] = "Выберите опыт работы";
    }

    if (count($formData['skills']) === 0) {
        $errors[] = "Выберите минимум один навык";
    }

    // Проверка существования навыков
    if (!empty($formData['skills'])) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM skills WHERE id IN (" . 
                str_repeat('?,', count($formData['skills']) - 1) . "?)");
            $stmt->execute($formData['skills']);
            $existingSkills = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($existingSkills) !== count($formData['skills'])) {
                $errors[] = "Обнаружены несуществующие навыки";
            }
            
        } catch (PDOException $e) {
            $errors[] = "Ошибка проверки навыков: " . $e->getMessage();
        }
    }

    // Сохранение данных
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Сохранение резюме с is_published = 1
            $stmt = $pdo->prepare("INSERT INTO resumes 
                (title, description, visibility_duration, 
                 salary_id, experience_id, user_id, is_published)
                VALUES (?, ?, ?, ?, ?, ?, 1)");
                
            $stmt->execute([
                $formData['title'],
                $formData['description'],
                $formData['visibility_duration'],
                $formData['salary_id'],
                $formData['experience_id'],
                $_SESSION['user_id']
            ]);
            
            $resumeId = $pdo->lastInsertId();

            // Привязка навыков
            $stmt = $pdo->prepare("INSERT INTO resume_skills 
                (resume_id, skill_id) VALUES (?, ?)");
            
            foreach ($formData['skills'] as $skillId) {
                $stmt->execute([$resumeId, $skillId]);
            }

            $pdo->commit();
            
            // Редирект на страницу резюме
            ob_end_clean();
            header("Location: my_resume.php");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка сохранения: " . $e->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header.php';
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать резюме - FlowHR</title>
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
            margin-left: 250px;
            padding: 20px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .resume-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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

        .form-section {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .form-section:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }

        .form-section::before {
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

        .form-section:hover::before {
            opacity: 1;
        }

        .section-title {
            color: var(--text-color);
            font-size: 1.2rem;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-title i {
            color: var(--primary-color);
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 1rem;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.3s ease;
            background: var(--card-bg);
            resize: none;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.15);
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
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.3s ease;
            background: var(--card-bg);
            color: var(--text-color);
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23FF6F61' d='M6 8.825L1.175 4 2.05 3.125 6 7.075 9.95 3.125 10.825 4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.15);
        }

        .skills-container {
            position: relative;
            margin-top: 15px;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .skill-tag {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .skill-tag i {
            color: var(--primary-color);
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .skill-tag:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 97, 0.1);
        }

        .skill-tag.selected {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .skill-tag.selected i {
            opacity: 1;
            transform: scale(1);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
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
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .btn-submit:hover::before {
            transform: translateX(100%);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .error-alert {
            background: #FFF5F2;
            border: 1px solid var(--primary-color);
            color: var(--danger-color);
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

            .resume-container {
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

            .skills-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }

        .resume-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .resume-header h1 {
            color: #2C3E50;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .resume-header h1 i {
            color: #FF6F61;
            font-size: 32px;
            transition: all 0.3s ease;
        }

        .resume-header h1:hover i {
            transform: scale(1.2) rotate(15deg);
            color: #FF6F61;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .resume-header h1 i {
            animation: float 3s ease-in-out infinite;
        }

        .resume-header h1:hover i {
            animation: none;
        }
    </style>
</head>
<body>
    <div class="resume-container">
        <h1 class="page-title">
            <i class="fas fa-file-alt"></i>
            Создание резюме
        </h1>

        <?php if (!empty($errors)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-file-alt"></i>
                    Основная информация
                </h2>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-heading"></i>
                        Название резюме
                    </label>
                    <input type="text" 
                           class="form-input" 
                           name="title" 
                           value="<?= htmlspecialchars($formData['title']) ?>" 
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
                              placeholder="Опишите ваш опыт работы, достижения и профессиональные качества"><?= htmlspecialchars($formData['description']) ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-tools"></i>
                    Навыки
                </h2>
                
                <div class="skills-container">
                    <div class="skills-grid">
                        <?php foreach ($skills as $skill): ?>
                            <div class="skill-tag <?= in_array($skill['id'], $formData['skills']) ? 'selected' : '' ?>" 
                                 onclick="toggleSkill(this, <?= $skill['id'] ?>)"
                                 data-skill-id="<?= $skill['id'] ?>">
                                <i class="fas fa-check"></i>
                                <?= htmlspecialchars($skill['name']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-cog"></i>
                    Дополнительные настройки
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt"></i>
                            Срок публикации
                        </label>
                        <select class="form-select" name="visibility_duration" required>
                            <option value="7" <?= $formData['visibility_duration'] == 7 ? 'selected' : '' ?>>7 дней</option>
                            <option value="14" <?= $formData['visibility_duration'] == 14 ? 'selected' : '' ?>>14 дней</option>
                            <option value="30" <?= $formData['visibility_duration'] == 30 ? 'selected' : '' ?>>30 дней</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-briefcase"></i>
                            Опыт работы
                        </label>
                        <select class="form-select" name="experience_id" required>
                            <option value="">Выберите опыт</option>
                            <?php foreach ($experiences as $experience): ?>
                                <option value="<?= $experience['id'] ?>" 
                                    <?= $formData['experience_id'] == $experience['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($experience['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave"></i>
                            Желаемая зарплата
                        </label>
                        <select class="form-select" name="salary_id" required>
                            <option value="">Выберите зарплату</option>
                            <?php foreach ($salaries as $salary): ?>
                                <option value="<?= $salary['id'] ?>" 
                                    <?= $formData['salary_id'] == $salary['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($salary['salary_range']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i>
                Опубликовать резюме
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
    </script>
</body>
</html>