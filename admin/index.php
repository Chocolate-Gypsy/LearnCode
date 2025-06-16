<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
require_once '../admin/admin_functions.php';

session_start();

// Проверка прав администратора
if (!isAdmin()) {
    header('Location: /user/login.php');
    exit;
}

$pageTitle = "Админ-панель - Learn Programming";
?>

<div class="admin-container">
        <?php include '../admin/admin_sidebar.php'; ?>
    
    <main class="admin-content">
        <h1>Обзор системы</h1>
        
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= getTotalUsers() ?></span>
                    <span class="stat-label">Пользователей</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= getTotalCourses() ?></span>
                    <span class="stat-label">Курсов</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-list-ul"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= getTotalLessons() ?></span>
                    <span class="stat-label">Уроков</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= getTotalCompletedLessons() ?></span>
                    <span class="stat-label">Завершенных уроков</span>
                </div>
            </div>
        </div>
        
        <div class="admin-sections">
            <section class="recent-users">
                <h2><i class="fas fa-user-clock"></i> Новые пользователи</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Email</th>
                                <th>Дата регистрации</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (getRecentUsers(5) as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <a href="/admin/user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">Редактировать</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <section class="recent-courses">
                <h2><i class="fas fa-book-open"></i> Последние курсы</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Уроков</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (getRecentCourses(5) as $course): ?>
                            <tr>
                                <td><?= $course['id'] ?></td>
                                <td><?= htmlspecialchars($course['title']) ?></td>
                                <td><?= $course['lesson_count'] ?></td>
                                <td>
                                    <span class="status-badge <?= $course['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $course['is_active'] ? 'Активен' : 'Неактивен' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/course_edit.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-primary">Редактировать</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>