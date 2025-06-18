
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Learn Programming'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="/assets/js/main.js" defer></script>
    <script src="/assets/js/api.js" defer></script>
</head>
<body><?php
// Проверка прав администратора
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-header">
        <div class="admin-logo">
            <i class="fas fa-shield-alt"></i>
            <span>Админ-панель</span>
        </div>
        <div class="admin-user">
            <img src="/assets/images/avatars/<?= htmlspecialchars($_SESSION['profile_pic'] ?? 'default.jpg') ?>" 
                 alt="<?= htmlspecialchars($_SESSION['username']) ?>" class="admin-avatar">
            <div class="admin-user-info">
                <span class="admin-username"><?= htmlspecialchars($_SESSION['username']) ?></span>
                <span class="admin-role">Администратор</span>
            </div>
        </div>
    </div>

    <nav class="admin-menu">
        <ul>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <a href="/admin/">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Обзор</span>
                </a>
            </li>

            <li class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                <a href="/admin/users.php">
                    <i class="fas fa-users"></i>
                    <span>Пользователи</span>
                </a>
            </li>
            
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'courses.php' || basename($_SERVER['PHP_SELF']) == 'course_edit.php' ? 'active' : '' ?>">
                <a href="/admin/courses.php">
                    <i class="fas fa-book"></i>
                    <span>Курсы</span>
                </a>
            </li>
            
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'lessons.php' || basename($_SERVER['PHP_SELF']) == 'lesson_edit.php' ? 'active' : '' ?>">
                <a href="/admin/lessons.php">
                    <i class="fas fa-list-ul"></i>
                    <span>Уроки</span>
                </a>
            </li>
            

            
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'exercises.php' ? 'active' : '' ?>">
                <a href="/admin/exercises.php">
                    <i class="fas fa-tasks"></i>
                    <span>Упражнения</span>
                </a>
            </li>
            
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'achievements.php' ? 'active' : '' ?>">
                <a href="/admin/achievements.php">
                    <i class="fas fa-trophy"></i>
                    <span>Достижения</span>
                </a>
            </li>
            
            <li class="menu-divider"></li>
            
            <li>
                <a href="/" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Перейти на сайт</span>
                </a>
            </li>
            
            <li>
                <a href="/user/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выйти</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
<script>
// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'admin-toggle';
    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.prepend(toggleBtn);
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
            sidebar.classList.remove('active');
        }
    });
});
</script>
</body>
</html>