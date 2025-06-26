<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь и является ли он модератором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    header("Location: login.php");
    exit();
}

// Получаем список всех вакансий
$jobs_query = "
    SELECT j.id, j.title, j.description, j.status, j.created_at, 
           e.name AS experience, s.salary_range
    FROM jobs j
    JOIN experiences e ON j.experience_id = e.id
    JOIN salaries s ON j.salary_id = s.id
    ORDER BY j.created_at DESC
";
$jobs_result = $pdo->query($jobs_query);
$jobs = $jobs_result->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Модерация вакансий</title>
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

        .table {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.05);
            border: none;
            overflow: hidden;
            margin-top: 20px;
            width: 100%;
            table-layout: fixed;
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

        .table th:nth-child(1) { width: 5%; }  /* ID */
        .table th:nth-child(2) { width: 15%; } /* Название */
        .table th:nth-child(3) { width: 25%; } /* Описание */
        .table th:nth-child(4) { width: 10%; } /* Статус */
        .table th:nth-child(5) { width: 10%; } /* Опыт */
        .table th:nth-child(6) { width: 10%; } /* Зарплата */
        .table th:nth-child(7) { width: 10%; } /* Дата создания */
        .table th:nth-child(8) { width: 15%; } /* Действия */

        .table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid #F8F9FA;
            transition: all 0.3s ease;
            color: #2C3E50;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table td:nth-child(3) { /* Описание */
            white-space: normal;
            max-height: 100px;
            overflow-y: auto;
        }

        .table td:last-child {
            white-space: normal;
        }

        .actions-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .btn-action {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
            width: 100%;
            justify-content: center;
        }

        .btn-action i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .btn-action:hover {
            background: #FF3B2F;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .btn-action:hover i {
            transform: scale(1.2);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-delete {
            background: #dc3545;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }

        .btn-delete:hover {
            background: #c82333;
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
        }

        .text-success {
            color: #28a745 !important;
            font-weight: 600;
            padding: 8px 16px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 8px;
            display: inline-block;
        }

        .text-danger {
            color: #dc3545 !important;
            font-weight: 600;
            padding: 8px 16px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 8px;
            display: inline-block;
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

        .btn-close:before {
            content: '×';
            font-size: 28px;
            line-height: 1;
            font-weight: 300;
        }

        .btn-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
            opacity: 1;
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

            .table {
                display: block;
                overflow-x: auto;
            }

            .btn-action {
                width: 100%;
                margin-bottom: 10px;
            }

            .modal-dialog {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-briefcase"></i>Модерация вакансий</h1>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Статус</th>
                    <th>Опыт</th>
                    <th>Зарплата</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?= htmlspecialchars($job['id']) ?></td>
                        <td><?= htmlspecialchars($job['title']) ?></td>
                        <td><?= htmlspecialchars($job['description']) ?></td>
                        <td>
                            <?php if ($job['status'] === 'active'): ?>
                                <span class="text-success">Активна</span>
                            <?php else: ?>
                                <span class="text-danger">Закрыта</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($job['experience']) ?></td>
                        <td><?= htmlspecialchars($job['salary_range']) ?></td>
                        <td><?= htmlspecialchars($job['created_at']) ?></td>
                        <td>
                            <div class="d-flex flex-wrap">
                                <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editJobModal" data-job-id="<?= $job['id'] ?>" data-job-title="<?= htmlspecialchars($job['title']) ?>" data-job-description="<?= htmlspecialchars($job['description']) ?>">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <button class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#deleteJobModal" data-job-id="<?= $job['id'] ?>">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно редактирования вакансии -->
    <div class="modal fade" id="editJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i>
                        Редактирование вакансии
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editJobForm">
                        <input type="hidden" name="job_id" id="editJobId">
                        <div class="mb-3">
                            <label for="editJobTitle" class="form-label">Название</label>
                            <input type="text" class="form-control" id="editJobTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="editJobDescription" class="form-label">Описание</label>
                            <textarea class="form-control" id="editJobDescription" name="description" rows="5" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="editJobForm" class="btn-save">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно удаления вакансии -->
    <div class="modal fade" id="deleteJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash"></i>
                        Удаление вакансии
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="confirmation-text">
                        Вы действительно хотите удалить эту вакансию?
                    </div>
                    <form id="deleteJobForm">
                        <input type="hidden" name="job_id" id="deleteJobId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="deleteJobForm" class="btn-save" style="background: #dc3545;">Удалить</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Заполнение данных в модальном окне редактирования
        const editJobModal = document.getElementById('editJobModal');
        editJobModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const jobId = button.getAttribute('data-job-id');
            const jobTitle = button.getAttribute('data-job-title');
            const jobDescription = button.getAttribute('data-job-description');

            document.getElementById('editJobId').value = jobId;
            document.getElementById('editJobTitle').value = jobTitle;
            document.getElementById('editJobDescription').value = jobDescription;
        });

        // Заполнение данных в модальном окне удаления
        const deleteJobModal = document.getElementById('deleteJobModal');
        deleteJobModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const jobId = button.getAttribute('data-job-id');
            document.getElementById('deleteJobId').value = jobId;
        });

        // Обработка формы редактирования
        document.getElementById('editJobForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch('moderator_actions.php?action=edit_job', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });

        // Обработка формы удаления
        document.getElementById('deleteJobForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch('moderator_actions.php?action=delete_job', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>