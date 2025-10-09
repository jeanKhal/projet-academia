/**
 * Académie IA - JavaScript Principal
 * Fonctionnalités générales de l'application
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('Académie IA - Application chargée');
    
    // Initialiser toutes les fonctionnalités
    initMobileMenu();
    initTooltips();
    initModals();
    initForms();
    initAnimations();
    initNotifications();
    initSearch();
    initLazyLoading();
});

/**
 * Gestion du menu mobile
 */
function initMobileMenu() {
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    console.log('Initialisation du menu mobile:', { mobileMenuButton, mobileMenu });
    
    if (mobileMenuButton && mobileMenu) {
        // Supprimer les événements existants pour éviter les doublons
        mobileMenuButton.removeEventListener('click', handleMobileMenuClick);
        mobileMenuButton.addEventListener('click', handleMobileMenuClick);
        
        console.log('Menu mobile initialisé avec succès');
    } else {
        console.error('Éléments du menu mobile non trouvés:', { mobileMenuButton, mobileMenu });
    }
}

/**
 * Gestionnaire de clic pour le menu mobile
 */
function handleMobileMenuClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    
    console.log('Clic sur le bouton mobile menu');
    
    if (mobileMenu && mobileMenuButton) {
        // Basculer la visibilité du menu
        mobileMenu.classList.toggle('hidden');
        
        // Animer l'icône du bouton
        const icon = mobileMenuButton.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        }
        
        console.log('Menu mobile visible:', !mobileMenu.classList.contains('hidden'));
    }
}

// Fermer le menu en cliquant à l'extérieur
document.addEventListener('click', function(event) {
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuButton && mobileMenu && !mobileMenuButton.contains(event.target) && !mobileMenu.contains(event.target)) {
        mobileMenu.classList.add('hidden');
        
        const icon = mobileMenuButton.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    }
});

/**
 * Gestion du menu utilisateur
 */
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Fermer le menu utilisateur si on clique ailleurs
document.addEventListener('click', function(event) {
    const menu = document.getElementById('userMenu');
    const button = event.target.closest('button');
    
    if (menu && !button && !menu.contains(event.target)) {
        menu.classList.add('hidden');
    }
});

/**
 * Gestion des tooltips
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 0.5rem;
                border-radius: 0.25rem;
                font-size: 0.875rem;
                z-index: 1000;
                pointer-events: none;
                white-space: nowrap;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

/**
 * Gestion des modales
 */
function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // Fermer les modales
    modals.forEach(modal => {
        const closeButton = modal.querySelector('.modal-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            });
        }
        
        // Fermer en cliquant à l'extérieur
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
        
        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
    });
}

/**
 * Gestion des formulaires
 */
function initForms() {
    const forms = document.querySelectorAll('form[data-ajax]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Afficher l'état de chargement
            submitButton.disabled = true;
            submitButton.textContent = 'Envoi en cours...';
            submitButton.classList.add('loading');
            
            fetch(this.action, {
                method: this.method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Succès !', 'success');
                    this.reset();
                } else {
                    showNotification(data.message || 'Erreur !', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue', 'error');
            })
            .finally(() => {
                // Restaurer l'état du bouton
                submitButton.disabled = false;
                submitButton.textContent = originalText;
                submitButton.classList.remove('loading');
            });
        });
    });
}

/**
 * Gestion des animations
 */
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observer les éléments avec la classe animate-on-scroll
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    animatedElements.forEach(el => observer.observe(el));
}

/**
 * Système de notifications
 */
function initNotifications() {
    // Créer le conteneur de notifications s'il n'existe pas
    if (!document.getElementById('notifications')) {
        const notificationsContainer = document.createElement('div');
        notificationsContainer.id = 'notifications';
        notificationsContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(notificationsContainer);
    }
}

/**
 * Afficher une notification
 */
function showNotification(message, type = 'info', duration = 5000) {
    const notificationsContainer = document.getElementById('notifications');
    const notification = document.createElement('div');
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    notification.className = `${colors[type]} text-white p-4 rounded-lg shadow-lg mb-2 animate-slide-in`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    notificationsContainer.appendChild(notification);
    
    // Supprimer automatiquement après la durée spécifiée
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, duration);
}

/**
 * Gestion de la recherche
 */
