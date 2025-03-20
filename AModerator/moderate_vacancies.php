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
           u.name AS hr_name, e.name AS experience, s.salary_range
    FROM jobs j
    JOIN users u ON j.user_id = u.id
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
                    <th>HR</th>
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
                        <td><?= htmlspecialchars($job['hr_name']) ?></td>
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
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать вакансию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно удаления вакансии -->
    <div class="modal fade" id="deleteJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить вакансию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить эту вакансию?</p>
                    <form id="deleteJobForm">
                        <input type="hidden" name="job_id" id="deleteJobId">
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </form>
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