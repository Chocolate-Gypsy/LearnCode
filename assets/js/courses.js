class CourseManager {
    constructor() {
        this.initFilters();
        this.initCourseCards();
    }
    
    initFilters() {
        const difficultyFilter = document.getElementById('difficulty-filter');
        const courseSearch = document.getElementById('course-search');
        
        if (difficultyFilter && courseSearch) {
            difficultyFilter.addEventListener('change', this.filterCourses.bind(this));
            courseSearch.addEventListener('input', this.filterCourses.bind(this));
        }
    }
    
    filterCourses() {
        const difficulty = document.getElementById('difficulty-filter').value;
        const searchTerm = document.getElementById('course-search').value.toLowerCase();
        const courseCards = document.querySelectorAll('.course-card');
        
        courseCards.forEach(card => {
            const matchesDifficulty = difficulty === 'all' || card.dataset.difficulty === difficulty;
            const matchesSearch = card.dataset.title.includes(searchTerm) || 
                                 card.querySelector('.course-description').textContent.toLowerCase().includes(searchTerm);
            
            card.style.display = matchesDifficulty && matchesSearch ? 'block' : 'none';
        });
    }
    
    initCourseCards() {
        const courseCards = document.querySelectorAll('.course-card');
        
        courseCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 5px 15px rgba(0,0,0,0.05)';
            });
        });
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.courses-page')) {
        new CourseManager();
    }
});