function initSearch() {
    const searchInput = document.querySelector('#search-input');
    const searchResults = document.querySelector('#search-results');
    
    if (searchInput && searchResults) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        // Fermer les résultats en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });
    }
}

/**
 * Effectuer une recherche
 */
function performSearch(query) {
    const searchResults = document.querySelector('#search-results');
    
    // Simuler une recherche (remplacer par un vrai appel API)
    fetch(`/api/search?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('Erreur de recherche:', error);
            searchResults.classList.add('hidden');
        });
}

/**
 * Afficher les résultats de recherche
 */
function displaySearchResults(results) {
    const searchResults = document.querySelector('#search-results');
    
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="p-4 text-gray-500">Aucun résultat trouvé</div>';
    } else {
        searchResults.innerHTML = results.map(result => `
            <a href="${result.url}" class="block p-4 hover:bg-gray-100 border-b border-gray-200">
                <div class="font-medium">${result.title}</div>
                <div class="text-sm text-gray-600">${result.description}</div>
            </a>
        `).join('');
    }
    
    searchResults.classList.remove('hidden');
}

/**
 * Chargement différé des images
 */
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

/**
 * Utilitaires
 */

// Fonction pour formater les dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Fonction pour formater les nombres
function formatNumber(number) {
    return new Intl.NumberFormat('fr-FR').format(number);
}

// Fonction pour tronquer le texte
function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// Fonction pour valider les emails
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Fonction pour valider les mots de passe
function isValidPassword(password) {
    return password.length >= 8;
}

// Fonction pour copier du texte dans le presse-papiers
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copié dans le presse-papiers !', 'success');
    }).catch(() => {
        showNotification('Erreur lors de la copie', 'error');
    });
}

// Fonction pour télécharger un fichier
function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Fonction pour ouvrir une URL dans un nouvel onglet
function openInNewTab(url) {
    window.open(url, '_blank', 'noopener,noreferrer');
}

// Fonction pour faire défiler vers le haut
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Fonction pour faire défiler vers un élément
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Fonction pour basculer la visibilité d'un élément
function toggleElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.toggle('hidden');
    }
}

// Fonction pour ajouter/retirer une classe
function toggleClass(elementId, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.toggle(className);
    }
}

// Fonction pour obtenir les paramètres de l'URL
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Fonction pour mettre à jour les paramètres de l'URL
function updateUrlParameter(name, value) {
    const url = new URL(window.location);
    url.searchParams.set(name, value);
    window.history.pushState({}, '', url);
}

// Fonction pour supprimer un paramètre de l'URL
function removeUrlParameter(name) {
    const url = new URL(window.location);
    url.searchParams.delete(name);
    window.history.pushState({}, '', url);
}

// Fonction pour vérifier si l'utilisateur est sur mobile
function isMobile() {
    return window.innerWidth < 768;
}

// Fonction pour vérifier si l'utilisateur est sur tablette
function isTablet() {
    return window.innerWidth >= 768 && window.innerWidth < 1024;
}

// Fonction pour vérifier si l'utilisateur est sur desktop
function isDesktop() {
    return window.innerWidth >= 1024;
}

// Fonction pour obtenir la taille de l'écran
function getScreenSize() {
    if (isMobile()) return 'mobile';
    if (isTablet()) return 'tablet';
    return 'desktop';
}

// Fonction pour logger les erreurs
function logError(error, context = '') {
    console.error(`[${context}] Erreur:`, error);
    
    // En production, envoyer l'erreur à un service de monitoring
    if (typeof Sentry !== 'undefined') {
        Sentry.captureException(error);
    }
}

// Fonction pour logger les événements
function logEvent(eventName, data = {}) {
    console.log(`[Event] ${eventName}:`, data);
    
    // En production, envoyer l'événement à un service d'analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', eventName, data);
    }
}

// Exposer les fonctions globalement
window.AcademieIA = {
    showNotification,
    formatDate,
    formatNumber,
    truncateText,
    isValidEmail,
    isValidPassword,
    copyToClipboard,
    downloadFile,
    openInNewTab,
    scrollToTop,
    scrollToElement,
    toggleElement,
    toggleClass,
    getUrlParameter,
    updateUrlParameter,
    removeUrlParameter,
    isMobile,
    isTablet,
    isDesktop,
    getScreenSize,
    logError,
    logEvent
};
