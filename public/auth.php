<?php
session_start();
require '../includes/config.php';

// Инициализация сессионных данных
$_SESSION['form_data'] = $_SESSION['form_data'] ?? [];
$_SESSION['error'] = $_SESSION['error'] ?? null;
$_SESSION['success'] = $_SESSION['success'] ?? null;

// Обработка входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_login'])) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Ошибка: Неверный логин или пароль";
        header("Location: auth.php");
        exit();
    }
}

// Обработка регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Сохранение данных формы
    $_SESSION['form_data'] = [
        'name' => $name,
        'email' => $email,
        'login' => $login,
        'role' => $role
    ];

    // Валидация данных
    $errors = [];
    if (empty($name)) $errors[] = 'Имя обязательно';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
    if (strlen($password) < 8) $errors[] = 'Пароль должен быть не менее 8 символов';
    if (strlen($login) < 3) $errors[] = 'Логин должен быть не менее 3 символов';

    if (!empty($errors)) {
        $_SESSION['error'] = "Ошибка: " . implode(', ', $errors);
        header("Location: auth.php?form=register");
        exit();
    }

    // Проверка уникальности логина и email
    try {
        // Проверка логина
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Ошибка: Пользователь с таким логином уже существует";
            header("Location: auth.php?form=register");
            exit();
        }

        // Проверка email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Ошибка: Пользователь с таким email уже существует";
            header("Location: auth.php?form=register");
            exit();
        }

        // Регистрация
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, login, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $login, $hashed_password, $role]);
        
        unset($_SESSION['form_data']);
        $_SESSION['success'] = "Регистрация успешна! Вы можете войти.";
        header("Location: auth.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Ошибка регистрации: " . $e->getMessage();
        header("Location: auth.php?form=register");
        exit();
    }
}

