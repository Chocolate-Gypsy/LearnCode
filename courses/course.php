<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();



// Получаем ID курса из URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    header('Location: /courses/');
    exit;
}

// Получаем данные курса
try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        header('Location: /courses/');
        exit;
    }
} catch (PDOException $e) {
    // Логирование ошибки
    error_log("Error fetching course: " . $e->getMessage());
    header('Location: /courses/');
    exit;
}

// Получаем уроки курса
try {
    $stmt = $pdo->prepare("SELECT * FROM lessons 
                          WHERE course_id = ? AND is_active = 1
                          ORDER BY order_number ASC");
    $stmt->execute([$course_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lessons = [];
    error_log("Error fetching lessons: " . $e->getMessage());
}

// Проверяем прогресс пользователя (если авторизован)
$user_progress = [];
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT lesson_id FROM user_progress 
                              WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        $user_progress = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (PDOException $e) {
        error_log("Error fetching user progress: " . $e->getMessage());
    }
}

// Установка заголовка страницы
$page_title = htmlspecialchars($course['title']) . " | LearnCode";

// Подключение шапки
include '../includes/header.php';
?>

<div class="course-header bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-3">
                        <li class="breadcrumb-item"><a href="/courses/">Все курсы</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($course['title']) ?></li>
                    </ol>
                </nav>
                <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($course['title']) ?></h1>
                <p class="lead mb-4"><?= htmlspecialchars($course['description']) ?></p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-signal me-1"></i>
                        <?= ucfirst(htmlspecialchars($course['difficulty'])) ?>
                    </span>
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-book me-1"></i>
                        <?= count($lessons) ?> уроков
                    </span>
                    <?php if ($course['icon']): ?>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-<?= htmlspecialchars($course['icon']) ?> me-1"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (isLoggedIn()): ?>
                    <div class="progress mb-3" style="height: 8px;">
                        <?php 
                        $completed_lessons = array_intersect(
                            array_column($lessons, 'id'),
                            $user_progress
                        );
                        $progress = count($lessons) > 0 
                            ? round(count($completed_lessons) / count($lessons) * 100) 
                            : 0;
                        ?>
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width: <?= $progress ?>%" 
                             aria-valuenow="<?= $progress ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                    <p class="small mb-0">Прогресс: <?= $progress ?>% завершено</p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-center">
                <div class="course-icon bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                     style="width: 120px; height: 120px;">
                    <i class="fas fa-<?= $course['icon'] ? htmlspecialchars($course['icon']) : 'book' ?> fa-3x"></i>
                </div>
                <?php if (isLoggedIn()): ?>
                    <a href="lesson.php?id=<?= $lessons[0]['id'] ?? '' ?>" class="btn btn-light btn-lg w-100">
                        <?= count($user_progress) > 0 ? 'Продолжить обучение' : 'Начать обучение' ?>
                    </a>
                <?php else: ?>
                    <a href="/user/register.php" class="btn btn-light btn-lg w-100">Начать обучение</a>
                    <p class="small mt-2">Уже есть аккаунт? <a href="/user/login.php" class="text-white">Войдите</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h2 class="mb-0">Содержание курса</h2>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($lessons)): ?>
                            <div class="list-group-item">
                                <p class="mb-0 text-muted">Уроки пока не добавлены</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($lessons as $index => $lesson): ?>
                                <a href="lesson.php?id=<?= $lesson['id'] ?>" 
                                   class="list-group-item list-group-item-action border-0 py-3 <?= in_array($lesson['id'], $user_progress) ? 'bg-light' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1">
                                                <span class="text-muted me-2"><?= $index + 1 ?>.</span>
                                                <?= htmlspecialchars($lesson['title']) ?>
                                            </h5>
                                            <small class="text-muted">
                                                <i class="far fa-clock me-1"></i>
                                                <?= $lesson['duration_minutes'] ?? 10 ?> мин
                                            </small>
                                        </div>
                                        <div>
                                            <?php if (in_array($lesson['id'], $user_progress)): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i> Завершено
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-lock-open me-1"></i> Доступно
                                                </span>
                                            <?php endif; ?>
                                            <i class="fas fa-chevron-right ms-3 text-muted"></i>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h2 class="mb-0">Описание курса</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($course['long_description'])): ?>
                        <?= nl2br(htmlspecialchars($course['long_description'])) ?>
                    <?php else: ?>
                        <p class="text-muted">Описание курса пока не добавлено</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h2 class="mb-0">Информация о курсе</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                            <span class="text-muted">Сложность</span>
                            <span class="fw-bold"><?= ucfirst(htmlspecialchars($course['difficulty'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                            <span class="text-muted">Уроков</span>
                            <span class="fw-bold"><?= count($lessons) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                            <span class="text-muted">Общая длительность</span>
                            <span class="fw-bold">
                                <?= array_sum(array_column($lessons, 'duration_minutes')) ?? 0 ?> мин
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                            <span class="text-muted">Дата создания</span>
                            <span class="fw-bold">
                                <?= date('d.m.Y', strtotime($course['created_at'])) ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <?php if (isLoggedIn() && !empty($user_progress)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h2 class="mb-0">Ваш прогресс</h2>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="progress-circle mx-auto" 
                                 data-value="<?= $progress / 100 ?>"
                                 data-size="120"
                                 data-thickness="8"
                                 data-fill="{
                                     'color': '#28a745'
                                 }">
                                <span class="progress-value fw-bold"><?= $progress ?>%</span>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="mb-1">Завершено уроков: <?= count($user_progress) ?>/<?= count($lessons) ?></p>
                            <a href="lesson.php?id=<?= $lessons[0]['id'] ?? '' ?>" class="btn btn-primary btn-sm">
                                <?= $progress == 100 ? 'Повторить курс' : 'Продолжить' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-white">
                    <h2 class="mb-0">Рекомендуемые курсы</h2>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT id, title, icon FROM courses 
                                              WHERE id != ? AND is_active = 1
                                              ORDER BY created_at DESC LIMIT 3");
                        $stmt->execute([$course_id]);
                        $recommended_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($recommended_courses as $rc): ?>
                            <a href="course.php?id=<?= $rc['id'] ?>" class="d-block mb-3 text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-<?= htmlspecialchars($rc['icon'] ?? 'book') ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?= htmlspecialchars($rc['title']) ?></h6>
                                        <small class="text-muted">Продолжить обучение</small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach;
                    } catch (PDOException $e) {
                        error_log("Error fetching recommended courses: " . $e->getMessage());
                        echo '<p class="text-muted">Не удалось загрузить рекомендуемые курсы</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Подключение подвала
include '../includes/footer.php';
?>