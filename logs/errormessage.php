<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка регистрации</title>
    <style>
        .error-message {
            color: red;
            display: none;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid red;
            border-radius: 4px;
            background-color: #ffe6e6;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin-top: 50px;">
        <button onclick="showError()">Проверить регистрацию</button>
        <div id="errorMessage" class="error-message">
            Пользователь с данным email или логином уже зарегистрирован!
        </div>
    </div>

    <script>
        function showError() {
            const errorMessage = document.getElementById('errorMessage');
            
            // Показываем сообщение
            errorMessage.style.display = 'block';
            
            // Скрываем сообщение через 3 секунды
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>