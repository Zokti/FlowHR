<?php
session_start();
require '../includes/config.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$application_id = $_POST['application_id'] ?? null;
$message = $_POST['message'] ?? null;

if (!$application_id || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Проверяем, существует ли заявка и имеет ли пользователь к ней доступ
    $stmt = $pdo->prepare("
        SELECT id FROM applications 
        WHERE id = ? AND (hr_id = ? OR candidate_id = ?)
    ");
    $stmt->execute([$application_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Добавляем сообщение
    $stmt = $pdo->prepare("
        INSERT INTO messages (application_id, user_id, content, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$application_id, $_SESSION['user_id'], $message]);
    $message_id = $pdo->lastInsertId();

    // Получаем информацию об аватаре пользователя
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'avatar' => $user['avatar']
    ]);
} catch(Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>