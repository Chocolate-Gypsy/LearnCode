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

$lessonId = $_GET['lesson_id'] ?? 0;
if (!$lessonId) {
    header('Location: /admin/lessons.php');
    exit;
}

// Получаем данные урока
$stmt = $pdo->prepare("SELECT l.*, c.title as course_title 
                      FROM lessons l
                      JOIN courses c ON l.course_id = c.id
                      WHERE l.id = ?");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header('Location: /admin/lessons.php');
    exit;
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_exercise'])) {
        $exerciseId = (int)$_POST['exercise_id'];
        deleteExercise($exerciseId);
    } elseif (isset($_POST['save_order'])) {
        $orderData = $_POST['order'] ?? [];
        updateExercisesOrder($orderData);
    }
}

// Получаем упражнения урока
$exercises = getLessonExercises($lessonId);

$pageTitle = "Управление упражнениями - " . htmlspecialchars($lesson['title']);
?>

<div class="admin-container">
    <?php include '../admin/admin_sidebar.php'; ?>
    
    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1>Управление упражнениями</h1>
                <h2 class="course-title">
                    Урок: <?= htmlspecialchars($lesson['title']) ?>
                    <small>(Курс: <?= htmlspecialchars($lesson['course_title']) ?>)</small>
                </h2>
            </div>
            <div>
                <a href="/admin/exercise_edit.php?lesson_id=<?= $lessonId ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить упражнение
                </a>
                <a href="/admin/lessons.php?course_id=<?= $lesson['course_id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к урокам
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

        <?php if (empty($exercises)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <p>В этом уроке пока нет упражнений</p>
                <a href="/admin/exercise_edit.php?lesson_id=<?= $lessonId ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить первое упражнение
                </a>
            </div>
        <?php else: ?>
            <form method="POST" id="exercises-form">
                <div class="table-responsive">
                    <table class="admin-table sortable-table">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Вопрос/Задание</th>
                                <th width="150">Тип</th>
                                <th width="120">Действия</th>
                            </tr>
                        </thead>
                        <tbody id="sortable">
                            <?php foreach ($exercises as $exercise): ?>
                            <tr data-id="<?= $exercise['id'] ?>">
                                <td class="order-handle">
                                    <i class="fas fa-grip-vertical"></i>
                                    <input type="hidden" name="order[]" value="<?= $exercise['id'] ?>">
                                </td>
                                <td>
                                    <?= htmlspecialchars(substr($exercise['question'], 0, 100)) ?>
                                    <?= strlen($exercise['question']) > 100 ? '...' : '' ?>
                                </td>
                                <td>
                                    <?php
                                    $typeLabels = [
                                        'multiple_choice' => 'Выбор ответа',
                                        'code' => 'Код',
                                        'fill_blank' => 'Заполнение'
                                    ];
                                    echo $typeLabels[$exercise['exercise_type']] ?? $exercise['exercise_type'];
                                    ?>
                                </td>
                                <td class="actions">
                                    <a href="/admin/exercise_edit.php?id=<?= $exercise['id'] ?>" class="btn btn-sm btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="exercise_id" value="<?= $exercise['id'] ?>">
                                        <button type="submit" name="delete_exercise" class="btn btn-sm btn-delete" 
                                                onclick="return confirm('Удалить это упражнение?')">
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
