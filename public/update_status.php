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
$status = $_POST['status'] ?? null;

// Добавляем отладочную информацию
error_log("Received status: " . $status);
error_log("Received application_id: " . $application_id);

if (!$application_id || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Проверяем, что статус валидный
$valid_statuses = ['pending', 'interview', 'hired', 'rejected'];
error_log("Valid statuses: " . implode(', ', $valid_statuses));
error_log("Checking if status is valid: " . (in_array($status, $valid_statuses) ? 'true' : 'false'));

if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status: ' . $status]);
    exit;
}

try {
    // Проверяем, что пользователь является HR и имеет доступ к этой заявке
    $stmt = $pdo->prepare("
        SELECT id, status FROM applications 
        WHERE id = ? AND hr_id = ?
    ");
    $stmt->execute([$application_id, $_SESSION['user_id']]);
    $application = $stmt->fetch();
    
    if (!$application) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    error_log("Current application status: " . $application['status']);

    // Обновляем статус заявки
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET status = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $application_id]);

    // Добавляем системное сообщение о изменении статуса
    $statusMessages = [
        'pending' => 'Статус заявки изменен на "На рассмотрении"',
        'interview' => 'Вас пригласили на собеседование',
        'hired' => 'Поздравляем! Вы приняты на работу',
        'rejected' => 'Ваша заявка была отклонена'
    ];

    $message = $statusMessages[$status] ?? 'Статус заявки был изменен';
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (application_id, user_id, content, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$application_id, $_SESSION['user_id'], $message]);

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    error_log("Error in update_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 