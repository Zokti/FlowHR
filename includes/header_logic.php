<?php
session_start();
require 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Гость';
$user_role = $_SESSION['role'] ?? null;

// Массив ролей
$roleNames = [
    'HR' => 'HR-Специалист',
    'candidate' => 'Кандидат',
    'moderator' => 'Модератор',
    'admin' => 'Администратор'
];

$roleText = $roleNames[$user_role] ?? 'Неизвестная роль';
// Закрывающий тег ?>