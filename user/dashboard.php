<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();

// Проверяем авторизацию пользователя
if (!isLoggedIn()) {
    header('Location: /user/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Получаем активные курсы пользователя
$stmt = $pdo->prepare("SELECT c.id, c.title, c.description, c.icon,
                      COUNT(up.lesson_id) as completed_lessons,
                      COUNT(l.id) as total_lessons
                      FROM courses c
                      LEFT JOIN lessons l ON c.id = l.course_id
                      LEFT JOIN user_progress up ON c.id = up.course_id AND up.user_id = ? AND up.lesson_id = l.id
                      GROUP BY c.id
                      HAVING completed_lessons > 0 OR c.id IN (
                          SELECT course_id FROM user_progress WHERE user_id = ?
                      )");
$stmt->execute([$userId, $userId]);
$activeCourses = $stmt->fetchAll();

// Получаем новые курсы
$stmt = $pdo->prepare("SELECT * FROM courses 
                      WHERE is_active = TRUE 
                      AND id NOT IN (
                          SELECT course_id FROM user_progress WHERE user_id = ?
                      )
                      ORDER BY created_at DESC
                      LIMIT 3");
$stmt->execute([$userId]);
$newCourses = $stmt->fetchAll();

// Получаем достижения
$stmt = $pdo->prepare("SELECT * FROM achievements WHERE user_id = ? ORDER BY earned_at DESC LIMIT 3");
$stmt->execute([$userId]);
$achievements = $stmt->fetchAll();

$pageTitle = "Личный кабинет - Learn Programming";
include '../includes/header.php';
?>

<div class="dashboard-container">
    <div class="welcome-section">
        <h1>Добро пожаловать, <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>!</h1>
        <p>Продолжайте учиться и достигайте новых высот в программировании</p>
        
        <div class="streak-counter">
            <i class="fas fa-fire"></i>
            <span>Серия: <?= $user['streak_days'] ?? 0 ?> дней подряд</span>
        </div>
    </div>
    
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $user['completed_lessons'] ?? 0 ?></span>
                <span class="stat-label">уроков пройдено</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $user['xp'] ?? 0 ?></span>
                <span class="stat-label">очков опыта</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= count($achievements) ?></span>
                <span class="stat-label">достижений</span>
            </div>
        </div>
    </div>
    
    <div class="courses-section">
        <h2>Активные курсы</h2>
        
        <?php if (empty($activeCourses)): ?>
        <div class="empty-state">
            <i class="fas fa-book"></i>
            <p>Вы еще не начали ни одного курса</p>
            <a href="/courses/" class="btn btn-primary">Начать обучение</a>
        </div>
        <?php else: ?>
        <div class="courses-grid">
            <?php foreach ($activeCourses as $course): ?>
            <div class="course-card">
                <div class="course-header">
                    <div class="course-icon">
                        <i class="fas <?= htmlspecialchars($course['icon'] ?? 'fa-laptop-code') ?>"></i>
                    </div>
                    <h3><?= htmlspecialchars($course['title']) ?></h3>
                </div>
                
                <p class="course-description"><?= htmlspecialchars($course['description']) ?></p>
                
                <div class="progress-container">
                    <div class="progress-info">
                        <span><?= $course['completed_lessons'] ?>/<?= $course['total_lessons'] ?> уроков</span>
                        <span><?= round(($course['completed_lessons'] / $course['total_lessons']) * 100) ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= round(($course['completed_lessons'] / $course['total_lessons']) * 100) ?>%"></div>
                    </div>
                </div>
                
                <a href="/courses/course.php?id=<?= $course['id'] ?>" class="btn btn-course">
                    <?= $course['completed_lessons'] > 0 ? 'Продолжить' : 'Начать' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="recommendations-section">
        <div class="new-courses">
            <h2>Новые курсы</h2>
            
            <div class="courses-list">
                <?php foreach ($newCourses as $course): ?>
                <div class="course-item">
                    <div class="course-icon">
                        <i class="fas <?= htmlspecialchars($course['icon'] ?? 'fa-laptop-code') ?>"></i>
                    </div>
                    <div class="course-info">
                        <h4><?= htmlspecialchars($course['title']) ?></h4>
                        <p><?= htmlspecialchars($course['description']) ?></p>
                    </div>
                    <a href="/courses/course.php?id=<?= $course['id'] ?>" class="btn btn-sm">Начать</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="achievements">
            <h2>Последние достижения</h2>
            
            <?php if (empty($achievements)): ?>
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <p>Пока нет достижений</p>
            </div>
            <?php else: ?>
            <div class="achievements-list">
                <?php foreach ($achievements as $achievement): ?>
                <div class="achievement-item">
                    <div class="achievement-badge">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="achievement-info">
                        <h4><?= htmlspecialchars($achievement['achievement_type']) ?></h4>
                        <p class="achievement-date">Получено: <?= date('d.m.Y', strtotime($achievement['earned_at'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <a href="/user/achievements.php" class="btn btn-link">Все достижения</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>