<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();
require_once '../admin/admin_functions.php';

session_start();

if (!isAdmin()) {
    header('Location: /user/login.php');
    exit;
}

$courseId = $_GET['course_id'] ?? 0;
if (!$courseId) {
    header('Location: /admin/courses.php');
    exit;
}

// Получаем данные курса
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: /admin/courses.php');
    exit;
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_lesson'])) {
        $lessonId = (int)$_POST['lesson_id'];
        deleteLesson($lessonId);
    } elseif (isset($_POST['save_order'])) {
        $orderData = $_POST['order'] ?? [];
        updateLessonsOrder($orderData);
    }
}

// Получаем уроки курса
$lessons = getCourseLessons($courseId);

$pageTitle = "Управление уроками - " . htmlspecialchars($course['title']);
?>

<div class="admin-container">
    
    <?php include '../admin/admin_sidebar.php'; ?>
    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1>Управление уроками</h1>
                <h2 class="course-title">
                    <i class="fas <?= htmlspecialchars($course['icon']) ?>"></i>
                    <?= htmlspecialchars($course['title']) ?>
                </h2>
            </div>
            <div>
                <a href="/admin/lesson_edit.php?course_id=<?= $courseId ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить урок
                </a>
                <a href="/admin/courses.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="alert alert-<?= $_SESSION['admin_message_type'] ?>">
                <?= $_SESSION['admin_message'] ?>
            </div>
            <?php unset($_SESSION['admin_message']); ?>
            <?php unset($_SESSION['admin_message_type']); ?>
        <?php endif; ?>

        <?php if (empty($lessons)): ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <p>В этом курсе пока нет уроков</p>
                <a href="/admin/lesson_edit.php?course_id=<?= $courseId ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить первый урок
                </a>
            </div>
        <?php else: ?>
            <form method="POST" id="lessons-form">
                <div class="table-responsive">
                    <table class="admin-table sortable-table">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Название урока</th>
                                <th width="120">Длительность</th>
                                <th width="120">Статус</th>
                                <th width="150">Действия</th>
                            </tr>
                        </thead>
                        <tbody id="sortable">
                            <?php foreach ($lessons as $lesson): ?>
                            <tr data-id="<?= $lesson['id'] ?>">
                                <td class="order-handle">
                                    <i class="fas fa-grip-vertical"></i>
                                    <input type="hidden" name="order[]" value="<?= $lesson['id'] ?>">
                                </td>
                                <td>
                                    <a href="/admin/lesson_edit.php?id=<?= $lesson['id'] ?>">
                                        <?= htmlspecialchars($lesson['title']) ?>
                                    </a>
                                </td>
                                <td><?= $lesson['duration_minutes'] ?> мин</td>
                                <td>
                                    <?= $lesson['is_active'] ? 
                                        '<span class="badge badge-success">Активен</span>' : 
                                        '<span class="badge badge-secondary">Скрыт</span>' ?>
                                </td>
                                <td class="actions">
                                    <a href="/admin/lesson_edit.php?id=<?= $lesson['id'] ?>" class="btn btn-sm btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                        <button type="submit" name="delete_lesson" class="btn btn-sm btn-delete" 
                                                onclick="return confirm('Удалить этот урок?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-actions">
                    <button type="submit" name="save_order" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить порядок
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </main>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script>
$(function() {
    $("#sortable").sortable({
        handle: ".order-handle",
        update: function() {
            $('input[name="order[]"]').each(function(index) {
                $(this).val($(this).closest('tr').data('id'));
            });
        }
    });
    $("#sortable").disableSelection();
});
</script>
