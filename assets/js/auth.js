document.addEventListener('DOMContentLoaded', function() {
    // Валидация формы регистрации
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm-password');
            
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
            
            // Дополнительные проверки можно добавить здесь
        });
    }
    
    // Валидация формы входа
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            if (!username.value || !password.value) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    }
    
    // AJAX проверка доступности имени пользователя
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.addEventListener('blur', function() {
            if (this.value.length < 3) return;
            
            fetch('/api/check_username.php?username=' + encodeURIComponent(this.value))
                .then(response => response.json())
                .then(data => {
                    const availabilityMsg = document.getElementById('username-availability');
                    if (!availabilityMsg) return;
                    
                    if (data.available) {
                        availabilityMsg.textContent = 'Username is available';
                        availabilityMsg.className = 'available';
                    } else {
                        availabilityMsg.textContent = 'Username is taken';
                        availabilityMsg.className = 'taken';
                    }
                });
        });
    }
});