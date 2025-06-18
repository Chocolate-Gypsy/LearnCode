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

// Установка заголовка страницы
$page_title = "Управление пользователями";



// Функция для получения списка пользователей
function getUsersList($search = null) {
    global $pdo;
    
    $sql = "SELECT id, username, email, full_name, created_at, is_active, is_admin, last_login 
            FROM users";
    
    if ($search) {
        $sql .= " WHERE username LIKE :search OR email LIKE :search OR full_name LIKE :search";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($search) {
        $searchTerm = "%$search%";
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Обработка действий
if (isset($_GET['action'])) {
    $userId = (int)$_GET['id'];
    
    try {
        switch ($_GET['action']) {
                     
            case 'promote':
                $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                $_SESSION['message'] = 'Пользователю назначены права администратора';
                break;
                
            case 'demote':
                $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
                $stmt->execute([$userId]);
                $_SESSION['message'] = 'Пользователь лишен прав администратора';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $_SESSION['message'] = 'Пользователь успешно удален';
                break;
        }
        
        header('Location: users.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при выполнении действия: ' . $e->getMessage();
        header('Location: users.php');
        exit;
    }
}

// Получаем список пользователей
$search = $_GET['search'] ?? null;
$users = getUsersList($search);
?>

<div class="admin-container">
    <?php include '../admin/admin_sidebar.php'; ?>
    
    <main class="admin-content">
        <div class="admin-header">
            <h1>Управление пользователями</h1>
            <form method="GET" class="admin-search">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Поиск пользователей..." 
                           value="<?= htmlspecialchars($search ?? '') ?>">
                    <button type="submit" class="btn btn-primary">Найти</button>
                    <?php if ($search): ?>
                        <a href="users.php" class="btn btn-outline-secondary">Сбросить</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Логин</th>
                                <th>Email</th>
                                <th>Имя</th>
                                <th>Дата регистрации</th>
                                <th>Последний вход</th>
                                <th>Статус</th>
                                <th>Роль</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Пользователи не найдены</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['full_name'] ?? 'Не указано') ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                        <td><?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Никогда' ?></td>
                                        <td>
                                            <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                                <?= $user['is_active'] ? 'Активен' : 'Неактивен' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $user['is_admin'] ? 'bg-primary' : 'bg-secondary' ?>">
                                                <?= $user['is_admin'] ? 'Админ' : 'Пользователь' ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <div class="dropdown">
                                                    <li>
                                                        <a class="dropdown-item text-danger" 
                                                           href="user_edit.php?id=<?= $user['id'] ?>">
                                                            Подробнее
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" 
                                                           href="users.php?action=delete&id=<?= $user['id'] ?>" 
                                                           onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">
                                                            Удалить
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
