<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();
require_once '../admin/admin_functions.php';

session_start();

// Проверка прав администратора
if (!isAdmin()) {
    header('Location: /user/login.php');
    exit;
}

// Получаем ID пользователя из URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error'] = 'Неверный ID пользователя';
    header('Location: users.php');
    exit;
}

// Получаем данные пользователя
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = 'Пользователь не найден';
        header('Location: users.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Ошибка при получении данных пользователя';
    header('Location: users.php');
    exit;
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $errors = [];
    
    // Валидация данных
    if (empty($username)) {
        $errors['username'] = 'Логин обязателен';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Логин должен содержать минимум 3 символа';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email обязателен';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }
    
    // Проверка уникальности email и username
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        
        if ($stmt->fetch()) {
            $errors['username'] = 'Логин или email уже заняты';
        }
    } catch (PDOException $e) {
        $errors['database'] = 'Ошибка при проверке данных';
    }
    
    // Если нет ошибок - обновляем пользователя
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET 
                username = ?, 
                email = ?, 
                full_name = ?, 
                bio = ?, 
                is_active = ?, 
                is_admin = ?,
                updated_at = NOW()
                WHERE id = ?");
            
            $stmt->execute([
                $username,
                $email,
                $full_name,
                $bio,
                $is_active,
                $is_admin,
                $user_id
            ]);
            
            $_SESSION['message'] = 'Данные пользователя успешно обновлены';
            header("Location: user_edit.php?id=$user_id");
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Ошибка при обновлении данных: ' . $e->getMessage();
        }
    }
}

// Установка заголовка страницы
$page_title = "Редактирование пользователя | LearnCode";

?>

<div class="admin-container">
    <?php include '../admin/admin_sidebar.php'; ?>
    
    <main class="admin-content">
        <div class="admin-header">
            <h1>Редактирование пользователя</h1>
            <a href="users.php" class="btn btn-outline-secondary">Назад к списку</a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?= $errors['database'] ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Логин</label>
                                <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                       id="username" name="username" 
                                       value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>" required>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback"><?= $errors['username'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" name="email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= $errors['email'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Полное имя</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($_POST['full_name'] ?? $user['full_name'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bio" class="form-label">О себе</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"><?= 
                                    htmlspecialchars($_POST['bio'] ?? $user['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?= ($_POST['is_active'] ?? $user['is_active']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Активный аккаунт</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" 
                                           <?= ($_POST['is_admin'] ?? $user['is_admin']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_admin">Администратор</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h3>Статистика пользователя</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div>
                                <span class="stat-value"><?= getUserCoursesCount($user_id) ?></span>
                                <span class="stat-label">Курсов начато</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <span class="stat-value"><?= getUserCompletedLessons($user_id) ?></span>
                                <span class="stat-label">Уроков завершено</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <span class="stat-value"><?= getUserXP($user_id) ?></span>
                                <span class="stat-label">Опыта (XP)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>