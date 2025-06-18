<?php
// Подключение необходимых файлов
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';

session_start();

// Установка заголовка страницы
$page_title = "LearnCode - Изучайте программирование с нуля";

// Подключение шапки
include 'includes/header.php';
?>

<main class="container mt-5">
    <section class="hero-section text-center py-5">
        <h1 class="display-4">Добро пожаловать в LearnCode</h1>
        <p class="lead">Изучайте программирование с нуля с помощью наших интерактивных курсов</p>
        
        <?php if (!isLoggedIn()): ?>
            <div class="mt-4">
                <a href="user/register.php" class="btn btn-primary btn-lg mx-2">Начать обучение</a>
                <a href="user/login.php" class="btn btn-outline-primary btn-lg mx-2">Войти</a>
            </div>
        <?php else: ?>
            <div class="mt-4">
                <a href="user/dashboard.php" class="btn btn-primary btn-lg mx-2">Мой кабинет</a>
                <a href="courses/" class="btn btn-outline-primary btn-lg mx-2">К курсам</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="features-section py-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Интерактивные уроки</h3>
                        <p class="card-text">Практикуйтесь прямо в браузере с нашей встроенной средой разработки.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Разнообразные языки</h3>
                        <p class="card-text">Python, JavaScript, PHP и другие популярные языки программирования.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Сертификаты</h3>
                        <p class="card-text">Получайте сертификаты после прохождения курсов.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="popular-courses py-5">
        <h2 class="text-center mb-5">Популярные курсы</h2>
        <div class="row">
            <?php
            // Запрос к базе данных для получения популярных курсов
            try {
                $stmt = $pdo->query("SELECT * FROM courses WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 3");
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($courses as $course) {
                    echo '<div class="col-md-4 mb-4">';
                    echo '<div class="card h-100">';
                    echo '<img src="assets/images/courses/' . htmlspecialchars($course['image']) . '" class="card-img-top" alt="' . htmlspecialchars($course['title']) . '">';
                    echo '<div class="card-body">';
                    echo '<h3 class="card-title">' . htmlspecialchars($course['title']) . '</h3>';
                    echo '<p class="card-text">' . htmlspecialchars(substr($course['description'], 0, 100)) . '...</p>';
                    echo '<a href="courses/course.php?id=' . $course['id'] . '" class="btn btn-primary">Подробнее</a>';
                    echo '</div></div></div>';
                }
            } catch (PDOException $e) {
                echo '<div class="col-12"><div class="alert alert-danger">Не удалось загрузить курсы. Пожалуйста, попробуйте позже.</div></div>';
            }
            ?>
        </div>
        <div class="text-center mt-4">
            <a href="courses/" class="btn btn-outline-primary btn-lg">Все курсы</a>
        </div>
    </section>
</main>

<?php
// Подключение подвала
include 'includes/footer.php';
?>