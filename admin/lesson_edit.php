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

$lessonId = $_GET['id'] ?? 0;
$courseId = $_GET['course_id'] ?? 0;

// Проверка course_id для нового урока
if (!$lessonId && !$courseId) {
    header('Location: /admin/courses.php');
    exit;
}

// Загрузка данных урока
$lesson = [
    'title' => '',
    'content' => '',
    'duration_minutes' => 15,
    'order_number' => 0,
    'is_active' => 1,
    'course_id' => $courseId
];

// Загрузка данных существующего урока
if ($lessonId) {
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        $_SESSION['admin_message'] = 'Урок не найден';
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: /admin/lessons.php?course_id=' . $courseId);
        exit;
    }
    
    $courseId = $lesson['course_id'];
}

// Получаем данные курса
$stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

// Обработка формы
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson['title'] = trim($_POST['title'] ?? '');
    $lesson['content'] = trim($_POST['content'] ?? '');
    $lesson['duration_minutes'] = (int)($_POST['duration_minutes'] ?? 15);
    $lesson['order_number'] = (int)($_POST['order_number'] ?? 0);
    $lesson['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $lesson['course_id'] = $courseId;

    // Валидация
    if (empty($lesson['title'])) {
        $errors['title'] = 'Название обязательно';
    }
    if (empty($lesson['content'])) {
        $errors['content'] = 'Содержание обязательно';
    }
    if ($lesson['duration_minutes'] < 1 || $lesson['duration_minutes'] > 180) {
        $errors['duration_minutes'] = 'Длительность должна быть от 1 до 180 минут';
    }

    if (empty($errors)) {
        try {
            if ($lessonId) {
                // Обновление урока
                $stmt = $pdo->prepare("UPDATE lessons SET 
                    title = ?, content = ?, duration_minutes = ?, 
                    order_number = ?, is_active = ?
                    WHERE id = ?");
                $stmt->execute([
                    $lesson['title'],
                    $lesson['content'],
                    $lesson['duration_minutes'],
                    $lesson['order_number'],
                    $lesson['is_active'],
                    $lessonId
                ]);
                $message = 'Урок успешно обновлен';
            } else {
                // Создание урока
                $stmt = $pdo->prepare("INSERT INTO lessons 
                    (title, content, duration_minutes, order_number, is_active, course_id) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $lesson['title'],
                    $lesson['content'],
                    $lesson['duration_minutes'],
                    $lesson['order_number'],
                    $lesson['is_active'],
                    $lesson['course_id']
                ]);
                $lessonId = $pdo->lastInsertId();
                $message = 'Урок успешно создан';
            }

            $_SESSION['admin_message'] = $message;
            $_SESSION['admin_message_type'] = 'success';
            header('Location: /admin/lessons.php?course_id=' . $courseId);
            exit;
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Получаем список уроков для порядка
$stmt = $pdo->prepare("SELECT id, title FROM lessons 
                      WHERE course_id = ? AND id != ?
                      ORDER BY order_number");
$stmt->execute([$courseId, $lessonId]);
$lessons = $stmt->fetchAll();

$pageTitle = $lessonId ? "Редактирование урока" : "Создание урока";
?>

<div class="admin-container">
    <?php include '../admin/admin_sidebar.php'; ?>
    
    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1><?= $lessonId ? 'Редактирование урока' : 'Создание урока' ?></h1>
                <h2 class="course-title">
                    Курс: <?= htmlspecialchars($course['title']) ?>
                </h2>
            </div>
            <div>
                <a href="/admin/lessons.php?course_id=<?= $courseId ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад
                </a>
            </div>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= $errors['general'] ?></div>
        <?php endif; ?>

        <form method="POST" class="admin-form" novalidate>
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label for="title">Название урока *</label>
                    <input type="text" id="title" name="title" 
                           value="<?= htmlspecialchars($lesson['title']) ?>" required>
                    <?php if (!empty($errors['title'])): ?>
                        <div class="error-message"><?= $errors['title'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="duration_minutes">Длительность (мин) *</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="180"
                           value="<?= $lesson['duration_minutes'] ?>" required>
                    <?php if (!empty($errors['duration_minutes'])): ?>
                        <div class="error-message"><?= $errors['duration_minutes'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="order_number">Порядок</label>
                    <select id="order_number" name="order_number">
                        <option value="0">-- Первый в списке --</option>
                        <?php foreach ($lessons as $index => $l): ?>
                            <option value="<?= $index + 1 ?>" <?= $lesson['order_number'] == $index + 1 ? 'selected' : '' ?>>
                                После: <?= htmlspecialchars($l['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="content">Содержание урока *</label>
                <textarea id="content" name="content" rows="15" required><?= 
                    htmlspecialchars($lesson['content']) 
                ?></textarea>
                <?php if (!empty($errors['content'])): ?>
                    <div class="error-message"><?= $errors['content'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?= $lesson['is_active'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Активный урок
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Сохранить
                </button>
                
                <?php if ($lessonId): ?>
                    <a href="/admin/exercises.php?lesson_id=<?= $lessonId ?>" class="btn btn-secondary">
                        <i class="fas fa-tasks"></i> Управление упражнениями
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </main>
</div>

<!-- Подключение редактора Markdown (опционально) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<script>
// Инициализация редактора Markdown
const simplemde = new SimpleMDE({
    element: document.getElementById("content"),
    spellChecker: false,
    toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "image", "|", "preview", "guide"],
    status: false,
    placeholder: "Введите содержание урока...",
    autoDownloadFontAwesome: false
});
</script>
