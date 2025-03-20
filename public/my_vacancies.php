<?php
session_start();
require '../includes/config.php';
require_once '../includes/header.php';

// Проверяем, авторизован ли пользователь и является ли он HR
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HR') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем данные для выпадающих списков и чекбоксов
$experiences = $pdo->query("SELECT * FROM experiences")->fetchAll();
$salaries = $pdo->query("SELECT * FROM salaries")->fetchAll();
$skills = $pdo->query("SELECT * FROM skills")->fetchAll();

// Обработка удаления вакансии
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job'])) {
    $job_id = $_POST['job_id'];
    $delete_query = "DELETE FROM jobs WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($delete_query);
    $stmt->execute([$job_id, $user_id]);
}

// Обработка редактирования вакансии
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_job'])) {
    $job_id = $_POST['job_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $experience_id = $_POST['experience_id'];
    $salary_id = $_POST['salary_id'];
    $skill_ids = implode(',', $_POST['skills'] ?? []);

    $update_query = "
        UPDATE jobs 
        SET title = ?, description = ?, experience_id = ?, salary_id = ?, skill_ids = ?
        WHERE id = ? AND user_id = ?
    ";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$title, $description, $experience_id, $salary_id, $skill_ids, $job_id, $user_id]);
}

// Получаем все вакансии текущего пользователя
$jobs_query = "
    SELECT j.id, j.title, j.description, j.status, j.experience_id, j.salary_id, j.skill_ids, 
           s.salary_range, e.name AS experience
    FROM jobs j
    JOIN salaries s ON j.salary_id = s.id
    JOIN experiences e ON j.experience_id = e.id
    WHERE j.user_id = ?
";
$jobs_stmt = $pdo->prepare($jobs_query);
$jobs_stmt->execute([$user_id]);
$jobs = $jobs_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои вакансии</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #FFFFFF; /* Белый фон страницы */
            padding: 20px;
            margin-left: 250px; /* Отступ для бокового меню */
        }
        .container {
            max-width: 1200px;
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
        .btn-primary {
            background-color: #FF6F61; /* Коралловый цвет */
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            transition: background-color 0.3s ease;
            display: block; /* Чтобы кнопка занимала всю ширину */
            margin: 0 auto 40px; /* Центрирование и отступ снизу */
        }
        .btn-primary:hover {
            background-color: #FF3B2F; /* Темно-коралловый при наведении */
        }
        .job-card {
            background: #FFFFFF; /* Фон карточки */
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Тень карточки */
            border: 1px solid #E0E0E0; /* Граница карточки */
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Анимация */
            animation: fadeInUp 0.8s ease-in-out; /* Анимация появления */
        }
        .job-card:hover {
            transform: translateY(-5px); /* Поднимаем карточку при наведении */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); /* Увеличиваем тень */
        }
        .job-card h3 {
            color: #2C3E50; /* Основной текст */
            margin-top: 0;
        }
        .job-card p {
            color: #666666; /* Второстепенный текст */
            margin: 10px 0;
        }
        .btn-edit, .btn-delete {
            margin-right: 10px;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-edit {
            background-color: #FF6F61; /* Коралловый цвет для редактирования */
            color: #FFFFFF; /* Белый текст */
        }
        .btn-edit:hover {
            background-color: #FF3B2F; /* Темно-коралловый при наведении */
        }
        .btn-delete {
            background-color: #DC3545; /* Красный для удаления */
            color: #FFFFFF;
        }
        .btn-delete:hover {
            background-color: #C82333; /* Темнее красный при наведении */
        }
        .modal-content {
            background: #FFFFFF; /* Фон модального окна */
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            background: #FF6F61; /* Акцентный цвет для заголовка модального окна */
            color: #FFFFFF;
            border-radius: 10px 10px 0 0;
        }
        .modal-title {
            font-size: 18px;
        }
        .modal-body {
            padding: 20px;
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
        }
        .form-check-input {
            margin-right: 10px;
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

        /* Задержка для анимации карточек */
        .job-card:nth-child(1) { animation-delay: 0.1s; }
        .job-card:nth-child(2) { animation-delay: 0.2s; }
        .job-card:nth-child(3) { animation-delay: 0.3s; }
        .job-card:nth-child(4) { animation-delay: 0.4s; }
        .job-card:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Мои вакансии</h1>
        <a href="create_vacancy.php" class="btn btn-primary">Создать новую вакансию</a>

        <?php foreach ($jobs as $job): ?>
            <?php
            // Преобразуем skill_ids в массив названий навыков
            $skill_ids = explode(',', $job['skill_ids']);
            $job_skills = array_map(function ($skill_id) use ($skills) {
                return $skills[$skill_id - 1]['name']; // Индексация навыков начинается с 0
            }, $skill_ids);
            ?>
            <div class="job-card">
                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                <p><?php echo htmlspecialchars($job['description']); ?></p>
                <p><strong>Зарплата:</strong> <?php echo htmlspecialchars($job['salary_range']); ?></p>
                <p><strong>Опыт работы:</strong> <?php echo htmlspecialchars($job['experience']); ?></p>
                <p><strong>Навыки:</strong> <?php echo implode(', ', $job_skills); ?></p>
                <p><strong>Статус:</strong> <?php echo $job['status'] == 'active' ? 'Активна' : 'Закрыта'; ?></p>

                <!-- Кнопки управления -->
                <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $job['id']; ?>">Редактировать</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                    <button type="submit" name="delete_job" class="btn btn-delete">Удалить</button>
                </form>

                <!-- Модальное окно для редактирования -->
                <div class="modal fade" id="editModal<?php echo $job['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $job['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel<?php echo $job['id']; ?>">Редактирование вакансии</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <div class="mb-3">
                                        <label for="title<?php echo $job['id']; ?>" class="form-label">Название</label>
                                        <input type="text" class="form-control" id="title<?php echo $job['id']; ?>" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description<?php echo $job['id']; ?>" class="form-label">Описание</label>
                                        <textarea class="form-control" id="description<?php echo $job['id']; ?>" name="description" rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="experience_id<?php echo $job['id']; ?>" class="form-label">Опыт работы</label>
                                        <select class="form-select" id="experience_id<?php echo $job['id']; ?>" name="experience_id" required>
                                            <?php foreach ($experiences as $experience): ?>
                                                <option value="<?php echo $experience['id']; ?>" <?php echo $experience['id'] == $job['experience_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($experience['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="salary_id<?php echo $job['id']; ?>" class="form-label">Зарплата</label>
                                        <select class="form-select" id="salary_id<?php echo $job['id']; ?>" name="salary_id" required>
                                            <?php foreach ($salaries as $salary): ?>
                                                <option value="<?php echo $salary['id']; ?>" <?php echo $salary['id'] == $job['salary_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($salary['salary_range']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Навыки</label>
                                        <?php foreach ($skills as $skill): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="skills[]" value="<?php echo $skill['id']; ?>" id="skill<?php echo $skill['id']; ?>_<?php echo $job['id']; ?>" <?php echo in_array($skill['id'], $skill_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="skill<?php echo $skill['id']; ?>_<?php echo $job['id']; ?>">
                                                    <?php echo htmlspecialchars($skill['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit" name="edit_job" class="btn btn-primary">Сохранить изменения</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>