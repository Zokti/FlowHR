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
            background: #F8F9FA;
            color: #2C3E50;
            padding: 30px;
            margin-left: 250px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
            background: #FFFFFF;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(44, 62, 80, 0.08);
            animation: fadeIn 0.5s ease;
        }

        h1 {
            color: #2C3E50;
            text-align: center;
            margin: 0 0 40px;
            font-size: 42px;
            font-weight: 800;
            position: relative;
            padding-bottom: 20px;
            animation: slideDown 0.5s ease;
        }

        h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 4px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            border-radius: 2px;
        }

        h1 i {
            margin-right: 15px;
            color: #FF6F61;
            animation: rotateIn 0.5s ease;
        }

        h2 {
            color: #2C3E50;
            font-size: 28px;
            font-weight: 700;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F8F9FA;
        }

        h2 i {
            color: #FF6F61;
            margin-right: 10px;
        }

        .table {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.05);
            border: none;
            overflow: hidden;
            margin-top: 20px;
            width: 100%;
        }

        .table th {
            background: #F8F9FA;
            color: #2C3E50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            padding: 20px;
            border-bottom: 2px solid #F8F9FA;
            white-space: nowrap;
        }

        .table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid #F8F9FA;
            transition: all 0.3s ease;
            color: #2C3E50;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table tr:hover td {
            background: #F8F9FA;
        }

        .table td:last-child {
            min-width: 300px;
            max-width: 300px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F8F9FA;
        }

        .section-title {
            color: #2C3E50;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #FF6F61;
        }

        .btn-add {
            background: #28a745;
            color: #FFFFFF;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }

        .btn-add i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .btn-add:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }

        .btn-add:hover i {
            transform: scale(1.2);
        }

        .btn-add:active {
            transform: translateY(0);
        }

        .actions-container {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.1);
            min-width: 140px;
            justify-content: center;
        }

        .btn-action i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .btn-action:hover i {
            transform: scale(1.2);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.2);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-edit {
            background: #FF6F61;
            color: #FFFFFF;
        }

        .btn-edit:hover {
            background: #FF3B2F;
        }

        .btn-delete {
            background: #dc3545;
            color: #FFFFFF;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        /* Стили для модальных окон */
        .modal-content {
            border: none;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(44, 62, 80, 0.15);
            overflow: hidden;
            animation: modalSlideIn 0.4s ease;
        }

        .modal-header {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 30px;
            border: none;
            position: relative;
        }

        .modal-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0.5), rgba(255,255,255,0.1));
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(44, 62, 80, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .modal-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            font-weight: 400;
        }

        .btn-close {
            position: absolute;
            right: 20px;
            top: 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
            color: #FFFFFF;
            font-size: 20px;
            opacity: 0.8;
            cursor: pointer;
            z-index: 1;
        }

        .btn-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
            opacity: 1;
        }

        .btn-close:before {
            content: '×';
            font-size: 28px;
            line-height: 1;
            font-weight: 300;
        }

        .btn-close:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(255,255,255,0.2);
        }

        .modal-body {
            padding: 35px;
            background: #F8F9FA;
        }

        .modal-footer {
            padding: 25px 35px;
            background: #F8F9FA;
            border-top: 1px solid rgba(44, 62, 80, 0.1);
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .form-label {
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .form-control, .form-select {
            padding: 15px 20px;
            border: 2px solid #F8F9FA;
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #FFFFFF;
            width: 100%;
            color: #2C3E50;
        }

        .form-control:focus, .form-select:focus {
            border-color: #FF6F61;
            box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
            transform: translateY(-2px);
        }

        .btn-save {
            background: #4CAF50;
            color: #FFFFFF;
            padding: 12px 35px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
            min-width: 180px;
        }

        .btn-save:hover {
            background: #43A047;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
        }

        .btn-save:active {
            transform: translateY(0);
        }

        .btn-cancel {
            background: #F8F9FA;
            color: #2C3E50;
            padding: 12px 35px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            border: 2px solid #E9ECEF;
            transition: all 0.3s ease;
            min-width: 180px;
        }

        .btn-cancel:hover {
            background: #E9ECEF;
            transform: translateY(-2px);
        }

        .btn-cancel:active {
            transform: translateY(0);
        }

        .confirmation-text {
            text-align: center;
            color: #2C3E50;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .confirmation-text strong {
            color: #FF6F61;
            font-weight: 600;
        }

        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes rotateIn {
            from {
                opacity: 0;
                transform: rotate(-180deg);
            }
            to {
                opacity: 1;
                transform: rotate(0);
            }
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }

            .container {
                padding: 20px;
            }

            h1 {
                font-size: 32px;
            }

            h2 {
                font-size: 24px;
            }

            .table {
                display: block;
                overflow-x: auto;
            }

            .actions-container {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-cogs"></i>Управление справочными данными</h1>

        <!-- Таблица навыков -->
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-tools"></i> Навыки</h2>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addSkillModal">
                <i class="fas fa-plus"></i> Добавить навык
            </button>
        </div>
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
                            <div class="actions-container">
                                <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#editSkillModal" data-skill-id="<?= $skill['id'] ?>" data-skill-name="<?= htmlspecialchars($skill['name']) ?>">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteSkillModal" data-skill-id="<?= $skill['id'] ?>">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Таблица зарплат -->
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-money-bill-wave"></i> Зарплаты</h2>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addSalaryModal">
                <i class="fas fa-plus"></i> Добавить зарплату
            </button>
        </div>
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
                            <div class="actions-container">
                                <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#editSalaryModal" data-salary-id="<?= $salary['id'] ?>" data-salary-range="<?= htmlspecialchars($salary['salary_range']) ?>">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteSalaryModal" data-salary-id="<?= $salary['id'] ?>">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Таблица опыта -->
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-user-tie"></i> Опыт работы</h2>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addExperienceModal">
                <i class="fas fa-plus"></i> Добавить опыт
            </button>
        </div>
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
                            <div class="actions-container">
                                <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#editExperienceModal" data-experience-id="<?= $experience['id'] ?>" data-experience-name="<?= htmlspecialchars($experience['name']) ?>">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteExperienceModal" data-experience-id="<?= $experience['id'] ?>">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальные окна для навыков -->
    <!-- Модальное окно добавления навыка -->
    <div class="modal fade" id="addSkillModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i>
                        Добавление навыка
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSkillForm" action="admin_actions.php?action=add_skill" method="POST">
                        <div class="mb-3">
                            <label for="skillName" class="form-label">Название навыка</label>
                            <input type="text" class="form-control" id="skillName" name="name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="addSkillForm" class="btn-save">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования навыка -->
    <div class="modal fade" id="editSkillModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i>
                        Редактирование навыка
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editSkillForm" action="admin_actions.php?action=edit_skill" method="POST">
                        <input type="hidden" id="editSkillId" name="id">
                        <div class="mb-3">
                            <label for="editSkillName" class="form-label">Название навыка</label>
                            <input type="text" class="form-control" id="editSkillName" name="name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="editSkillForm" class="btn-save">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно удаления навыка -->
    <div class="modal fade" id="deleteSkillModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash"></i>
                        Удаление навыка
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="confirmation-text">
                        Вы действительно хотите удалить навык <strong id="deleteSkillName"></strong>?
                    </div>
                    <form id="deleteSkillForm" action="admin_actions.php?action=delete_skill" method="POST">
                        <input type="hidden" id="deleteSkillId" name="id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="deleteSkillForm" class="btn-save" style="background: #dc3545;">Удалить</button>
                </div>
            </div>
        </div>
    </div>

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

    document.addEventListener('DOMContentLoaded', function() {
        // Обработка модального окна редактирования навыка
        const editSkillModal = document.getElementById('editSkillModal');
        if (editSkillModal) {
            editSkillModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const skillId = button.getAttribute('data-skill-id');
                const skillName = button.getAttribute('data-skill-name');

                document.getElementById('editSkillId').value = skillId;
                document.getElementById('editSkillName').value = skillName;
            });
        }

        // Обработка модального окна удаления навыка
        const deleteSkillModal = document.getElementById('deleteSkillModal');
        if (deleteSkillModal) {
            deleteSkillModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const skillId = button.getAttribute('data-skill-id');
                const skillName = button.getAttribute('data-skill-name');

                document.getElementById('deleteSkillId').value = skillId;
                document.getElementById('deleteSkillName').textContent = skillName;
            });
        }
    });
    </script>
</body>
</html>