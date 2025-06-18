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
$page_title = "Управление достижениями";

?>

<div class="admin-container">
    <?php include '../admin/admin_sidebar.php'; ?>
    
    <main class="admin-content">
        <div class="admin-header">
            <h1>Управление достижениями</h1>
        </div>

        <?php
        // Обработка добавления нового достижения
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_achievement'])) {
            $user_id = (int)$_POST['user_id'];
            $achievement_type = trim($_POST['achievement_type']);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO achievements (user_id, achievement_type) VALUES (?, ?)");
                $stmt->execute([$user_id, $achievement_type]);
                
                echo '<div class="alert alert-success">Достижение успешно добавлено!</div>';
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Ошибка при добавлении достижения: ' . $e->getMessage() . '</div>';
            }
        }
        
        // Обработка удаления достижения
        if (isset($_GET['delete'])) {
            $achievement_id = (int)$_GET['delete'];
            
            try {
                $stmt = $pdo->prepare("DELETE FROM achievements WHERE id = ?");
                $stmt->execute([$achievement_id]);
                
                echo '<div class="alert alert-success">Достижение успешно удалено!</div>';
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Ошибка при удалении достижения: ' . $e->getMessage() . '</div>';
            }
        }
        ?>

        <div class="admin-sections">
            <div class="card">
                <div class="card-header">
                    <h3>Список достижений</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Пользователь</th>
                                    <th>Тип достижения</th>
                                    <th>Дата получения</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Получаем список всех достижений с именами пользователей
                                $stmt = $pdo->query("
                                    SELECT a.*, u.username, u.email 
                                    FROM achievements a
                                    JOIN users u ON a.user_id = u.id
                                    ORDER BY a.earned_at DESC
                                ");
                                $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (empty($achievements)) {
                                    echo '<tr><td colspan="5" class="text-center">Достижения не найдены</td></tr>';
                                } else {
                                    foreach ($achievements as $achievement) {
                                        echo '<tr>';
                                        echo '<td>' . $achievement['id'] . '</td>';
                                        echo '<td>' . htmlspecialchars($achievement['username']) . '<br><small>' . htmlspecialchars($achievement['email']) . '</small></td>';
                                        echo '<td>' . htmlspecialchars($achievement['achievement_type']) . '</td>';
                                        echo '<td>' . date('d.m.Y H:i', strtotime($achievement['earned_at'])) . '</td>';
                                        echo '<td class="actions">
                                                <a href="?delete=' . $achievement['id'] . '" class="btn-sm btn-delete" onclick="return confirm(\'Удалить это достижение?\')">Удалить</a>
                                              </td>';
                                        echo '</tr>';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Добавить достижение</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Пользователь</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Выберите пользователя</option>
                                <?php
                                $users = $pdo->query("SELECT id, username, email FROM users ORDER BY username")->fetchAll();
                                foreach ($users as $user) {
                                    echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['email']) . ')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="achievement_type" class="form-label">Тип достижения</label>
                            <input type="text" class="form-control" id="achievement_type" name="achievement_type" required>
                            <div class="form-text">Например: "Пройдено 10 уроков", "Первое место" и т.д.</div>
                        </div>
                        
                        <button type="submit" name="add_achievement" class="btn btn-primary">Добавить</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
