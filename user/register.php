<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();

// Если пользователь уже авторизован, перенаправляем его
if (isLoggedIn()) {
    header('Location: /user/dashboard.php');
    exit;
}

$errors = [];
$formData = [
    'username' => '',
    'email' => '',
    'full_name' => ''
];

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // Валидация
    if (empty($formData['username'])) {
        $errors['username'] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $formData['username'])) {
        $errors['username'] = 'Username must be 3-20 characters (letters, numbers, _)';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($formData['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Проверка уникальности username и email
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$formData['username'], $formData['email']]);
            
            if ($stmt->rowCount() > 0) {
                $existingUser = $stmt->fetch();
                
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$existingUser['id']]);
                $existingUsername = $stmt->fetchColumn();
                
                if ($existingUsername === $formData['username']) {
                    $errors['username'] = 'Username already taken';
                } else {
                    $errors['email'] = 'Email already registered';
                }
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Registration error. Please try again later.';
        }
    }
    
    // Регистрация пользователя
    if (empty($errors)) {
        if (registerUser(
            $formData['username'],
            $formData['email'],
            $formData['password'],
            $formData['full_name']
        )) {
            // Авторизуем пользователя после регистрации
            if (loginUser($formData['username'], $formData['password'])) {
                header('Location: /user/dashboard.php');
                exit;
            } else {
                header('Location: /user/login.php');
                exit;
            }
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}

// Устанавливаем заголовок страницы
$pageTitle = "Sign Up - Learn Programming";

// Подключаем header
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Create Your Account</h1>
        <p>Start your programming journey today</p>
        
        <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>
        
        <form id="register-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($formData['username']); ?>" required>
                <?php if (!empty($errors['username'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['username']); ?></div>
                <?php endif; ?>
                <div id="username-availability" class="small"></div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                <?php if (!empty($errors['email'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name (Optional)</label>
                <input type="text" id="full_name" name="full_name" class="form-control" 
                       value="<?php echo htmlspecialchars($formData['full_name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <div class="small">Must be at least 8 characters</div>
                <?php if (!empty($errors['password'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                <?php if (!empty($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
            </div>
            
            <div class="auth-footer">
                Already have an account? <a href="/user/login.php">Log in</a>
            </div>
        </form>
    </div>
    
    <div class="auth-image">
        <img src="/assets/images/signup-illustration.svg" alt="Sign Up Illustration">
    </div>
</div>

<script src="/assets/js/auth.js"></script>

<?php
// Подключаем footer
include '../includes/footer.php';
?>