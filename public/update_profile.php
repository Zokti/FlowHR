<?php
session_start();
require_once "../config/config.php";

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Если была отправлена форма для изменения данных
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обновление логина и пароля
    $login = isset($_POST['login']) ? $_POST['login'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Загрузка аватарки или сброс стандартной
    $avatar = $_FILES['avatar'] ?? null;
    $reset_avatar = isset($_POST['reset_avatar']) && $_POST['reset_avatar'] == '1';

    try {
        // Проверка и обработка пароля
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET login = ?, password = ? WHERE id = ?");
            $stmt->execute([$login, $password_hash, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET login = ? WHERE id = ?");
            $stmt->execute([$login, $user_id]);
        }

        // Обработка аватарки
        if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/avatars/";
            $avatar_name = $user_id . "_" . basename($avatar["name"]);
            $target_file = $target_dir . $avatar_name;

            // Проверяем, что файл — это изображение
            $check = getimagesize($avatar["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($avatar["tmp_name"], $target_file)) {
                    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->execute([$avatar_name, $user_id]);
                } else {
                    throw new Exception("Ошибка при загрузке файла.");
                }
            } else {
                throw new Exception("Файл не является изображением.");
            }
        }

        // Если сбрасываем аватарку, ставим стандартную
        if ($reset_avatar) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = 'default_avatar.png' WHERE id = ?");
            $stmt->execute([$user_id]);
        }

        // Перенаправляем на профиль после обновления
        header("Location: profile.php");
        exit();

    } catch (Exception $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}
?>
