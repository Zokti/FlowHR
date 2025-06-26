<?php
require __DIR__ . '/../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

try {
    // Получение данных
    $resumeId = (int)$_POST['resume_id'];
    $jobId = (int)$_POST['job_id'];
    $message = trim($_POST['message']);

    // Валидация данных
    if (!$resumeId || !$jobId || empty($message)) {
        throw new Exception('Не все поля заполнены');
    }

    // Проверка существования резюме
    $stmt = $pdo->prepare("SELECT id, user_id FROM resumes WHERE id = ? AND is_published = 1");
    $stmt->execute([$resumeId]);
    $resume = $stmt->fetch();
    if (!$resume) {
        throw new Exception('Резюме не найдено или не опубликовано');
    }
    $candidate_id = $resume['user_id'];

    // Проверка существования вакансии и прав доступа
    $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND hr_id = ? AND status = 'active'");
    $stmt->execute([$jobId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Вакансия не найдена или у вас нет прав для её предложения');
    }

    // Проверка существующих заявок
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM applications 
        WHERE entity_type = 'resume'
        AND entity_id = ?
        AND job_id = ?
        AND status = 'pending'
    ");
    $stmt->execute([$resumeId, $jobId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Вы уже отправили предложение по этой вакансии для данного резюме');
    }

    // Создание заявки
    $stmt = $pdo->prepare("
        INSERT INTO applications 
        (entity_type, entity_id, job_id, hr_id, candidate_id, status, message) 
        VALUES ('resume', ?, ?, ?, ?, 'pending', ?)
    ");
    $stmt->execute([$resumeId, $jobId, $_SESSION['user_id'], $candidate_id, $message]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Предложение успешно отправлено']);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 