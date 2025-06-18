<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();
require_once '../admin/admin_functions.php';

session_start();

// Проверка прав администратора
if (!isAdmin()) {
    header('Location: /user/login.php');
    exit;
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_course'])) {
        $courseId = (int)$_POST['course_id'];
        deleteCourse($courseId);
    } elseif (isset($_POST['toggle_status'])) {
        $courseId = (int)$_POST['course_id'];
        toggleCourseStatus($courseId);
    }
}

// Получаем список всех курсов
$courses = getAllCourses();

$pageTitle = "Управление курсами - Админ-панель";
?>


<div class="admin-container">
    <?php include '../admin/admin_sidebar.php'; ?>
    <main class="admin-content">
        <div class="admin-header">
            <h1>Управление курсами</h1>
            <a href="/admin/course_edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить курс
            </a>
        </div>

        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="alert alert-<?= $_SESSION['admin_message_type'] ?>">
                <?= $_SESSION['admin_message'] ?>
            </div>
            <?php unset($_SESSION['admin_message']); ?>
            <?php unset($_SESSION['admin_message_type']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Уроков</th>
                        <th>Сложность</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?= $course['id'] ?></td>
                        <td>
                            <a href="/admin/course_edit.php?id=<?= $course['id'] ?>">
                                <?= htmlspecialchars($course['title']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars(substr($course['description'], 0, 50)) ?>...</td>
                        <td><?= $course['lesson_count'] ?></td>
                        <td>
                            <span class="badge badge-<?= getDifficultyClass($course['difficulty']) ?>">
                                <?= getDifficultyLabel($course['difficulty']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <button type="submit" name="toggle_status" class="status-toggle <?= $course['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $course['is_active'] ? 'Активен' : 'Неактивен' ?>
                                </button>
                            </form>
                        </td>
                        <td class="actions">
                            <a href="/admin/course_edit.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" onsubmit="return confirm('Вы уверены? Все уроки курса также будут удалены!');">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <button type="submit" name="delete_course" class="btn btn-sm btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <a href="/admin/lessons.php?course_id=<?= $course['id'] ?>" class="btn btn-sm btn-lessons">
                                <i class="fas fa-list-ul"></i> Уроки
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>