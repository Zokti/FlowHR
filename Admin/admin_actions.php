<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь и является ли он админом
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = $_POST['user_id'] ?? null;

    switch ($action) {
        case 'edit_user':
            $name = $_POST['name'];
            $email = $_POST['email'];
            $role = $_POST['role'];

            // Подтверждение для ролей "модератор" и "админ"
            if (in_array($role, ['moderator', 'admin'])) {
                echo json_encode(['confirm' => true]);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $user_id]);
            break;

        case 'delete_user':
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$user_id]);
            break;

        case 'block_user':
            $duration = $_POST['duration'];
            $blocked_until = null;

            if ($duration !== 'forever') {
                $blocked_until = date('Y-m-d H:i:s', strtotime("+$duration"));
            }

            $stmt = $pdo->prepare("UPDATE users SET is_blocked = TRUE, blocked_until = ? WHERE id = ?");
            $stmt->execute([$blocked_until, $user_id]);
            break;

        case 'unblock_user':
            $stmt = $pdo->prepare("UPDATE users SET is_blocked = FALSE, blocked_until = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            break;

        default:
            break;
    }
// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'edit_job':
            $job_id = $_POST['job_id'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            $status = $_POST['status'];

            $stmt = $pdo->prepare("UPDATE jobs SET title = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $description, $status, $job_id]);
            break;

        case 'delete_job':
            $job_id = $_POST['job_id'];
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
            $stmt->execute([$job_id]);
            break;

        case 'toggle_job_status':
            $data = json_decode(file_get_contents('php://input'), true);
            $job_id = $data['job_id'];
            $status = $data['status'];

            $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
            $stmt->execute([$status, $job_id]);
            break;

        default:
            break;
    }
}
// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        // Обработка для навыков
        case 'add_skill':
            $name = $_POST['name'];
            $stmt = $pdo->prepare("INSERT INTO skills (name) VALUES (?)");
            $stmt->execute([$name]);
            break;

        case 'edit_skill':
            $id = $_POST['id'];
            $name = $_POST['name'];
            $stmt = $pdo->prepare("UPDATE skills SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            break;

        case 'delete_skill':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM skills WHERE id = ?");
            $stmt->execute([$id]);
            break;

        // Обработка для зарплат
        case 'add_salary':
            $salary_range = $_POST['salary_range'];
            $stmt = $pdo->prepare("INSERT INTO salaries (salary_range) VALUES (?)");
            $stmt->execute([$salary_range]);
            break;

        case 'edit_salary':
            $id = $_POST['id'];
            $salary_range = $_POST['salary_range'];
            $stmt = $pdo->prepare("UPDATE salaries SET salary_range = ? WHERE id = ?");
            $stmt->execute([$salary_range, $id]);
            break;

        case 'delete_salary':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM salaries WHERE id = ?");
            $stmt->execute([$id]);
            break;

        // Обработка для опыта
        case 'add_experience':
            $name = $_POST['name'];
            $stmt = $pdo->prepare("INSERT INTO experiences (name) VALUES (?)");
            $stmt->execute([$name]);
            break;

        case 'edit_experience':
            $id = $_POST['id'];
            $name = $_POST['name'];
            $stmt = $pdo->prepare("UPDATE experiences SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            break;

        case 'delete_experience':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM experiences WHERE id = ?");
            $stmt->execute([$id]);
            break;

        default:
            echo json_encode(['error' => 'Неизвестное действие']);
            break;
    }

    // Перенаправляем обратно на страницу управления
    header("Location: list_data.php");
    exit();
}
// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        // Обработка для тестов
        case 'add_test':
            $title = $_POST['title'];
            $time_limit = $_POST['time_limit'];
            $stmt = $pdo->prepare("INSERT INTO tests (title, time_limit) VALUES (?, ?)");
            $stmt->execute([$title, $time_limit]);
            break;

        case 'edit_test':
            $id = $_POST['id'];
            $title = $_POST['title'];
            $time_limit = $_POST['time_limit'];
            $stmt = $pdo->prepare("UPDATE tests SET title = ?, time_limit = ? WHERE id = ?");
            $stmt->execute([$title, $time_limit, $id]);
            break;

        case 'delete_test':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM tests WHERE id = ?");
            $stmt->execute([$id]);
            break;

        // Обработка для вопросов
        case 'add_question':
            $test_id = $_POST['test_id'];
            $question_text = $_POST['question_text'];
            $max_score = 10; // По умолчанию
            $stmt = $pdo->prepare("INSERT INTO questions (test_id, question_text, max_score) VALUES (?, ?, ?)");
            $stmt->execute([$test_id, $question_text, $max_score]);
            break;

        case 'edit_question':
            $id = $_POST['id'];
            $test_id = $_POST['test_id'];
            $question_text = $_POST['question_text'];
            $stmt = $pdo->prepare("UPDATE questions SET test_id = ?, question_text = ? WHERE id = ?");
            $stmt->execute([$test_id, $question_text, $id]);
            break;

        case 'delete_question':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$id]);
            break;

        // Обработка для ответов
        case 'add_answer':
            $question_id = $_POST['question_id'];
            $answer_text = $_POST['answer_text'];
            $is_correct = $_POST['is_correct'] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
            $stmt->execute([$question_id, $answer_text, $is_correct]);
            break;

        case 'edit_answer':
            $id = $_POST['id'];
            $question_id = $_POST['question_id'];
            $answer_text = $_POST['answer_text'];
            $is_correct = $_POST['is_correct'] ?? 0;
            $stmt = $pdo->prepare("UPDATE answers SET question_id = ?, answer_text = ?, is_correct = ? WHERE id = ?");
            $stmt->execute([$question_id, $answer_text, $is_correct, $id]);
            break;

        case 'delete_answer':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM answers WHERE id = ?");
            $stmt->execute([$id]);
            break;

        default:
            echo json_encode(['error' => 'Неизвестное действие']);
            break;
    }

    // Перенаправляем обратно на страницу управления
    header("Location: manage_tests.php");
    exit();
}
}