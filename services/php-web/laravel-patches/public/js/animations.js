// Анимации для элементов
class SpaceAnimations {
    // Анимация появления карточек
    static animateCards() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Анимация счетчиков
    static animateCounter(element, target, duration = 2000) {
        let start = 0;
        const increment = target / (duration / 16);
        const timer = setInterval(() => {
            start += increment;
            if (start >= target) {
                element.textContent = Math.round(target).toLocaleString();
                clearInterval(timer);
            } else {
                element.textContent = Math.round(start).toLocaleString();
            }
        }, 16);
    }

    // Параллакс эффект для фона
    static initParallax() {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.parallax-bg');
            if (parallax) {
                parallax.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    }

    // Анимация загрузки
    static showLoading(container) {
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        container.appendChild(spinner);
        return spinner;
    }

    static hideLoading(spinner) {
        if (spinner) spinner.remove();
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', () => {
    SpaceAnimations.animateCards();
    SpaceAnimations.initParallax();
});