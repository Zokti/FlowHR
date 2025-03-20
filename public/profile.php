<?php
ob_start(); // Включаем буферизацию вывода
session_start();
require_once "../includes/config.php";
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    ob_end_flush(); // Очищаем буфер
    header("Location: login.php");
    exit();
}

// Остальной код
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Пользователь не найден.");
}

// Загрузка статистики
$total_applications = $pending_count = $accepted_count = $rejected_count = 0;
$total_jobs = $hr_applications = $hr_pending = $hr_accepted = $hr_rejected = 0;

// Общая статистика
$query = $pdo->query("SELECT COUNT(*) as total_applications FROM applications");
$total_applications = $query->fetch()['total_applications'];

$query_pending = $pdo->query("SELECT COUNT(*) as pending FROM applications WHERE status = 'pending'");
$pending_count = $query_pending->fetch()['pending'];

$query_accepted = $pdo->query("SELECT COUNT(*) as accepted FROM applications WHERE status = 'accepted'");
$accepted_count = $query_accepted->fetch()['accepted'];

$query_rejected = $pdo->query("SELECT COUNT(*) as rejected FROM applications WHERE status = 'rejected'");
$rejected_count = $query_rejected->fetch()['rejected'];

// Если HR, считаем количество его вакансий и откликов
if ($user['role'] == 'HR') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_jobs FROM jobs WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_jobs = $stmt->fetch()['total_jobs'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as hr_applications 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $hr_applications = $stmt->fetch()['hr_applications'];

    $stmt_pending = $pdo->prepare("
        SELECT COUNT(*) as hr_pending 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.user_id = ? AND applications.status = 'pending'
    ");
    $stmt_pending->execute([$user_id]);
    $hr_pending = $stmt_pending->fetch()['hr_pending'];

    $stmt_accepted = $pdo->prepare("
        SELECT COUNT(*) as hr_accepted 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.user_id = ? AND applications.status = 'accepted'
    ");
    $stmt_accepted->execute([$user_id]);
    $hr_accepted = $stmt_accepted->fetch()['hr_accepted'];

    $stmt_rejected = $pdo->prepare("
        SELECT COUNT(*) as hr_rejected 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.user_id = ? AND applications.status = 'rejected'
    ");
    $stmt_rejected->execute([$user_id]);
    $hr_rejected = $stmt_rejected->fetch()['hr_rejected'];
}

// Статистика для админа и модератора
$total_users = $pdo->query("SELECT COUNT(*) as total_users FROM users")->fetch()['total_users'];
$total_hr = $pdo->query("SELECT COUNT(*) as total_hr FROM users WHERE role = 'HR'")->fetch()['total_hr'];
$total_candidates = $pdo->query("SELECT COUNT(*) as total_candidates FROM users WHERE role = 'candidate'")->fetch()['total_candidates'];
$total_jobs_all = $pdo->query("SELECT COUNT(*) as total_jobs_all FROM jobs")->fetch()['total_jobs_all'];
$total_blocked_users = $pdo->query("SELECT COUNT(*) as total_blocked_users FROM users WHERE is_blocked = TRUE")->fetch()['total_blocked_users'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка удаления аватарки
    if (isset($_POST['delete_avatar']) && $_POST['delete_avatar'] == '1') {
        $stmt = $pdo->prepare("UPDATE users SET avatar = 'default_avatar.png' WHERE id = ?");
        $stmt->execute([$user_id]);
        if ($user['avatar'] !== 'default_avatar.png') {
            $avatar_path = "../uploads/avatars/" . $user['avatar'];
            if (file_exists($avatar_path)) {
                unlink($avatar_path);
            }
        }
        header("Location: profile.php");
        exit();
    }

    // Обработка обновления данных профиля
    if (isset($_POST['update_data'])) {
        $login = $_POST['login'];
        $password = $_POST['password'];
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET login = ?, password = ? WHERE id = ?");
            $stmt->execute([$login, $password_hash, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET login = ? WHERE id = ?");
            $stmt->execute([$login, $user_id]);
        }
    }

    // Обработка загрузки новой аватарки
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar = $_FILES['avatar'];
        $target_dir = "../uploads/avatars/";
        $avatar_name = $user_id . "_" . basename($avatar["name"]);
        $target_file = $target_dir . $avatar_name;
        $check = getimagesize($avatar["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($avatar["tmp_name"], $target_file)) {
                if ($user['avatar'] !== 'default_avatar.png') {
                    $old_avatar_path = "../uploads/avatars/" . $user['avatar'];
                    if (file_exists($old_avatar_path)) {
                        unlink($old_avatar_path);
                    }
                }
                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$avatar_name, $user_id]);
                header("Location: profile.php");
                exit();
            } else {
                echo "Ошибка при загрузке файла.";
            }
        } else {
            echo "Файл не является изображением.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Общие стили */
        body {
            margin-left: 250px;
            font-family: 'Arial', sans-serif;
            background: #FFFFFF;
            color: #2C3E50;
            padding: 20px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        h1 {
            color: #FF6F61;
            font-size: 32px;
            margin-bottom: 20px;
            text-align: center;
            padding: 20px 0;
        }

        .profile-container {
            display: flex;
            gap: 30px;
            background: #FFF8E1;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            align-items: flex-start;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #FF6F61;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .profile-avatar:hover .profile-avatar-overlay {
            opacity: 1;
        }

        .profile-avatar-overlay i {
            color: #FFFFFF;
            font-size: 24px;
            cursor: pointer;
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-info p {
            font-size: 16px;
            color: #2C3E50;
            margin-bottom: 10px;
        }

        .profile-info strong {
            color: #FF6F61;
            font-size: 16px;
        }

        .profile-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .profile-actions button, .profile-actions a {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-actions button:hover, .profile-actions a:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .stat-section {
            margin-top: 40px;
            padding: 20px;
            background: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-section h2 {
            color: #FF6F61;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .stat-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .stat-card {
            background: #FFF8E1;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            width: 200px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        .stat-card i {
            font-size: 32px;
            color: #FF6F61;
            margin-bottom: 10px;
        }

        .stat-card h5 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #2C3E50;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #FF6F61;
        }

        .btn-analytic {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            display: block;
            margin: 20px auto 0;
            text-align: center;
            width: fit-content;
            text-decoration: none;
        }

        .btn-analytic:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        /* Модальные окна */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 250px;
            width: calc(100% - 250px);
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #FFFFFF;
            padding: 20px;
            border-radius: 15px;
            width: 300px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease-in-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-content h3 {
            color: #2C3E50;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
            color: #2C3E50;
            font-size: 14px;
        }

        .modal-content input[type="text"],
        .modal-content input[type="password"],
        .modal-content input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #E0E0E0;
            border-radius: 10px;
            font-size: 14px;
        }

        .modal-content button {
            background: #FF6F61;
            color: #FFFFFF;
            padding: 8px 15px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .modal-content button:hover {
            background: #FF3B2F;
        }

        .modal-content button[type="submit"] {
            background: #dc3545;
        }

        .modal-content button[type="submit"]:hover {
            background: #c82333;
        }

        /* Мобильная адаптация */
        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 10px;
            }

            h1 {
                font-size: 24px;
                padding: 10px 0;
            }

            .profile-container {
                flex-direction: column;
                align-items: center;
                padding: 20px;
            }

            .profile-avatar {
                width: 120px;
                height: 120px;
            }

            .profile-info {
                text-align: center;
            }

            .profile-actions {
                flex-direction: column;
                gap: 10px;
            }

            .stat-grid {
                flex-direction: column;
                gap: 10px;
            }

            .stat-card {
                width: 100%;
                padding: 15px;
            }

            .stat-card i {
                font-size: 24px;
            }

            .stat-card h5 {
                font-size: 16px;
            }

            .stat-card p {
                font-size: 20px;
            }

            .modal {
                left: 0;
                width: 100%;
            }

            .modal-content {
                width: 90%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <h1>Профиль</h1>

    <div class="profile-container">
        <!-- Аватарка -->
        <div class="profile-avatar">
            <img src="../uploads/avatars/<?= $user['avatar'] ?>" alt="Аватар">
            <div class="profile-avatar-overlay" onclick="openModal('uploadAvatarModal')">
                <i class="fas fa-camera"></i>
            </div>
        </div>

        <!-- Информация о пользователе -->
        <div class="profile-info">
            <p><strong>ФИО:</strong> <?= htmlspecialchars($user['name']) ?></p>
            <p><strong>Логин:</strong> <?= htmlspecialchars($user['login']) ?></p>
            <p><strong>Роль:</strong> <?= htmlspecialchars($user['role']) ?></p>

            <!-- Кнопки действий -->
            <div class="profile-actions">
                <button onclick="openModal('updateDataModal')">Редактировать данные</button>
                <a href="logout.php">Выйти</a>
            </div>
        </div>
    </div>

    <!-- Статистика для HR -->
    <?php if ($user['role'] == 'HR'): ?>
        <div class="stat-section">
            <h2>Краткая статистика</h2>

            <div class="stat-grid">
                <div class="stat-card">
                    <i class="fas fa-briefcase"></i>
                    <h5>Ваши вакансии</h5>
                    <p><?= $total_jobs ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-alt"></i>
                    <h5>Все отклики</h5>
                    <p><?= $hr_applications ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hourglass-half"></i>
                    <h5>На рассмотрении</h5>
                    <p><?= $hr_pending ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h5>Принято</h5>
                    <p><?= $hr_accepted ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-times-circle"></i>
                    <h5>Отклонено</h5>
                    <p><?= $hr_rejected ?></p>
                </div>
            </div>

            <a href="analytics.php" class="btn-analytic">Перейти в полную аналитику</a>
        </div>
    <?php endif; ?>

    <!-- Статистика для кандидата -->
    <?php if ($user['role'] == 'candidate'): ?>
        <div class="stat-section">
            <h2>Краткая статистика</h2>

            <div class="stat-grid">
                <div class="stat-card">
                    <i class="fas fa-file-alt"></i>
                    <h5>Все отклики</h5>
                    <p><?= $total_applications ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hourglass-half"></i>
                    <h5>На рассмотрении</h5>
                    <p><?= $pending_count ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h5>Принято</h5>
                    <p><?= $accepted_count ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-times-circle"></i>
                    <h5>Отклонено</h5>
                    <p><?= $rejected_count ?></p>
                </div>
            </div>

            <a href="jobs.php" class="btn-analytic">Просмотреть вакансии</a>
        </div>
    <?php endif; ?>

    <!-- Статистика для админа и модератора -->
    <?php if ($user['role'] === 'admin' || $user['role'] === 'moderator'): ?>
        <div class="stat-section">
            <h2>Статистика системы</h2>

            <div class="stat-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h5>Всего пользователей</h5>
                    <p><?= $total_users ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-tie"></i>
                    <h5>HR-специалистов</h5>
                    <p><?= $total_hr ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-graduate"></i>
                    <h5>Кандидатов</h5>
                    <p><?= $total_candidates ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-briefcase"></i>
                    <h5>Вакансий</h5>
                    <p><?= $total_jobs_all ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-ban"></i>
                    <h5>Заблокировано</h5>
                    <p><?= $total_blocked_users ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Модальные окна -->
    <div id="updateDataModal" class="modal">
        <div class="modal-content">
            <h3>Изменить данные профиля</h3>
            <form action="profile.php" method="POST">
                <label for="login">Новый логин:</label>
                <input type="text" name="login" value="<?= htmlspecialchars($user['login']) ?>" required>
                <label for="password">Новый пароль:</label>
                <input type="password" name="password" placeholder="Введите новый пароль">
                <button type="submit" name="update_data">Сохранить изменения</button>
                <button type="button" onclick="closeModal('updateDataModal')">Отмена</button>
            </form>
        </div>
    </div>

    <div id="uploadAvatarModal" class="modal">
        <div class="modal-content">
            <h3>Загрузить новую аватарку</h3>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="avatar" required>
                <button type="submit">Загрузить</button>
                <button type="button" onclick="closeModal('uploadAvatarModal')">Отмена</button>
            </form>
            <form action="profile.php" method="POST" style="margin-top: 10px;">
                <input type="hidden" name="delete_avatar" value="1">
                <button type="submit" style="background: #dc3545;">Удалить аватарку</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>
</body>
</html>