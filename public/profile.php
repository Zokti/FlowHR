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
$total_applications = $pending_count = $interview_count = $hired_count = $rejected_count = 0;
$total_jobs = $hr_applications = $hr_pending = $hr_interview = $hr_hired = $hr_rejected = 0;

// Общая статистика
$query = $pdo->query("SELECT COUNT(*) as total_applications FROM applications");
$total_applications = $query->fetch()['total_applications'];

$query_pending = $pdo->query("SELECT COUNT(*) as pending FROM applications WHERE status = 'pending'");
$pending_count = $query_pending->fetch()['pending'];

$query_interview = $pdo->query("SELECT COUNT(*) as interview FROM applications WHERE status = 'interview'");
$interview_count = $query_interview->fetch()['interview'];

$query_hired = $pdo->query("SELECT COUNT(*) as hired FROM applications WHERE status = 'hired'");
$hired_count = $query_hired->fetch()['hired'];

$query_rejected = $pdo->query("SELECT COUNT(*) as rejected FROM applications WHERE status = 'rejected'");
$rejected_count = $query_rejected->fetch()['rejected'];

// Если HR, считаем количество его вакансий и откликов
if ($user['role'] == 'HR') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_jobs FROM jobs WHERE hr_id = ?");
    $stmt->execute([$user_id]);
    $total_jobs = $stmt->fetch()['total_jobs'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as hr_applications 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ?
    ");
    $stmt->execute([$user_id]);
    $hr_applications = $stmt->fetch()['hr_applications'];

    $stmt_pending = $pdo->prepare("
        SELECT COUNT(*) as hr_pending 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ? AND applications.status = 'pending'
    ");
    $stmt_pending->execute([$user_id]);
    $hr_pending = $stmt_pending->fetch()['hr_pending'];

    $stmt_interview = $pdo->prepare("
        SELECT COUNT(*) as hr_interview 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ? AND applications.status = 'interview'
    ");
    $stmt_interview->execute([$user_id]);
    $hr_interview = $stmt_interview->fetch()['hr_interview'];

    $stmt_hired = $pdo->prepare("
        SELECT COUNT(*) as hr_hired 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ? AND applications.status = 'hired'
    ");
    $stmt_hired->execute([$user_id]);
    $hr_hired = $stmt_hired->fetch()['hr_hired'];

    $stmt_rejected = $pdo->prepare("
        SELECT COUNT(*) as hr_rejected 
        FROM applications 
        JOIN jobs ON applications.job_id = jobs.id 
        WHERE jobs.hr_id = ? AND applications.status = 'rejected'
    ");
    $stmt_rejected->execute([$user_id]);
    $hr_rejected = $stmt_rejected->fetch()['hr_rejected'];
}

// Загрузка статистики для кандидата
if ($user['role'] == 'candidate') {
    // Количество опубликованных резюме
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_resumes FROM resumes WHERE user_id = ? AND is_published = 1");
    $stmt->execute([$user_id]);
    $total_resumes = $stmt->fetch()['total_resumes'];

    // Отклики на вакансии
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_applications,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'interview' THEN 1 ELSE 0 END) as interview_count,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
        FROM applications 
        WHERE candidate_id = ? AND entity_type = 'vacancy'
    ");
    $stmt->execute([$user_id]);
    $applications_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Предложения по резюме (отклики HR на резюме кандидата)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_offers
        FROM applications a
        JOIN resumes r ON a.entity_id = r.id
        WHERE r.user_id = ? AND a.entity_type = 'resume'
    ");
    $stmt->execute([$user_id]);
    $total_offers = $stmt->fetch()['total_offers'];
}

// Статистика для админа и модератора
$total_users = $pdo->query("SELECT COUNT(*) as total_users FROM users")->fetch()['total_users'];
$total_hr = $pdo->query("SELECT COUNT(*) as total_hr FROM users WHERE role = 'HR'")->fetch()['total_hr'];
$total_candidates = $pdo->query("SELECT COUNT(*) as total_candidates FROM users WHERE role = 'candidate'")->fetch()['total_candidates'];
$total_jobs_all = $pdo->query("SELECT COUNT(*) as total_jobs_all FROM jobs")->fetch()['total_jobs_all'];
$total_blocked_users = $pdo->query("SELECT COUNT(*) as total_blocked_users FROM users WHERE is_blocked = TRUE")->fetch()['total_blocked_users'];

// Функция для расчета прогресса заполнения профиля
function calculateProfileProgress($user, $pdo) {
    $total_weight = 0;
    $filled_weight = 0;
    
    $stmt = $pdo->query("SELECT * FROM profile_fields");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($fields as $field) {
        $total_weight += $field['weight'];
        if (!empty($user[$field['field_name']])) {
            $filled_weight += $field['weight'];
        }
    }
    
    $progress = ($total_weight > 0) ? round(($filled_weight / $total_weight) * 100) : 0;
    
    // Обновляем прогресс в базе данных
    $stmt = $pdo->prepare("UPDATE users SET profile_completion = ? WHERE id = ?");
    $stmt->execute([$progress, $user['id']]);
    
    return $progress;
}

// Рассчитываем прогресс заполнения профиля
$profile_progress = calculateProfileProgress($user, $pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received");
    error_log("POST data: " . print_r($_POST, true));

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

    // Обработка обновления профиля
    if (isset($_POST['update_profile'])) {
        error_log("Update profile request detected");
        try {
            // Получаем и очищаем данные
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
            $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
            $city = !empty($_POST['city']) ? trim($_POST['city']) : null;

            error_log("Processed data: " . print_r([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'age' => $age,
                'city' => $city,
                'user_id' => $user_id
            ], true));

            // Проверка email на уникальность
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                error_log("Email already in use");
                echo "<script>alert('Этот email уже используется другим пользователем');</script>";
            } else {
                // Обновляем данные пользователя
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?,
                        email = ?,
                        phone = ?,
                        age = ?,
                        city = ?
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$name, $email, $phone, $age, $city, $user_id]);
                
                error_log("Update query executed. Result: " . ($result ? "success" : "failure"));
                
                if ($result) {
                    // Обновляем данные в сессии
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    error_log("Session updated, redirecting...");
                    // Перезагружаем страницу
                    header("Location: profile.php");
                    exit();
                } else {
                    error_log("Update failed");
                    echo "<script>alert('Ошибка при обновлении данных');</script>";
                }
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo "<script>alert('Ошибка при обновлении данных: " . $e->getMessage() . "');</script>";
        }
    }

    // Обработка загрузки новой аватарки
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar = $_FILES['avatar'];
        $target_dir = "../uploads/avatars/";
        $avatar_name = $user_id . "_" . time() . "_" . basename($avatar["name"]);
        $target_file = $target_dir . $avatar_name;
        $check = getimagesize($avatar["tmp_name"]);
        
        if ($check !== false) {
            // Проверяем тип файла
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($avatar["type"], $allowed_types)) {
                echo "<script>alert('Разрешены только файлы JPG, PNG и GIF');</script>";
            } else {
                // Проверяем размер файла (максимум 5MB)
                if ($avatar["size"] > 5000000) {
                    echo "<script>alert('Файл слишком большой. Максимальный размер - 5MB');</script>";
                } else {
                    if (move_uploaded_file($avatar["tmp_name"], $target_file)) {
                        // Удаляем старую аватарку, если она не дефолтная
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
                        echo "<script>alert('Ошибка при загрузке файла');</script>";
                    }
                }
            }
        } else {
            echo "<script>alert('Файл не является изображением');</script>";
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
            background: #F8F9FA;
            color: #2C3E50;
            padding: 20px;
        }

        h1 {
            color: #FF6F61;
            font-size: 32px;
            margin-bottom: 20px;
            text-align: center;
            padding: 20px 0;
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        .profile-sidebar {
            background: #F8F9FA;
            border-radius: 15px;
            padding: 25px;
        }

        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .profile-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .profile-header h1 {
            color: #2C3E50;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .profile-header h1 i {
            color: #FF6F61;
            font-size: 32px;
            transition: all 0.3s ease;
        }

        .profile-header h1:hover i {
            transform: scale(1.2) rotate(15deg);
            color: #FF6F61;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .profile-header h1 i {
            animation: pulse 2s infinite;
        }

        .profile-header h1:hover i {
            animation: none;
        }

        .profile-avatar {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .profile-avatar:hover img {
            transform: scale(1.1);
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
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 50%;
        }

        .profile-avatar:hover .profile-avatar-overlay {
            opacity: 1;
        }

        .profile-avatar-overlay i {
            color: #FFFFFF;
            font-size: 28px;
            transform: translateY(10px);
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover .profile-avatar-overlay i {
            transform: translateY(0);
        }

        .profile-name {
            font-size: 24px;
            color: #2C3E50;
            margin-bottom: 5px;
        }

        .profile-role {
            display: inline-block;
            padding: 6px 15px;
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 500;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(255, 111, 97, 0.2);
            margin-top: 10px;
        }

        .profile-role.admin {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
        }

        .profile-role.moderator {
            background: linear-gradient(135deg, #4ECDC4, #2BAF9F);
        }

        .profile-role.hr {
            background: linear-gradient(135deg, #FFC107, #FF9800);
        }

        .profile-role.candidate {
            background: linear-gradient(135deg, #2196F3, #1976D2);
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .profile-info-item {
            background: #FFFFFF;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #E9ECEF;
            transition: all 0.3s ease;
        }

        .profile-info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-info-item .label {
            color: #6C757D;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .profile-info-item .value {
            color: #2C3E50;
            font-size: 18px;
            font-weight: 500;
        }

        .profile-actions {
            margin-top: 20px;
        }

        .profile-actions button {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
            width: 100%;
        }

        .profile-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        /* Стили для статистики */
        .stat-section {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-section h2 {
            color: #2C3E50;
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: #F8F9FA;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #E9ECEF;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card i {
            font-size: 24px;
            margin-bottom: 15px;
            color: #FF6F61;
            transition: transform 0.3s ease;
        }

        .stat-card:hover i {
            transform: scale(1.1);
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2C3E50;
            margin: 10px 0;
            line-height: 1;
        }

        .stat-card .stat-label {
            font-size: 14px;
            color: #6C757D;
            font-weight: 500;
        }

        .stat-card.pending i { color: #FFC107; }
        .stat-card.interview i { color: #4ECDC4; }
        .stat-card.hired i { color: #28a745; }
        .stat-card.rejected i { color: #dc3545; }

        .btn-analytic {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            padding: 14px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
            display: block;
            margin: 30px auto 0;
            text-align: center;
            width: fit-content;
            text-decoration: none;
        }

        .btn-analytic:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
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
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: #FFFFFF;
            padding: 30px;
            border-radius: 20px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            transform: translateY(0);
            animation: modalSlideIn 0.3s ease-out;
            border: 1px solid rgba(255, 111, 97, 0.1);
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #E9ECEF;
            position: relative;
        }

        .modal-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            opacity: 0.5;
        }

        .modal-header h3 {
            color: #2C3E50;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
            position: relative;
            padding-left: 15px;
        }

        .modal-header h3::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            border-radius: 2px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: #6C757D;
            cursor: pointer;
            padding: 5px;
            transition: all 0.3s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-modal:hover {
            color: #FF6F61;
            background: rgba(255, 111, 97, 0.1);
            transform: rotate(90deg);
        }

        .profile-progress {
            margin-bottom: 25px;
            padding: 20px;
            background: #F8F9FA;
            border-radius: 15px;
            border: 1px solid #E9ECEF;
            position: relative;
            overflow: hidden;
        }

        .profile-progress::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 111, 97, 0.05), rgba(255, 59, 47, 0.05));
            z-index: 0;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #6C757D;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        .progress-bar {
            height: 8px;
            background: #E9ECEF;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            transition: width 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: progressShine 2s infinite;
        }

        @keyframes progressShine {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }

        .modal-form {
            display: grid;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
        }

        .form-group label {
            color: #2C3E50;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-group input {
            padding: 12px 15px;
            border: 1px solid #E9ECEF;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #FFFFFF;
        }

        .form-group input:focus {
            border-color: #FF6F61;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.1);
        }

        .form-group input:hover {
            border-color: #FF6F61;
        }

        .required {
            color: #FF6F61;
            font-size: 16px;
            margin-left: 3px;
        }

        .modal-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #E9ECEF;
            position: relative;
            flex-wrap: wrap;
        }

        .modal-footer::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            opacity: 0.5;
        }

        .modal-footer button {
            min-width: 180px;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modal-footer button[type="submit"] {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .modal-footer button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .modal-footer button[type="button"] {
            background: #F8F9FA;
            color: #2C3E50;
            border: 1px solid #E9ECEF;
        }

        .modal-footer button[type="button"]:hover {
            background: #E9ECEF;
            transform: translateY(-2px);
        }

        .modal-footer button i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .modal-footer button:hover i {
            transform: scale(1.1);
        }

        @media (max-width: 576px) {
            .modal-footer {
                flex-direction: column;
                gap: 15px;
            }

            .modal-footer button {
                width: 100%;
            }
        }

        /* Модальное окно подтверждения */
        .confirm-modal .modal-content {
            width: 400px;
            text-align: center;
            padding: 30px;
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }

        .confirm-modal .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
        }

        .confirm-modal .modal-header {
            justify-content: center;
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 20px;
            position: relative;
        }

        .confirm-modal .modal-header h3 {
            font-size: 24px;
            color: #2C3E50;
            margin: 0;
            font-weight: 600;
            position: relative;
            display: inline-block;
        }

        .confirm-modal .modal-header h3::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #FF6F61, #FF3B2F);
            border-radius: 2px;
        }

        .confirm-modal .modal-body {
            padding: 20px 0;
            color: #2C3E50;
            font-size: 16px;
            line-height: 1.5;
            position: relative;
        }

        .confirm-modal .modal-body::before {
            content: '\f05a';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 48px;
            color: #FF6F61;
            display: block;
            margin-bottom: 20px;
            animation: iconPulse 2s infinite;
        }

        .confirm-modal .modal-footer {
            justify-content: center;
            border-top: none;
            padding-top: 0;
            margin-top: 20px;
            gap: 15px;
            display: flex;
            flex-wrap: wrap;
        }

        .confirm-modal .btn-confirm {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 150px;
        }

        .confirm-modal .btn-confirm::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .confirm-modal .btn-confirm:hover::before {
            left: 100%;
        }

        .confirm-modal .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .confirm-modal .btn-confirm:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(255, 111, 97, 0.2);
        }

        .confirm-modal .btn-confirm i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .confirm-modal .btn-confirm:hover i {
            transform: scale(1.1);
        }

        .confirm-modal .btn-cancel {
            background: #F8F9FA;
            color: #2C3E50;
            padding: 12px 30px;
            border: 1px solid #E9ECEF;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 150px;
        }

        .confirm-modal .btn-cancel::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.05), transparent);
            transition: 0.5s;
        }

        .confirm-modal .btn-cancel:hover::before {
            left: 100%;
        }

        .confirm-modal .btn-cancel:hover {
            background: #E9ECEF;
            transform: translateY(-2px);
        }

        .confirm-modal .btn-cancel:active {
            transform: translateY(1px);
        }

        .confirm-modal .btn-cancel i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .confirm-modal .btn-cancel:hover i {
            transform: scale(1.1);
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes iconPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                order: 2;
            }

            .profile-main {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 10px;
            }

            .profile-container {
                padding: 20px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-info-grid,
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .profile-info-item,
            .stat-card {
                padding: 15px;
            }

            .modal {
                left: 0;
                width: 100%;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }

        /* Стили для модального окна загрузки аватарки */
        #uploadAvatarModal .modal-content {
            width: 400px;
            text-align: center;
        }

        #uploadAvatarModal .modal-header {
            border-bottom: none;
            padding-bottom: 0;
        }

        #uploadAvatarModal .modal-header h3 {
            font-size: 24px;
            color: #2C3E50;
            margin-bottom: 20px;
        }

        .avatar-upload-container {
            padding: 20px;
            background: #F8F9FA;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 3px solid #FFFFFF;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-upload-input {
            display: none;
        }

        .avatar-upload-label {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .avatar-upload-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.3);
        }

        .avatar-upload-label i {
            margin-right: 8px;
        }

        .avatar-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .avatar-actions button {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .avatar-actions button[type="submit"] {
            background: linear-gradient(135deg, #FF6F61, #FF3B2F);
            color: #FFFFFF;
            box-shadow: 0 4px 15px rgba(255, 111, 97, 0.2);
        }

        .avatar-actions button[type="button"] {
            background: #E9ECEF;
            color: #2C3E50;
        }

        .avatar-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .delete-avatar-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #E9ECEF;
        }

        .delete-avatar-btn {
            background: #FFFFFF !important;
            color: #dc3545 !important;
            border: 2px solid #dc3545 !important;
            padding: 10px 20px !important;
            font-size: 14px !important;
            box-shadow: none !important;
            width: 100%;
            justify-content: center;
        }

        .delete-avatar-btn:hover {
            background: #dc3545 !important;
            color: #FFFFFF !important;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2) !important;
        }

        .delete-avatar-btn i {
            font-size: 16px;
        }

        .required {
            color: #dc3545;
            margin-left: 3px;
        }

        /* Стили для уведомлений */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            background: #FFFFFF;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease-in-out;
            animation: slideIn 0.5s forwards;
        }

        .notification.success {
            border-left: 4px solid #28a745;
        }

        .notification.error {
            border-left: 4px solid #dc3545;
        }

        .notification i {
            font-size: 24px;
        }

        .notification.success i {
            color: #28a745;
        }

        .notification.error i {
            color: #dc3545;
        }

        .notification-content {
            display: flex;
            flex-direction: column;
        }

        .notification-title {
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 4px;
        }

        .notification-message {
            color: #6C757D;
            font-size: 14px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(120%);
            }
            to {
                transform: translateX(0);
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
            }
            to {
                transform: translateX(120%);
            }
        }

        .notification.hide {
            animation: slideOut 0.5s forwards;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> Профиль пользователя</h1>
        </div>
    </div>

    <div class="profile-container">
        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <img src="../uploads/avatars/<?= $user['avatar'] ?>" alt="Аватар">
                        <div class="profile-avatar-overlay" onclick="openModal('uploadAvatarModal')">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <div class="profile-main-info">
                        <h1 class="profile-name"><?= htmlspecialchars($user['name']) ?></h1>
                        <div class="profile-role <?= strtolower($user['role']) ?>"><?= ucfirst($user['role']) ?></div>
                    </div>
                </div>

                <div class="profile-info-grid">
                    <div class="profile-info-item">
                        <div class="label">Email</div>
                        <div class="value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>

                    <?php if (!empty($user['phone'])): ?>
                    <div class="profile-info-item">
                        <div class="label">Телефон</div>
                        <div class="value"><?= htmlspecialchars($user['phone']) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($user['age'])): ?>
                    <div class="profile-info-item">
                        <div class="label">Возраст</div>
                        <div class="value"><?= htmlspecialchars($user['age']) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($user['city'])): ?>
                    <div class="profile-info-item">
                        <div class="label">Город</div>
                        <div class="value"><?= htmlspecialchars($user['city']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="profile-actions">
                    <button onclick="openModal('editProfileModal')">Редактировать профиль</button>
                </div>
            </div>

            <div class="profile-main">
                <!-- Статистика для HR -->
                <?php if ($user['role'] == 'HR'): ?>
                    <div class="stat-section">
                        <h2>Краткая статистика</h2>
                        <div class="stats-container">
                            <div class="stat-card">
                                <i class="fas fa-briefcase"></i>
                                <div class="stat-value"><?php echo $total_jobs; ?></div>
                                <div class="stat-label">Вакансий</div>
                            </div>
                            <div class="stat-card pending">
                                <i class="fas fa-clock"></i>
                                <div class="stat-value"><?php echo $hr_pending; ?></div>
                                <div class="stat-label">Ожидают</div>
                            </div>
                            <div class="stat-card interview">
                                <i class="fas fa-user-tie"></i>
                                <div class="stat-value"><?php echo $hr_interview; ?></div>
                                <div class="stat-label">На собеседовании</div>
                            </div>
                            <div class="stat-card hired">
                                <i class="fas fa-check-circle"></i>
                                <div class="stat-value"><?php echo $hr_hired; ?></div>
                                <div class="stat-label">Приняты</div>
                            </div>
                            <div class="stat-card rejected">
                                <i class="fas fa-times-circle"></i>
                                <div class="stat-value"><?php echo $hr_rejected; ?></div>
                                <div class="stat-label">Отклонены</div>
                            </div>
                        </div>
                        <a href="analytics.php" class="btn-analytic">Перейти в полную аналитику</a>
                    </div>
                <?php endif; ?>

                <!-- Статистика для кандидата -->
                <?php if ($user['role'] == 'candidate'): ?>
                    <div class="stat-section">
                        <h2>Краткая статистика</h2>
                        <div class="stats-container">
                            <div class="stat-card">
                                <i class="fas fa-file-alt"></i>
                                <div class="stat-value"><?= $total_resumes ?></div>
                                <div class="stat-label">Опубликованных резюме</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-briefcase"></i>
                                <div class="stat-value"><?= $total_offers ?></div>
                                <div class="stat-label">Предложений по резюме</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-paper-plane"></i>
                                <div class="stat-value"><?= $applications_stats['total_applications'] ?></div>
                                <div class="stat-label">Ваши отклики</div>
                            </div>
                            <div class="stat-card pending">
                                <i class="fas fa-clock"></i>
                                <div class="stat-value"><?= $applications_stats['pending_count'] ?></div>
                                <div class="stat-label">На рассмотрении</div>
                            </div>
                            <div class="stat-card interview">
                                <i class="fas fa-user-tie"></i>
                                <div class="stat-value"><?= $applications_stats['interview_count'] ?></div>
                                <div class="stat-label">Предложенных собеседований</div>
                            </div>
                            <div class="stat-card rejected">
                                <i class="fas fa-times-circle"></i>
                                <div class="stat-value"><?= $applications_stats['rejected_count'] ?></div>
                                <div class="stat-label">Отклонены</div>
                            </div>
                        </div>
                        <a href="jobs.php" class="btn-analytic">Просмотреть вакансии</a>
                    </div>
                <?php endif; ?>

                <!-- Статистика для админа и модератора -->
                <?php if ($user['role'] === 'admin' || $user['role'] === 'moderator'): ?>
                    <div class="stat-section">
                        <h2>Статистика системы</h2>
                        <div class="stats-container">
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <div class="stat-value"><?php echo $total_users; ?></div>
                                <div class="stat-label">Всего пользователей</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-user-tie"></i>
                                <div class="stat-value"><?php echo $total_hr; ?></div>
                                <div class="stat-label">HR-специалистов</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-user"></i>
                                <div class="stat-value"><?php echo $total_candidates; ?></div>
                                <div class="stat-label">Кандидатов</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-briefcase"></i>
                                <div class="stat-value"><?php echo $total_jobs_all; ?></div>
                                <div class="stat-label">Вакансий</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-ban"></i>
                                <div class="stat-value"><?php echo $total_blocked_users; ?></div>
                                <div class="stat-label">Заблокированных</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Модальные окна -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Редактирование профиля</h3>
                <button class="close-modal" onclick="closeModal('editProfileModal')">&times;</button>
            </div>
            
            <?php if ($profile_progress < 100): ?>
            <div class="profile-progress">
                <div class="progress-text">
                    <span>Заполнение профиля</span>
                    <span><?= $profile_progress ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $profile_progress ?>%"></div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="modal-form" id="editProfileForm">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label for="name">ФИО <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+7 (___) ___-__-__">
                </div>
                <div class="form-group">
                    <label for="age">Возраст</label>
                    <input type="number" id="age" name="age" value="<?= htmlspecialchars($user['age'] ?? '') ?>" min="18" max="100">
                </div>
                <div class="form-group">
                    <label for="city">Город</label>
                    <input type="text" id="city" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('editProfileModal')">Отмена</button>
                    <button type="submit" name="submit_profile">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <div id="uploadAvatarModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Обновить аватар</h3>
                <button class="close-modal" onclick="closeModal('uploadAvatarModal')">&times;</button>
            </div>
            
            <div class="avatar-upload-container">
                <div class="avatar-preview">
                    <img src="../uploads/avatars/<?= $user['avatar'] ?>" alt="Предпросмотр аватара" id="avatarPreview">
                </div>
                
                <form action="profile.php" method="POST" enctype="multipart/form-data" id="avatarUploadForm">
                    <input type="file" name="avatar" id="avatarInput" class="avatar-upload-input" accept="image/*" onchange="previewAvatar(this)">
                    <label for="avatarInput" class="avatar-upload-label">
                        <i class="fas fa-upload"></i> Выбрать фото
                    </label>
                    
                    <div class="avatar-actions">
                        <button type="submit">
                            <i class="fas fa-check"></i> Сохранить
                        </button>
                        <button type="button" onclick="closeModal('uploadAvatarModal')">
                            <i class="fas fa-times"></i> Отмена
                        </button>
                    </div>
                </form>
            </div>

            <div class="delete-avatar-section">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="delete_avatar" value="1">
                    <button type="submit" class="delete-avatar-btn">
                        <i class="fas fa-trash-alt"></i> Удалить текущий аватар
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения -->
    <div id="confirmModal" class="modal confirm-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Подтверждение</h3>
            </div>
            <div class="modal-body">
                Вы уверены, что хотите сохранить изменения?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('confirmModal')">Отмена</button>
                <button type="button" class="btn-confirm" onclick="confirmAndSubmit()">Подтвердить</button>
            </div>
        </div>
    </div>

    <script>
        let formToSubmit = null;

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function confirmAction(modalId) {
            formToSubmit = document.querySelector(`#${modalId} form`);
            openModal('confirmModal');
            return false;
        }

        function confirmAndSubmit() {
            console.log('Confirming form submission');
            if (formToSubmit) {
                console.log('Submitting form');
                // Удаляем обработчик события submit перед отправкой
                formToSubmit.removeEventListener('submit', arguments.callee);
                formToSubmit.submit();
            }
            closeModal('confirmModal');
        }

        // Закрытие модальных окон при клике вне их области
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target.id);
            }
        });

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        function showNotification(type, title, message) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            
            notification.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('hide');
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 3000);
        }

        // Обновляем обработчик отправки формы редактирования профиля
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    // Показываем уведомление об успехе
                    showNotification('success', 'Успешно!', 'Профиль успешно обновлен');
                    
                    // Обновляем прогресс профиля, если он есть в ответе
                    if (data.data && data.data.progress !== undefined) {
                        const progressFill = document.querySelector('.progress-fill');
                        const progressText = document.querySelector('.progress-text span:last-child');
                        if (progressFill && progressText) {
                            progressFill.style.width = data.data.progress + '%';
                            progressText.textContent = data.data.progress + '%';
                        }
                    }
                    
                    // Перезагружаем страницу через 2 секунды
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Показываем уведомление об ошибке
                    showNotification('error', 'Ошибка!', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Ошибка!', 'Произошла ошибка при сохранении данных');
            });
        });

        // Обработчик отправки формы загрузки аватара
        document.getElementById('avatarUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Upload avatar form submitted');
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    // Обновляем аватар на странице
                    const avatarImg = document.querySelector('.profile-avatar img');
                    if (avatarImg) {
                        avatarImg.src = data.avatar_path + '?t=' + new Date().getTime();
                    }
                    // Показываем сообщение об успехе
                    alert(data.message);
                    // Закрываем модальное окно
                    closeModal('uploadAvatarModal');
                } else {
                    // Показываем сообщение об ошибке
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при загрузке аватара');
            });
        });

        // Обработчик отправки формы удаления аватара
        document.getElementById('deleteAvatarForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Delete avatar form submitted');
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    // Обновляем аватар на странице
                    const avatarImg = document.querySelector('.profile-avatar img');
                    if (avatarImg) {
                        avatarImg.src = '../uploads/avatars/default_avatar.png';
                    }
                    // Показываем сообщение об успехе
                    alert(data.message);
                    // Закрываем модальное окно
                    closeModal('uploadAvatarModal');
                } else {
                    // Показываем сообщение об ошибке
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при удалении аватара');
            });
        });
    </script>
</body>
</html>