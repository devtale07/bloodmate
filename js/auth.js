// Authentication JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    initializeForms();
});

function initializeTabs() {
    const tabs = document.querySelectorAll('.auth-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });
}

function switchTab(tabName) {
    // Update tabs
    const tabs = document.querySelectorAll('.auth-tab');
    tabs.forEach(tab => {
        tab.classList.remove('active');
        if (tab.getAttribute('data-tab') === tabName) {
            tab.classList.add('active');
        }
    });
    
    // Update forms
    const forms = document.querySelectorAll('.auth-form');
    forms.forEach(form => {
        form.classList.remove('active');
    });
    
    const activeForm = document.getElementById(tabName + 'Form');
    if (activeForm) {
        activeForm.classList.add('active');
    }
    
    // Hide notification when switching tabs
    hideNotification();
}

function initializeForms() {
    // Registration form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegistration);
    }
    
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleUserLogin);
    }
    
    // Admin form
    const adminForm = document.getElementById('adminForm');
    if (adminForm) {
        adminForm.addEventListener('submit', handleAdminLogin);
    }
}

async function handleRegistration(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = document.getElementById('registerBtn');
    const originalText = submitButton.innerHTML;
    
    showLoading(submitButton);
    
    try {
        const formData = new FormData(form);
        
        const response = await fetch('api/register-user.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            form.reset();
            
            // Switch to login tab after successful registration
            setTimeout(() => {
                switchTab('login');
            }, 2000);
        } else {
            showNotification(result.message || 'Registration failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    } finally {
        hideLoading(submitButton, originalText);
    }
}

async function handleUserLogin(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = document.getElementById('loginBtn');
    const originalText = submitButton.innerHTML;
    
    showLoading(submitButton);
    
    try {
        const formData = new FormData(form);
        
        const response = await fetch('api/login-user.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Store user data in localStorage
            localStorage.setItem('user_token', result.token);
            localStorage.setItem('user_data', JSON.stringify(result.user));
            
            showNotification('Login successful! Redirecting...', 'success');
            
            // Redirect to index page after successful login
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1500);
        } else {
            showNotification(result.message || 'Login failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    } finally {
        hideLoading(submitButton, originalText);
    }
}

async function handleAdminLogin(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = document.getElementById('adminBtn');
    const originalText = submitButton.innerHTML;
    
    showLoading(submitButton);
    
    try {
        const formData = new FormData(form);
        
        const response = await fetch('api/admin-login.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Store admin data in localStorage
            localStorage.setItem('admin_token', result.token);
            localStorage.setItem('admin_user', JSON.stringify(result.user));
            
            showNotification('Admin login successful! Redirecting...', 'success');
            
            // Redirect to admin dashboard after successful login
            setTimeout(() => {
                window.location.href = 'admin/dashboard.html';
            }, 1500);
        } else {
            showNotification(result.message || 'Admin login failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    } finally {
        hideLoading(submitButton, originalText);
    }
}

function showNotification(message, type) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = 'notification ' + type + ' show';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        hideNotification();
    }, 5000);
}

function hideNotification() {
    const notification = document.getElementById('notification');
    notification.classList.remove('show');
}

function showLoading(button) {
    button.disabled = true;
    button.innerHTML = '<span class="loading"></span> Processing...';
}

function hideLoading(button, originalText) {
    button.disabled = false;
    button.innerHTML = originalText;
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
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
