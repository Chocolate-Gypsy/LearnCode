<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';

session_start();


// Обработка формы обратной связи
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $errors = [];

    // Валидация данных
    if (empty($name)) {
        $errors['name'] = 'Пожалуйста, введите ваше имя';
    }

    if (empty($email)) {
        $errors['email'] = 'Пожалуйста, введите ваш email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Пожалуйста, введите корректный email';
    }

    if (empty($message)) {
        $errors['message'] = 'Пожалуйста, введите ваше сообщение';
    }

    // Если нет ошибок - сохраняем обращение
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, message, created_at) 
                                  VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $message]);
            
            $success_message = 'Ваше сообщение успешно отправлено! Мы ответим вам в ближайшее время.';
        } catch (PDOException $e) {
            $errors['database'] = 'Ошибка при отправке сообщения. Пожалуйста, попробуйте позже.';
        }
    }
}

// Установка заголовка страницы
$page_title = "Контакты | LearnCode";

// Подключение шапки
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4">Свяжитесь с нами</h1>
            <p class="lead">Есть вопросы или предложения? Напишите нам, и мы обязательно ответим!</p>
            
            <div class="contact-info mt-5">
                <div class="d-flex align-items-center mb-3">
                    <div class="contact-icon bg-primary bg-opacity-10 text-primary rounded-circle me-3 p-3">
                        <i class="fas fa-map-marker-alt fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Адрес</h5>
                        <p class="text-muted mb-0">г. Москва, ул. Программистов, 42</p>
                    </div>
                </div>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="contact-icon bg-primary bg-opacity-10 text-primary rounded-circle me-3 p-3">
                        <i class="fas fa-phone-alt fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Телефон</h5>
                        <p class="text-muted mb-0">+7 (495) 123-45-67</p>
                    </div>
                </div>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="contact-icon bg-primary bg-opacity-10 text-primary rounded-circle me-3 p-3">
                        <i class="fas fa-envelope fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Email</h5>
                        <p class="text-muted mb-0">support@learncode.example</p>
                    </div>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="contact-icon bg-primary bg-opacity-10 text-primary rounded-circle me-3 p-3">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Часы работы</h5>
                        <p class="text-muted mb-0">Пн-Пт: 9:00 - 18:00</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-4">Форма обратной связи</h3>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors['database'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errors['database']) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Ваше имя</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Сообщение</label>
                            <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" 
                                      id="message" name="message" rows="5"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['message']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">Отправить сообщение</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-5">
        <h2 class="text-center mb-4">Мы на карте</h2>
        <div class="ratio ratio-16x9 bg-light rounded-3 overflow-hidden">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2245.373789135826!2d37.61763331593095!3d55.75199998055146!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46b54a5a738fa419%3A0x7c347d506f52311f!2z0JrRgNCw0YHQvdC-0Y_RgNGB0LosINCc0L7RgdC60LLQsA!5e0!3m2!1sru!2sru!4v1623941234567!5m2!1sru!2sru" 
                    width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</div>

<?php
// Подключение подвала
include 'includes/footer.php';
?>