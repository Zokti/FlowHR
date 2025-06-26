<?php
session_start();
require_once "../includes/config.php";

// Функция для логирования ошибок
function logError($message, $data = null) {
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    error_log($logMessage . "\n", 3, "../logs/profile_errors.log");
}

// Функция для отправки JSON-ответа
function sendJsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    logError("Unauthorized access attempt");
    sendJsonResponse(false, "Необходима авторизация");
}

$user_id = $_SESSION['user_id'];

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Invalid request method", $_SERVER['REQUEST_METHOD']);
    sendJsonResponse(false, "Неверный метод запроса");
}

// Получаем данные пользователя
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        logError("User not found", ['user_id' => $user_id]);
        sendJsonResponse(false, "Пользователь не найден");
    }
} catch (PDOException $e) {
    logError("Database error while fetching user", [
        'error' => $e->getMessage(),
        'user_id' => $user_id
    ]);
    sendJsonResponse(false, "Ошибка при получении данных пользователя");
}

// Обработка обновления профиля
if (isset($_POST['update_profile'])) {
    try {
        // Получаем и очищаем данные
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
        $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
        $city = !empty($_POST['city']) ? trim($_POST['city']) : null;

        // Валидация данных
        if (empty($name)) {
            sendJsonResponse(false, "Имя не может быть пустым");
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse(false, "Некорректный email");
        }

        if ($age !== null && ($age < 18 || $age > 100)) {
            sendJsonResponse(false, "Возраст должен быть от 18 до 100 лет");
        }

        // Проверка email на уникальность
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            logError("Email already in use", ['email' => $email, 'user_id' => $user_id]);
            sendJsonResponse(false, "Этот email уже используется другим пользователем");
        }

        // Обновляем данные пользователя
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?,
                email = ?,
                phone = ?,
                age = ?,
                city = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$name, $email, $phone, $age, $city, $user_id]);
        
        if ($result) {
            // Обновляем данные в сессии
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            // Рассчитываем новый прогресс профиля
            $stmt = $pdo->query("SELECT * FROM profile_fields");
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_weight = 0;
            $filled_weight = 0;
            
            foreach ($fields as $field) {
                $total_weight += $field['weight'];
                if (!empty($user[$field['field_name']])) {
                    $filled_weight += $field['weight'];
                }
            }
            
            $progress = ($total_weight > 0) ? round(($filled_weight / $total_weight) * 100) : 0;
            
            // Обновляем прогресс в базе данных
            $stmt = $pdo->prepare("UPDATE users SET profile_completion = ? WHERE id = ?");
            $stmt->execute([$progress, $user_id]);
            
            sendJsonResponse(true, "Профиль успешно обновлен", [
                'progress' => $progress
            ]);
        } else {
            logError("Failed to update user profile", [
                'user_id' => $user_id,
                'data' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'age' => $age,
                    'city' => $city
                ]
            ]);
            sendJsonResponse(false, "Ошибка при обновлении данных");
        }
    } catch (PDOException $e) {
        logError("Database error while updating profile", [
            'error' => $e->getMessage(),
            'user_id' => $user_id
        ]);
        sendJsonResponse(false, "Ошибка при обновлении данных: " . $e->getMessage());
    }
}

// Обработка загрузки аватарки
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    try {
        $avatar = $_FILES['avatar'];
        $target_dir = "../uploads/avatars/";
        
        // Проверяем существование директории
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $avatar_name = $user_id . "_" . time() . "_" . basename($avatar["name"]);
        $target_file = $target_dir . $avatar_name;
        
        // Проверяем, является ли файл изображением
        $check = getimagesize($avatar["tmp_name"]);
        if ($check === false) {
            logError("Invalid image file", ['file' => $avatar["name"]]);
            sendJsonResponse(false, "Файл не является изображением");
        }
        
        // Проверяем тип файла
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($avatar["type"], $allowed_types)) {
            logError("Invalid file type", ['type' => $avatar["type"]]);
            sendJsonResponse(false, "Разрешены только файлы JPG, PNG и GIF");
        }
        
        // Проверяем размер файла (максимум 5MB)
        if ($avatar["size"] > 5000000) {
            logError("File too large", ['size' => $avatar["size"]]);
            sendJsonResponse(false, "Файл слишком большой. Максимальный размер - 5MB");
        }
        
        if (move_uploaded_file($avatar["tmp_name"], $target_file)) {
            // Удаляем старую аватарку, если она не дефолтная
            if ($user['avatar'] !== 'default_avatar.png') {
                $old_avatar_path = "../uploads/avatars/" . $user['avatar'];
                if (file_exists($old_avatar_path)) {
                    unlink($old_avatar_path);
                }
            }
            
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$avatar_name, $user_id]);
            
            sendJsonResponse(true, "Аватар успешно обновлен", [
                'avatar' => $avatar_name
            ]);
        } else {
            logError("Failed to upload avatar", [
                'user_id' => $user_id,
                'file' => $avatar["name"]
            ]);
            sendJsonResponse(false, "Ошибка при загрузке файла");
        }
    } catch (Exception $e) {
        logError("Error while uploading avatar", [
            'error' => $e->getMessage(),
            'user_id' => $user_id
        ]);
        sendJsonResponse(false, "Ошибка при загрузке аватара: " . $e->getMessage());
    }
}

// Обработка удаления аватарки
if (isset($_POST['delete_avatar']) && $_POST['delete_avatar'] == '1') {
    try {
        if ($user['avatar'] !== 'default_avatar.png') {
            $avatar_path = "../uploads/avatars/" . $user['avatar'];
            if (file_exists($avatar_path)) {
                unlink($avatar_path);
            }
        }
        
        $stmt = $pdo->prepare("UPDATE users SET avatar = 'default_avatar.png' WHERE id = ?");
        $stmt->execute([$user_id]);
        
        sendJsonResponse(true, "Аватар успешно удален");
    } catch (Exception $e) {
        logError("Error while deleting avatar", [
            'error' => $e->getMessage(),
            'user_id' => $user_id
        ]);
        sendJsonResponse(false, "Ошибка при удалении аватара: " . $e->getMessage());
    }
}

// Если дошли до этой точки, значит запрос не обработан
sendJsonResponse(false, "Неизвестный запрос");
?>
