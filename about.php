<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';

session_start();

// Установка заголовка страницы
$page_title = "О нас | LearnCode";

// Подключение шапки
include 'includes/header.php';
?>

<div class="container py-5">
    <section class="about-section mb-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">О проекте LearnCode</h1>
                <p class="lead">LearnCode - это интерактивная платформа для изучения программирования с нуля.</p>
                <p>Наша миссия - сделать обучение программированию доступным, интересным и эффективным для каждого.</p>
            </div>
            <div class="col-lg-6">
                <img src="assets/images/about-hero.jpg" alt="О LearnCode" class="img-fluid rounded shadow">
            </div>
        </div>
    </section>

    <section class="features-section mb-5">
        <h2 class="text-center mb-5">Почему выбирают нас?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-4 p-3">
                            <i class="fas fa-laptop-code fa-2x"></i>
                        </div>
                        <h3>Практика в браузере</h3>
                        <p>Встроенная среда разработки позволяет практиковаться прямо во время обучения.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-4 p-3">
                            <i class="fas fa-graduation-cap fa-2x"></i>
                        </div>
                        <h3>Структурированные курсы</h3>
                        <p>Пошаговые уроки от основ до продвинутых тем с понятными объяснениями.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-4 p-3">
                            <i class="fas fa-trophy fa-2x"></i>
                        </div>
                        <h3>Система мотивации</h3>
                        <p>Достижения, рейтинги и сертификаты помогут сохранить интерес к обучению.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="team-section mb-5">
        <h2 class="text-center mb-5">Наша команда</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <img src="assets/images/team/team1.jpg" class="card-img-top" alt="Основатель проекта">
                    <div class="card-body text-center">
                        <h5 class="card-title">Александр Морозов</h5>
                        <p class="text-muted">Основатель &amp; Разработчик</p>
                        <div class="social-links">
                            <a href="#" class="text-primary me-2"><i class="fab fa-github"></i></a>
                            <a href="#" class="text-primary me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-primary"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <img src="assets/images/team/team2.jpg" class="card-img-top" alt="Главный методист">
                    <div class="card-body text-center">
                        <h5 class="card-title">Елена Смирнова</h5>
                        <p class="text-muted">Главный методист</p>
                        <div class="social-links">
                            <a href="#" class="text-primary me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-primary"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <img src="assets/images/team/team3.jpg" class="card-img-top" alt="Frontend разработчик">
                    <div class="card-body text-center">
                        <h5 class="card-title">Иван Петров</h5>
                        <p class="text-muted">Frontend разработчик</p>
                        <div class="social-links">
                            <a href="#" class="text-primary me-2"><i class="fab fa-github"></i></a>
                            <a href="#" class="text-primary"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section bg-light py-5 rounded-3 mb-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="display-5 fw-bold text-primary">10+</div>
                    <p class="text-muted">Курсов</p>
                </div>
                <div class="col-md-3">
                    <div class="display-5 fw-bold text-primary">500+</div>
                    <p class="text-muted">Уроков</p>
                </div>
                <div class="col-md-3">
                    <div class="display-5 fw-bold text-primary">10,000+</div>
                    <p class="text-muted">Студентов</p>
                </div>
                <div class="col-md-3">
                    <div class="display-5 fw-bold text-primary">95%</div>
                    <p class="text-muted">Удовлетворенности</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section text-center py-5 bg-primary text-white rounded-3">
        <h2 class="mb-4">Готовы начать обучение?</h2>
        <p class="lead mb-4">Присоединяйтесь к тысячам студентов, которые уже научились программировать с LearnCode</p>
        <?php if (isLoggedIn()): ?>
            <a href="/courses/" class="btn btn-light btn-lg px-4">К курсам</a>
        <?php else: ?>
            <a href="/user/register.php" class="btn btn-light btn-lg px-4">Начать бесплатно</a>
        <?php endif; ?>
    </section>
</div>

<?php
// Подключение подвала
include 'includes/footer.php';
?>