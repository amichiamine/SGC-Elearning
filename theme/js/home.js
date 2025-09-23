/**
 * JavaScript spécifique à la page d'accueil SGC E-Learning
 * Gestion des interactions avancées et animations
 */

class SGCHomePage {
    constructor() {
        this.init();
    }

    init() {
        this.initAnnouncementsSlider();
        this.initScrollAnimations();
        this.initStatisticsCounter();
        this.initTestimonialsSlider();
        this.initCourseCardEffects();
        this.initInstructorCards();
        this.initHeroEffects();
    }

    /**
     * Slider des annonces avec auto-défilement
     */
    initAnnouncementsSlider() {
        const slider = document.querySelector('.announcements-slider');
        if (!slider) return;

        let currentIndex = 0;
        const items = slider.querySelectorAll('.announcement-item');
        const itemWidth = items[0]?.offsetWidth + 16; // 16px de gap

        // Auto-scroll toutes les 5 secondes
        setInterval(() => {
            currentIndex = (currentIndex + 1) % items.length;
            slider.scrollTo({
                left: currentIndex * itemWidth,
                behavior: 'smooth'
            });
        }, 5000);

        // Navigation tactile améliorée
        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.style.cursor = 'grabbing';
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });

        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });

        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2;
            slider.scrollLeft = scrollLeft - walk;
        });
    }

    /**
     * Animations au scroll avec Intersection Observer
     */
    initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    
                    // Animations spéciales par type d'élément
                    if (entry.target.classList.contains('course-card')) {
                        this.animateCourseCard(entry.target);
                    } else if (entry.target.classList.contains('instructor-card')) {
                        this.animateInstructorCard(entry.target);
                    } else if (entry.target.classList.contains('testimonial-card')) {
                        this.animateTestimonialCard(entry.target);
                    }
                    
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observer tous les éléments animables
        document.querySelectorAll('.course-card, .instructor-card, .testimonial-card, .stat-item').forEach(el => {
            observer.observe(el);
        });
    }

    /**
     * Compteur animé pour les statistiques
     */
    initStatisticsCounter() {
        const statNumbers = document.querySelectorAll('.stat-number');
        
        statNumbers.forEach(stat => {
            const finalValue = stat.textContent.replace(/[^\d]/g, '');
            if (!finalValue) return;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(stat, 0, parseInt(finalValue));
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            observer.observe(stat);
        });
    }

    /**
     * Animation du compteur
     */
    animateCounter(element, start, end) {
        const duration = 2000;
        const startTime = performance.now();
        const originalText = element.textContent;
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentValue = Math.floor(start + (end - start) * this.easeOutQuart(progress));
            
            // Maintenir le formatage original
            if (originalText.includes('+')) {
                element.textContent = currentValue.toLocaleString() + '+';
            } else if (originalText.includes('%')) {
                element.textContent = currentValue + '%';
            } else {
                element.textContent = currentValue.toLocaleString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    /**
     * Fonction d'easing pour les animations
     */
    easeOutQuart(t) {
        return 1 - (--t) * t * t * t;
    }

    /**
     * Slider des témoignages avec navigation automatique
     */
    initTestimonialsSlider() {
        const testimonials = document.querySelectorAll('.testimonial-card');
        if (testimonials.length <= 1) return;

        let currentTestimonial = 0;

        // Rotation automatique toutes les 6 secondes
        setInterval(() => {
            testimonials[currentTestimonial].style.opacity = '0.7';
            currentTestimonial = (currentTestimonial + 1) % testimonials.length;
            testimonials[currentTestimonial].style.opacity = '1';
            testimonials[currentTestimonial].style.transform = 'scale(1.02)';
            
            setTimeout(() => {
                testimonials.forEach((t, i) => {
                    if (i !== currentTestimonial) {
                        t.style.transform = 'scale(1)';
                        t.style.opacity = '0.8';
                    }
                });
                testimonials[currentTestimonial].style.transform = 'scale(1)';
            }, 500);
        }, 6000);
    }

    /**
     * Effets avancés sur les cartes de cours
     */
    initCourseCardEffects() {
        document.querySelectorAll('.course-card').forEach(card => {
            // Effet de tilt au survol
            card.addEventListener('mouseenter', () => {
                card.style.transition = 'transform 0.3s ease-out';
                card.style.transform = 'translateY(-8px) rotateX(5deg)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) rotateX(0)';
            });

            // Effet de loading sur le bouton
            const btn = card.querySelector('.btn');
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    btn.classList.add('btn-loading');
                    btn.disabled = true;
                    
                    setTimeout(() => {
                        // Simulation de navigation
                        window.location.href = btn.href;
                    }, 1000);
                });
            }
        });
    }

    /**
     * Animations des cartes formateurs
     */
    initInstructorCards() {
        document.querySelectorAll('.instructor-card').forEach(card => {
            const avatar = card.querySelector('.instructor-avatar img');
            
            card.addEventListener('mouseenter', () => {
                if (avatar) {
                    avatar.style.transform = 'scale(1.1) rotate(5deg)';
                    avatar.style.transition = 'transform 0.3s ease-out';
                }
                
                // Effet de glow
                card.style.boxShadow = '0 20px 60px 0 rgba(74, 144, 226, 0.4)';
            });

            card.addEventListener('mouseleave', () => {
                if (avatar) {
                    avatar.style.transform = 'scale(1) rotate(0deg)';
                }
                
                card.style.boxShadow = '';
            });
        });
    }

    /**
     * Effets spéciaux pour la section hero
     */
    initHeroEffects() {
        const heroPlaceholder = document.querySelector('.hero-placeholder');
        if (!heroPlaceholder) return;

        // Animation de rotation continue
        let rotation = 0;
        const rotateHero = () => {
            rotation += 0.5;
            heroPlaceholder.style.transform = `rotate(${rotation}deg)`;
            requestAnimationFrame(rotateHero);
        };
        rotateHero();

        // Parallax léger au scroll
        window.addEventListener('scroll', this.throttle(() => {
            const scrolled = window.pageYOffset;
            const heroSection = document.querySelector('.hero-section');
            
            if (heroSection && scrolled < window.innerHeight) {
                heroSection.style.transform = `translateY(${scrolled * 0.3}px)`;
            }
        }, 16));

        // Effet de particules (simulation simple)
        this.createFloatingElements();
    }

    /**
     * Création d'éléments flottants décoratifs
     */
    createFloatingElements() {
        const heroSection = document.querySelector('.hero-section');
        if (!heroSection) return;

        for (let i = 0; i < 5; i++) {
            const element = document.createElement('div');
            element.className = 'floating-element';
            element.style.cssText = `
                position: absolute;
                width: ${Math.random() * 10 + 5}px;
                height: ${Math.random() * 10 + 5}px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                left: ${Math.random() * 100}%;
                top: ${Math.random() * 100}%;
                animation: float ${Math.random() * 10 + 10}s infinite linear;
                pointer-events: none;
                z-index: 0;
            `;
            
            heroSection.appendChild(element);
        }
    }

    /**
     * Animations spécifiques par type de carte
     */
    animateCourseCard(card) {
        card.style.animationDelay = Math.random() * 0.5 + 's';
    }

    animateInstructorCard(card) {
        card.style.animationDelay = Math.random() * 0.3 + 's';
        
        // Animation spéciale pour l'avatar
        setTimeout(() => {
            const avatar = card.querySelector('.instructor-avatar');
            if (avatar) {
                avatar.style.animation = 'pulse 2s ease-in-out infinite';
            }
        }, 500);
    }

    animateTestimonialCard(card) {
        // Animation des étoiles en séquence
        const stars = card.querySelectorAll('.icon-star');
        stars.forEach((star, index) => {
            setTimeout(() => {
                star.style.animation = 'sparkle 0.5s ease-out';
            }, index * 100);
        });
    }

    /**
     * Utilitaire throttle
     */
    throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Keyframes pour les animations CSS injectées
const additionalStyles = `
    @keyframes float {
        0% { transform: translateY(0px) rotate(0deg); }
        33% { transform: translateY(-10px) rotate(120deg); }
        66% { transform: translateY(5px) rotate(240deg); }
        100% { transform: translateY(0px) rotate(360deg); }
    }

    @keyframes sparkle {
        0% { transform: scale(1) rotate(0deg); }
        50% { transform: scale(1.2) rotate(180deg); }
        100% { transform: scale(1) rotate(360deg); }
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .floating-element {
        opacity: 0.6;
    }

    .btn-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        margin: auto;
        border: 2px solid currentColor;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }

    @media (prefers-reduced-motion: reduce) {
        .floating-element,
        .hero-placeholder {
            animation: none !important;
        }
    }
`;

// Injecter les styles additionnels
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);

// Initialisation automatique au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    new SGCHomePage();
});

// Export pour utilisation dans d'autres modules si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SGCHomePage;
}