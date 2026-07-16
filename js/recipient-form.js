// Recipient Request Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const recipientForm = document.getElementById('recipientForm');
    
    if (recipientForm) {
        recipientForm.addEventListener('submit', handleRecipientSubmission);
        
        // Add real-time validation
        const inputs = recipientForm.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });

        // Set minimum date for request date
        const requestDateInput = document.getElementById('request_date');
        if (requestDateInput) {
            const today = new Date();
            const minDate = today.toISOString().split('T')[0];
            requestDateInput.setAttribute('min', minDate);
        }

        // Update urgency level based on request date
        const urgencySelect = document.getElementById('urgency_level');
        if (urgencySelect && requestDateInput) {
            requestDateInput.addEventListener('change', updateUrgencyBasedOnDate);
        }
    }
});

async function handleRecipientSubmission(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Validate form
    if (!validateForm(form)) {
        return;
    }
    
    // Additional recipient-specific validation
    if (!validateRecipientSpecific(form)) {
        return;
    }
    
    showLoading(submitButton);
    
    try {
        const formData = new FormData(form);
        
        const response = await fetch('api/submit-request.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Blood request submitted successfully! We will contact you soon.', 'success');
            form.reset();
            
            // Show request ID
            if (result.request_id) {
                showNotification(`Your request ID is: ${result.request_id}. Please save this for future reference.`, 'success');
            }
            
            // Redirect to confirmation page
            setTimeout(() => {
                window.location.href = `request-confirmation.html?id=${result.request_id}`;
            }, 3000);
        } else {
            showNotification(result.message || 'Request submission failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error. Please check your connection and try again.', 'error');
    } finally {
        hideLoading(submitButton, originalText);
    }
}

function validateRecipientSpecific(form) {
    let isValid = true;
    
    // Request date validation
    const requestDateInput = form.querySelector('#request_date');
    const requestDate = new Date(requestDateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (requestDate < today) {
        showFieldError(requestDateInput, 'Request date cannot be in the past');
        isValid = false;
    }
    
    // Urgency level validation based on date
    const urgencySelect = form.querySelector('#urgency_level');
    const urgency = urgencySelect.value;
    const daysDifference = Math.ceil((requestDate - today) / (1000 * 60 * 60 * 24));
    
    if (urgency === 'Critical' && daysDifference > 1) {
        showFieldError(urgencySelect, 'Critical requests should be for immediate need (within 24 hours)');
        isValid = false;
    } else if (urgency === 'High' && daysDifference > 1) {
        showFieldError(urgencySelect, 'High urgency requests should be within 24 hours');
        isValid = false;
    } else if (urgency === 'Low' && daysDifference < 7) {
        showFieldError(urgencySelect, 'Low urgency requests should be at least 7 days in advance');
        isValid = false;
    }
    
    // Terms and conditions
    const termsCheckbox = form.querySelector('#terms');
    if (!termsCheckbox.checked) {
        showFieldError(termsCheckbox, 'You must confirm the accuracy of information provided');
        isValid = false;
    }
    
    return isValid;
}

function updateUrgencyBasedOnDate() {
    const requestDateInput = document.getElementById('request_date');
    const urgencySelect = document.getElementById('urgency_level');
    
    if (!requestDateInput.value) return;
    
    const requestDate = new Date(requestDateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const daysDifference = Math.ceil((requestDate - today) / (1000 * 60 * 60 * 24));
    
    // Suggest urgency level based on date
    let suggestedUrgency = '';
    if (daysDifference <= 1) {
        suggestedUrgency = 'Critical';
    } else if (daysDifference <= 2) {
        suggestedUrgency = 'High';
    } else if (daysDifference <= 7) {
        suggestedUrgency = 'Medium';
    } else {
        suggestedUrgency = 'Low';
    }
    
    // Update urgency select if not already set
    if (!urgencySelect.value) {
        urgencySelect.value = suggestedUrgency;
    }
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
        case 'date':
            if (field.id === 'request_date' && field.value) {
                const requestDate = new Date(field.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (requestDate < today) {
                    showFieldError(field, 'Request date cannot be in the past');
                    return false;
                }
            }
            break;
    }
    
    return true;
}