// Очистка сообщений при обновлении
if (empty($_POST)) {
    unset($_SESSION['error']);
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация и Регистрация | FlowHR</title>
    <!-- Подключаем Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Общие стили */
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4; /* Светлый фон */
            color: #2C3E50; /* Темно-серый текст */
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Контейнер для изображения и формы */
        .auth-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px; /* Максимальная ширина */
            background: white;
            border-radius: 15px; /* Закругленные углы */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Тень */
            overflow: hidden;
            flex-direction: column; /* Для мобильных устройств */
        }

        /* Изображение слева */
        .auth-image {
            display: none; /* Скрываем изображение на мобильных устройствах */
        }

        /* Контейнер для формы */
        .auth-container {
            width: 100%; /* Занимает всю ширину */
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* Выравнивание по верхнему краю */
            align-items: center;
            position: relative;
        }

        /* Форма */
        .auth-form {
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .auth-form h2 {
            margin-bottom: 20px;
            color: #2C3E50; /* Темно-серый заголовок */
            font-size: 24px;
            font-weight: bold;
        }

        /* Кнопки переключения */
        .toggle-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            width: 100%;
        }

        .toggle-buttons button {
            flex: 1;
            padding: 10px;
            background: none; /* Прозрачный фон */
            color: #FF6F61; /* Коралловый цвет текста */
            border: none;
            border-bottom: 2px solid transparent; /* Прозрачная граница */
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            margin: 0 5px;
        }

        .toggle-buttons button:hover {
            border-bottom: 2px solid #FF6F61; /* Коралловая граница при наведении */
        }

        .toggle-buttons button.active {
            border-bottom: 2px solid #FF6F61; /* Коралловая граница для активной кнопки */
            color: #FF6F61; /* Коралловый цвет текста */
        }

        /* Поля ввода */
        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px;
            padding-left: 40px; /* Отступ для иконки */
            border: 1px solid #E0E0E0; /* Серая граница */
            border-radius: 8px; /* Закругленные углы */
            font-size: 14px;
            color: #2C3E50; /* Темно-серый текст */
            box-sizing: border-box;
            transition: border-color 0.3s ease-in-out;
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: #FF6F61; /* Коралловый акцент */
            outline: none;
            box-shadow: 0 0 5px rgba(255, 111, 97, 0.5); /* Тень при фокусе */
        }

        /* Иконки внутри полей ввода */
        .input-group i {
            position: absolute;
            left: 12px; /* Расположение иконки по горизонтали */
            top: 50%; /* Вертикальное центрирование */
            transform: translateY(-50%); /* Корректировка по вертикали */
            color: #666666; /* Серый цвет иконок */
            font-size: 16px; /* Размер иконки */
            pointer-events: none; /* Запрет взаимодействия с иконкой */
        }

        /* Кнопка отправки */
        .auth-form button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #FF6F61; /* Коралловый цвет */
            color: #FFFFFF; /* Белый текст */
            border: none;
            border-radius: 8px; /* Закругленные углы */
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease-in-out;
            margin-top: 10px;
        }

        .auth-form button[type="submit"]:hover {
            background: #FF3B2F; /* Темно-коралловый при наведении */
        }

        /* Сообщения об ошибках и успехе */
        .error-message {
            color: #dc3545; /* Красный цвет для ошибок */
            margin-bottom: 15px;
            font-size: 14px;
        }

        .success-message {
            color: #28a745; /* Зеленый цвет для успешного сообщения */
            margin-bottom: 15px;
            font-size: 14px;
        }

        /* Анимация переключения форм */
        .form {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }

        .form.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Медиазапросы для мобильных устройств */
        @media (min-width: 768px) {
            .auth-wrapper {
                flex-direction: row; /* Возвращаем горизонтальное расположение */
                height: 500px; /* Фиксированная высота */
            }

            .auth-image {
                display: block; /* Показываем изображение */
                width: 50%; /* Фиксированная ширина */
                background-image: url('../assets/images/1.png'); /* Путь к изображению */
                background-size: cover;
                background-position: center;
            }

            .auth-container {
                width: 50%; /* Фиксированная ширина */
                padding: 30px;
            }
        }

        /* Адаптация для мобильных устройств */
        @media (max-width: 767px) {
            .auth-wrapper {
                height: auto; /* Автоматическая высота */
                margin: 20px; /* Отступы по краям */
            }

            .auth-container {
                padding: 15px; /* Уменьшенные отступы */
            }

            .auth-form h2 {
                font-size: 20px; /* Уменьшенный размер заголовка */
            }

            .toggle-buttons button {
                font-size: 14px; /* Уменьшенный размер текста кнопок */
            }

            .input-group input,
            .input-group select {
                padding: 10px; /* Уменьшенные отступы внутри полей */
                padding-left: 35px; /* Уменьшенный отступ для иконки */
                font-size: 12px; /* Уменьшенный размер текста */
            }

            .input-group i {
                font-size: 14px; /* Уменьшенный размер иконок */
            }

            .auth-form button[type="submit"] {
                padding: 10px; /* Уменьшенные отступы кнопки */
                font-size: 14px; /* Уменьшенный размер текста кнопки */
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="auth-wrapper">
        <div class="auth-image"></div>
        
        <div class="auth-container">
            <div class="auth-form">
                <div class="toggle-buttons">
                    <button id="loginBtn" class="<?= empty($_GET['form']) ? 'active' : '' ?>">Войти</button>
                    <button id="registerBtn" class="<?= isset($_GET['form']) ? 'active' : '' ?>">Регистрироваться</button>
                </div>

                <!-- Форма входа -->
                <form id="loginForm" method="post" class="form <?= empty($_GET['form']) ? 'active' : '' ?>">
                    <h2>Вход в систему</h2>
                    <?php if ($_SESSION['error'] && !isset($_GET['form'])): ?>
                        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <?php endif; ?>
                    <?php if ($_SESSION['success']): ?>
                        <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
                    <?php endif; ?>

                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="login" placeholder="Ваш логин" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Пароль" required>
                    </div>
                    <button type="submit" name="submit_login">Войти</button>
                </form>

                <!-- Форма регистрации -->
                <form id="registerForm" method="post" class="form <?= isset($_GET['form']) ? 'active' : '' ?>">
    <h2>Регистрация аккаунта</h2>
    
    <!-- Вывод сообщения об ошибке для регистрации -->
    <?php if ($_SESSION['error'] && isset($_GET['form'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); // Очистка ошибки после вывода ?>
    <?php endif; ?>

    <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="name" placeholder="Полное имя" 
               value="<?= htmlspecialchars($_SESSION['form_data']['name'] ?? '') ?>" required>
    </div>
    <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Email"
               value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>" required>
    </div>
    <div class="input-group">
        <i class="fas fa-user-tag"></i>
        <input type="text" name="login" placeholder="Логин"
               value="<?= htmlspecialchars($_SESSION['form_data']['login'] ?? '') ?>" required>
    </div>
    <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Пароль (минимум 8 символов)" required>
    </div>
    <div class="input-group">
        <i class="fas fa-user-tie"></i>
        <select name="role" required>
            <option value="Candidate" <?= ($_SESSION['form_data']['role'] ?? '') === 'Candidate' ? 'selected' : '' ?>>Ищу работу</option>
            <option value="HR" <?= ($_SESSION['form_data']['role'] ?? '') === 'HR' ? 'selected' : '' ?>>Ищу сотрудников</option>
        </select>
    </div>
    <button type="submit" name="register">Создать аккаунт</button>
</form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginBtn = document.getElementById('loginBtn');
            const registerBtn = document.getElementById('registerBtn');
            const forms = document.querySelectorAll('.form');

            // Автоматическое переключение форм
            if (window.location.search.includes('form=register')) {
                registerBtn.click();
            }

            // Обработчики переключения
            [loginBtn, registerBtn].forEach(btn => {
                btn.addEventListener('click', () => {
                    forms.forEach(form => form.classList.remove('active'));
                    document.getElementById(`${btn.id.replace('Btn', 'Form')}`).classList.add('active');
                    
                    btn.classList.add('active');
                    (btn === loginBtn ? registerBtn : loginBtn).classList.remove('active');
                });
            });
        });
    </script>
</body>
</html>
