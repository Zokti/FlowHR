<?php
require_once "../includes/config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $experience_id = $_POST['experience_id'];
    $salary_id = $_POST['salary_id'];
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    $hr_id = 1; // Здесь можно использовать $_SESSION['user_id']

    // Вставляем вакансию
    $stmt = $pdo->prepare("INSERT INTO jobs (title, description, hr_id, experience_id, salary_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $hr_id, $experience_id, $salary_id]);
    $job_id = $pdo->lastInsertId();

    // Привязываем навыки к вакансии
    if (!empty($skills)) {
        $stmt = $pdo->prepare("INSERT INTO job_skills (job_id, skill_id) VALUES (?, ?)");
        foreach ($skills as $skill_id) {
            $stmt->execute([$job_id, $skill_id]);
        }
    }

    header("Location: my_vacancy.php");
    exit();
}
?>
