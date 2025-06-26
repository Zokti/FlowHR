<?php
require __DIR__ . '/../../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

// Получение данных из запроса
$data = json_decode(file_get_contents('php://input'), true);
$job_id = $data['job_id'] ?? null;
$match_type = $data['match_type'] ?? null;

if (!$job_id || !$match_type) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Не указаны необходимые параметры']);
    exit;
}

try {
    // Получение информации о вакансии
    $stmt = $pdo->prepare("
        SELECT j.*, u.id as hr_id
        FROM jobs j
        JOIN users u ON j.hr_id = u.id
        WHERE j.id = ? AND u.id = ? AND u.role = 'HR'
    ");
    $stmt->execute([$job_id, $_SESSION['user_id']]);
    $job = $stmt->fetch();

    if (!$job) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Вакансия не найдена']);
        exit;
    }

    // Получение навыков вакансии
    $stmt = $pdo->prepare("
        SELECT s.id, s.name 
        FROM job_skills js
        JOIN skills s ON js.skill_id = s.id
        WHERE js.job_id = ?
    ");
    $stmt->execute([$job_id]);
    $job_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found job skills: " . json_encode($job_skills));

    if (empty($job_skills)) {
        error_log("No skills found for job");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'У вакансии не указаны навыки']);
        exit;
    }

    // Получение уже предложенных резюме
    $stmt = $pdo->prepare("
        SELECT entity_id 
        FROM applications 
        WHERE job_id = ? AND entity_type = 'resume'
    ");
    $stmt->execute([$job_id]);
    $proposed_resumes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Proposed resumes: " . json_encode($proposed_resumes));

    // Поиск подходящих резюме
    $sql = "
        SELECT 
            r.id,
            r.title,
            r.experience_id,
            r.salary_id,
            u.id as user_id,
            u.name,
            u.email,
            GROUP_CONCAT(DISTINCT s.id ORDER BY s.id) as skill_ids,
            GROUP_CONCAT(DISTINCT s.name ORDER BY s.id) as skill_names,
            COUNT(DISTINCT s.id) as total_skills
        FROM resumes r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN resume_skills rs ON r.id = rs.resume_id
        LEFT JOIN skills s ON rs.skill_id = s.id
        WHERE u.role = 'candidate'
        AND r.is_published = 1
        " . (!empty($proposed_resumes) ? "AND r.id NOT IN (" . implode(',', $proposed_resumes) . ")" : "") . "
        GROUP BY r.id, r.title, r.experience_id, r.salary_id, u.id, u.name, u.email
        HAVING skill_ids IS NOT NULL
    ";
    
    error_log("SQL Query: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resumes = $stmt->fetchAll();
    error_log("Found resumes: " . json_encode($resumes));

    $matched_resumes = [];
    $job_skill_ids = array_column($job_skills, 'id');
    
    foreach ($resumes as $resume) {
        if (empty($resume['skill_ids'])) {
            error_log("Resume " . $resume['id'] . " has no skills");
            continue;
        }

        $resume_skill_ids = array_filter(explode(',', $resume['skill_ids']));
        $matching_skills = array_intersect($job_skill_ids, $resume_skill_ids);
        $matching_count = count($matching_skills);
        $job_skills_count = count($job_skill_ids);

        error_log("=== Resume ID: " . $resume['id'] . " ===");
        error_log("Resume title: " . $resume['title']);
        error_log("Resume skill IDs (raw): " . $resume['skill_ids']);
        error_log("Resume skill IDs (array): " . json_encode($resume_skill_ids));
        error_log("Job skill IDs: " . json_encode($job_skill_ids));
        error_log("Matching skills: " . json_encode($matching_skills));
        error_log("Matching count: " . $matching_count . " of " . $job_skills_count);
        error_log("Match type: " . $match_type);
        error_log("Is full match: " . ($matching_count == $job_skills_count && $job_skills_count > 0 ? "yes" : "no"));
        error_log("Is partial match: " . ($matching_count >= ceil($job_skills_count / 2) && $job_skills_count > 0 ? "yes" : "no"));

        if ($match_type == '100' && $matching_count == $job_skills_count && $job_skills_count > 0) {
            $matched_resumes[] = $resume;
            error_log("Full match found for resume " . $resume['id']);
        } elseif ($match_type == '50' && $matching_count >= ceil($job_skills_count / 2) && $job_skills_count > 0) {
            $matched_resumes[] = $resume;
            error_log("Partial match found for resume " . $resume['id']);
        } else {
            error_log("No match for resume " . $resume['id']);
        }
        error_log("===================");
    }

    if (empty($matched_resumes)) {
        echo json_encode([
            'success' => true,
            'message' => 'Подходящих кандидатов не найдено',
            'matched_count' => 0
        ]);
        exit;
    }

    // Отправка предложений о работе
    $pdo->beginTransaction();
    try {
        $offers_sent = 0;
        foreach ($matched_resumes as $resume) {
            $stmt = $pdo->prepare("
                INSERT INTO applications (
                    job_id,
                    hr_id,
                    candidate_id,
                    entity_type,
                    entity_id,
                    status,
                    created_at
                ) VALUES (?, ?, ?, 'resume', ?, 'pending', NOW())
            ");
            $stmt->execute([
                $job_id,
                $_SESSION['user_id'],
                $resume['user_id'],
                $resume['id']
            ]);
            $offers_sent++;
        }
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => sprintf(
                'Успешно отправлено %d предложений из %d подходящих кандидатов',
                $offers_sent,
                count($matched_resumes)
            ),
            'matched_count' => count($matched_resumes),
            'offers_sent' => $offers_sent
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error sending offers: " . $e->getMessage());
        throw $e;
    }

} catch(PDOException $e) {
    error_log("Database error in match_candidates.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных при подборе кандидатов']);
} catch(Exception $e) {
    error_log("General error in match_candidates.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка при подборе кандидатов']);
} 