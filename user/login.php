<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

session_start();

// Если пользователь уже авторизован, перенаправляем его
if (isLoggedIn()) {
    header('Location: /user/dashboard.php');
    exit;
}

$error = '';
$username = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Валидация
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Попытка авторизации
        if (loginUser($username, $password)) {
            // Перенаправление после успешного входа
            header('Location: /user/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Устанавливаем заголовок страницы
$pageTitle = "Login - Learn Programming";

// Подключаем header
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Welcome Back!</h1>
        <p>Log in to continue your programming journey</p>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form id="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <div class="text-right">
                    <a href="/user/forgot_password.php" class="small">Forgot password?</a>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Log In</button>
            </div>
            
            <div class="auth-footer">
                Don't have an account? <a href="/user/register.php">Sign up</a>
            </div>
        </form>
    </div>
    
    <div class="auth-image">
        <img src="/assets/images/login-illustration.svg" alt="Login Illustration">
    </div>
</div>

<script src="/assets/js/auth.js"></script>

<?php
// Подключаем footer
include '../includes/footer.php';
?>