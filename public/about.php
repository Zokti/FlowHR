<?php
require '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нас - FlowHR</title>
    <!-- Подключаем Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Общие стили */
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #f4f4f4 60%, #ffe3db 100%);
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

            .about-section h2,
            .benefits-section h2,
            .testimonials-section h2,
            .partners-section h2,
            .team-section h2 {
                font-size: 24px; /* Уменьшаем размер заголовков */
            }

            .about-section p,
            .benefit-card p,
            .testimonial-card p,
            .team-member p {
                font-size: 14px; /* Уменьшаем размер текста */
            }

            .benefits-grid,
            .testimonials-grid,
            .team-grid {
                display: flex;
                overflow-x: auto; /* Горизонтальная прокрутка */
                gap: 20px;
                padding-bottom: 20px; /* Отступ для скролла */
                scroll-snap-type: x mandatory; /* Плавная прокрутка */
            }

            .benefit-card,
            .testimonial-card,
            .team-member {
                flex: 0 0 auto; /* Карточки не сжимаются */
                width: 80%; /* Ширина карточки */
                scroll-snap-align: start; /* Плавная прокрутка */
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                border-radius: 10px;
                padding: 15px;
                background: white;
                border: 1px solid #E0E0E0;
            }

            .testimonial-card img,
            .team-member img {
                width: 60px; /* Уменьшаем размер изображений */
                height: 60px;
            }

            .tech-icons i,
            .social-links a {
                font-size: 16px; /* Уменьшаем размер иконок */
            }

            .contact-button a {
                padding: 10px 20px; /* Уменьшаем размер кнопки */
                font-size: 14px;
            }
        }

        /* Остальные стили остаются без изменений */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: rgba(255,255,255,0.95);
            border-radius: 18px;
            box-shadow: 0 12px 32px rgba(255,111,97,0.08), 0 2px 8px rgba(44,62,80,0.06);
            padding: 32px 24px;
        }

        .header {
            text-align: center;
            padding: 50px 0;
        }

        .header h1 {
            font-size: 40px;
            color: #FF6F61;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px #ffe3db;
            transition: color 0.5s, letter-spacing 0.5s;
        }

        .header h1:hover {
            color: #2196F3;
            letter-spacing: 4px;
        }

        .header p {
            font-size: 20px;
            color: #2C3E50;
            opacity: 0.8;
        }

        .about-section {
            padding: 50px 0;
            text-align: center;
            opacity: 0;
            transform: translateY(60px) scale(0.98);
            transition: opacity 0.9s cubic-bezier(.77,0,.18,1), transform 0.9s cubic-bezier(.77,0,.18,1);
        }

        .about-section.in-view {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .about-section h2 {
            font-size: 32px;
            color: #FF6F61;
            margin-bottom: 24px;
            letter-spacing: 1px;
            transition: color 0.5s, letter-spacing 0.5s;
        }

        .about-section h2:hover {
            color: #2196F3;
            letter-spacing: 4px;
        }

        .about-section p {
            font-size: 16px;
            max-width: 800px;
            margin: 0 auto;
            color: #2C3E50; /* Темно-серый текст */
        }

        .benefits-section {
            padding: 50px 0;
            background: white; /* Белый фон */
            text-align: center;
            opacity: 0;
            transform: translateY(60px) scale(0.98);
            transition: opacity 0.9s cubic-bezier(.77,0,.18,1), transform 0.9s cubic-bezier(.77,0,.18,1);
        }

        .benefits-section.in-view {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .benefits-section h2 {
            font-size: 32px;
            color: #FF6F61;
            margin-bottom: 24px;
            letter-spacing: 1px;
            transition: color 0.5s, letter-spacing 0.5s;
        }

        .benefits-section h2:hover {
            color: #2196F3;
            letter-spacing: 4px;
        }

        .benefits-grid {
            display: flex;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .benefit-card {
            background: linear-gradient(120deg, #fff 80%, #ffe3db 100%);
            border-radius: 10px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 6px 24px rgba(255,111,97,0.10), 0 1.5px 6px rgba(44,62,80,0.06);
            transition: opacity 0.7s cubic-bezier(.77,0,.18,1), transform 0.7s cubic-bezier(.77,0,.18,1);
            opacity: 0;
            transform: scale(0.95) translateY(40px);
            border: 1.5px solid #ffe3db;
        }

        .benefit-card.in-view {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        .benefit-card:hover {
            box-shadow: 0 16px 32px rgba(255,111,97,0.18), 0 2px 8px rgba(44,62,80,0.10);
            transform: scale(1.04) translateY(-8px) !important;
            border-color: #FF6F61;
            z-index: 2;
        }

        .benefit-card i {
            font-size: 44px;
            color: #FF6F61;
            margin-bottom: 16px;
            transition: color 0.5s, transform 0.5s;
        }

        .benefit-card:hover i {
            color: #2196F3;
            transform: scale(1.2) rotate(-8deg);
        }

        .benefit-card h3 {
            font-size: 20px;
            color: #2C3E50; /* Темно-серый текст */
            margin-bottom: 10px;
        }

        .benefit-card p {
            font-size: 14px;
            color: #666666; /* Серый текст */
        }

        .testimonials-section {
            padding: 50px 0;
            text-align: center;
            opacity: 0;
            transform: translateY(60px) scale(0.98);
            transition: opacity 0.9s cubic-bezier(.77,0,.18,1), transform 0.9s cubic-bezier(.77,0,.18,1);
        }

        .testimonials-section.in-view {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .testimonials-section h2 {
            font-size: 32px;
            color: #FF6F61;
            margin-bottom: 24px;
            letter-spacing: 1px;
            transition: color 0.5s, letter-spacing 0.5s;
        }

        .testimonials-section h2:hover {
            color: #2196F3;
            letter-spacing: 4px;
        }

        .testimonials-grid {
            display: flex;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .testimonial-card {
            background: white; /* Белый фон */
            border-radius: 10px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 6px 24px rgba(255,111,97,0.10);
            transition: opacity 0.7s cubic-bezier(.77,0,.18,1), transform 0.7s cubic-bezier(.77,0,.18,1);
            opacity: 0;
            transform: scale(0.95) translateY(40px);
            border: 1.5px solid #ffe3db;
        }

        .testimonial-card.in-view {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        .testimonial-card:hover {
            box-shadow: 0 16px 32px rgba(255,111,97,0.18), 0 2px 8px rgba(44,62,80,0.10);
            transform: scale(1.04) translateY(-8px) !important;
            border-color: #FF6F61;
            z-index: 2;
        }

        .testimonial-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%; /* Круглая фотография */
            margin-bottom: 15px;
            box-shadow: 0 4px 16px rgba(255,111,97,0.10);
            border: 3px solid #ffe3db;
            transition: border-color 0.4s;
        }

        .testimonial-card:hover img {
            border-color: #FF6F61;
        }

        .testimonial-card h3 {
            font-size: 18px;
            color: #2C3E50; /* Темно-серый текст */
            margin-bottom: 10px;
        }

        .testimonial-card p {
            font-size: 14px;
            color: #666666; /* Серый текст */
            font-style: italic;
        }

        .team-section {
            padding: 50px 0;
            background: white; /* Белый фон */
            text-align: center;
            opacity: 0;
            transform: translateY(60px) scale(0.98);
            transition: opacity 0.9s cubic-bezier(.77,0,.18,1), transform 0.9s cubic-bezier(.77,0,.18,1);
        }

        .team-section.in-view {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .team-section h2 {
            font-size: 32px;
            color: #FF6F61;
            margin-bottom: 24px;
            letter-spacing: 1px;
            transition: color 0.5s, letter-spacing 0.5s;
        }

        .team-section h2:hover {
            color: #2196F3;
            letter-spacing: 4px;
        }

        .team-grid {
            display: flex;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .team-member {
            background: linear-gradient(120deg, #fff 80%, #ffe3db 100%);
            border-radius: 10px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 6px 24px rgba(255,111,97,0.10), 0 1.5px 6px rgba(44,62,80,0.06);
            transition: opacity 0.7s cubic-bezier(.77,0,.18,1), transform 0.7s cubic-bezier(.77,0,.18,1);
            opacity: 0;
            transform: scale(0.95) translateY(40px);
            border: 1.5px solid #ffe3db;
        }

        .team-member.in-view {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        .team-member:hover {
            box-shadow: 0 16px 32px rgba(255,111,97,0.18), 0 2px 8px rgba(44,62,80,0.10);
            transform: scale(1.04) translateY(-8px) !important;
            border-color: #FF6F61;
            z-index: 2;
        }

        .team-member img {
            width: 100px;
            height: 100px;
            border-radius: 50%; /* Круглая фотография */
            margin-bottom: 15px;
            box-shadow: 0 4px 16px rgba(255,111,97,0.10);
            border: 3px solid #ffe3db;
            transition: border-color 0.4s;
        }

        .team-member:hover img {
            border-color: #FF6F61;
        }

        .team-member h3 {
            font-size: 20px;
            color: #2C3E50; /* Темно-серый текст */
            margin-bottom: 10px;
        }

        .team-member p {
            font-size: 14px;
            color: #666666; /* Серый текст */
        }

        .tech-icons {
            margin-top: 10px;
        }

        .tech-icons i {
            font-size: 22px;
            margin: 0 6px;
            color: #FF6F61;
            transition: color 0.4s, text-shadow 0.4s;
            text-shadow: 0 1px 4px #ffe3db;
        }

        .tech-icons i:hover {
            color: #2196F3;
            text-shadow: 0 2px 8px #2196F3;
        }

        .social-links {
            margin-top: 10px;
        }

        .social-links a {
            color: #FF6F61;
            font-size: 22px;
            margin: 0 6px;
            transition: color 0.4s, text-shadow 0.4s;
            text-shadow: 0 1px 4px #ffe3db;
        }

        .social-links a:hover {
            color: #2196F3;
            text-shadow: 0 2px 8px #2196F3;
        }

        .contact-button {
            text-align: center;
            margin: 50px 0;
        }

        .contact-button a {
            background: linear-gradient(90deg, #FF6F61 0%, #FFB88C 100%);
            color: white;
            padding: 16px 36px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 0 16px 2px #FFB88C66, 0 2px 8px rgba(44,62,80,0.08);
            transition: background 0.4s, box-shadow 0.4s, transform 0.3s;
            border: none;
            outline: none;
            text-shadow: 0 1px 4px #FF6F61;
        }

        .contact-button a:hover {
            background: linear-gradient(90deg, #2196F3 0%, #FF6F61 100%);
            box-shadow: 0 0 32px 4px #2196F3AA, 0 2px 8px rgba(44,62,80,0.12);
            transform: scale(1.06);
        }

        /* --- КРАСИВЫЕ СОВРЕМЕННЫЕ СТИЛИ --- */
        .contact-section {
            margin-top: 48px;
            text-align: center;
            background: linear-gradient(120deg, #fff 80%, #ffe3db 100%);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(255,111,97,0.10), 0 2px 8px rgba(44,62,80,0.06);
            padding: 36px 20px 32px 20px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0;
            transform: translateY(60px) scale(0.98);
            transition: opacity 0.9s cubic-bezier(.77,0,.18,1), transform 0.9s cubic-bezier(.77,0,.18,1);
        }
        .contact-section.in-view {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .contact-section h2 {
            font-size: 30px;
            color: #FF6F61;
            margin-bottom: 18px;
            letter-spacing: 1px;
            transition: color 0.5s, letter-spacing 0.5s;
        }
        .contact-section h2:hover {
            color: #2196F3;
            letter-spacing: 4px;
        }
        .contact-section p {
            max-width: 500px;
            margin: 0 auto 24px;
            color: #555;
            font-size: 18px;
        }
        .contact-links {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            font-size: 22px;
        }
        .contact-link {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(255,111,97,0.08);
            padding: 12px 24px;
            transition: box-shadow 0.3s, background 0.3s, transform 0.3s;
            font-weight: 500;
        }
        .contact-link:hover {
            background: #ffe3db;
            box-shadow: 0 4px 16px #FF6F6166;
            transform: scale(1.04);
        }
        .contact-link i {
            font-size: 26px;
            transition: color 0.4s, text-shadow 0.4s;
            text-shadow: 0 1px 4px #ffe3db;
        }
        .contact-link.phone i { color: #2196F3; }
        .contact-link.email i { color: #FF6F61; }
        .contact-link.tg i { color: #229ED9; }
        .contact-link a {
            color: #2C3E50;
            text-decoration: none;
            transition: color 0.3s;
        }
        .contact-link a:hover {
            color: #2196F3;
        }
        @media (max-width: 600px) {
            .contact-section {
                padding: 24px 5px 20px 5px;
            }
            .contact-links {
                font-size: 18px;
            }
            .contact-link {
                padding: 10px 10px;
            }
        }
    </style>
    <script>
    // Анимация появления секций и карточек при скролле
    document.addEventListener('DOMContentLoaded', function() {
        const animatedSections = document.querySelectorAll('.about-section, .benefits-section, .testimonials-section, .team-section, .contact-section');
        const animatedCards = document.querySelectorAll('.benefit-card, .testimonial-card, .team-member');
        function animateOnScroll(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    observer.unobserve(entry.target);
                }
            });
        }
        const sectionObserver = new IntersectionObserver(animateOnScroll, { threshold: 0.2 });
        animatedSections.forEach(section => sectionObserver.observe(section));
        animatedCards.forEach(card => sectionObserver.observe(card));
    });
    </script>
</head>
<body>
    <!-- Заголовок -->
    <div class="header">
        <h1>FlowHR</h1>
        <p>Сервис для HR-специалистов</p>
    </div>

    <!-- О компании -->
    <div class="container">
        <div class="about-section">
            <h2>О компании</h2>
            <p>
                FlowHR — это современный сервис, созданный для упрощения работы HR-специалистов. 
                Мы помогаем находить лучших кандидатов, управлять вакансиями и оптимизировать процессы подбора персонала. 
                Наша миссия — сделать HR-процессы максимально эффективными и удобными.
            </p>
        </div>

        <!-- Ключевые преимущества -->
        <div class="benefits-section">
            <h2>Наши преимущества</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <i class="fas fa-users"></i>
                    <h3>Широкая база кандидатов</h3>
                    <p>Доступ к тысячам профилей кандидатов по всей стране.</p>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Аналитика и отчеты</h3>
                    <p>Подробная аналитика по всем этапам подбора персонала.</p>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-clock"></i>
                    <h3>Экономия времени</h3>
                    <p>Автоматизация рутинных процессов для HR-специалистов.</p>
                </div>
            </div>
        </div>

        <!-- Отзывы клиентов -->
        <div class="testimonials-section">
            <h2>Отзывы наших клиентов</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <img src="../assets/images/users/1.jpg" alt="Фото клиента">
                    <h3>Гора Алексей</h3>
                    <p>"FlowHR помог нам сократить время подбора персонала на 30%. Очень довольны результатом!"</p>
                </div>
                <div class="testimonial-card">
                    <img src="../assets/images/users/2.jpg" alt="Фото клиента">
                    <h3>Черман Алексей</h3>
                    <p>"Отличный сервис! Удобный интерфейс и мощные инструменты для работы с кандидатами."</p>
                </div>
                <div class="testimonial-card">
                    <img src="../assets/images/users/3.jpg" alt="Фото клиента">
                    <h3>Сотников Даниил</h3>
                    <p>"Спасибо за качественный сервис. Теперь мы можем сосредоточиться на стратегических задачах."</p>
                </div>
            </div>
        </div>

        <!-- Команда разработчиков -->
        <div class="team-section">
            <h2>Наша команда</h2>
            <div class="team-grid">
                <!-- Разработчик 1 -->
                <div class="team-member">
                    <img src="../assets/images/developers/1.jpg" alt="Фото разработчика">
                    <h3>Ульянов Кирилл</h3>
                    <p>Frontend-разработчик</p>
                    <div class="tech-icons">
                        <i class="fab fa-html5" title="HTML5"></i>
                        <i class="fab fa-css3-alt" title="CSS3"></i>
                        <i class="fab fa-js" title="JavaScript"></i>
                    </div>
                    <div class="social-links">
                        <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
                        <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
                    </div>
                </div>

                <!-- Разработчик 2 -->
                <div class="team-member">
                    <img src="../assets/images/developers/1.jpg" alt="Фото разработчика">
                    <h3>Ульянов Кирилл</h3>
                    <p>Backend-разработчик</p>
                    <div class="tech-icons">
                        <i class="fab fa-php" title="PHP"></i>
                        <i class="fab fa-laravel" title="Laravel"></i>
                        <i class="fab fa-database" title="SQL"></i>
                    </div>
                    <div class="social-links">
                        <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
                        <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
                    </div>
                </div>

                <!-- Разработчик 3 -->
                <div class="team-member">
                    <img src="../assets/images/developers/1.jpg" alt="Фото разработчика">
                    <h3>Ульянов Кирилл</h3>
                    <p>Дизайнер</p>
                    <div class="tech-icons">
                        <i class="fab fa-figma" title="Figma"></i>
                        <i class="fab fa-sketch" title="Sketch"></i>
                        <i class="fas fa-palette" title="UI/UX Design"></i>
                    </div>
                    <div class="social-links">
                        <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
                        <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Контакты -->
        <div class="contact-section">
            <h2>Связаться с нами</h2>
            <p>Если у вас есть вопросы, предложения или вы хотите сотрудничать с нами — свяжитесь удобным для вас способом. Мы всегда открыты для обратной связи!</p>
            <div class="contact-links">
                <div class="contact-link phone"><i class="fas fa-phone-alt"></i> <a href="tel:+79991234567">+7 (999) 123-45-67</a></div>
                <div class="contact-link email"><i class="fas fa-envelope"></i> <a href="mailto:info@flowhr.ru">info@flowhr.ru</a></div>
                <div class="contact-link tg"><i class="fab fa-telegram-plane"></i> <a href="https://t.me/flowhr_support" target="_blank">@flowhr_support</a></div>
            </div>
        </div>
    </div>
</body>
</html>