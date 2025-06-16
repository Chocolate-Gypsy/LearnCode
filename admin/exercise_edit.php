<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
require_once '../admin/admin_functions.php';

session_start();

if (!isAdmin()) {
    header('Location: /user/login.php');
    exit;
}

$exerciseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;

// Проверка lesson_id для нового упражнения
if (!$exerciseId && !$lessonId) {
    header('Location: /admin/lessons.php');
    exit;
}

// Загрузка данных упражнения
$exercise = [
    'question' => '',
    'answer' => '',
    'code_template' => '',
    'exercise_type' => 'multiple_choice',
    'lesson_id' => $lessonId
];

$options = []; // Для упражнений с выбором ответа

// Загрузка существующего упражнения
if ($exerciseId) {
    $stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ?");
    $stmt->execute([$exerciseId]);
    $exercise = $stmt->fetch();
    
    if (!$exercise) {
        $_SESSION['admin_message'] = 'Упражнение не найдено';
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: /admin/exercises.php?lesson_id=' . $lessonId);
        exit;
    }
    
    $lessonId = $exercise['lesson_id'];
    
    // Загрузка вариантов ответа для multiple_choice
    if ($exercise['exercise_type'] === 'multiple_choice') {
        $stmt = $pdo->prepare("SELECT * FROM exercise_options WHERE exercise_id = ? ORDER BY id");
        $stmt->execute([$exerciseId]);
        $options = $stmt->fetchAll();
    }
}

// Получаем данные урока
$stmt = $pdo->prepare("SELECT l.title, c.title as course_title 
                      FROM lessons l
                      JOIN courses c ON l.course_id = c.id
                      WHERE l.id = ?");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    $_SESSION['admin_message'] = 'Урок не найден';
    $_SESSION['admin_message_type'] = 'danger';
    header('Location: /admin/lessons.php');
    exit;
}

