<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

header('Content-Type: application/json');

// Разрешаем CORS запросы
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Проверка авторизации
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
    exit;
}

// Получаем данные из POST запроса
$data = json_decode(file_get_contents('php://input'), true);

// Валидация данных
if (!isset($data['exercise_id'], $data['lesson_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверные данные запроса']);
    exit;
}

$userId = $_SESSION['user_id'];
$exerciseId = (int)$data['exercise_id'];
$lessonId = (int)$data['lesson_id'];

try {
    $pdo->beginTransaction();
    
    // 1. Проверяем, не завершено ли уже это упражнение
    $stmt = $pdo->prepare("SELECT id FROM user_exercise_progress 
                          WHERE user_id = ? AND exercise_id = ?");
    $stmt->execute([$userId, $exerciseId]);
    
    if ($stmt->rowCount() === 0) {
        // 2. Добавляем запись о выполнении упражнения
        $stmt = $pdo->prepare("INSERT INTO user_exercise_progress 
                              (user_id, lesson_id, exercise_id, completed_at) 
                              VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $lessonId, $exerciseId]);
        
        // 3. Обновляем XP пользователя
        $stmt = $pdo->prepare("UPDATE users SET xp = xp + 10 WHERE id = ?");
        $stmt->execute([$userId]);
        
        // 4. Проверяем, все ли упражнения урока выполнены
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_exercises FROM exercises 
                              WHERE lesson_id = ?");
        $stmt->execute([$lessonId]);
        $totalExercises = $stmt->fetch()['total_exercises'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as completed_exercises 
                              FROM user_exercise_progress 
                              WHERE user_id = ? AND lesson_id = ?");
        $stmt->execute([$userId, $lessonId]);
        $completedExercises = $stmt->fetch()['completed_exercises'];
        
        // Если все упражнения урока выполнены
        if ($completedExercises >= $totalExercises) {
            // Добавляем запись о завершении урока
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_lesson_progress 
                                  (user_id, lesson_id, completed_at) 
                                  VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $lessonId]);
            
            // Начисляем дополнительные XP за завершение урока
            $stmt = $pdo->prepare("UPDATE users SET xp = xp + 50 WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Проверяем, все ли уроки курса выполнены
            $stmt = $pdo->prepare("SELECT COUNT(*) as total_lessons FROM lessons 
                                  WHERE course_id = (SELECT course_id FROM lessons WHERE id = ?)");
            $stmt->execute([$lessonId]);
            $totalLessons = $stmt->fetch()['total_lessons'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as completed_lessons 
                                  FROM user_lesson_progress 
                                  WHERE user_id = ? AND lesson_id IN 
                                  (SELECT id FROM lessons WHERE course_id = 
                                  (SELECT course_id FROM lessons WHERE id = ?))");
            $stmt->execute([$userId, $lessonId]);
            $completedLessons = $stmt->fetch()['completed_lessons'];
            
            // Если все уроки курса выполнены
            if ($completedLessons >= $totalLessons) {
                // Добавляем достижение "Завершение курса"
                $stmt = $pdo->prepare("INSERT IGNORE INTO user_achievements 
                                      (user_id, achievement_type, earned_at) 
                                      VALUES (?, 'course_completed', NOW())");
                $stmt->execute([$userId]);
                
                // Начисляем дополнительные XP за завершение курса
                $stmt = $pdo->prepare("UPDATE users SET xp = xp + 200 WHERE id = ?");
                $stmt->execute([$userId]);
            }
        }
    }
    
    $pdo->commit();
    
    // Получаем обновленные данные пользователя
    $stmt = $pdo->prepare("SELECT xp FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'xp' => $user['xp'],
        'message' => 'Прогресс сохранен'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Database error in complete_exercise.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка базы данных',
        'details' => $e->getMessage()
    ]);
}