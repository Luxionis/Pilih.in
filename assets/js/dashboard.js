// ===================================
// PILAH.IN - DASHBOARD JAVASCRIPT
// Dashboard Functionality
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Dashboard
    initDashboard();
    
    // Load user data
    loadUserData();
    
    // Initialize charts
    initCharts();
    
    // Setup event listeners
    setupEventListeners();
});

// Initialize Dashboard
function initDashboard() {
    // Check authentication
    const auth = window.checkAuth();
    if (!auth.isAuthenticated) {
        // For demo, we'll use mock data
        console.log('Using demo mode');
    }
    
    // Set active navigation
    setActiveNav();
    
    // Animate stats on load
    animateStats();
}

// Load User Data
function loadUserData() {
    // In production, this would fetch from API
    const mockUser = {
        name: 'Ahmad Fauzi',
        email: 'ahmad@example.com',
        points: 2450,
        tier: 'Gold',
        nextTier: 'Platinum',
        nextTierPoints: 3000,
        activities: 127,
        donations: 750000,
        eventsJoined: 12
    };
    
    // Update UI with user data
    const userName = document.getElementById('userName');
    const userAvatar = document.getElementById('userAvatar');
    const totalPoints = document.getElementById('totalPoints');
    
    if (userName) userName.textContent = mockUser.name;
    if (userAvatar) userAvatar.textContent = mockUser.name.charAt(0);
    if (totalPoints) totalPoints.textContent = formatNumber(mockUser.points) + ' Poin';
    
    // Update tier progress
    const progress = (mockUser.points / mockUser.nextTierPoints) * 100;
    const progressFill = document.querySelector('.tier-progress .progress-fill');
    const progressLabel = document.querySelector('.tier-progress .progress-label span:last-child');
    if (progressFill) progressFill.style.width = progress + '%';
    if (progressLabel) progressLabel.textContent = Math.round(progress) + '%';
}

// Setup Event Listeners
function setupEventListeners() {
    // Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
    
    // Theme Toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    // Logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.logout();
            }
        });
    }
    
    // Notification button
    const notificationBtn = document.getElementById('notificationBtn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', () => {
            console.log('Show notifications');
        });
    }
    
    // Search button
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            console.log('Show search');
        });
    }
    
    // Chart buttons
    document.querySelectorAll('.chart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            updateChart(this.textContent.trim());
        });
    });
}

// Set Active Navigation
function setActiveNav() {
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.html';
    document.querySelectorAll('.nav-item').forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage.replace('.html', ''))) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

// Animate Stats
function animateStats() {
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(stat => {
        const text = stat.textContent;
        const numMatch = text.match(/[\d,]+/);
        if (numMatch) {
            const num = parseInt(numMatch[0].replace(/,/g, ''));
            animateValue(stat, 0, num, 1500);
        }
    });
}

function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        const currentText = element.textContent;
        if (currentText.includes('Rp')) {
            element.textContent = 'Rp ' + formatNumber(value);
        } else if (currentText.includes('Poin')) {
            element.textContent = formatNumber(value) + ' Poin';
        } else if (currentText.includes('kg')) {
            element.textContent = value + 'kg';
        } else {
            element.textContent = formatNumber(value);
        }
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Initialize Charts
function initCharts() {
    // Activity Chart (Line)
    const activityCtx = document.getElementById('activityChart');
    if (activityCtx && typeof Chart !== 'undefined') {
        new Chart(activityCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                datasets: [{
                    label: 'Aktivitas',
                    data: [5, 8, 6, 9, 4, 7, 3],
                    borderColor: '#1a3d2e',
                    backgroundColor: 'rgba(26, 61, 46, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1a3d2e',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Category Chart (Doughnut)
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx && typeof Chart !== 'undefined') {
        new Chart(categoryCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Plastik', 'Kertas', 'Organik', 'Logam', 'Kaca'],
                datasets: [{
                    data: [35, 25, 20, 12, 8],
                    backgroundColor: [
                        '#1a3d2e',
                        '#d4a574',
                        '#2d5a45',
                        '#8b7355',
                        '#f4e4bc'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
}

// Update Chart based on time range
function updateChart(range) {
    console.log('Updating chart for:', range);
}

// Toggle Theme (Dark/Light Mode)
function toggleTheme() {
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle ? themeToggle.querySelector('i') : null;
    
    body.classList.toggle('dark-mode');
    
    if (body.classList.contains('dark-mode')) {
        if (icon) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
        window.storage.set('theme', 'dark');
    } else {
        if (icon) {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
        window.storage.set('theme', 'light');
    }
    
    updateChartsForTheme();
}

function updateChartsForTheme() {
    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#94a3b8' : '#666';
    
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = textColor;
    }
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Event Registration
function registerEvent(eventId, eventTitle, quota) {
    const modalEventTitle = document.getElementById('modalEventTitle');
    const modalQuota = document.getElementById('modalQuota');
    
    if (modalEventTitle) modalEventTitle.textContent = eventTitle;
    if (modalQuota) modalQuota.textContent = quota;
    openModal('eventModal');
}

function confirmEventRegistration() {
    window.showToast('Pendaftaran event berhasil!', 'success');
    closeModal('eventModal');
}

// Export functions for global use
window.registerEvent = registerEvent;
window.confirmEventRegistration = confirmEventRegistration;
window.openModal = openModal;
window.closeModal = closeModal;
window.toggleTheme = toggleTheme;

console.log('Dashboard initialized');
