<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
require_once '../admin/admin_functions.php';

session_start();

if (!isAdmin()) {
    header('Location: /user/login.php');
    exit;
}

$courseId = $_GET['id'] ?? 0;
$course = [
    'title' => '',
    'description' => '',
    'difficulty' => 'beginner',
    'icon' => 'fa-laptop-code',
    'is_active' => 1
];

// Загрузка данных курса, если редактирование
if ($courseId) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        $_SESSION['admin_message'] = 'Курс не найден';
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: /admin/courses.php');
        exit;
    }
}

// Обработка формы
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course['title'] = trim($_POST['title'] ?? '');
    $course['description'] = trim($_POST['description'] ?? '');
    $course['difficulty'] = $_POST['difficulty'] ?? 'beginner';
    $course['icon'] = $_POST['icon'] ?? 'fa-laptop-code';
    $course['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    // Валидация
    if (empty($course['title'])) {
        $errors['title'] = 'Название обязательно';
    }
    if (empty($course['description'])) {
        $errors['description'] = 'Описание обязательно';
    }

    if (empty($errors)) {
        try {
            if ($courseId) {
                // Обновление курса
                $stmt = $pdo->prepare("UPDATE courses SET 
                    title = ?, description = ?, difficulty = ?, 
                    icon = ?, is_active = ? 
                    WHERE id = ?");
                $stmt->execute([
                    $course['title'],
                    $course['description'],
                    $course['difficulty'],
                    $course['icon'],
                    $course['is_active'],
                    $courseId
                ]);
                $message = 'Курс успешно обновлен';
            } else {
                // Создание курса
                $stmt = $pdo->prepare("INSERT INTO courses 
                    (title, description, difficulty, icon, is_active) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $course['title'],
                    $course['description'],
                    $course['difficulty'],
                    $course['icon'],
                    $course['is_active']
                ]);
                $courseId = $pdo->lastInsertId();
                $message = 'Курс успешно создан';
            }

            $_SESSION['admin_message'] = $message;
            $_SESSION['admin_message_type'] = 'success';
            header('Location: /admin/courses.php');
            exit;
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

$pageTitle = $courseId ? "Редактирование курса" : "Создание курса";
?>

<div class="admin-container">
    <?php include '../admin/admin_sidebar.php'; ?>
    
    <main class="admin-content">
        <div class="admin-header">
            <h1><?= $courseId ? 'Редактирование курса' : 'Создание нового курса' ?></h1>
            <a href="/admin/courses.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= $errors['general'] ?></div>
        <?php endif; ?>

        <form method="POST" class="admin-form" >
            <div class="form-group">
                <label for="title">Название курса *</label>
                <input type="text" id="title" name="title" 
                       value="<?= htmlspecialchars($course['title']) ?>" required>
                <?php if (!empty($errors['title'])): ?>
                    <div class="error-message"><?= $errors['title'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Описание курса *</label>
                <textarea id="description" name="description" rows="5" required><?= 
                    htmlspecialchars($course['description']) 
                ?></textarea>
                <?php if (!empty($errors['description'])): ?>
                    <div class="error-message"><?= $errors['description'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="difficulty">Уровень сложности</label>
                    <select id="difficulty" name="difficulty">
                        <option value="beginner" <?= $course['difficulty'] === 'beginner' ? 'selected' : '' ?>>Начинающий</option>
                        <option value="intermediate" <?= $course['difficulty'] === 'intermediate' ? 'selected' : '' ?>>Средний</option>
                        <option value="advanced" <?= $course['difficulty'] === 'advanced' ? 'selected' : '' ?>>Продвинутый</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="icon">Иконка</label>
                    <div class="icon-selector">
                        <select id="icon" name="icon" class="icon-picker">
                            <option value="fa-laptop-code" <?= $course['icon'] === 'fa-laptop-code' ? 'selected' : '' ?>>💻 Код</option>
                            <option value="fa-js" <?= $course['icon'] === 'fa-js' ? 'selected' : '' ?>>🟨 JavaScript</option>
                            <option value="fa-python" <?= $course['icon'] === 'fa-python' ? 'selected' : '' ?>>🐍 Python</option>
                            <option value="fa-html5" <?= $course['icon'] === 'fa-html5' ? 'selected' : '' ?>>📄 HTML</option>
                            <option value="fa-css3" <?= $course['icon'] === 'fa-css3' ? 'selected' : '' ?>>🎨 CSS</option>
                            <option value="fa-database" <?= $course['icon'] === 'fa-database' ? 'selected' : '' ?>>🗄️ Базы данных</option>
                        </select>
                        <span class="icon-preview">
                            <i class="fas <?= $course['icon'] ?>"></i>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?= $course['is_active'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Активный курс
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Сохранить
                </button>
                
                <?php if ($courseId): ?>
                    <a href="/admin/lessons.php?course_id=<?= $courseId ?>" class="btn btn-secondary">
                        <i class="fas fa-list-ul"></i> Управление уроками
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </main>
</div>

<script>
// Обновление превью иконки
document.getElementById('icon').addEventListener('change', function() {
    const iconClass = this.value;
    document.querySelector('.icon-preview i').className = 'fas ' + iconClass;
});
</script>

<?php include '../includes/footer.php'; ?>