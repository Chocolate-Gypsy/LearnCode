<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();

// Проверяем авторизацию пользователя
if (!isLoggedIn()) {
    header('Location: /user/login.php');
    exit;
}

// Получаем данные пользователя
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
// Обработка загрузки фото
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $uploadDir = '../assets/images/avatars/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Проверяем ошибки загрузки
    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $errors['avatar'] = 'Ошибка загрузки файла: ' . $_FILES['avatar']['error'];
    } 
    // Проверяем тип файла
    elseif (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
        $errors['avatar'] = 'Допустимые форматы: JPG, PNG, GIF';
    }
    // Проверяем размер файла
    elseif ($_FILES['avatar']['size'] > $maxSize) {
        $errors['avatar'] = 'Максимальный размер файла: 2MB';
    } else {
        // Генерируем уникальное имя файла
        $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        // Пытаемся загрузить файл
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
            // Обновляем запись в БД
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            if ($stmt->execute([$filename, $userId])) {
                // Обновляем данные в сессии
                $_SESSION['profile_pic'] = $filename;
                $user['profile_picture'] = $filename;
                $success = true;
            } else {
                $errors['avatar'] = 'Ошибка при обновлении профиля';
            }
        } else {
            $errors['avatar'] = 'Ошибка при сохранении файла';
        }
    }
}

// Получаем статистику пользователя
$stmt = $pdo->prepare("SELECT COUNT(*) as completed_lessons FROM user_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// Обработка формы обновления профиля
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Валидация
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $errors['current_password'] = 'Введите текущий пароль';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $errors['current_password'] = 'Неверный текущий пароль';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Пароли не совпадают';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Пароль должен быть не менее 8 символов';
        }
    }

    if (empty($errors)) {
        try {
            // Обновляем данные пользователя
            $updateData = [
                'full_name' => $fullName,
                'email' => $email,
                'bio' => $bio,
                'id' => $userId
            ];

            $sql = "UPDATE users SET full_name = :full_name, email = :email, bio = :bio";

            // Если меняем пароль
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql .= ", password_hash = :password_hash";
                $updateData['password_hash'] = $hashedPassword;
            }

            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateData);

            // Обновляем данные в сессии
            $_SESSION['email'] = $email;
            $user['full_name'] = $fullName;
            $user['email'] = $email;
            $user['bio'] = $bio;

            $success = true;
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка при обновлении профиля: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Мой профиль - Learn Programming";
include '../includes/header.php';
?>

<div class="profile-container">
    <div class="profile-sidebar">
        <div class="profile-card">
            <div class="profile-avatar">
                <img src="/assets/images/avatars/<?= htmlspecialchars($user['profile_picture'] ?? 'default.jpg') ?>" alt="Аватар" id="avatar-preview">
                <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
            </div>
            <h2><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h2>
            <p class="username">@<?= htmlspecialchars($user['username']) ?></p>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['completed_lessons'] ?? 0 ?></span>
                    <span class="stat-label">уроков</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $user['streak_days'] ?? 0 ?></span>
                    <span class="stat-label">дней подряд</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $user['xp'] ?? 0 ?></span>
                    <span class="stat-label">очков</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="profile-content">
        <h1>Настройки профиля</h1>
        
        <?php if ($success): ?>
        <div class="alert alert-success">Профиль успешно обновлен!</div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="profile-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="avatar">Изображение профиля</label>
                <input type="file" id="avatar" name="avatar" accept="image/*">
                <?php if (!empty($errors['avatar'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['avatar']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="full_name">Полное имя</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="bio">О себе</label>
                <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            
            <h3>Смена пароля</h3>
            
            <div class="form-group">
                <label for="current_password">Текущий пароль</label>
                <input type="password" id="current_password" name="current_password">
                <?php if (!empty($errors['current_password'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['current_password']) ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="new_password">Новый пароль</label>
                <input type="password" id="new_password" name="new_password">
                <?php if (!empty($errors['new_password'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['new_password']) ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Подтвердите пароль</label>
                <input type="password" id="confirm_password" name="confirm_password">
                <?php if (!empty($errors['confirm_password'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>
    </div>
</div>
<script>
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(file);
        // Автоматически отправляем форму при выборе файла
        document.querySelector('.profile-form').submit();
    }
});
</script>
<?php include '../includes/footer.php'; ?>