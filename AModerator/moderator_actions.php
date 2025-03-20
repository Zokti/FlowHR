<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь и является ли он модератором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    header("Location: login.php");
    exit();
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'edit_job':
            $job_id = $_POST['job_id'];
            $title = $_POST['title'];
            $description = $_POST['description'];

            $stmt = $pdo->prepare("UPDATE jobs SET title = ?, description = ? WHERE id = ?");
            $stmt->execute([$title, $description, $job_id]);
            break;

        case 'delete_job':
            $job_id = $_POST['job_id'];
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
            $stmt->execute([$job_id]);
            break;

        default:
            echo json_encode(['error' => 'Неизвестное действие']);
            break;
    }

    // Перенаправляем обратно на страницу модерации
    header("Location: moderate_vacancies.php");
    exit();


 // Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'edit_candidate':
            $candidate_id = $_POST['candidate_id'];
            $name = $_POST['name'];
            $email = $_POST['email'];

            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'candidate'");
            $stmt->execute([$name, $email, $candidate_id]);
            break;

        case 'block_candidate':
            $candidate_id = $_POST['candidate_id'];
            $duration = $_POST['duration'];

            $blocked_until = null;
            if ($duration !== 'forever') {
                $blocked_until = date('Y-m-d H:i:s', strtotime("+$duration"));
            }

            $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1, blocked_until = ? WHERE id = ? AND role = 'candidate'");
            $stmt->execute([$blocked_until, $candidate_id]);
            break;

        case 'unblock_candidate':
            $candidate_id = $_POST['candidate_id'];

            $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0, blocked_until = NULL WHERE id = ? AND role = 'candidate'");
            $stmt->execute([$candidate_id]);
            break;

        default:
            echo json_encode(['error' => 'Неизвестное действие']);
            exit();
    }

    // Возвращаем успешный ответ
    echo json_encode(['success' => true]);
    exit();
} else {
    echo json_encode(['error' => 'Некорректный запрос']);
    exit();
}


// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'delete_message':
            // Получаем данные из тела запроса
            $data = json_decode(file_get_contents('php://input'), true);
            $message_id = $data['message_id'];

            // Удаляем сообщение из базы данных
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->execute([$message_id]);

            // Возвращаем успешный ответ
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Неизвестное действие']);
            break;
    }
} else {
    echo json_encode(['error' => 'Некорректный запрос']);
}
}