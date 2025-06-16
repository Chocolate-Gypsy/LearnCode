<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();
if (!isLoggedIn()) {
    header('Location: /user/login.php');
    exit;
}

$courseId = $_GET['id'] ?? 1;
$userId = $_SESSION['user_id'];

// Получаем данные курса
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

// Получаем уроки курса
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number");
$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();

// Получаем прогресс пользователя
$stmt = $pdo->prepare("SELECT lesson_id FROM user_progress WHERE user_id = ? AND course_id = ?");
$stmt->execute([$userId, $courseId]);
$completedLessons = $stmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<div class="course-container">
    <div class="course-header">
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <p><?= htmlspecialchars($course['description']) ?></p>
        <div class="course-meta">
            <span class="difficulty"><?= ucfirst($course['difficulty']) ?></span>
            <span class="lessons-count"><?= count($lessons) ?> lessons</span>
        </div>
    </div>
    
    <div class="lessons-list">
        <?php foreach ($lessons as $lesson): ?>
        <div class="lesson-card <?= in_array($lesson['id'], $completedLessons) ? 'completed' : '' ?>">
            <a href="lesson.php?id=<?= $lesson['id'] ?>">
                <h3><?= htmlspecialchars($lesson['title']) ?></h3>
                <p><?= $lesson['duration_minutes'] ?> min</p>
                <?php if (in_array($lesson['id'], $completedLessons)): ?>
                <span class="completed-badge">✓ Completed</span>
                <?php endif; ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="/assets/js/courses.js" defer></script>
<?php include '../includes/footer.php'; ?>