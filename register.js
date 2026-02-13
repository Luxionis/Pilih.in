// ===================================
// REGISTRATION JAVASCRIPT
// Multi-step Registration Flow
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 3;
    
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const registerForm = document.getElementById('registerForm');
    
    // Update Progress Steps UI
    function updateStepsUI() {
        // Update step indicators
        document.querySelectorAll('.step').forEach((step, index) => {
            const stepNum = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNum < currentStep) {
                step.classList.add('completed');
            } else if (stepNum === currentStep) {
                step.classList.add('active');
            }
        });
        
        // Show/hide form steps
        document.querySelectorAll('.form-step').forEach((step, index) => {
            const stepNum = index + 1;
            step.classList.remove('active');
            
            if (stepNum === currentStep) {
                step.classList.add('active');
            }
        });
        
        // Show/hide navigation buttons
        prevBtn.style.display = currentStep === 1 ? 'none' : 'flex';
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'flex';
        submitBtn.style.display = currentStep === totalSteps ? 'flex' : 'none';
    }
    
    // Validate Current Step
    function validateCurrentStep() {
        const currentStepEl = document.getElementById(`step${currentStep}`);
        const inputs = currentStepEl.querySelectorAll('input[required], select[required]');
        
        let isValid = true;
        const errorAlert = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');
        
        // Hide previous errors
        errorAlert.style.display = 'none';
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.style.borderColor = '#c45c47';
            } else {
                input.style.borderColor = '#f4e4bc';
            }
        });
        
        // Step-specific validation
        if (currentStep === 1) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const username = document.getElementById('username').value;
            
            if (!validateEmail(email)) {
                errorMessage.textContent = 'Format email tidak valid';
                errorAlert.style.display = 'flex';
                return false;
            }
            
            if (username.length < 4) {
                errorMessage.textContent = 'Username minimal 4 karakter';
                errorAlert.style.display = 'flex';
                return false;
            }
            
            if (password.length < 8) {
                errorMessage.textContent = 'Password minimal 8 karakter';
                errorAlert.style.display = 'flex';
                return false;
            }
            
            if (password !== confirmPassword) {
                errorMessage.textContent = 'Password dan konfirmasi password tidak cocok';
                errorAlert.style.display = 'flex';
                return false;
            }
        }
        
        if (currentStep === 2) {
            const phone = document.getElementById('phone').value;
            
            if (!/^08[0-9]{9,11}$/.test(phone)) {
                errorMessage.textContent = 'Nomor telepon tidak valid. Contoh: 08123456789';
                errorAlert.style.display = 'flex';
                return false;
            }
        }
        
        if (currentStep === 3) {
            const terms = document.getElementById('terms');
            
            if (!terms.checked) {
                errorMessage.textContent = 'Anda harus menyetujui Syarat & Ketentuan';
                errorAlert.style.display = 'flex';
                return false;
            }
        }
        
        if (!isValid) {
            errorMessage.textContent = 'Mohon lengkapi semua field yang required';
            errorAlert.style.display = 'flex';
        }
        
        return isValid;
    }
    
    // Next Button Handler
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (validateCurrentStep()) {
                currentStep++;
                updateStepsUI();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
    
    // Previous Button Handler
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            currentStep--;
            updateStepsUI();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Form Submit Handler
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateCurrentStep()) {
                return;
            }
            
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');
            
            // Collect form data
            const formData = {
                fullname: document.getElementById('fullname').value,
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                province: document.getElementById('province').value,
                city: document.getElementById('city').value,
                phone: document.getElementById('phone').value,
                referral: document.getElementById('referral').value,
                newsletter: document.getElementById('newsletter').checked
            };
            
            try {
                // Set loading state
                setLoading(submitBtn, true);
                
                // Make API call
                const response = await api.post('/auth/register.php', formData);
                
                if (response.success) {
                    // Show success message
                    showToast('Pendaftaran berhasil! Silakan cek email Anda untuk verifikasi.', 'success');
                    
                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    throw new Error(response.message || 'Pendaftaran gagal');
                }
                
            } catch (error) {
                console.error('Registration error:', error);
                errorMessage.textContent = error.message || 'Terjadi kesalahan saat mendaftar';
                errorAlert.style.display = 'flex';
                setLoading(submitBtn, false);
            }
        });
    }
    
    // Province -> City Dropdown Handler
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    
    const cityData = {
        'bali': ['Denpasar', 'Badung', 'Gianyar', 'Tabanan', 'Klungkung', 'Bangli', 'Karangasem', 'Buleleng', 'Jembrana'],
        'jakarta': ['Jakarta Pusat', 'Jakarta Utara', 'Jakarta Barat', 'Jakarta Selatan', 'Jakarta Timur', 'Kepulauan Seribu'],
        'jawa-barat': ['Bandung', 'Bekasi', 'Bogor', 'Cirebon', 'Depok', 'Sukabumi', 'Tasikmalaya'],
        'jawa-tengah': ['Semarang', 'Solo', 'Magelang', 'Salatiga', 'Pekalongan', 'Tegal'],
        'jawa-timur': ['Surabaya', 'Malang', 'Kediri', 'Madiun', 'Mojokerto', 'Blitar', 'Pasuruan']
    };
    
    if (provinceSelect && citySelect) {
        provinceSelect.addEventListener('change', function() {
            const selectedProvince = this.value;
            citySelect.innerHTML = '<option value="">Pilih Kota</option>';
            
            if (selectedProvince && cityData[selectedProvince]) {
                cityData[selectedProvince].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.toLowerCase().replace(/\s+/g, '-');
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        });
    }
    
    // Initialize
    updateStepsUI();
});
