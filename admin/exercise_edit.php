<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
require_once '../admin/admin_functions.php';

session_start();

if (!isAdmin()) {
    header('Location: /user/login.php');
    exit;
}

$exerciseId = $_GET['id'] ?? 0;
$lessonId = $_GET['lesson_id'] ?? 0;

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

$options = [];

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
    
    if ($exercise['exercise_type'] === 'multiple_choice') {
        $stmt = $pdo->prepare("SELECT * FROM exercise_options WHERE exercise_id = ?");
        $stmt->execute([$exerciseId]);
        $options = $stmt->fetchAll();
    }
}

$stmt = $pdo->prepare("SELECT l.title, c.title as course_title 
                      FROM lessons l
                      JOIN courses c ON l.course_id = c.id
                      WHERE l.id = ?");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exercise['question'] = trim($_POST['question'] ?? '');
    $exercise['answer'] = trim($_POST['answer'] ?? '');
    $exercise['code_template'] = trim($_POST['code_template'] ?? '');
    $exercise['exercise_type'] = $_POST['exercise_type'] ?? 'multiple_choice';
    $exercise['lesson_id'] = $lessonId;
    
    if ($exercise['exercise_type'] === 'multiple_choice') {
        $optionTexts = $_POST['option_text'] ?? [];
        $optionCorrect = $_POST['is_correct'] ?? [];
        
        if (count($optionTexts) < 2) {
            $errors['options'] = 'Необходимо минимум 2 варианта ответа';
        }
        
        $correctCount = 0;
        foreach ($optionCorrect as $correct) {
            if ($correct) $correctCount++;
        }
        
        if ($correctCount === 0) {
            $errors['options'] = 'Необходимо выбрать хотя бы один правильный ответ';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            if ($exerciseId) {
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
            }
            
            if ($exercise['exercise_type'] === 'multiple_choice') {
                $stmt = $pdo->prepare("DELETE FROM exercise_options WHERE exercise_id = ?");
                $stmt->execute([$exerciseId]);
                
                $optionTexts = $_POST['option_text'] ?? [];
                $optionCorrect = $_POST['is_correct'] ?? [];
                
                foreach ($optionTexts as $index => $text) {
                    if (!empty(trim($text))) {
                        $isCorrect = isset($optionCorrect[$index]) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO exercise_options 
                            (exercise_id, option_text, is_correct) 
                            VALUES (?, ?, ?)");
                        $stmt->execute([
                            $exerciseId,
                            trim($text),
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
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errors['general'] = 'Произошла ошибка при сохранении. Пожалуйста, попробуйте позже.';
    error_log("Ошибка БД в exercise_edit.php: " . $e->getMessage());
}
    }
}

$pageTitle = $exerciseId ? "Редактирование упражнения" : "Создание упражнения";
?>

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
            <div class="alert alert-danger"><?= $errors['general'] ?></div>
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
                    <div class="error-message"><?= $errors['question'] ?></div>
                <?php endif; ?>
            </div>

            <div class="exercise-type-content">
                <div class="exercise-type-panel" id="multiple_choice_panel" 
                     style="<?= $exercise['exercise_type'] !== 'multiple_choice' ? 'display: none;' : '' ?>">
                    <h3>Варианты ответа</h3>
                    
                    <?php if (!empty($errors['options'])): ?>
                        <div class="alert alert-danger"><?= $errors['options'] ?></div>
                    <?php endif; ?>
                    
                    <div id="options-container">
                        <?php if (!empty($options)): ?>
                            <?php foreach ($options as $index => $option): ?>
                            <div class="option-item">
                                <div class="form-group">
                                    <label for="option_<?= $index ?>">Вариант <?= $index + 1 ?></label>
                                    <div class="option-input-group">
                                        <input type="text" 
                                               id="option_<?= $index ?>" 
                                               name="option_text[]" 
                                               value="<?= htmlspecialchars($option['option_text']) ?>" 
                                               class="option-text">
                                        <label class="checkbox-label option-correct">
                                            <input type="checkbox" 
                                                   name="is_correct[]" 
                                                   value="<?= $index ?>" 
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
                            <div class="option-item">
                                <div class="form-group">
                                    <label for="option_0">Вариант 1</label>
                                    <div class="option-input-group">
                                        <input type="text" 
                                               id="option_0" 
                                               name="option_text[]" 
                                               class="option-text">
                                        <label class="checkbox-label option-correct">
                                            <input type="checkbox" 
                                                   name="is_correct[]" 
                                                   value="0">
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
                                    <label for="option_1">Вариант 2</label>
                                    <div class="option-input-group">
                                        <input type="text" 
                                               id="option_1" 
                                               name="option_text[]" 
                                               class="option-text">
                                        <label class="checkbox-label option-correct">
                                            <input type="checkbox" 
                                                   name="is_correct[]" 
                                                   value="1">
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
                
                <div class="exercise-type-panel" id="code_panel" 
                     style="<?= $exercise['exercise_type'] !== 'code' ? 'display: none;' : '' ?>">
                    <div class="form-group">
                        <label for="code_template">Шаблон кода</label>
                        <textarea id="code_template" name="code_template" rows="10"><?= 
                            htmlspecialchars($exercise['code_template']) 
                        ?></textarea>
                    </div>
                </div>
                
                <div class="exercise-type-panel" id="fill_blank_panel" 
                     style="<?= $exercise['exercise_type'] !== 'fill_blank' ? 'display: none;' : '' ?>">
                    <div class="form-group">
                        <label for="answer">Правильный ответ *</label>
                        <input type="text" 
                               id="answer" 
                               name="answer" 
                               value="<?= htmlspecialchars($exercise['answer']) ?>" 
                               <?= $exercise['exercise_type'] === 'multiple_choice' ? 'class="hidden-field"' : 'required' ?>>
                        <?php if (!empty($errors['answer'])): ?>
                            <div class="error-message"><?= $errors['answer'] ?></div>
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

<script>
// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initializeExerciseTypeHandler();
    initializeOptionManagement();
    initializeCodeEditor();
    updateFieldRequirements();
});

/**
 * Обработчик изменения типа упражнения
 */
function initializeExerciseTypeHandler() {
    const exerciseTypeSelect = document.getElementById('exercise_type');
    
    if (!exerciseTypeSelect) return;
    
    exerciseTypeSelect.addEventListener('change', function() {
        // Скрыть все панели
        document.querySelectorAll('.exercise-type-panel').forEach(panel => {
            panel.style.display = 'none';
        });
        
        // Показать выбранную панель
        const selectedPanel = document.getElementById(this.value + '_panel');
        if (selectedPanel) {
            selectedPanel.style.display = 'block';
        }
        
        updateFieldRequirements();
    });
}

/**
 * Управление вариантами ответов (для множественного выбора)
 */
function initializeOptionManagement() {
    const optionsContainer = document.getElementById('options-container');
    if (!optionsContainer) return;
    
    let optionCounter = optionsContainer.querySelectorAll('.option-item').length || 2;
    
    // Добавление нового варианта
    document.getElementById('add-option')?.addEventListener('click', function() {
        optionCounter++;
        const newOption = createOptionElement(optionCounter);
        optionsContainer.appendChild(newOption);
    });
    
    // Удаление варианта
    optionsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-option')) {
            const optionItem = e.target.closest('.option-item');
            const allOptions = optionsContainer.querySelectorAll('.option-item');
            
            if (allOptions.length <= 2) {
                showAlert('Необходимо минимум 2 варианта ответа');
                return;
            }
            
            optionItem.remove();
            renumberOptions();
            optionCounter = optionsContainer.querySelectorAll('.option-item').length;
        }
    });
}

