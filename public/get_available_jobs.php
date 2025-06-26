<?php
session_start();
require '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HR') {
    echo json_encode([]);
    exit();
}

$hr_id = $_SESSION['user_id'];
$candidate_id = $_GET['candidate_id'];

// Получаем все вакансии HR
$query_jobs = "SELECT id, title FROM jobs WHERE hr_id = :hr_id";
$stmt_jobs = $pdo->prepare($query_jobs);
$stmt_jobs->execute(['hr_id' => $hr_id]);
$jobs = $stmt_jobs->fetchAll(PDO::FETCH_ASSOC);

// Проверяем, какие вакансии уже предложены кандидату
$query_applied_jobs = "
    SELECT job_id FROM applications
    WHERE user_id = :candidate_id AND job_id IN (
        SELECT id FROM jobs WHERE hr_id = :hr_id
    )
";
$stmt_applied = $pdo->prepare($query_applied_jobs);
$stmt_applied->execute(['candidate_id' => $candidate_id, 'hr_id' => $hr_id]);
$applied_jobs = $stmt_applied->fetchAll(PDO::FETCH_COLUMN);

// Фильтруем вакансии, которые еще не предложены
$available_jobs = array_filter($jobs, function($job) use ($applied_jobs) {
    return !in_array($job['id'], $applied_jobs);
});

echo json_encode(array_values($available_jobs));