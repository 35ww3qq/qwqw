// Main application JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize notifications
    initNotifications();
    
    // Setup theme switcher
    setupThemeSwitcher();
    
    // Setup navigation
    setupNavigation();
});

// Tooltip initialization
function initTooltips() {
    tippy('[data-tooltip]', {
        theme: 'dark',
        placement: 'top',
        animation: 'fade'
    });
}

// Notification system
const notifications = {
    show(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} fade-in`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                type === 'error' ? 'exclamation-circle' : 
                                'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, duration);
    },
    
    success(message, duration) {
        this.show(message, 'success', duration);
    },
    
    error(message, duration) {
        this.show(message, 'error', duration);
    },
    
    info(message, duration) {
        this.show(message, 'info', duration);
    }
};

function initNotifications() {
    // Add notification styles
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            z-index: 1000;
            min-width: 300px;
            max-width: 500px;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .notification i {
            font-size: 1.25rem;
        }
        
        .notification-success i {
            color: var(--success);
        }
        
        .notification-error i {
            color: var(--danger);
        }
        
        .notification-info i {
            color: var(--accent-primary);
        }
    `;
    document.head.appendChild(style);
}

// Theme switcher
function setupThemeSwitcher() {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) return;
    
    const currentTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    themeToggle.addEventListener('click', () => {
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
}

// Navigation
function setupNavigation() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
        
        // Add hover animation
        link.addEventListener('mouseenter', () => {
            if (!link.classList.contains('active')) {
                link.style.transform = 'translateY(-2px)';
            }
        });
        
        link.addEventListener('mouseleave', () => {
            link.style.transform = 'translateY(0)';
        });
    });
}

// Modal system
const modal = {
    show(title, content) {
        return new Promise(resolve => {
            const modalElement = document.createElement('div');
            modalElement.className = 'modal fade-in';
            modalElement.innerHTML = `
                <div class="modal-backdrop"></div>
                <div class="modal-container">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>${title}</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary modal-cancel">İptal</button>
                            <button class="btn btn-primary modal-confirm">Tamam</button>
                        </div>
                    </div>
                </div>
            `;
            
            const modalBody = modalElement.querySelector('.modal-body');
            if (typeof content === 'string') {
                modalBody.innerHTML = content;
            } else {
                modalBody.appendChild(content);
            }
            
            document.body.appendChild(modalElement);
            
            // Handle close
            const close = () => {
                modalElement.classList.add('fade-out');
                setTimeout(() => modalElement.remove(), 300);
            };
            
            modalElement.querySelector('.modal-close').onclick = () => {
                close();
                resolve(false);
            };
            
            modalElement.querySelector('.modal-cancel').onclick = () => {
                close();
                resolve(false);
            };
            
            modalElement.querySelector('.modal-confirm').onclick = () => {
                close();
                resolve(true);
            };
            
            modalElement.querySelector('.modal-backdrop').onclick = () => {
                close();
                resolve(false);
            };
        });
    },
    
    async confirm(message) {
        return this.show('Onay', `<p>${message}</p>`);
    },
    
    async prompt(message, defaultValue = '') {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control';
        input.value = defaultValue;
        
        const result = await this.show(message, input);
        return result ? input.value : null;
    }
};

// Export utilities
window.notifications = notifications;
window.modal = modal;