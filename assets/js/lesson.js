document.addEventListener('DOMContentLoaded', function() {
    // Обработка множественного выбора
    document.querySelectorAll('.exercise-options .option').forEach(option => {
        option.addEventListener('click', function() {
            this.classList.toggle('selected');
        });
    });
    
    // Проверка ответов
    document.querySelectorAll('.check-answer').forEach(button => {
        button.addEventListener('click', function() {
            const exerciseId = this.dataset.exerciseId;
            const exercise = document.querySelector(`.exercise[data-exercise-id="${exerciseId}"]`);
            const feedback = exercise.querySelector('.feedback');
            
            // Здесь должна быть логика проверки ответа
            // В реальном приложении это может быть AJAX запрос к серверу
            
            // Пример для множественного выбора
            const selectedOptions = exercise.querySelectorAll('.option.selected');
            let allCorrect = true;
            
            selectedOptions.forEach(option => {
                if (option.dataset.isCorrect === '0') {
                    allCorrect = false;
                }
            });
            
            if (allCorrect && selectedOptions.length > 0) {
                feedback.textContent = 'Correct! Well done!';
                feedback.className = 'feedback correct';
            } else {
                feedback.textContent = 'Not quite right. Try again!';
                feedback.className = 'feedback incorrect';
            }
        });
    });
    
    // Завершение урока
    document.getElementById('complete-lesson').addEventListener('click', function() {
        const lessonId = this.dataset.lessonId;
        
        fetch('/api/complete_lesson.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                lesson_id: lessonId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Lesson completed! You earned ' + data.xp + ' XP!');
                window.location.href = `/courses/course.php?id=${data.course_id}`;
            }
        });
    });
    
    // Инициализация редактора кода (используем CodeMirror или другой редактор)
    document.querySelectorAll('.code-editor').forEach(editor => {
        // В реальном приложении здесь будет инициализация редактора кода
        console.log('Initialize code editor for', editor.id);
    });
});