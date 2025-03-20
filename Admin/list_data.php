<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь и является ли он админом
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

// Получаем данные из таблиц
$skills = $pdo->query("SELECT * FROM skills")->fetchAll();
$salaries = $pdo->query("SELECT * FROM salaries")->fetchAll();
$experiences = $pdo->query("SELECT * FROM experiences")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление справочными данными</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #FFFFFF;
            color: #2C3E50;
            padding: 20px;
            margin-left: 250px; /* Отступ для бокового меню */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #2C3E50;
            text-align: center;
            margin: 40px 0;
            font-size: 36px;
            font-weight: bold;
            animation: fadeInUp 0.5s ease-in-out;
        }

        h1 i {
            margin-right: 10px;
        }

        .table {
            background: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #E0E0E0;
        }

        .table th, .table td {
            vertical-align: middle;
        }

        .btn-action {
            background: #FF6F61;
            color: #FFFFFF;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px; /* Горизонтальный отступ между кнопками */
            margin-bottom: 10px; /* Вертикальный отступ между кнопками */
        }

        .btn-action:last-child {
            margin-right: 0; /* Убираем горизонтальный отступ у последней кнопки */
            margin-bottom: 0; /* Убираем вертикальный отступ у последней кнопки */
        }

        .btn-action i {
            margin-right: 8px;
        }

        .btn-action:hover {
            background: #FF3B2F;
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .btn-status {
            background: #28a745;
        }

        .btn-status:hover {
            background: #218838;
        }

        /* Стили для модальных окон */
        .modal-header {
            background: #FF6F61;
            color: #FFFFFF;
            border-radius: 15px 15px 0 0;
        }

        .modal-title {
            font-size: 18px;
        }

        .modal-body {
            padding: 20px;
        }

        .form-select, .form-control {
            border-radius: 5px;
            border: 1px solid #E0E0E0;
            padding: 8px;
            margin-bottom: 10px;
            transition: border-color 0.3s ease;
        }

        .form-select:focus, .form-control:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 5px rgba(255, 111, 97, 0.5);
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

        /* Убираем подчеркивание у всех ссылок */
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-cogs"></i>Управление справочными данными</h1>

        <!-- Таблица навыков -->
        <h2><i class="fas fa-tools"></i> Навыки</h2>
        <button class="btn-action" data-bs-toggle="modal" data-bs-target="#addSkillModal">
            <i class="fas fa-plus"></i> Добавить навык
        </button>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($skills as $skill): ?>
                    <tr>
                        <td><?= htmlspecialchars($skill['id']) ?></td>
                        <td><?= htmlspecialchars($skill['name']) ?></td>
                        <td>
                            <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editSkillModal" data-skill-id="<?= $skill['id'] ?>" data-skill-name="<?= htmlspecialchars($skill['name']) ?>">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                            <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteSkillModal" data-skill-id="<?= $skill['id'] ?>">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Таблица зарплат -->
        <h2><i class="fas fa-money-bill-wave"></i> Зарплаты</h2>
        <button class="btn-action" data-bs-toggle="modal" data-bs-target="#addSalaryModal">
            <i class="fas fa-plus"></i> Добавить зарплату
        </button>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Диапазон зарплат</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($salaries as $salary): ?>
                    <tr>
                        <td><?= htmlspecialchars($salary['id']) ?></td>
                        <td><?= htmlspecialchars($salary['salary_range']) ?></td>
                        <td>
                            <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editSalaryModal" data-salary-id="<?= $salary['id'] ?>" data-salary-range="<?= htmlspecialchars($salary['salary_range']) ?>">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                            <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteSalaryModal" data-salary-id="<?= $salary['id'] ?>">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Таблица опыта -->
        <h2><i class="fas fa-user-tie"></i> Опыт работы</h2>
        <button class="btn-action" data-bs-toggle="modal" data-bs-target="#addExperienceModal">
            <i class="fas fa-plus"></i> Добавить опыт
        </button>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($experiences as $experience): ?>
                    <tr>
                        <td><?= htmlspecialchars($experience['id']) ?></td>
                        <td><?= htmlspecialchars($experience['name']) ?></td>
                        <td>
                            <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editExperienceModal" data-experience-id="<?= $experience['id'] ?>" data-experience-name="<?= htmlspecialchars($experience['name']) ?>">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                            <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteExperienceModal" data-experience-id="<?= $experience['id'] ?>">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальные окна для навыков -->
    <?php include 'modals/skills_modals.php'; ?>

    <!-- Модальные окна для зарплат -->
    <?php include 'modals/salaries_modals.php'; ?>

    <!-- Модальные окна для опыта -->
    <?php include 'modals/experiences_modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

     <script>
    // Заполнение модальных окон данными при открытии
    document.addEventListener('DOMContentLoaded', function () {
        // Для тестов
        const editTestModal = document.getElementById('editTestModal');
        if (editTestModal) {
            editTestModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const testId = button.getAttribute('data-test-id');
                const testTitle = button.getAttribute('data-test-title');
                const testTimeLimit = button.getAttribute('data-test-time-limit');

                document.getElementById('editTestId').value = testId;
                document.getElementById('editTestTitle').value = testTitle;
                document.getElementById('editTestTimeLimit').value = testTimeLimit;
            });
        }

        // Для вопросов
        const editQuestionModal = document.getElementById('editQuestionModal');
        if (editQuestionModal) {
            editQuestionModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const questionId = button.getAttribute('data-question-id');
                const questionText = button.getAttribute('data-question-text');
                const testId = button.getAttribute('data-test-id');

                document.getElementById('editQuestionId').value = questionId;
                document.getElementById('editQuestionText').value = questionText;
                document.getElementById('editQuestionTestId').value = testId;
            });
        }

        // Для ответов
        const editAnswerModal = document.getElementById('editAnswerModal');
        if (editAnswerModal) {
            editAnswerModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const answerId = button.getAttribute('data-answer-id');
                const answerText = button.getAttribute('data-answer-text');
                const questionId = button.getAttribute('data-question-id');
                const isCorrect = button.getAttribute('data-is-correct');

                document.getElementById('editAnswerId').value = answerId;
                document.getElementById('editAnswerText').value = answerText;
                document.getElementById('editAnswerQuestionId').value = questionId;
                document.getElementById('editAnswerIsCorrect').checked = isCorrect === '1';
            });
        }
    });

    // Функция для удаления данных
    function deleteItem(url, formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const formData = new FormData(form);

                fetch(url, {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) {
                        location.reload(); // Перезагружаем страницу после успешного удаления
                    }
                });
            });
        }
    }

    // Инициализация удаления для каждой таблицы
    deleteItem('admin_actions.php?action=delete_test', 'deleteTestForm');
    deleteItem('admin_actions.php?action=delete_question', 'deleteQuestionForm');
    deleteItem('admin_actions.php?action=delete_answer', 'deleteAnswerForm');

    // Функция для редактирования данных
    function editItem(url, formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const formData = new FormData(form);

                fetch(url, {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) {
                        location.reload(); // Перезагружаем страницу после успешного редактирования
                    }
                });
            });
        }
    }

    // Инициализация редактирования для каждой таблицы
    editItem('admin_actions.php?action=edit_test', 'editTestForm');
    editItem('admin_actions.php?action=edit_question', 'editQuestionForm');
    editItem('admin_actions.php?action=edit_answer', 'editAnswerForm');
    </script>
</body>
</html>