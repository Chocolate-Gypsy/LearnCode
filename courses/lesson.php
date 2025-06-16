<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();

// Проверка авторизации
if (!isLoggedIn()) {
    header('Location: /user/login.php');
    exit;
}

$lessonId = $_GET['id'] ?? 0;

// Получаем данные урока
$stmt = $pdo->prepare("SELECT l.*, c.title AS course_title 
                      FROM lessons l
                      JOIN courses c ON l.course_id = c.id
                      WHERE l.id = ? AND l.is_active = 1");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header('Location: /courses/');
    exit;
}

// Получаем упражнения урока
$stmt = $pdo->prepare("SELECT * FROM exercises 
                      WHERE lesson_id = ? 
                      ORDER BY order_number ASC");
$stmt->execute([$lessonId]);
$exercises = $stmt->fetchAll();

// Проверяем прогресс пользователя
$userId = $_SESSION['user_id'];
$completedExercises = [];
$stmt = $pdo->prepare("SELECT exercise_id FROM user_exercise_progress 
                      WHERE user_id = ? AND lesson_id = ?");
$stmt->execute([$userId, $lessonId]);
$completedExercises = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = $lesson['title'] . " - Learn Programming";
include '../includes/header.php';
?>

<div class="lesson-container">
    <div class="breadcrumbs">
        <a href="/courses/">Все курсы</a> &raquo;
        <a href="/courses/course.php?id=<?= $lesson['course_id'] ?>"><?= htmlspecialchars($lesson['course_title']) ?></a> &raquo;
        <span><?= htmlspecialchars($lesson['title']) ?></span>
    </div>

    <div class="lesson-header">
        <h1><?= htmlspecialchars($lesson['title']) ?></h1>
        <div class="lesson-meta">
            <span class="duration"><i class="fas fa-clock"></i> <?= $lesson['duration_minutes'] ?> мин</span>
            <span class="progress">Выполнено: <?= count($completedExercises) ?>/<?= count($exercises) ?></span>
        </div>
    </div>

    <div class="lesson-content">
        <?= nl2br(htmlspecialchars($lesson['content'])) ?>
    </div>

    <?php if (!empty($exercises)): ?>
    <div class="exercises-section">
        <h2>Практические задания</h2>
        
        <?php foreach ($exercises as $exercise): ?>
        <div class="exercise-card <?= in_array($exercise['id'], $completedExercises) ? 'completed' : '' ?>" 
             id="exercise-<?= $exercise['id'] ?>">
            <div class="exercise-header">
                <h3>Задание <?= array_search($exercise, $exercises) + 1 ?></h3>
                <?php if (in_array($exercise['id'], $completedExercises)): ?>
                <span class="completed-badge"><i class="fas fa-check-circle"></i> Выполнено</span>
                <?php endif; ?>
            </div>
            
            <div class="exercise-question">
                <?= nl2br(htmlspecialchars($exercise['question'])) ?>
            </div>
            
            <?php if ($exercise['exercise_type'] === 'multiple_choice'): ?>
                <div class="exercise-options">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM exercise_options WHERE exercise_id = ?");
                    $stmt->execute([$exercise['id']]);
                    $options = $stmt->fetchAll();
                    
                    foreach ($options as $option): ?>
                    <div class="option" data-option-id="<?= $option['id'] ?>" 
                         data-is-correct="<?= $option['is_correct'] ?>">
                        <?= htmlspecialchars($option['option_text']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
            <?php elseif ($exercise['exercise_type'] === 'code'): ?>
                <div class="code-editor-container">
                    <div class="code-editor" id="editor-<?= $exercise['id'] ?>"><?= 
                        htmlspecialchars($exercise['code_template']) 
                    ?></div>
                    <button class="run-code" data-exercise-id="<?= $exercise['id'] ?>">
                        <i class="fas fa-play"></i> Запустить код
                    </button>
                    <div class="code-output"></div>
                </div>
            <?php endif; ?>
            
            <div class="exercise-actions">
                <button class="check-answer" data-exercise-id="<?= $exercise['id'] ?>">
                    Проверить ответ
                </button>
                <div class="feedback"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="lesson-navigation">
        <?php
        // Получаем предыдущий и следующий урок
        $stmt = $pdo->prepare("SELECT id, title FROM lessons 
                              WHERE course_id = ? AND order_number < ? AND is_active = 1
                              ORDER BY order_number DESC LIMIT 1");
        $stmt->execute([$lesson['course_id'], $lesson['order_number']]);
        $prevLesson = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT id, title FROM lessons 
                              WHERE course_id = ? AND order_number > ? AND is_active = 1
                              ORDER BY order_number ASC LIMIT 1");
        $stmt->execute([$lesson['course_id'], $lesson['order_number']]);
        $nextLesson = $stmt->fetch();
        ?>
        
        <?php if ($prevLesson): ?>
        <a href="/courses/lesson.php?id=<?= $prevLesson['id'] ?>" class="btn btn-prev">
            <i class="fas fa-arrow-left"></i> <?= htmlspecialchars($prevLesson['title']) ?>
        </a>
        <?php endif; ?>
        
        <?php if ($nextLesson): ?>
        <a href="/courses/lesson.php?id=<?= $nextLesson['id'] ?>" class="btn btn-next">
            <?= htmlspecialchars($nextLesson['title']) ?> <i class="fas fa-arrow-right"></i>
        </a>
        <?php else: ?>
        <a href="/courses/course.php?id=<?= $lesson['course_id'] ?>" class="btn btn-next">
            Завершить курс <i class="fas fa-check"></i>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Подключение CodeMirror для редактора кода -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>

<script>
// Инициализация редакторов кода
document.querySelectorAll('.code-editor').forEach(editor => {
    const exerciseId = editor.id.split('-')[1];
    const codeMirror = CodeMirror.fromTextArea(editor, {
        lineNumbers: true,
        mode: 'javascript',
        theme: 'default',
        indentUnit: 4,
        lineWrapping: true
    });
    
    // Сохраняем ссылку на редактор
    editor.cmInstance = codeMirror;
});

// Обработка выбора вариантов ответа
document.querySelectorAll('.exercise-options .option').forEach(option => {
    option.addEventListener('click', function() {
        this.classList.toggle('selected');
    });
});

// Проверка ответов
document.querySelectorAll('.check-answer').forEach(button => {
    button.addEventListener('click', function() {
        const exerciseId = this.dataset.exerciseId;
        const exercise = document.getElementById(`exercise-${exerciseId}`);
        const feedback = exercise.querySelector('.feedback');
        
        // Определяем тип упражнения
        const exerciseType = exercise.querySelector('.code-editor') ? 'code' : 
                           (exercise.querySelector('.exercise-options') ? 'multiple_choice' : 'fill_blank');
        
        // Проверка ответа
        if (exerciseType === 'multiple_choice') {
            const selectedOptions = exercise.querySelectorAll('.option.selected');
            let allCorrect = true;
            
            selectedOptions.forEach(option => {
                if (option.dataset.isCorrect === '0') {
                    allCorrect = false;
                }
            });
            
            // Проверяем, что выбраны все правильные и только они
            const correctOptions = exercise.querySelectorAll('.option[data-is-correct="1"]');
            if (selectedOptions.length === correctOptions.length && allCorrect) {
                markExerciseComplete(exerciseId, true);
                feedback.innerHTML = '<i class="fas fa-check-circle"></i> Правильно! Молодец!';
                feedback.className = 'feedback correct';
            } else {
                feedback.innerHTML = '<i class="fas fa-times-circle"></i> Не совсем верно. Попробуйте еще раз.';
                feedback.className = 'feedback incorrect';
            }
        } else if (exerciseType === 'code') {
            const editor = exercise.querySelector('.code-editor').cmInstance;
            const code = editor.getValue();
            const output = exercise.querySelector('.code-output');
            
            // В реальном приложении здесь будет AJAX-запрос к серверу для выполнения кода
            output.textContent = "Вывод: код выполнен (в реальном приложении будет реальный вывод)";
            
            // Помечаем как выполненное (в реальном приложении это должно быть после проверки)
            markExerciseComplete(exerciseId, true);
            feedback.innerHTML = '<i class="fas fa-check-circle"></i> Код выполнен!';
            feedback.className = 'feedback correct';
        }
    });
});

// Запуск кода (для упражнений с кодом)
document.querySelectorAll('.run-code').forEach(button => {
    button.addEventListener('click', function() {
        const exerciseId = this.dataset.exerciseId;
        const exercise = document.getElementById(`exercise-${exerciseId}`);
        const editor = exercise.querySelector('.code-editor').cmInstance;
        const output = exercise.querySelector('.code-output');
        
        // В реальном приложении здесь будет AJAX-запрос к серверу
        output.textContent = "Вывод: код выполнен (в реальном приложении будет реальный вывод)";
    });
});

// Отметка упражнения как выполненного
function markExerciseComplete(exerciseId, isCorrect) {
    if (isCorrect) {
        const exercise = document.getElementById(`exercise-${exerciseId}`);
        exercise.classList.add('completed');
        
        // Отправляем данные на сервер
        fetch('/api/complete_exercise.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                exercise_id: exerciseId,
                lesson_id: <?= $lessonId ?>,
                user_id: <?= $_SESSION['user_id'] ?>
            })
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>