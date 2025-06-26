<?php
require __DIR__ . '/../../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

// Получение ID вакансии
$job_id = $_GET['job_id'] ?? null;

if (!$job_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Не указан ID вакансии']);
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
        error_log("Job not found for ID: " . $job_id);
        http_response_code(404);
        echo json_encode(['error' => 'Вакансия не найдена']);
        exit;
    }

    error_log("Found job: " . json_encode($job));

    // Проверка данных в таблицах
    error_log("=== Database Check ===");
    
    // Проверка навыков вакансии
    $stmt = $pdo->prepare("
        SELECT js.job_id, js.skill_id, s.name as skill_name
        FROM job_skills js
        JOIN skills s ON js.skill_id = s.id
        WHERE js.job_id = ?
    ");
    $stmt->execute([$job_id]);
    $job_skills_check = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Job skills in database: " . json_encode($job_skills_check, JSON_UNESCAPED_UNICODE));
    
    // Проверка навыков резюме
    $stmt = $pdo->prepare("
        SELECT rs.resume_id, rs.skill_id, s.name as skill_name, r.title as resume_title
        FROM resume_skills rs
        JOIN skills s ON rs.skill_id = s.id
        JOIN resumes r ON rs.resume_id = r.id
        WHERE r.is_published = 1
    ");
    $stmt->execute();
    $resume_skills_check = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Resume skills in database: " . json_encode($resume_skills_check, JSON_UNESCAPED_UNICODE));
    
    // Проверка всех навыков
    $stmt = $pdo->prepare("SELECT * FROM skills");
    $stmt->execute();
    $all_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("All skills in database: " . json_encode($all_skills, JSON_UNESCAPED_UNICODE));
    
    error_log("=== End Database Check ===");

    // Получение навыков вакансии
    $stmt = $pdo->prepare("
        SELECT s.id, s.name 
        FROM job_skills js
        JOIN skills s ON js.skill_id = s.id
        WHERE js.job_id = ?
    ");
    $stmt->execute([$job_id]);
    $job_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Job skills: " . json_encode($job_skills));

    if (empty($job_skills)) {
        echo json_encode(['full_match' => 0, 'partial_match' => 0]);
        exit;
    }

    // Получение ID навыков вакансии
    $job_skill_ids = array_column($job_skills, 'id');
    error_log("Job skill IDs: " . json_encode($job_skill_ids));

    // Получение уже предложенных резюме
    $stmt = $pdo->prepare("
        SELECT entity_id 
        FROM applications 
        WHERE job_id = ? AND entity_type = 'resume'
    ");
    $stmt->execute([$job_id]);
    $proposed_resumes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Proposed resumes: " . json_encode($proposed_resumes));

    // Получение всех опубликованных резюме с их навыками
    $sql = "
        SELECT 
            r.id,
            r.title,
            GROUP_CONCAT(DISTINCT s.id) as skill_ids,
            GROUP_CONCAT(DISTINCT s.name) as skill_names,
            COUNT(DISTINCT s.id) as total_skills,
            COUNT(DISTINCT CASE WHEN s.id IN (" . implode(',', $job_skill_ids) . ") THEN s.id END) as matching_skills_count
        FROM resumes r
        LEFT JOIN resume_skills rs ON r.id = rs.resume_id
        LEFT JOIN skills s ON rs.skill_id = s.id
        WHERE r.is_published = 1
        " . (!empty($proposed_resumes) ? "AND r.id NOT IN (" . implode(',', $proposed_resumes) . ")" : "") . "
        GROUP BY r.id, r.title
        HAVING total_skills > 0
    ";
    
    error_log("SQL Query: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found resumes: " . json_encode($resumes));

    $full_match_count = 0;
    $partial_match_count = 0;

    foreach ($resumes as $resume) {
        error_log("=== Resume Debug ===");
        error_log("Resume ID: " . $resume['id']);
        error_log("Resume title: " . $resume['title']);
        error_log("Total skills: " . $resume['total_skills']);
        error_log("Matching skills count: " . $resume['matching_skills_count']);

        // Определяем тип совпадения
        if ($resume['matching_skills_count'] == count($job_skill_ids)) {
            $full_match_count++;
            error_log("Match type: full");
        } elseif ($resume['matching_skills_count'] >= ceil(count($job_skill_ids) / 2)) {
            $partial_match_count++;
            error_log("Match type: partial");
        } else {
            error_log("Match type: none");
        }
        error_log("===================");
    }

    error_log("Final counts - Full match: " . $full_match_count . ", Partial match: " . $partial_match_count);

    echo json_encode([
        'full_match' => $full_match_count,
        'partial_match' => $partial_match_count,
        'debug' => [
            'job_skills' => $job_skills,
            'total_resumes' => count($resumes),
            'job_skill_count' => count($job_skill_ids)
        ]
    ]);

} catch(PDOException $e) {
    error_log("Database error in get_match_counts.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных при подсчете совпадений']);
} catch(Exception $e) {
    error_log("General error in get_match_counts.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Произошла ошибка при подсчете совпадений']);
} 