// Обработка формы
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exercise['question'] = trim($_POST['question'] ?? '');
    $exercise['answer'] = trim($_POST['answer'] ?? '');
    $exercise['code_template'] = trim($_POST['code_template'] ?? '');
    $exercise['exercise_type'] = $_POST['exercise_type'] ?? 'multiple_choice';
    $exercise['lesson_id'] = $lessonId;
    
    // Валидация
    if (empty($exercise['question'])) {
        $errors['question'] = 'Вопрос обязателен';
    }
    
    // Валидация для multiple_choice
    if ($exercise['exercise_type'] === 'multiple_choice') {
        $optionTexts = $_POST['option_text'] ?? [];
        $optionCorrect = $_POST['is_correct'] ?? [];
        
        // Фильтруем пустые варианты
        $optionTexts = array_filter($optionTexts, function($text) {
            return !empty(trim($text));
        });
        
        if (count($optionTexts) < 2) {
            $errors['options'] = 'Нужно минимум 2 варианта ответа';
        }
        
        $correctCount = count(array_filter($optionCorrect));
        
        if ($correctCount === 0) {
            $errors['options'] = 'Нужно выбрать хотя бы один правильный ответ';
        }
    } elseif (empty($exercise['answer'])) {
        // Валидация ответа для других типов упражнений
        $errors['answer'] = 'Ответ обязателен';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            if ($exerciseId) {
                // Обновление упражнения
                $stmt = $pdo->prepare("UPDATE exercises SET 
                    question = ?, answer = ?, code_template = ?, 
                    exercise_type = ?, lesson_id = ?
                    WHERE id = ?");
                $stmt->execute([
                    $exercise['question'],
                    $exercise['answer'],
                    $exercise['code_template'],
                    $exercise['exercise_type'],
                    $exercise['lesson_id'],
                    $exerciseId
                ]);
            } else {
                // Создание упражнения
                $stmt = $pdo->prepare("INSERT INTO exercises 
                    (question, answer, code_template, exercise_type, lesson_id) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $exercise['question'],
                    $exercise['answer'],
                    $exercise['code_template'],
                    $exercise['exercise_type'],
                    $exercise['lesson_id']
                ]);
                $exerciseId = $pdo->lastInsertId();
                
                if (!$exerciseId) {
                    throw new PDOException("Не удалось создать упражнение");
                }
            }
            
            // Обработка вариантов ответа для multiple_choice
            if ($exercise['exercise_type'] === 'multiple_choice') {
                // Удаляем старые варианты (только для существующего упражнения)
                if ($exerciseId) {
                    $stmt = $pdo->prepare("DELETE FROM exercise_options WHERE exercise_id = ?");
                    $stmt->execute([$exerciseId]);
                }
                
                // Добавляем новые варианты
                $optionTexts = $_POST['option_text'] ?? [];
                $optionCorrect = $_POST['is_correct'] ?? [];
                
                foreach ($optionTexts as $index => $text) {
                    $text = trim($text);
                    if (!empty($text)) {
                        $isCorrect = isset($optionCorrect[$index]) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO exercise_options 
                            (exercise_id, option_text, is_correct) 
                            VALUES (?, ?, ?)");
                        $stmt->execute([
                            $exerciseId,
                            $text,
                            $isCorrect
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            
            $_SESSION['admin_message'] = 'Упражнение успешно сохранено';
            $_SESSION['admin_message_type'] = 'success';
            header('Location: /admin/exercises.php?lesson_id=' . $lessonId);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
            error_log("DB Error in exercise_edit.php: " . $e->getMessage());
        }
    }
}

$pageTitle = $exerciseId ? "Редактирование упражнения" : "Создание упражнения";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Админ-панель</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <?php include '../admin/admin_sidebar.php'; ?>
    
    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1><?= $exerciseId ? 'Редактирование упражнения' : 'Создание упражнения' ?></h1>
                <h2 class="course-title">
                    Урок: <?= htmlspecialchars($lesson['title']) ?>
                    <small>(Курс: <?= htmlspecialchars($lesson['course_title']) ?>)</small>
                </h2>
            </div>
            <div>
                <a href="/admin/exercises.php?lesson_id=<?= $lessonId ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад
                </a>
            </div>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" class="admin-form" novalidate>
            <div class="form-group">
                <label for="exercise_type">Тип упражнения *</label>
                <select id="exercise_type" name="exercise_type" class="exercise-type-selector" required>
                    <option value="multiple_choice" <?= $exercise['exercise_type'] === 'multiple_choice' ? 'selected' : '' ?>>Выбор ответа</option>
                    <option value="code" <?= $exercise['exercise_type'] === 'code' ? 'selected' : '' ?>>Написание кода</option>
                    <option value="fill_blank" <?= $exercise['exercise_type'] === 'fill_blank' ? 'selected' : '' ?>>Заполнение пропусков</option>
                </select>
            </div>

            <div class="form-group">
                <label for="question">Вопрос/Задание *</label>
                <textarea id="question" name="question" rows="3" required><?= 
                    htmlspecialchars($exercise['question']) 
                ?></textarea>
                <?php if (!empty($errors['question'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['question']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Блок для разных типов упражнений -->
            <div class="exercise-type-content">
                <!-- Multiple Choice -->
                <div class="exercise-type-panel" id="multiple_choice_panel" 
                     style="<?= $exercise['exercise_type'] !== 'multiple_choice' ? 'display: none;' : '' ?>">
                    <h3>Варианты ответа</h3>
                    
                    <?php if (!empty($errors['options'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errors['options']) ?></div>
                    <?php endif; ?>
                    
                    <div id="options-container">
                        <?php if (!empty($options)): ?>
                            <?php foreach ($options as $index => $option): ?>
                            <div class="option-item">
                                <div class="form-group">
                                    <label>Вариант <?= $index + 1 ?></label>
                                    <div class="option-input-group">
                                        <input type="text" name="option_text[]" 
                                               value="<?= htmlspecialchars($option['option_text']) ?>" 
                                               placeholder="Текст варианта" class="option-text" required>
                                        <label class="checkbox-label option-correct">
                                            <input type="checkbox" name="is_correct[]" value="<?= $index ?>" 
                                                   <?= $option['is_correct'] ? 'checked' : '' ?>>
                                            <span class="checkmark"></span>
                                            Правильный
                                        </label>
                                        <button type="button" class="btn btn-sm btn-danger remove-option">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Пустые варианты по умолчанию -->
                            <div class="option-item">
                                <div class="form-group">
                                    <label>Вариант 1</label>
                                    <div class="option-input-group">
                                        <input type="text" name="option_text[]" placeholder="Текст варианта" class="option-text" required>
                                        <label class="checkbox-label option-correct">
                                            <input type="checkbox" name="is_correct[]" value="0" checked>
                                            <span class="checkmark"></span>
                                            Правильный
                                        </label>
                                        <button type="button" class="btn btn-sm btn-danger remove-option">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="option-item">
                                <div class="form-group">
                                    <label>Вариант 2</label>
                                    <div class="option-input-group">
                                        <input type="text" name="option_text[]" placeholder="Текст варианта" class="option-text" required>
                                        <label class="checkbox-label option-correct">
                                            <input type="checkbox" name="is_correct[]" value="0">
                                            <span class="checkmark"></span>
                                            Правильный
                                        </label>
                                        <button type="button" class="btn btn-sm btn-danger remove-option">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" id="add-option" class="btn btn-sm btn-secondary">
                        <i class="fas fa-plus"></i> Добавить вариант
                    </button>
                </div>
                
                <!-- Code -->
                <div class="exercise-type-panel" id="code_panel" 
                     style="<?= $exercise['exercise_type'] !== 'code' ? 'display: none;' : '' ?>">
                    <div class="form-group">
                        <label for="code_template">Шаблон кода</label>
                        <textarea id="code_template" name="code_template" rows="10"><?= 
                            htmlspecialchars($exercise['code_template']) 
                        ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="answer">Правильный ответ *</label>
                        <input type="text" id="answer" name="answer" 
                               value="<?= htmlspecialchars($exercise['answer']) ?>" required>
                        <?php if (!empty($errors['answer'])): ?>
                            <div class="error-message"><?= htmlspecialchars($errors['answer']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Fill Blank -->
                <div class="exercise-type-panel" id="fill_blank_panel" 
                     style="<?= $exercise['exercise_type'] !== 'fill_blank' ? 'display: none;' : '' ?>">
                    <div class="form-group">
                        <label for="answer">Правильный ответ *</label>
                        <input type="text" id="answer" name="answer" 
                               value="<?= htmlspecialchars($exercise['answer']) ?>" required>
                        <?php if (!empty($errors['answer'])): ?>
                            <div class="error-message"><?= htmlspecialchars($errors['answer']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Сохранить
                </button>
            </div>
        </form>
    </main>
</div>

<!-- Подключение CodeMirror для редактирования кода -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>

<script>
// Переключение типов упражнений
document.getElementById('exercise_type').addEventListener('change', function() {
    document.querySelectorAll('.exercise-type-panel').forEach(panel => {
        panel.style.display = 'none';
    });
    document.getElementById(this.value + '_panel').style.display = 'block';
    updateFieldRequirements();
});

// Управление вариантами ответа
let optionCounter = <?= empty($options) ? count($options) : 2 ?>;

document.getElementById('add-option').addEventListener('click', function() {
    optionCounter++;
    const newOption = document.createElement('div');
    newOption.className = 'option-item';
    newOption.innerHTML = `
        <div class="form-group">
            <label>Вариант ${optionCounter}</label>
            <div class="option-input-group">
                <input type="text" name="option_text[]" placeholder="Текст варианта" class="option-text" required>
                <label class="checkbox-label option-correct">
                    <input type="checkbox" name="is_correct[]" value="1"> <!-- Всегда value="1" -->
                    <span class="checkmark"></span>
                    Правильный
                </label>
                <button type="button" class="btn btn-sm btn-danger remove-option">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    document.getElementById('options-container').appendChild(newOption);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-option')) {
        const optionItem = e.target.closest('.option-item');
        if (document.querySelectorAll('.option-item').length > 2) {
            optionItem.remove();
            // Перенумеруем только labels, значения чекбоксов не меняем
            document.querySelectorAll('.option-item').forEach((item, index) => {
                item.querySelector('label').textContent = `Вариант ${index + 1}`;
            });
            optionCounter = document.querySelectorAll('.option-item').length;
        } else {
            alert('Должно быть минимум 2 варианта ответа');
        }
    }
});

// Инициализация редактора кода
let editor;
if (document.getElementById('code_template')) {
    editor = CodeMirror.fromTextArea(document.getElementById('code_template'), {
        lineNumbers: true,
        mode: 'javascript',
        theme: 'dracula',
        indentUnit: 4,
        lineWrapping: true,
        autoCloseBrackets: true,
        matchBrackets: true
    });
}

// Динамическое управление атрибутом required
function updateFieldRequirements() {
    const type = document.getElementById('exercise_type').value;
    const answerField = document.getElementById('answer');
    
    if (type === 'multiple_choice') {
        answerField?.removeAttribute('required');
        answerField?.classList.add('hidden-field');
    } else {
        answerField?.setAttribute('required', 'required');
        answerField?.classList.remove('hidden-field');
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    updateFieldRequirements();
    
    // Валидация формы перед отправкой
document.querySelector('.admin-form').addEventListener('submit', function(e) {
    const exerciseType = document.getElementById('exercise_type').value;
    let isValid = true;
    
    if (exerciseType === 'multiple_choice') {
        const checkedOptions = document.querySelectorAll('input[name="is_correct[]"]:checked');
        
        if (checkedOptions.length === 0) {
            alert('Нужно выбрать хотя бы один правильный ответ');
            isValid = false;
        }
    }
    
    if (!isValid) {
        e.preventDefault();
    }
});
        // Проверка обязательных полей
        if (document.getElementById('question').value.trim() === '') {
            alert('Поле "Вопрос/Задание" обязательно для заполнения');
            isValid = false;
        }
        
        // Проверка для разных типов упражнений
        if (exerciseType === 'multiple_choice') {
            const options = document.querySelectorAll('input[name="option_text[]"]');
            const checkedOptions = document.querySelectorAll('input[name="is_correct[]"]:checked');
            
            // Проверка на пустые варианты
            options.forEach(option => {
                if (option.value.trim() === '') {
                    alert('Все варианты ответа должны быть заполнены');
                    isValid = false;
                    return;
                }
            });
            
            if (options.length < 2) {
                alert('Нужно минимум 2 варианта ответа');
                isValid = false;
            } else if (checkedOptions.length === 0) {
                alert('Нужно выбрать хотя бы один правильный ответ');
                isValid = false;
            }
        } else if (document.getElementById('answer').value.trim() === '') {
            alert('Поле "Правильный ответ" обязательно для заполнения');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Для типа "code" синхронизируем содержимое редактора с textarea
        if (exerciseType === 'code' && typeof editor !== 'undefined') {
            editor.save();
        }
    });

</script>
</body>
</html>