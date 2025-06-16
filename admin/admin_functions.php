<?php
/**
 * Проверка прав администратора
 */
function isAdmin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user && $user['is_admin'];
}

/**
 * Получить общее количество пользователей
 */
function getTotalUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    return $stmt->fetchColumn();
}

/**
 * Получить общее количество курсов
 */
function getTotalCourses() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM courses");
    return $stmt->fetchColumn();
}

/**
 * Получить общее количество уроков
 */
function getTotalLessons() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM lessons");
    return $stmt->fetchColumn();
}

/**
 * Получить общее количество завершенных уроков
 */
function getTotalCompletedLessons() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_progress");
    return $stmt->fetchColumn();
}

/**
 * Получить последних пользователей
 */
function getRecentUsers($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Получить последние курсы
 */
function getRecentCourses($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.id, c.title, c.is_active, COUNT(l.id) as lesson_count 
                          FROM courses c
                          LEFT JOIN lessons l ON c.id = l.course_id
                          GROUP BY c.id
                          ORDER BY c.created_at DESC
                          LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}


/**
 * Получить все курсы с количеством уроков
 */
function getAllCourses() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT c.*, COUNT(l.id) as lesson_count 
        FROM courses c
        LEFT JOIN lessons l ON c.id = l.course_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Удалить курс
 */
function deleteCourse($courseId) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Удаляем прогресс по урокам курса
        $stmt = $pdo->prepare("DELETE up FROM user_progress up
                              JOIN lessons l ON up.lesson_id = l.id
                              WHERE l.course_id = ?");
        $stmt->execute([$courseId]);
        
        // Удаляем уроки курса
        $stmt = $pdo->prepare("DELETE FROM lessons WHERE course_id = ?");
        $stmt->execute([$courseId]);
        
        // Удаляем сам курс
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        
        $pdo->commit();
        
        $_SESSION['admin_message'] = 'Курс успешно удален';
        $_SESSION['admin_message_type'] = 'success';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['admin_message'] = 'Ошибка при удалении курса: ' . $e->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
    }
}

/**
 * Переключить статус курса
 */
function toggleCourseStatus($courseId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE courses SET is_active = NOT is_active WHERE id = ?");
    if ($stmt->execute([$courseId])) {
        $_SESSION['admin_message'] = 'Статус курса изменен';
        $_SESSION['admin_message_type'] = 'success';
    } else {
        $_SESSION['admin_message'] = 'Ошибка при изменении статуса';
        $_SESSION['admin_message_type'] = 'danger';
    }
}

/**
 * Получить класс для сложности
 */
function getDifficultyClass($difficulty) {
    switch ($difficulty) {
        case 'beginner': return 'success';
        case 'intermediate': return 'warning';
        case 'advanced': return 'danger';
        default: return 'secondary';
    }
}

/**
 * Получить label для сложности
 */
function getDifficultyLabel($difficulty) {
    switch ($difficulty) {
        case 'beginner': return 'Начинающий';
        case 'intermediate': return 'Средний';
        case 'advanced': return 'Продвинутый';
        default: return $difficulty;
    }
}



/**
 * Получить уроки курса
 */
function getCourseLessons($courseId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM lessons 
                          WHERE course_id = ? 
                          ORDER BY order_number ASC");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

/**
 * Удалить урок
 */
function deleteLesson($lessonId) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Удаляем прогресс по уроку
        $stmt = $pdo->prepare("DELETE FROM user_progress WHERE lesson_id = ?");
        $stmt->execute([$lessonId]);
        
        // Удаляем упражнения урока
        $stmt = $pdo->prepare("DELETE FROM exercises WHERE lesson_id = ?");
        $stmt->execute([$lessonId]);
        
        // Удаляем сам урок
        $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        
        $pdo->commit();
        
        $_SESSION['admin_message'] = 'Урок успешно удален';
        $_SESSION['admin_message_type'] = 'success';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['admin_message'] = 'Ошибка при удалении урока: ' . $e->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
    }
}

/**
 * Обновить порядок уроков
 */
function updateLessonsOrder($orderData) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        foreach ($orderData as $order => $lessonId) {
            $stmt = $pdo->prepare("UPDATE lessons SET order_number = ? WHERE id = ?");
            $stmt->execute([$order + 1, $lessonId]);
        }
        
        $pdo->commit();
        
        $_SESSION['admin_message'] = 'Порядок уроков сохранен';
        $_SESSION['admin_message_type'] = 'success';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['admin_message'] = 'Ошибка при сохранении порядка: ' . $e->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
    }
}
/**
 * Получить упражнения урока
 */
function getLessonExercises($lessonId) {
    global $pdo;
    
    try {
        // Проверяем существование столбца order_number
        $stmt = $pdo->prepare("SHOW COLUMNS FROM exercises LIKE 'order_number'");
        $stmt->execute();
        $columnExists = $stmt->rowCount() > 0;
        
        if ($columnExists) {
            $stmt = $pdo->prepare("SELECT * FROM exercises 
                                 WHERE lesson_id = ? 
                                 ORDER BY order_number ASC, id ASC");
        } else {
            // Если столбец не существует, сортируем только по ID
            $stmt = $pdo->prepare("SELECT * FROM exercises 
                                 WHERE lesson_id = ? 
                                 ORDER BY id ASC");
        }
        
        $stmt->execute([$lessonId]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error getting exercises: " . $e->getMessage());
        return [];
    }
}
/**
 * Удалить упражнение
 */
function deleteExercise($exerciseId) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Удаляем варианты ответов (для multiple_choice)
        $stmt = $pdo->prepare("DELETE FROM exercise_options WHERE exercise_id = ?");
        $stmt->execute([$exerciseId]);
        
        // Удаляем само упражнение
        $stmt = $pdo->prepare("DELETE FROM exercises WHERE id = ?");
        $stmt->execute([$exerciseId]);
        
        $pdo->commit();
        
        $_SESSION['admin_message'] = 'Упражнение успешно удалено';
        $_SESSION['admin_message_type'] = 'success';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['admin_message'] = 'Ошибка при удалении упражнения: ' . $e->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
    }
}

/**
 * Обновить порядок упражнений
 */
function updateExercisesOrder($orderData) {
    global $pdo;
    
    try {
        // Проверяем существование столбца order_number
        $stmt = $pdo->query("SHOW COLUMNS FROM exercises LIKE 'order_number'");
        if ($stmt->rowCount() === 0) {
            $_SESSION['admin_message'] = 'Сортировка не поддерживается (отсутствует order_number)';
            $_SESSION['admin_message_type'] = 'warning';
            return;
        }

        $pdo->beginTransaction();
        
        foreach ($orderData as $order => $exerciseId) {
            $stmt = $pdo->prepare("UPDATE exercises SET order_number = ? WHERE id = ?");
            $stmt->execute([$order + 1, $exerciseId]);
        }
        
        $pdo->commit();
        
        $_SESSION['admin_message'] = 'Порядок упражнений сохранен';
        $_SESSION['admin_message_type'] = 'success';
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['admin_message'] = 'Ошибка при сохранении порядка: ' . $e->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
    }
}