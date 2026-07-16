// Contact Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmission);
        
        // Add real-time validation
        const inputs = contactForm.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });

        // Pre-fill form if coming from urgent request
        prefillFromUrlParams();
    }
});

async function handleContactSubmission(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Validate form
    if (!validateForm(form)) {
        return;
    }
    
    showLoading(submitButton);
    
    try {
        const formData = new FormData(form);
        
        const response = await fetch('api/submit-contact.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Message sent successfully! We will get back to you soon.', 'success');
            form.reset();
        } else {
            showNotification(result.message || 'Failed to send message. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Network error. Please check your connection and try again.', 'error');
    } finally {
        hideLoading(submitButton, originalText);
    }
}

function prefillFromUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const requestId = urlParams.get('request_id');
    
    if (requestId) {
        const subjectSelect = document.getElementById('subject');
        const messageTextarea = document.getElementById('message');
        
        if (subjectSelect) {
            subjectSelect.value = 'Blood Request';
        }
        
        if (messageTextarea) {
            messageTextarea.value = `I am interested in donating blood for request ID: ${requestId}. Please provide me with more details about the donation process and location.`;
        }
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
    }
    
    // Message length validation
    if (field.id === 'message') {
        if (field.value.length < 10) {
            showFieldError(field, 'Message must be at least 10 characters long');
            return false;
        }
        if (field.value.length > 1000) {
            showFieldError(field, 'Message must be less than 1000 characters');
            return false;
        }
    }
    
    return true;
}
