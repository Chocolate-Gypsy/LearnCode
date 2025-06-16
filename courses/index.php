<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();

// Устанавливаем заголовок страницы
$pageTitle = "All Courses - Learn Programming";

// Получаем список всех активных курсов
$stmt = $pdo->prepare("SELECT * FROM courses WHERE is_active = TRUE ORDER BY created_at DESC");
$stmt->execute();
$courses = $stmt->fetchAll();

// Если пользователь авторизован, получаем его прогресс
$userProgress = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT course_id, COUNT(*) as completed_lessons 
                          FROM user_progress 
                          WHERE user_id = ? 
                          GROUP BY course_id");
    $stmt->execute([$_SESSION['user_id']]);
    $progressData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Получаем общее количество уроков для каждого курса
    $stmt = $pdo->prepare("SELECT course_id, COUNT(*) as total_lessons 
                          FROM lessons 
                          GROUP BY course_id");
    $stmt->execute();
    $totalLessons = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Рассчитываем процент завершения для каждого курса
    foreach ($courses as $course) {
        $courseId = $course['id'];
        $completed = $progressData[$courseId] ?? 0;
        $total = $totalLessons[$courseId] ?? 1;
        $userProgress[$courseId] = [
            'completed' => $completed,
            'total' => $total,
            'percentage' => round(($completed / $total) * 100)
        ];
    }
}

// Подключаем header
include '../includes/header.php';
?>

<div class="courses-page">
    <div class="hero-section">
        <div class="hero-content">
            <h1>Start Your Coding Journey</h1>
            <p>Learn programming with our interactive courses designed for beginners and advanced learners alike.</p>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="hero-actions">
                <a href="/user/register.php" class="btn btn-primary">Get Started</a>
                <a href="/user/login.php" class="btn btn-outline">I Already Have an Account</a>
            </div>
            <?php endif; ?>
        </div>
        <div class="hero-image">
            <img src="/assets/images/coding-hero.png" alt="Coding Illustration">
        </div>
    </div>

    <div class="container">
        <div class="courses-header">
            <h2>Available Courses</h2>
            
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="difficulty-filter">Difficulty:</label>
                    <select id="difficulty-filter" class="form-select">
                        <option value="all">All Levels</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
                
                <div class="search-box">
                    <input type="text" id="course-search" placeholder="Search courses...">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>

        <div class="courses-grid" id="courses-container">
            <?php foreach ($courses as $course): ?>
            <div class="course-card" 
                 data-difficulty="<?= $course['difficulty'] ?>" 
                 data-title="<?= strtolower($course['title']) ?>">
                <div class="course-badge"><?= ucfirst($course['difficulty']) ?></div>
                
                <div class="course-icon">
                    <i class="fas <?= $course['icon'] ? $course['icon'] : 'fa-laptop-code' ?>"></i>
                </div>
                
                <div class="course-info">
                    <h3><?= htmlspecialchars($course['title']) ?></h3>
                    <p class="course-description"><?= htmlspecialchars($course['description']) ?></p>
                    
                    <?php if (isset($_SESSION['user_id']) && isset($userProgress[$course['id']])): ?>
                    <div class="progress-container">
                        <div class="progress-info">
                            <span><?= $userProgress[$course['id']]['completed'] ?>/<?= $userProgress[$course['id']]['total'] ?> lessons</span>
                            <span><?= $userProgress[$course['id']]['percentage'] ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $userProgress[$course['id']]['percentage'] ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="course-actions">
                    <a href="course.php?id=<?= $course['id'] ?>" class="btn btn-course">
                        <?= isset($userProgress[$course['id']]) ? 'Continue' : 'Start' ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($courses)): ?>
            <div class="no-courses">
                <i class="fas fa-book-open"></i>
                <h3>No courses available at the moment</h3>
                <p>Check back later or contact us for more information.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фильтрация курсов по сложности
    const difficultyFilter = document.getElementById('difficulty-filter');
    difficultyFilter.addEventListener('change', filterCourses);
    
    // Поиск курсов
    const courseSearch = document.getElementById('course-search');
    courseSearch.addEventListener('input', filterCourses);
    
    function filterCourses() {
        const difficulty = difficultyFilter.value;
        const searchTerm = courseSearch.value.toLowerCase();
        const courseCards = document.querySelectorAll('.course-card');
        
        courseCards.forEach(card => {
            const matchesDifficulty = difficulty === 'all' || card.dataset.difficulty === difficulty;
            const matchesSearch = card.dataset.title.includes(searchTerm) || 
                                 card.querySelector('.course-description').textContent.toLowerCase().includes(searchTerm);
            
            if (matchesDifficulty && matchesSearch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Анимация при наведении на карточки курсов
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '0 5px 15px rgba(0,0,0,0.05)';
        });
    });
});
</script>

<?php
// Подключаем footer
include '../includes/footer.php';
?>