/**
 * Создает элемент варианта ответа
 */
function createOptionElement(index) {
    const div = document.createElement('div');
    div.className = 'option-item';
    div.innerHTML = `
        <div class="form-group">
            <label for="option_${index}">Вариант ${index}</label>
            <div class="option-input-group">
                <input type="text" 
                       id="option_${index}" 
                       name="option_text[]" 
                       class="option-text"
                       required
                       placeholder="Введите текст варианта">
                <label class="checkbox-label option-correct">
                    <input type="checkbox" 
                           name="is_correct[]" 
                           value="${index - 1}"
                           class="correct-option-checkbox">
                    <span class="checkmark"></span>
                    Правильный
                </label>
                <button type="button" class="btn btn-sm btn-danger remove-option" aria-label="Удалить вариант">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    return div;
}

/**
 * Перенумерация вариантов после удаления
 */
function renumberOptions() {
    const optionsContainer = document.getElementById('options-container');
    if (!optionsContainer) return;
    
    optionsContainer.querySelectorAll('.option-item').forEach((item, index) => {
        const newIndex = index + 1;
        item.querySelector('label').textContent = `Вариант ${newIndex}`;
        item.querySelector('label').setAttribute('for', `option_${newIndex}`);
        
        const textInput = item.querySelector('input[type="text"]');
        textInput.id = `option_${newIndex}`;
        textInput.name = `option_text[]`;
        
        const checkbox = item.querySelector('input[type="checkbox"]');
        checkbox.value = index;
        checkbox.name = `is_correct[]`;
    });
}

/**
 * Обновление обязательных полей в зависимости от типа упражнения
 */
function updateFieldRequirements() {
    const type = document.getElementById('exercise_type')?.value;
    const answerField = document.getElementById('answer');
    
    if (!answerField) return;
    
    if (type === 'multiple_choice') {
        answerField.removeAttribute('required');
        answerField.classList.add('hidden-field');
    } else {
        answerField.setAttribute('required', 'required');
        answerField.classList.remove('hidden-field');
    }
}

/**
 * Инициализация редактора кода (если есть)
 */
function initializeCodeEditor() {
    const codeTemplateTextarea = document.getElementById('code_template');
    if (!codeTemplateTextarea) return;
    
    const editor = CodeMirror.fromTextArea(codeTemplateTextarea, {
        lineNumbers: true,
        mode: 'javascript',
        theme: 'default',
        indentUnit: 4,
        extraKeys: {
            'Tab': function(cm) {
                cm.replaceSelection('    ', 'end');
            }
        }
    });
    
    // Сохраняем ссылку на редактор для использования в форме
    window.codeEditor = editor;
}

/**
 * Валидация формы перед отправкой
 */
document.querySelector('.admin-form')?.addEventListener('submit', function(e) {
    const exerciseType = document.getElementById('exercise_type')?.value;
    
    if (!validateExerciseForm(exerciseType)) {
        e.preventDefault();
    }
});

/**
 * Валидация формы в зависимости от типа упражнения
 */
function validateExerciseForm(exerciseType) {
    if (exerciseType === 'multiple_choice') {
        return validateMultipleChoiceForm();
    }
    
    if (exerciseType === 'code' && window.codeEditor) {
        window.codeEditor.save();
    }
    
    return true;
}

/**
 * Валидация формы с множественным выбором
 */
function validateMultipleChoiceForm() {
    const options = document.querySelectorAll('input[name="option_text[]"]');
    const checkedOptions = document.querySelectorAll('input[name="is_correct[]"]:checked');
    
    // Проверка минимального количества вариантов
    if (options.length < 2) {
        showAlert('Необходимо минимум 2 варианта ответа');
        return false;
    }
    
    // Проверка заполненности всех вариантов
    let emptyOptions = Array.from(options).filter(opt => !opt.value.trim());
    if (emptyOptions.length > 0) {
        showAlert('Все варианты должны содержать текст');
        // Подсветка пустых полей
        emptyOptions.forEach(opt => {
            opt.classList.add('is-invalid');
            opt.addEventListener('input', function() {
                if (this.value.trim()) this.classList.remove('is-invalid');
            });
        });
        return false;
    }
    
    // Проверка выбранных правильных ответов
    if (checkedOptions.length === 0) {
        showAlert('Необходимо выбрать хотя бы один правильный ответ');
        return false;
    }
    
    return true;
}

/**
 * Показ уведомления
 */
function showAlert(message) {
    // Можно заменить на более красивый Toast или модальное окно
    alert(message);
}

/**
 * Делегирование событий для динамически добавляемых элементов
 */
document.addEventListener('input', function(e) {
    // Убираем класс ошибки при вводе
    if (e.target.classList.contains('is-invalid') && e.target.value.trim()) {
        e.target.classList.remove('is-invalid');
    }
});
</script>