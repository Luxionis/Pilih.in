// ===================================
// PILAH.IN - MAIN JAVASCRIPT
// Homepage & Global Functionality
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile Navigation Toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
    
    // Smooth Scroll for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Close mobile menu if open
                    if (navMenu.classList.contains('active')) {
                        navMenu.classList.remove('active');
                        mobileToggle.classList.remove('active');
                    }
                }
            }
        });
    });
    
    // Navbar Scroll Effect
    let lastScroll = 0;
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            navbar.style.boxShadow = '0 4px 16px rgba(26, 61, 46, 0.12)';
        } else {
            navbar.style.boxShadow = '0 2px 8px rgba(26, 61, 46, 0.08)';
        }
        
        lastScroll = currentScroll;
    });
    
    // Animated Counter for Stats
    const animateCounter = (element, target, duration = 2000) => {
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = Math.floor(target).toLocaleString();
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
        }, 16);
    };
    
    // Intersection Observer for Stats Animation
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                const target = parseInt(entry.target.getAttribute('data-count'));
                animateCounter(entry.target, target);
                entry.target.classList.add('animated');
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.stat-number').forEach(stat => {
        statsObserver.observe(stat);
    });
    
    // Fade In Animation on Scroll
    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.feature-card, .step, .testimonial-card, .metric').forEach(el => {
        fadeObserver.observe(el);
    });
    
    // Impact Chart (if Chart.js is loaded)
    const impactChartCanvas = document.getElementById('impactChart');
    if (impactChartCanvas && typeof Chart !== 'undefined') {
        const ctx = impactChartCanvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Plastik', 'Kertas', 'Organik', 'Logam', 'Kaca'],
                datasets: [{
                    data: [35, 25, 20, 12, 8],
                    backgroundColor: [
                        '#1a3d2e',
                        '#d4a574',
                        '#f4e4bc',
                        '#8b7355',
                        '#2d5a45'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                family: 'Inter',
                                size: 12
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    // Form Validation Helper
    window.validateEmail = function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };
    
    window.validatePassword = function(password) {
        return password.length >= 8;
    };
    
    // Show/Hide Password Toggle
    window.togglePassword = function(inputId) {
        const input = document.getElementById(inputId);
        const button = input.parentElement.querySelector('.toggle-password');
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    };
    
    // Toast Notification System
    window.showToast = function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    };
    
    // Loading State Helper
    window.setLoading = function(button, loading = true) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Memproses...</span>';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText;
        }
    };
    
    // Local Storage Helper
    window.storage = {
        set: function(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (e) {
                console.error('Error saving to localStorage:', e);
                return false;
            }
        },
        get: function(key) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : null;
            } catch (e) {
                console.error('Error reading from localStorage:', e);
                return null;
            }
        },
        remove: function(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (e) {
                console.error('Error removing from localStorage:', e);
                return false;
            }
        }
    };
    
    // API Helper
    window.api = {
        baseUrl: '/api',
        
        async call(endpoint, options = {}) {
            const token = storage.get('authToken');
            const headers = {
                'Content-Type': 'application/json',
                ...(token && { 'Authorization': `Bearer ${token}` }),
                ...options.headers
            };
            
            try {
                const response = await fetch(`${this.baseUrl}${endpoint}`, {
                    ...options,
                    headers
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Request failed');
                }
                
                return data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        },
        
        get(endpoint) {
            return this.call(endpoint, { method: 'GET' });
        },
        
        post(endpoint, data) {
            return this.call(endpoint, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },
        
        put(endpoint, data) {
            return this.call(endpoint, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        },
        
        delete(endpoint) {
            return this.call(endpoint, { method: 'DELETE' });
        }
    };
    
    // Check Authentication Status
    window.checkAuth = function() {
        const token = storage.get('authToken');
        const user = storage.get('userData');
        
        if (token && user) {
            return { isAuthenticated: true, user };
        }
        
        return { isAuthenticated: false, user: null };
    };
    
    // Redirect if Not Authenticated
    window.requireAuth = function() {
        const { isAuthenticated } = checkAuth();
        if (!isAuthenticated) {
            window.location.href = '/login.html';
        }
    };
    
    // Logout Function
    window.logout = function() {
        storage.remove('authToken');
        storage.remove('userData');
        window.location.href = '/index.html';
    };
    
    // Format Number
    window.formatNumber = function(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    };
    
    // Format Date
    window.formatDate = function(date) {
        const d = new Date(date);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return d.toLocaleDateString('id-ID', options);
    };
    
    // Calculate Time Ago
    window.timeAgo = function(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        
        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + ' tahun lalu';
        
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + ' bulan lalu';
        
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + ' hari lalu';
        
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + ' jam lalu';
        
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + ' menit lalu';
        
        return 'Baru saja';
    };
    
    console.log('🌱 Pilah.in initialized successfully');
});

// Add Toast Styles Dynamically
const toastStyles = document.createElement('style');
toastStyles.textContent = `
    .toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(26, 61, 46, 0.16);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 10000;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease-out;
    }
    
    .toast.show {
        transform: translateY(0);
        opacity: 1;
    }
    
    .toast-success {
        border-left: 4px solid #2d5a45;
        color: #2d5a45;
    }
    
    .toast-error {
        border-left: 4px solid #c45c47;
        color: #c45c47;
    }
    
    .toast i {
        font-size: 20px;
    }
    
    @media (max-width: 768px) {
        .toast {
            left: 16px;
            right: 16px;
            bottom: 16px;
        }
    }
`;
document.head.appendChild(toastStyles);
