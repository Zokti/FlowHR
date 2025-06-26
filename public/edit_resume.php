<?php
require __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Проверка авторизации
    if (empty($_SESSION['user_id'])) {
        throw new Exception("Требуется авторизация", 401);
    }

    // Валидация входных данных
    if (empty($_POST['resume_id']) || !is_numeric($_POST['resume_id'])) {
        throw new Exception("Некорректный идентификатор резюме", 400);
    }

    $resumeId = (int)$_POST['resume_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Проверка обязательных полей
    if (empty($title)) {
        throw new Exception("Название не может быть пустым", 400);
    }

    // Проверка прав доступа
    $stmt = $pdo->prepare("SELECT id FROM resumes WHERE id = ? AND user_id = ?");
    $stmt->execute([$resumeId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Резюме не найдено", 404);
    }

    // Обновление данных
    $stmt = $pdo->prepare("UPDATE resumes SET title = ?, description = ? WHERE id = ?");
    $stmt->execute([$title, $description, $resumeId]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'resume_id' => $resumeId,
        'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        'description' => nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8'))
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
} catch(Exception $e) {
    $code = $e->getCode() ?: 400;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}