<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Learn Programming'; ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/courses.css">
<link rel="stylesheet" href="/assets/css/auth.css">
<link rel="stylesheet" href="/assets/css/dashboard.css">
<link rel="stylesheet" href="/assets/css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="/assets/js/main.js" defer></script>
    <script src="/assets/js/api.js" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo">
                <a href="/index.php">
                    <i class="fas fa-code"></i>
                    <span>LearnCode</span>
                </a>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="/courses/"><i class="fas fa-book"></i> Courses</a></li>
                    <li><a href="/about.php"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="/contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </nav>
            
            <div class="user-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-profile">
                        <div class="xp-badge">
                            <i class="fas fa-star"></i>
                            <span>
                                <?php 
                                // Здесь можно добавить запрос к БД для получения XP пользователя
                                echo isset($userXP) ? $userXP : '0'; 
                                ?> XP
                            </span>
                        </div>
                        <a href="/user/profile.php" class="profile-link">
                            <img src="/assets/images/avatars/<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg'; ?>" alt="Profile" class="profile-pic">
                        </a>
                        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="/user/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="/user/login.php" class="btn login-btn">Log In</a>
                        <a href="/user/register.php" class="btn register-btn">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
       
    </header>

