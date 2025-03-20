<?php
require '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - FlowHR</title>
    <!-- Подключаем Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Подключаем API Яндекс.Карт -->
    <script src="https://api-maps.yandex.ru/2.1/?apikey=ваш_api_ключ&lang=ru_RU" type="text/javascript"></script>
    <style>
        /* Общие стили */
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4; /* Светлый фон */
            color: #2C3E50; /* Темно-серый текст */
            margin: 0;
            padding: 0;
            line-height: 1.6;
            margin-left: 250px; /* Отступ для бокового меню */
        }

        /* Адаптация для мобильных устройств */
        @media (max-width: 768px) {
            body {
                margin-left: 0; /* Убираем отступ для мобильных устройств */
            }

            .container {
                padding: 10px; /* Уменьшаем отступы */
            }

            .header h1 {
                font-size: 28px; /* Уменьшаем размер заголовка */
            }

            .header p {
                font-size: 16px; /* Уменьшаем размер текста */
            }

            .contact-cards {
                flex-direction: column; /* Карточки в столбик */
                gap: 20px;
            }

            .contact-card {
                width: 100%; /* Карточки на всю ширину */
            }

            #map {
                height: 300px; /* Уменьшаем высоту карты на мобильных устройствах */
            }
        }

        /* Остальные стили остаются без изменений */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            padding: 50px 0;
        }

        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            color: #FF6F61; /* Коралловый цвет */
        }

        .header p {
            font-size: 18px;
            margin: 0;
            color: #2C3E50; /* Темно-серый текст */
        }

        .contact-cards {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            padding: 50px 0;
        }

        .contact-card {
            background: white; /* Белый фон */
            border-radius: 15px;
            padding: 30px;
            width: 300px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Тень */
            text-align: center;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .contact-card:hover {
            transform: translateY(-10px); /* Эффект поднятия */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); /* Увеличенная тень */
        }

        .contact-card i {
            font-size: 40px;
            color: #FF6F61; /* Коралловый цвет */
            margin-bottom: 20px;
        }

        .contact-card h3 {
            font-size: 24px;
            color: #2C3E50; /* Темно-серый текст */
            margin-bottom: 10px;
        }

        .contact-card p {
            font-size: 16px;
            color: #666666; /* Серый текст */
            margin-bottom: 20px;
        }

        .contact-card a {
            color: #FF6F61; /* Коралловый цвет */
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s ease-in-out;
        }

        .contact-card a:hover {
            color: #FF3B2F; /* Темно-коралловый при наведении */
        }

        #map {
            width: 100%;
            height: 400px; /* Высота карты */
            margin: 50px 0;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Тень */
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .contact-cards {
            opacity: 0;
            animation: fadeInUp 0.5s ease-in-out forwards;
        }
    </style>
</head>
<body>
    <!-- Заголовок -->
    <div class="header">
        <h1>Контакты</h1>
        <p>Свяжитесь с нами, если у вас есть вопросы или предложения.</p>
    </div>

    <!-- Карточки с контактной информацией -->
    <div class="container">
        <div class="contact-cards">
            <!-- Карточка с почтой -->
            <div class="contact-card">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p>Напишите нам на почту, и мы ответим в ближайшее время.</p>
                <a href="mailto:dokl200100300400@gmail.com">dokl200100300400@gmail.com</a>
            </div>

            <!-- Карточка с телефоном -->
            <div class="contact-card">
                <i class="fas fa-phone"></i>
                <h3>Телефон</h3>
                <p>Позвоните нам, чтобы обсудить ваши вопросы.</p>
                <a href="tel:+79204990656">+7 (920) 499-06-56</a>
            </div>

            <!-- Карточка с адресом -->
            <div class="contact-card">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Адрес</h3>
                <p>Мы находимся по адресу: г. Таганрог, ул. Чехова, 320А.</p>
                <a href="#map">Посмотреть на карте</a>
            </div>
        </div>

        <!-- Карта -->
        <div id="map"></div>
    </div>

    <!-- Скрипт для инициализации Яндекс.Карты -->
    <script type="text/javascript">
        // Инициализация карты
        ymaps.ready(init);

        function init() {
            // Координаты адреса: г. Таганрог, ул. Чехова, 320А
            const address = "г. Таганрог, ул. Чехова, 320А";
            const coordinates = [47.2218, 38.9176]; // Широта и долгота

            // Создаем карту
            const map = new ymaps.Map("map", {
                center: coordinates, // Центр карты
                zoom: 16, // Масштаб
                controls: ["zoomControl", "fullscreenControl"] // Элементы управления
            });

            // Добавляем метку
            const placemark = new ymaps.Placemark(coordinates, {
                hintContent: address, // Подсказка при наведении
                balloonContent: address // Текст в балуне
            });

            map.geoObjects.add(placemark); // Добавляем метку на карту
        }
    </script>
</body>
</html>