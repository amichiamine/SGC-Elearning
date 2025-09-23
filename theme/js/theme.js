/**
 * Système JavaScript du thème SGC E-Learning
 * Gestion des interactions, animations et composants claymorphism
 */

class SGCTheme {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initAnimations();
        this.initComponents();
        this.initMessages();
        this.initResponsive();
    }

    /**
     * Configuration des écouteurs d'événements globaux
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initNavigation();
            this.initForms();
            this.initCards();
            this.initModals();
        });

        // Gestion du redimensionnement
        window.addEventListener('resize', this.debounce(() => {
            this.updateResponsive();
        }, 250));

        // Gestion du scroll
        window.addEventListener('scroll', this.throttle(() => {
            this.updateScrollEffects();
        }, 16));
    }

    /**
     * Initialisation des animations d'entrée
     */
    initAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observer les éléments animables
        document.querySelectorAll('.clay-card, .card, .form-container').forEach(el => {
            observer.observe(el);
        });
    }

    /**
     * Initialisation des composants interactifs
     */
    initComponents() {
        this.initButtons();
        this.initDropdowns();
    }

    /**
     * Gestion des boutons avec effets
     */
    initButtons() {
        document.querySelectorAll('.btn').forEach(button => {
            // Effet ripple sur clic
            button.addEventListener('click', (e) => {
                this.createRippleEffect(e, button);
            });

            // Prévention du double-clic
            button.addEventListener('click', this.debounce(() => {
                // Logique anti-double-clic
            }, 300));
        });

        // Boutons de chargement
        document.querySelectorAll('.btn-loading').forEach(button => {
            button.disabled = true;
        });
    }

    /**
     * Effet ripple pour les boutons
     */
    createRippleEffect(event, element) {
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;

        element.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    /**
     * Initialisation des dropdowns
     */
    initDropdowns() {
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            if (toggle && menu) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Fermer tous les autres dropdowns
                    document.querySelectorAll('.dropdown').forEach(otherDropdown => {
                        if (otherDropdown !== dropdown) {
                            otherDropdown.classList.remove('active');
                        }
                    });
                    
                    // Toggle le dropdown actuel
                    dropdown.classList.toggle('active');
                });
            }
        });
        
        // Fermer les dropdowns en cliquant ailleurs
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        });
    }

    /**
     * Initialisation de la navigation
     */
    initNavigation() {
        const navToggle = document.getElementById('navbar-toggle');
        const navMenu = document.getElementById('navbar-menu');

        if (navToggle && navMenu) {
            navToggle.addEventListener('click', () => {
                navMenu.classList.toggle('active');
                navToggle.classList.toggle('active');
            });
        }

        // Navigation sticky
        const header = document.getElementById('main-header');
        if (header) {
            this.updateHeaderSticky(header);
        }
    }

    /**
     * Gestion du header sticky
     */
    updateHeaderSticky(header) {
        const scrollY = window.scrollY;
        
        if (scrollY > 100) {
            header.classList.add('header-scrolled');
        } else {
            header.classList.remove('header-scrolled');
        }
    }

    /**
     * Initialisation des formulaires
     */
    initForms() {
        // Animation des labels flottants
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.parentElement.classList.remove('focused');
                }
            });

            // État initial
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });

        // Validation en temps réel
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });

        // Upload de fichiers avec drag & drop
        this.initFileUpload();
    }

    /**
     * Validation de formulaire
     */
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('.form-input[required]');

        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('invalid');
                isValid = false;
            } else {
                input.classList.remove('invalid');
            }
        });

        return isValid;
    }

    /**
     * Initialisation des cartes avec effets
     */
    initCards() {
        document.querySelectorAll('.card, .clay-card').forEach(card => {
            // Effet parallax léger
            card.addEventListener('mousemove', (e) => {
                if (window.innerWidth > 768) {
                    this.cardParallaxEffect(e, card);
                }
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }

    /**
     * Effet parallax sur les cartes
     */
    cardParallaxEffect(event, card) {
        const rect = card.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const rotateX = (y - centerY) / centerY * 5;
        const rotateY = (centerX - x) / centerX * 5;
        
        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
    }

    /**
     * Initialisation des messages flash
     */
    initMessages() {
        document.querySelectorAll('.message').forEach(message => {
            // Auto-hide après 5 secondes
            setTimeout(() => {
                this.hideMessage(message);
            }, 5000);

            // Bouton de fermeture
            const closeBtn = message.querySelector('.message-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.hideMessage(message);
                });
            }
        });
    }

    /**
     * Masquer un message
     */
    hideMessage(message) {
        message.style.transform = 'translateX(100%)';
        message.style.opacity = '0';
        
        setTimeout(() => {
            message.remove();
        }, 300);
    }

    /**
     * Initialisation des modales
     */
    initModals() {
        document.querySelectorAll('[data-modal-target]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.getAttribute('data-modal-target');
                this.showModal(modalId);
            });
        });

        document.querySelectorAll('.modal-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                this.hideModal(closeBtn.closest('.modal'));
            });
        });
    }

    /**
     * Afficher une modale
     */
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Masquer une modale
     */
    hideModal(modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }

    /**
     * Gestion responsive
     */
    initResponsive() {
        this.updateResponsive();
    }

    updateResponsive() {
        const isMobile = window.innerWidth <= 768;
        document.body.classList.toggle('mobile', isMobile);
    }

    /**
     * Effets de scroll
     */
    updateScrollEffects() {
        const header = document.getElementById('main-header');
        if (header) {
            this.updateHeaderSticky(header);
        }

        // Parallax elements
        document.querySelectorAll('.parallax').forEach(element => {
            const scrolled = window.pageYOffset;
            const speed = element.getAttribute('data-speed') || 0.5;
            element.style.transform = `translateY(${scrolled * speed}px)`;
        });
    }

    /**
     * Upload de fichiers
     */
    initFileUpload() {
        document.querySelectorAll('.form-file').forEach(fileInput => {
            const input = fileInput.querySelector('input[type="file"]');
            const label = fileInput.querySelector('.form-file-label');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                label.addEventListener(eventName, this.preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                label.addEventListener(eventName, () => label.classList.add('drag-over'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                label.addEventListener(eventName, () => label.classList.remove('drag-over'), false);
            });

            label.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                input.files = files;
                this.handleFileSelect(files, label);
            });

            input.addEventListener('change', (e) => {
                this.handleFileSelect(e.target.files, label);
            });
        });
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    handleFileSelect(files, label) {
        if (files.length > 0) {
            const fileName = files[0].name;
            const fileText = label.querySelector('.file-text');
            if (fileText) {
                fileText.textContent = `Fichier sélectionné: ${fileName}`;
            }
        }
    }

    /**
     * Utilitaires
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

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

// Initialisation du thème
const sgcTheme = new SGCTheme();

// Styles CSS d'animation pour JavaScript
const animationStyles = `
    .animate-in {
        animation: slideInUp 0.6s ease-out forwards;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .header-scrolled {
        background: rgba(255, 255, 255, 0.2) !important;
        backdrop-filter: blur(25px) !important;
        box-shadow: 0 4px 20px rgba(74, 144, 226, 0.1) !important;
    }

    .drag-over {
        border-color: var(--color-sky-blue) !important;
        background: var(--glass-medium) !important;
    }

    .message {
        position: fixed;
        top: var(--spacing-4);
        right: var(--spacing-4);
        z-index: 10000;
        transform: translateX(100%);
        transition: all 0.3s ease-out;
        animation: slideInRight 0.3s ease-out forwards;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .mobile .card:hover {
        transform: none !important;
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
`;

// Injecter les styles
const themeStyleSheet = document.createElement('style');
themeStyleSheet.textContent = animationStyles;
document.head.appendChild(themeStyleSheet);