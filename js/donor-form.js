// Donor Registration Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const donorForm = document.getElementById('donorForm');
    
    if (donorForm) {
        donorForm.addEventListener('submit', handleDonorSubmission);
        
        // Add real-time validation
        const inputs = donorForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });

        // Set minimum date for last donation
        const lastDonationInput = document.getElementById('last_donation');
        if (lastDonationInput) {
            const today = new Date();
            const maxDate = today.toISOString().split('T')[0];
            lastDonationInput.setAttribute('max', maxDate);
        }
    }
});

async function handleDonorSubmission(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Validate form
    if (!validateForm(form)) {
        return;
    }
    
    // Additional donor-specific validation
    if (!validateDonorSpecific(form)) {
        return;
    }
    
    showLoading(submitButton);
    
    try {
        const formData = new FormData(form);
        
        const response = await fetch('api/register-donor.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Registration successful! Welcome to BloodMate community.', 'success');
            form.reset();
            
            // Redirect to thank you page or show success message
            setTimeout(() => {
                window.location.href = 'index.html?registered=true';
            }, 2000);
        } else {
            showNotification(result.message || 'Registration failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error. Please check your connection and try again.', 'error');
    } finally {
        hideLoading(submitButton, originalText);
    }
}

function validateDonorSpecific(form) {
    let isValid = true;
    
    // Age validation
    const ageInput = form.querySelector('#age');
    const age = parseInt(ageInput.value);
    if (age < 18 || age > 65) {
        showFieldError(ageInput, 'Age must be between 18 and 65 years');
        isValid = false;
    }
    
    // Last donation date validation
    const lastDonationInput = form.querySelector('#last_donation');
    if (lastDonationInput.value) {
        const lastDonationDate = new Date(lastDonationInput.value);
        const today = new Date();
        const daysSinceLastDonation = Math.floor((today - lastDonationDate) / (1000 * 60 * 60 * 24));
        
        if (daysSinceLastDonation < 56) {
            showFieldError(lastDonationInput, 'You must wait at least 56 days between donations');
            isValid = false;
        }
    }
    
    // Terms and conditions
    const termsCheckbox = form.querySelector('#terms');
    if (!termsCheckbox.checked) {
        showFieldError(termsCheckbox, 'You must agree to the terms and conditions');
        isValid = false;
    }
    
    return isValid;
}

function validateField(event) {
    const field = event.target;
    clearFieldError(field);
    
    if (field.hasAttribute('required') && !field.value.trim()) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    // Specific field validations
    switch (field.type) {
        case 'email':
            if (field.value && !isValidEmail(field.value)) {
                showFieldError(field, 'Please enter a valid email address');
                return false;
            }
            break;
        case 'tel':
            if (field.value && !isValidPhone(field.value)) {
                showFieldError(field, 'Please enter a valid phone number');
                return false;
            }
            break;
        case 'number':
            if (field.id === 'age') {
                const age = parseInt(field.value);
                if (age < 18 || age > 65) {
                    showFieldError(field, 'Age must be between 18 and 65 years');
                    return false;
                }
            }
            break;
    }
    
    return true;
}
