// ===================================
// AUTHENTICATION JAVASCRIPT
// Login & Password Functions
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Login Form Handler
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');
            
            // Hide previous errors
            errorAlert.style.display = 'none';
            
            // Get form data
            const formData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                remember: document.getElementById('remember').checked
            };
            
            // Basic validation
            if (!formData.email || !formData.password) {
                errorMessage.textContent = 'Email dan password harus diisi';
                errorAlert.style.display = 'flex';
                return;
            }
            
            try {
                // Set loading state
                setLoading(submitBtn, true);
                
                // Make API call
                const response = await api.post('/auth/login.php', formData);
                
                if (response.success) {
                    // Store authentication data
                    storage.set('authToken', response.data.token);
                    storage.set('userData', response.data.user);
                    
                    // Show success message
                    showToast('Login berhasil! Mengalihkan...', 'success');
                    
                    // Redirect to dashboard
                    setTimeout(() => {
                        window.location.href = 'dashboard.html';
                    }, 1000);
                } else {
                    throw new Error(response.message || 'Login gagal');
                }
                
            } catch (error) {
                console.error('Login error:', error);
                errorMessage.textContent = error.message || 'Terjadi kesalahan saat login';
                errorAlert.style.display = 'flex';
                setLoading(submitBtn, false);
            }
        });
    }
    
    // Social Login Handlers
    const googleBtn = document.querySelector('.btn-social.google');
    const facebookBtn = document.querySelector('.btn-social.facebook');
    
    if (googleBtn) {
        googleBtn.addEventListener('click', function() {
            showToast('Fitur login Google akan segera hadir!', 'success');
        });
    }
    
    if (facebookBtn) {
        facebookBtn.addEventListener('click', function() {
            showToast('Fitur login Facebook akan segera hadir!', 'success');
        });
    }
    
    // Password Strength Checker
    window.checkPasswordStrength = function(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        return strength;
    };
    
    // Update Password Strength UI
    const passwordInput = document.getElementById('password');
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');
    
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            
            strengthBar.className = 'strength-bar';
            
            if (strength === 0) {
                strengthBar.classList.remove('weak', 'medium', 'strong');
                strengthText.textContent = 'Kekuatan password';
            } else if (strength <= 2) {
                strengthBar.classList.add('weak');
                strengthText.textContent = 'Password lemah';
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
                strengthText.textContent = 'Password sedang';
            } else {
                strengthBar.classList.add('strong');
                strengthText.textContent = 'Password kuat';
            }
        });
    }
    
    // Forgot Password Handler
    const forgotPasswordLink = document.querySelector('a[href="forgot-password.html"]');
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            showToast('Fitur reset password akan segera hadir!', 'success');
        });
    }
});

// Toggle Password Visibility (defined globally for inline onclick)
function togglePassword(inputId) {
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
}
