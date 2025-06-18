<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();

// Получаем параметры фильтрации
$difficulty = $_GET['difficulty'] ?? null;
$search = $_GET['search'] ?? null;

// Подготавливаем SQL запрос с учетом фильтров
$sql = "SELECT c.*, 
       (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id AND l.is_active = 1) as lesson_count,
       (SELECT COUNT(DISTINCT up.user_id) FROM user_progress up WHERE up.course_id = c.id) as students_count
        FROM courses c
        WHERE c.is_active = 1";

$params = [];

if ($difficulty && in_array($difficulty, ['beginner', 'intermediate', 'advanced'])) {
    $sql .= " AND c.difficulty = ?";
    $params[] = $difficulty;
}

if ($search) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY c.created_at DESC";

// Получаем курсы из базы данных
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $courses = [];
    error_log("Error fetching courses: " . $e->getMessage());
}

// Получаем прогресс пользователя (если авторизован)
$user_progress = [];
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT course_id, COUNT(*) as completed_lessons 
                              FROM user_progress 
                              WHERE user_id = ? 
                              GROUP BY course_id");
        $stmt->execute([$user_id]);
        $user_progress = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Error fetching user progress: " . $e->getMessage());
    }
}

// Установка заголовка страницы
$page_title = "Все курсы | LearnCode";

// Подключение шапки
include '../includes/header.php';
?>

<div class="courses-header bg-primary text-white py-5">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Все курсы</h1>
        <p class="lead mb-4">Выберите курс для изучения и начните свой путь в программировании</p>
        
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control form-control-lg" 
                       placeholder="Поиск курсов..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-4">
                <select name="difficulty" class="form-select form-select-lg">
                    <option value="">Все уровни</option>
                    <option value="beginner" <?= $difficulty === 'beginner' ? 'selected' : '' ?>>Начинающий</option>
                    <option value="intermediate" <?= $difficulty === 'intermediate' ? 'selected' : '' ?>>Средний</option>
                    <option value="advanced" <?= $difficulty === 'advanced' ? 'selected' : '' ?>>Продвинутый</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-light btn-lg w-100">Найти</button>
            </div>
        </form>
    </div>
</div>

<div class="container py-5">
    <?php if (!empty($search) || !empty($difficulty)): ?>
        <div class="mb-4">
            <h2 class="h4">
                Найдено курсов: <?= count($courses) ?>
                <?php if ($search): ?>
                    по запросу "<?= htmlspecialchars($search) ?>"
                <?php endif; ?>
                <?php if ($difficulty): ?>
                    уровня <?= $difficulty === 'beginner' ? 'начинающий' : ($difficulty === 'intermediate' ? 'средний' : 'продвинутый') ?>
                <?php endif; ?>
            </h2>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Сбросить фильтры</a>
        </div>
    <?php endif; ?>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (empty($courses)): ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h3 class="h5">Курсы не найдены</h3>
                        <p class="text-muted">Попробуйте изменить параметры поиска</p>
                        <a href="index.php" class="btn btn-primary">Все курсы</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <?php
                $progress = 0;
                if (isLoggedIn() && isset($user_progress[$course['id']])) {
                    $progress = $course['lesson_count'] > 0 
                        ? round($user_progress[$course['id']] / $course['lesson_count'] * 100) 
                        : 0;
                }
                ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm course-card">
                        <div class="card-img-top bg-light text-center py-4">
                            <i class="fas fa-<?= htmlspecialchars($course['icon'] ?? 'book') ?> fa-4x text-primary"></i>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-<?= 
                                    $course['difficulty'] === 'beginner' ? 'success' : 
                                    ($course['difficulty'] === 'intermediate' ? 'warning' : 'danger') 
                                ?>">
                                    <?= $course['difficulty'] === 'beginner' ? 'Начинающий' : 
                                       ($course['difficulty'] === 'intermediate' ? 'Средний' : 'Продвинутый') ?>
                                </span>
                                <small class="text-muted">
                                    <i class="fas fa-users me-1"></i>
                                    <?= $course['students_count'] ?? 0 ?>
                                </small>
                            </div>
                            <h3 class="h5 card-title"><?= htmlspecialchars($course['title']) ?></h3>
                            <p class="card-text text-muted"><?= htmlspecialchars($course['description']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-book me-1"></i>
                                    <?= $course['lesson_count'] ?> уроков
                                </small>
                                <?php if (isLoggedIn() && $progress > 0): ?>
                                    <small class="fw-bold">
                                        <?= $progress ?>% завершено
                                    </small>
                                <?php endif; ?>
                            </div>
                            <?php if (isLoggedIn() && $progress > 0): ?>
                                <div class="progress mb-3" style="height: 6px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: <?= $progress ?>%" 
                                         aria-valuenow="<?= $progress ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <a href="course.php?id=<?= $course['id'] ?>" class="btn btn-primary w-100">
                                <?php if (isLoggedIn() && $progress > 0): ?>
                                    <?= $progress == 100 ? 'Повторить курс' : 'Продолжить' ?>
                                <?php else: ?>
                                    Начать обучение
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Подключение подвала
include '../includes/footer.php';
?>