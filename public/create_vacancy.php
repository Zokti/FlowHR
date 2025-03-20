<?php
session_start();
require '../includes/config.php';
require_once '../includes/header.php';

// Проверяем, авторизован ли пользователь и является ли он HR
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HR') {
    header("Location: login.php");
    exit();
}

// Получаем данные для выпадающих списков и чекбоксов
$experiences = $pdo->query("SELECT * FROM experiences")->fetchAll();
$salaries = $pdo->query("SELECT * FROM salaries")->fetchAll();
$skills = $pdo->query("SELECT * FROM skills")->fetchAll();

// Обработка формы создания вакансии
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $experience_id = $_POST['experience_id'];
    $salary_id = $_POST['salary_id'];
    $skill_ids = implode(',', $_POST['skills'] ?? []); // Преобразуем массив навыков в строку

    // Вставляем новую вакансию в базу данных
    $insert_query = "
        INSERT INTO jobs (title, description, user_id, experience_id, salary_id, skill_ids)
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    $stmt = $pdo->prepare($insert_query);
    $stmt->execute([$title, $description, $_SESSION['user_id'], $experience_id, $salary_id, $skill_ids]);

    // Перенаправляем на страницу с вакансиями
    header("Location: my_vacancies.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание вакансии</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #FFFFFF; /* Белый фон страницы */
            padding: 20px;
            margin-left: 250px; /* Отступ для бокового меню */
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #FF6F61; /* Коралловый цвет для заголовка */
            text-align: center; /* Заголовок по центру */
            margin: 40px 0; /* Отступ сверху и снизу */
            font-size: 36px; /* Увеличенный размер текста */
            font-weight: bold; /* Жирный шрифт */
            animation: fadeInUp 0.5s ease-in-out; /* Анимация появления */
        }
        .form-container {
            background: #FFFFFF; /* Фон формы */
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Тень формы */
            border: 1px solid #E0E0E0; /* Граница формы */
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Анимация */
            animation: fadeInUp 0.8s ease-in-out; /* Анимация появления */
        }
        .form-container:hover {
            transform: translateY(-5px); /* Поднимаем форму при наведении */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); /* Увеличиваем тень */
        }
        .form-label {
            color: #2C3E50; /* Основной текст */
            font-weight: bold;
        }
        .form-control, .form-select {
            border-radius: 5px;
            border: 1px solid #E0E0E0; /* Граница для полей ввода */
            padding: 8px;
            margin-bottom: 10px;
            transition: border-color 0.3s ease; /* Анимация для полей ввода */
        }
        .form-control:focus, .form-select:focus {
            border-color: #FF6F61; /* Акцентный цвет при фокусе */
            box-shadow: 0 0 5px rgba(255, 111, 97, 0.5); /* Тень при фокусе */
        }
        .btn-save {
            background-color: #FF6F61; /* Коралловый цвет */
            color: #FFFFFF; /* Белый текст */
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease; /* Анимация */
            display: block; /* Чтобы кнопка занимала всю ширину */
            margin: 20px auto 0; /* Центрирование и отступ сверху */
        }
        .btn-save:hover {
            background-color: #FF3B2F; /* Темно-коралловый при наведении */
        }
        .form-check-input {
            margin-right: 10px;
        }
        .form-check-label {
            color: #666666; /* Второстепенный текст */
        }

        /* Анимация появления */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Создание вакансии</h1>
        <div class="form-container">
            <form method="POST">
                <!-- Название вакансии -->
                <div class="mb-3">
                    <label for="title" class="form-label">Название вакансии</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>

                <!-- Описание вакансии -->
                <div class="mb-3">
                    <label for="description" class="form-label">Описание вакансии</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                </div>

                <!-- Опыт работы -->
                <div class="mb-3">
                    <label for="experience_id" class="form-label">Опыт работы</label>
                    <select class="form-select" id="experience_id" name="experience_id" required>
                        <option value="">Выберите опыт работы</option>
                        <?php foreach ($experiences as $experience): ?>
                            <option value="<?php echo $experience['id']; ?>"><?php echo htmlspecialchars($experience['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Зарплата -->
                <div class="mb-3">
                    <label for="salary_id" class="form-label">Зарплата</label>
                    <select class="form-select" id="salary_id" name="salary_id" required>
                        <option value="">Выберите зарплату</option>
                        <?php foreach ($salaries as $salary): ?>
                            <option value="<?php echo $salary['id']; ?>"><?php echo htmlspecialchars($salary['salary_range']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Навыки -->
                <div class="mb-3">
                    <label class="form-label">Навыки</label>
                    <div>
                        <?php foreach ($skills as $skill): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="skills[]" value="<?php echo $skill['id']; ?>" id="skill<?php echo $skill['id']; ?>">
                                <label class="form-check-label" for="skill<?php echo $skill['id']; ?>">
                                    <?php echo htmlspecialchars($skill['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Кнопка сохранения -->
                <button type="submit" class="btn-save">Создать вакансию</button>
            </form>
        </div>
    </div>
</body>
</html>