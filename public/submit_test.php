<?php
session_start();
require '../includes/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем ID теста из формы
$test_id = $_POST['test_id']; // Предполагаем, что test_id передается через скрытое поле в форме

// Получаем ответы пользователя
$user_answers = [];
foreach ($_POST as $key => $value) {
    if (strpos($key, 'q') === 0) { // Ответы на вопросы начинаются с "q"
        $question_id = substr($key, 1); // Извлекаем ID вопроса
        $user_answers[$question_id] = $value; // Сохраняем ответ пользователя
    }
}

// Получаем правильные ответы из базы данных
$correct_answers = [];
$answers_query = "
    SELECT a.question_id, a.id 
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    WHERE a.is_correct = 1 AND q.test_id = ?
";
$answers_stmt = $pdo->prepare($answers_query);
$answers_stmt->execute([$test_id]);
$correct_answers = $answers_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Ключ - ID вопроса, значение - ID правильного ответа

// Подсчитываем количество правильных ответов
$score = 0;
foreach ($user_answers as $question_id => $answer_id) {
    if (isset($correct_answers[$question_id]) && $correct_answers[$question_id] == $answer_id) {
        $score += 10; // Каждый правильный ответ дает 10 баллов
    }
}

// Сохраняем результат в базу данных
$user_id = $_SESSION['user_id'];
$time_taken = (int)$_POST['time_taken']; // Преобразуем значение в целое число
if ($time_taken <= 0) {
    $time_taken = 0; // Если значение пустое или некорректное, устанавливаем 0
}

$insert_query = "INSERT INTO results (test_id, user_id, score, time_taken) VALUES (?, ?, ?, ?)";
$insert_stmt = $pdo->prepare($insert_query);
$insert_stmt->execute([$test_id, $user_id, $score, $time_taken]);

// Перенаправляем пользователя на страницу с результатами
header("Location: results.php?test_id=$test_id");
exit();