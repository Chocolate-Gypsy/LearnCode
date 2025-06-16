<?php
require_once 'db_connect.php';

/**
 * Регистрация нового пользователя
 */
function registerUser($username, $email, $password, $fullName = '') {
    global $pdo;
    
    try {
        // Проверяем, существует ли пользователь
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            return false; // Пользователь уже существует
        }

        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Вставляем нового пользователя
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $hashedPassword, $fullName]);
        
        return $result;
    } catch (PDOException $e) {
        // Логируем ошибку
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

/**
 * Авторизация пользователя
 */
function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile_pic'] = $user['profile_picture'];
        
        updateUserStreak($user['id']);
        return true;
    }
    return false;
}

/**
 * Проверка авторизации пользователя
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Обновление "серии" (streak) пользователя
 */
function updateUserStreak($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT last_streak_date, streak_days FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $today = date('Y-m-d');
    $lastDate = $user['last_streak_date'];
    $streak = $user['streak_days'];
    
    if ($lastDate) {
        $diff = date_diff(new DateTime($lastDate), new DateTime($today))->days;
        
        if ($diff == 1) {
            $streak++;
        } elseif ($diff > 1) {
            $streak = 1;
        }
    } else {
        $streak = 1;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET last_streak_date = ?, streak_days = ?, last_login = NOW() WHERE id = ?");
    $stmt->execute([$today, $streak, $userId]);
}

/**
 * Выход пользователя
 */
function logoutUser() {
    session_unset();
    session_destroy();
    session_start();
}
?>