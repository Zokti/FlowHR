<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь и является ли он модератором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    header("Location: login.php");
    exit();
}

// Получаем список кандидатов
$candidates_query = "SELECT id, name, email, login, is_blocked, blocked_until FROM users WHERE role = 'candidate'";
$candidates_result = $pdo->query($candidates_query);
$candidates = $candidates_result->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Модерация кандидатов</title>
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
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn-action:last-child {
            margin-right: 0;
            margin-bottom: 0;
        }

        .btn-action i {
            margin-right: 8px;
        }

        .btn-action:hover {
            background: #FF3B2F;
        }

        .btn-block {
            background: #ffc107;
        }

        .btn-block:hover {
            background: #e0a800;
        }

        .btn-unblock {
            background: #28a745;
        }

        .btn-unblock:hover {
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
        <h1><i class="fas fa-users-cog"></i>Модерация кандидатов</h1>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Логин</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidates as $candidate): ?>
                    <tr>
                        <td><?= htmlspecialchars($candidate['id']) ?></td>
                        <td><?= htmlspecialchars($candidate['name']) ?></td>
                        <td><?= htmlspecialchars($candidate['email']) ?></td>
                        <td><?= htmlspecialchars($candidate['login']) ?></td>
                        <td>
                            <?php if ($candidate['is_blocked']): ?>
                                <span class="text-danger">Заблокирован до <?= htmlspecialchars($candidate['blocked_until']) ?></span>
                            <?php else: ?>
                                <span class="text-success">Активен</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap">
                                <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editCandidateModal" data-candidate-id="<?= $candidate['id'] ?>" data-candidate-name="<?= htmlspecialchars($candidate['name']) ?>" data-candidate-email="<?= htmlspecialchars($candidate['email']) ?>">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <?php if ($candidate['is_blocked']): ?>
                                    <button class="btn-action btn-unblock" data-bs-toggle="modal" data-bs-target="#unblockCandidateModal" data-candidate-id="<?= $candidate['id'] ?>">
                                        <i class="fas fa-unlock"></i> Разблокировать
                                    </button>
                                <?php else: ?>
                                    <button class="btn-action btn-block" data-bs-toggle="modal" data-bs-target="#blockCandidateModal" data-candidate-id="<?= $candidate['id'] ?>">
                                        <i class="fas fa-lock"></i> Заблокировать
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно редактирования кандидата -->
    <div class="modal fade" id="editCandidateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать кандидата</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCandidateForm">
                        <input type="hidden" name="candidate_id" id="editCandidateId">
                        <div class="mb-3">
                            <label for="editCandidateName" class="form-label">Имя</label>
                            <input type="text" class="form-control" id="editCandidateName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCandidateEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editCandidateEmail" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно блокировки кандидата -->
    <div class="modal fade" id="blockCandidateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-lock"></i> Заблокировать кандидата</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="blockCandidateForm">
                        <input type="hidden" name="candidate_id" id="blockCandidateId">
                        <div class="mb-3">
                            <label for="blockDuration" class="form-label">Выберите срок блокировки</label>
                            <select class="form-select" id="blockDuration" name="duration" required>
                                <option value="1 hour">1 час</option>
                                <option value="12 hours">12 часов</option>
                                <option value="1 day">1 день</option>
                                <option value="1 week">1 неделя</option>
                                <option value="1 month">1 месяц</option>
                                <option value="forever">Вечная блокировка</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning">Заблокировать</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно разблокировки кандидата -->
    <div class="modal fade" id="unblockCandidateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-unlock"></i> Разблокировать кандидата</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите разблокировать этого кандидата?</p>
                    <form id="unblockCandidateForm">
                        <input type="hidden" name="candidate_id" id="unblockCandidateId">
                        <button type="submit" class="btn btn-success">Разблокировать</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Обработка модальных окон
    const editCandidateModal = document.getElementById('editCandidateModal');
    const blockCandidateModal = document.getElementById('blockCandidateModal');
    const unblockCandidateModal = document.getElementById('unblockCandidateModal');

    // Заполнение данных в модальном окне редактирования
    editCandidateModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const candidateId = button.getAttribute('data-candidate-id');
        const candidateName = button.getAttribute('data-candidate-name');
        const candidateEmail = button.getAttribute('data-candidate-email');

        document.getElementById('editCandidateId').value = candidateId;
        document.getElementById('editCandidateName').value = candidateName;
        document.getElementById('editCandidateEmail').value = candidateEmail;
    });

    blockCandidateModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const candidateId = button.getAttribute('data-candidate-id');
        document.getElementById('blockCandidateId').value = candidateId;
    });

    unblockCandidateModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const candidateId = button.getAttribute('data-candidate-id');
        document.getElementById('unblockCandidateId').value = candidateId;
    });

    // Обработка формы редактирования кандидата
    document.getElementById('editCandidateForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Предотвращаем стандартное поведение формы
        const formData = new FormData(this);

        fetch('moderator_actions.php?action=edit_candidate', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload(); // Перезагружаем страницу после успешного обновления
            } else {
                console.error('Ошибка при редактировании кандидата');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
        });
    });

    // Обработка формы блокировки кандидата
    document.getElementById('blockCandidateForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Предотвращаем стандартное поведение формы
        const formData = new FormData(this);

        fetch('moderator_actions.php?action=block_candidate', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload(); // Перезагружаем страницу после успешной блокировки
            } else {
                console.error('Ошибка при блокировке кандидата');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
        });
    });

    // Обработка формы разблокировки кандидата
    document.getElementById('unblockCandidateForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Предотвращаем стандартное поведение формы
        const formData = new FormData(this);

        fetch('moderator_actions.php?action=unblock_candidate', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload(); // Перезагружаем страницу после успешной разблокировки
            } else {
                console.error('Ошибка при разблокировке кандидата');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
        });
    });
</script>
</body>
</html>