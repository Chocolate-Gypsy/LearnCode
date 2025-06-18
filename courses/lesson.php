<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();

// Получаем ID урока из URL
$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lesson_id <= 0) {
    header('Location: /courses/');
    exit;
}

// Получаем данные урока и курса
try {
    $stmt = $pdo->prepare("SELECT l.*, c.title as course_title, c.id as course_id 
                          FROM lessons l
                          JOIN courses c ON l.course_id = c.id
                          WHERE l.id = ? AND l.is_active = 1 AND c.is_active = 1");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lesson) {
        header('Location: /courses/');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching lesson: " . $e->getMessage());
    header('Location: /courses/');
    exit;
}

// Получаем список всех уроков курса для навигации
try {
    $stmt = $pdo->prepare("SELECT id, title FROM lessons 
                          WHERE course_id = ? AND is_active = 1
                          ORDER BY order_number");
    $stmt->execute([$lesson['course_id']]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Определяем текущий индекс урока для навигации
    $current_index = array_search($lesson_id, array_column($lessons, 'id'));
    $prev_lesson = $current_index > 0 ? $lessons[$current_index - 1]['id'] : null;
    $next_lesson = $current_index < count($lessons) - 1 ? $lessons[$current_index + 1]['id'] : null;
} catch (PDOException $e) {
    $lessons = [];
    $prev_lesson = null;
    $next_lesson = null;
    error_log("Error fetching course lessons: " . $e->getMessage());
}

// Получаем упражнения для урока
try {
    $stmt = $pdo->prepare("SELECT * FROM exercises 
                          WHERE lesson_id = ?
                          ORDER BY order_number");
    $stmt->execute([$lesson_id]);
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $exercises = [];
    error_log("Error fetching exercises: " . $e->getMessage());
}

// Отмечаем урок как пройденный, если пользователь авторизован
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Проверяем, не пройден ли уже урок
        $stmt = $pdo->prepare("SELECT id FROM user_progress 
                              WHERE user_id = ? AND lesson_id = ?");
        $stmt->execute([$user_id, $lesson_id]);
        $is_completed = (bool)$stmt->fetch();
        
        // Если не пройден - отмечаем
        if (!$is_completed) {
            $stmt = $pdo->prepare("INSERT INTO user_progress 
                                 (user_id, course_id, lesson_id, completed_at) 
                                 VALUES (?, ?, ?, NOW())
                                 ON DUPLICATE KEY UPDATE completed_at = NOW()");
            $stmt->execute([$user_id, $lesson['course_id'], $lesson_id]);
            
            // Обновляем XP пользователя
            $stmt = $pdo->prepare("UPDATE users SET xp = xp + 10 WHERE id = ?");
            $stmt->execute([$user_id]);
        }
    } catch (PDOException $e) {
        error_log("Error updating user progress: " . $e->getMessage());
    }
}

// Установка заголовка страницы
$page_title = htmlspecialchars($lesson['title']) . " | " . htmlspecialchars($lesson['course_title']) . " | LearnCode";

// Подключение шапки
include '../includes/header.php';
?>

<div class="lesson-header bg-light py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/courses/">Курсы</a></li>
                <li class="breadcrumb-item"><a href="course.php?id=<?= $lesson['course_id'] ?>"><?= htmlspecialchars($lesson['course_title']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($lesson['title']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <article class="lesson-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0"><?= htmlspecialchars($lesson['title']) ?></h1>
                    <?php if (isLoggedIn()): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i> Завершено
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="content-wrapper mb-5">
                    <?= nl2br(htmlspecialchars($lesson['content'])) ?>
                </div>
                
                <?php if (!empty($exercises)): ?>
                    <div class="exercises-section mb-5">
                        <h3 class="mb-4">Практические задания</h3>
                        
                        <?php foreach ($exercises as $exercise): ?>
                            <div class="card mb-3 exercise-card">
                                <div class="card-header bg-white">
                                    <h4 class="mb-0">Задание <?= $exercise['order_number'] ?></h4>
                                </div>
                                <div class="card-body">
                                    <div class="exercise-question mb-3">
                                        <p class="fw-bold"><?= nl2br(htmlspecialchars($exercise['question'])) ?></p>
                                    </div>
                                    
                                    <?php if ($exercise['exercise_type'] === 'code' && !empty($exercise['code_template'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Редактор кода:</label>
                                            <textarea class="form-control code-editor" rows="8" 
                                                      placeholder="Введите ваш код здесь"><?= htmlspecialchars($exercise['code_template']) ?></textarea>
                                        </div>
                                        <button class="btn btn-primary run-code">Запустить код</button>
                                        <button class="btn btn-outline-secondary show-answer ms-2">Показать ответ</button>
                                        <div class="answer-container mt-3 d-none">
                                            <h5 class="mb-2">Правильный ответ:</h5>
                                            <pre class="bg-light p-3 rounded"><code><?= htmlspecialchars($exercise['answer']) ?></code></pre>
                                        </div>
                                    <?php elseif ($exercise['exercise_type'] === 'multiple_choice'): ?>
                                        <?php
                                        // Получаем варианты ответов
                                        try {
                                            $stmt = $pdo->prepare("SELECT * FROM exercise_options 
                                                                  WHERE exercise_id = ? 
                                                                  ORDER BY id");
                                            $stmt->execute([$exercise['id']]);
                                            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (PDOException $e) {
                                            $options = [];
                                            error_log("Error fetching exercise options: " . $e->getMessage());
                                        }
                                        ?>
                                        
                                        <div class="options-list">
                                            <?php foreach ($options as $option): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" 
                                                           name="exercise_<?= $exercise['id'] ?>" 
                                                           id="option_<?= $option['id'] ?>">
                                                    <label class="form-check-label" for="option_<?= $option['id'] ?>">
                                                        <?= htmlspecialchars($option['option_text']) ?>
                                                        <?php if ($option['is_correct']): ?>
                                                            <span class="correct-answer text-success d-none">
                                                                <i class="fas fa-check-circle ms-2"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="btn btn-primary check-answer">Проверить ответ</button>
                                        <button class="btn btn-outline-secondary show-answer ms-2">Показать ответ</button>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <textarea class="form-control" rows="3" placeholder="Введите ваш ответ"></textarea>
                                        </div>
                                        <button class="btn btn-primary check-answer">Проверить ответ</button>
                                        <button class="btn btn-outline-secondary show-answer ms-2">Показать ответ</button>
                                        <div class="answer-container mt-3 d-none">
                                            <h5 class="mb-2">Правильный ответ:</h5>
                                            <p><?= nl2br(htmlspecialchars($exercise['answer'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="lesson-navigation d-flex justify-content-between pt-4 mt-4 border-top">
                    <?php if ($prev_lesson): ?>
                        <a href="lesson.php?id=<?= $prev_lesson ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Предыдущий урок
                        </a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    
                    <?php if ($next_lesson): ?>
                        <a href="lesson.php?id=<?= $next_lesson ?>" class="btn btn-primary">
                            Следующий урок <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    <?php else: ?>
                        <a href="course.php?id=<?= $lesson['course_id'] ?>" class="btn btn-primary">
                            Завершить курс <i class="fas fa-check ms-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Содержание курса</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($lessons as $l): ?>
                            <a href="lesson.php?id=<?= $l['id'] ?>" 
                               class="list-group-item list-group-item-action border-0 py-3 <?= $l['id'] == $lesson_id ? 'active' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($l['title']) ?></h5>
                                        <?php if ($l['id'] == $lesson_id): ?>
                                            <small class="text-white">Вы сейчас здесь</small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isLoggedIn()): ?>
                                        <?php
                                        try {
                                            $stmt = $pdo->prepare("SELECT id FROM user_progress 
                                                                  WHERE user_id = ? AND lesson_id = ?");
                                            $stmt->execute([$_SESSION['user_id'], $l['id']]);
                                            $completed = (bool)$stmt->fetch();
                                        } catch (PDOException $e) {
                                            $completed = false;
                                        }
                                        ?>
                                        <?php if ($completed): ?>
                                            <span class="badge bg-success rounded-circle p-2">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Информация об уроке</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                            <span class="text-muted">Курс</span>
                            <span class="fw-bold"><?= htmlspecialchars($lesson['course_title']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                            <span class="text-muted">Длительность</span>
                            <span class="fw-bold"><?= $lesson['duration_minutes'] ?? 15 ?> мин</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                            <span class="text-muted">Задания</span>
                            <span class="fw-bold"><?= count($exercises) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Подключение подвала
include '../includes/footer.php';
?>

<style>
.code-editor {
    font-family: 'Courier New', Courier, monospace;
    font-size: 14px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.exercise-card {
    border: 1px solid rgba(0,0,0,.125);
    border-radius: .5rem;
    overflow: hidden;
}

.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>

<script>
// Обработка кнопок в упражнениях
// Показать ответ
document.querySelectorAll('.show-answer').forEach(button => {
    button.addEventListener('click', function() {
        const container = this.closest('.exercise-card')?.querySelector('.answer-container');
        if (container) {
            container.classList.toggle('d-none');
        }
    });
});

// Проверить ответ (для тестов с выбором)
document.querySelectorAll('.check-answer').forEach(button => {
    button.addEventListener('click', function() {
        const card = this.closest('.exercise-card');
        if (card) {
            card.querySelectorAll('.correct-answer').forEach(el => {
                if (el) {
                    el.classList.remove('d-none');
                }
            });
        }
    });
});
</script>