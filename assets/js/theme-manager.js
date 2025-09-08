/**
 * ZapShop Theme Manager
 * จัดการ Dark/Light Mode และ Animations
 */

class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        this.applyTheme();
        this.createThemeToggle();
        this.setupAnimations();
        this.setupLoadingStates();
        this.setupImageOptimization();
    }

    /**
     * ใช้ธีมปัจจุบัน
     */
    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.currentTheme);
        document.body.classList.toggle('dark-mode', this.currentTheme === 'dark');
        
        // อัปเดต icon ในปุ่ม toggle
        const themeIcon = document.querySelector('.theme-toggle i');
        if (themeIcon) {
            themeIcon.className = this.currentTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    /**
     * สลับธีม
     */
    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.currentTheme);
        this.applyTheme();
        
        // เพิ่ม animation เมื่อสลับธีม
        document.body.classList.add('theme-transitioning');
        setTimeout(() => {
            document.body.classList.remove('theme-transitioning');
        }, 300);
    }

    /**
     * สร้างปุ่มสลับธีม
     */
    createThemeToggle() {
        const toggle = document.createElement('button');
        toggle.className = 'theme-toggle';
        toggle.innerHTML = `<i class="fas fa-${this.currentTheme === 'dark' ? 'sun' : 'moon'}"></i>`;
        toggle.setAttribute('title', `เปลี่ยนเป็นธีม${this.currentTheme === 'dark' ? 'สว่าง' : 'มืด'}`);
        toggle.setAttribute('aria-label', 'สลับธีม');
        
        toggle.addEventListener('click', () => {
            this.toggleTheme();
            toggle.setAttribute('title', `เปลี่ยนเป็นธีม${this.currentTheme === 'dark' ? 'สว่าง' : 'มืด'}`);
        });

        document.body.appendChild(toggle);
    }

    /**
     * ตั้งค่า Animations
     */
    setupAnimations() {
        // Intersection Observer สำหรับ scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        // Observe elements with animation classes
        const animatedElements = document.querySelectorAll(
            '.fade-in, .fade-in-up, .fade-in-down, .fade-in-left, .fade-in-right, ' +
            '.slide-in-up, .slide-in-down, .slide-in-left, .slide-in-right, ' +
            '.scale-in, .bounce-in'
        );

        animatedElements.forEach(el => {
            observer.observe(el);
        });

        // Hover animations
        this.setupHoverAnimations();
    }

    /**
     * ตั้งค่า Hover Animations
     */
    setupHoverAnimations() {
        // Hover lift effect
        document.querySelectorAll('.hover-lift').forEach(el => {
            el.addEventListener('mouseenter', () => {
                el.style.transform = 'translateY(-4px)';
                el.style.boxShadow = 'var(--shadow-xl)';
            });
            
            el.addEventListener('mouseleave', () => {
                el.style.transform = 'translateY(0)';
                el.style.boxShadow = 'var(--shadow-sm)';
            });
        });

        // Hover scale effect
        document.querySelectorAll('.hover-scale').forEach(el => {
            el.addEventListener('mouseenter', () => {
                el.style.transform = 'scale(1.05)';
            });
            
            el.addEventListener('mouseleave', () => {
                el.style.transform = 'scale(1)';
            });
        });

        // Hover rotate effect
        document.querySelectorAll('.hover-rotate').forEach(el => {
            el.addEventListener('mouseenter', () => {
                el.style.transform = 'rotate(5deg)';
            });
            
            el.addEventListener('mouseleave', () => {
                el.style.transform = 'rotate(0deg)';
            });
        });

        // Hover glow effect
        document.querySelectorAll('.hover-glow').forEach(el => {
            el.addEventListener('mouseenter', () => {
                el.style.boxShadow = '0 0 20px rgba(220, 53, 69, 0.3)';
            });
            
            el.addEventListener('mouseleave', () => {
                el.style.boxShadow = 'var(--shadow-sm)';
            });
        });
    }

    /**
     * ตั้งค่า Loading States
     */
    setupLoadingStates() {
        // Auto-loading สำหรับ buttons
        document.querySelectorAll('button[type="submit"], .btn[type="submit"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!btn.disabled) {
                    btn.classList.add('loading');
                    btn.disabled = true;
                    
                    // Simulate loading time (remove in production)
                    setTimeout(() => {
                        btn.classList.remove('loading');
                        btn.disabled = false;
                    }, 2000);
                }
            });
        });

        // Skeleton loading สำหรับ content
        this.setupSkeletonLoading();
    }

    /**
     * ตั้งค่า Skeleton Loading
     */
    setupSkeletonLoading() {
        const skeletonElements = document.querySelectorAll('.skeleton');
        
        skeletonElements.forEach(skeleton => {
            // Simulate content loading
            setTimeout(() => {
                skeleton.style.animation = 'none';
                skeleton.style.background = 'transparent';
                skeleton.innerHTML = '<div class="content-loaded">เนื้อหาถูกโหลดแล้ว</div>';
            }, Math.random() * 2000 + 1000);
        });
    }

    /**
     * ตั้งค่า Image Optimization
     */
    setupImageOptimization() {
        // Lazy loading สำหรับ images
        const images = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => {
            imageObserver.observe(img);
        });

        // Image error handling
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', () => {
                img.src = 'https://placehold.co/400x300/cccccc/333333?text=Image+Error';
                img.alt = 'ไม่สามารถโหลดรูปภาพได้';
                img.classList.add('image-error');
            });
        });
    }

    /**
     * เพิ่ม CSS สำหรับ theme transition
     */
    addThemeTransitionCSS() {
        const style = document.createElement('style');
        style.textContent = `
            .theme-transitioning * {
                transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
            }
            
            .animate-in {
                animation-play-state: running;
            }
            
            .lazy {
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .loaded {
                opacity: 1;
            }
            
            .image-error {
                border: 2px dashed #ccc;
                padding: 20px;
                text-align: center;
                color: #666;
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Utility Functions
 */
class UIUtils {
    /**
     * แสดง Toast Notification
     */
    static showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} fade-in`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Add toast styles
        this.addToastStyles();
        
        document.body.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    /**
     * Get Toast Icon
     */
    static getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Add Toast Styles
     */
    static addToastStyles() {
        if (document.querySelector('#toast-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                padding: 16px;
                min-width: 300px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border-left: 4px solid var(--primary-color);
            }
            
            .toast-success { border-left-color: var(--success-color); }
            .toast-error { border-left-color: var(--danger-color); }
            .toast-warning { border-left-color: var(--warning-color); }
            .toast-info { border-left-color: var(--info-color); }
            
            .toast-content {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .toast-close {
                background: none;
                border: none;
                color: #666;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                transition: all 0.2s ease;
            }
            
            .toast-close:hover {
                background: #f0f0f0;
                color: #333;
            }
            
            .fade-out {
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * แสดง Loading Spinner
     */
    static showSpinner(container, text = 'กำลังโหลด...') {
        const spinner = document.createElement('div');
        spinner.className = 'spinner-overlay';
        spinner.innerHTML = `
            <div class="spinner-content">
                <div class="spinner"></div>
                <p>${text}</p>
            </div>
        `;
        
        container.appendChild(spinner);
        return spinner;
    }

    /**
     * ซ่อน Loading Spinner
     */
    static hideSpinner(spinner) {
        if (spinner) {
            spinner.remove();
        }
    }

    /**
     * แสดง Skeleton Loading
     */
    static showSkeleton(container, count = 3) {
        const skeleton = document.createElement('div');
        skeleton.className = 'skeleton-container';
        
        for (let i = 0; i < count; i++) {
            const item = document.createElement('div');
            item.className = 'skeleton-item';
            item.innerHTML = `
                <div class="skeleton skeleton-image"></div>
                <div class="skeleton skeleton-text" style="width: 80%"></div>
                <div class="skeleton skeleton-text" style="width: 60%"></div>
                <div class="skeleton skeleton-text" style="width: 40%"></div>
            `;
            skeleton.appendChild(item);
        }
        
        container.appendChild(skeleton);
        return skeleton;
    }
}

/**
 * Performance Optimizations
 */
class PerformanceOptimizer {
    constructor() {
        this.init();
    }

    init() {
        this.setupIntersectionObserver();
        this.setupResizeObserver();
        this.setupScrollOptimization();
    }

    /**
     * ตั้งค่า Intersection Observer
     */
    setupIntersectionObserver() {
        const options = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, options);

        // Observe elements for performance
        document.querySelectorAll('.lazy-load, .performance-optimize').forEach(el => {
            observer.observe(el);
        });
    }

    /**
     * ตั้งค่า Resize Observer
     */
    setupResizeObserver() {
        const resizeObserver = new ResizeObserver(entries => {
            entries.forEach(entry => {
                // Handle responsive behavior
                if (entry.contentRect.width < 768) {
                    entry.target.classList.add('mobile');
                } else {
                    entry.target.classList.remove('mobile');
                }
            });
        });

        // Observe main container
        const mainContainer = document.querySelector('.container, main');
        if (mainContainer) {
            resizeObserver.observe(mainContainer);
        }
    }

    /**
     * ตั้งค่า Scroll Optimization
     */
    setupScrollOptimization() {
        let ticking = false;

        function updateOnScroll() {
            // Handle scroll-based animations
            const scrolledElements = document.querySelectorAll('.scroll-animate');
            scrolledElements.forEach(el => {
                const rect = el.getBoundingClientRect();
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                el.style.transform = `translateY(${rate}px)`;
            });
            
            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateOnScroll);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestTick);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Theme Manager
    const themeManager = new ThemeManager();
    themeManager.addThemeTransitionCSS();
    
    // Initialize Performance Optimizer
    const performanceOptimizer = new PerformanceOptimizer();
    
    // Global functions for easy access
    window.showToast = UIUtils.showToast;
    window.showSpinner = UIUtils.showSpinner;
    window.hideSpinner = UIUtils.hideSpinner;
    window.showSkeleton = UIUtils.showSkeleton;
    
            console.log('🎨 ZapShop Theme Manager initialized successfully!');
    console.log('🌙 Current theme:', themeManager.currentTheme);
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ThemeManager, UIUtils, PerformanceOptimizer };
}
