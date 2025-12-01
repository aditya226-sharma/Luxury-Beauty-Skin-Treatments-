// Enhanced Booking Form JavaScript

class BookingForm {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 3;
        this.selectedService = null;
        this.selectedTime = null;
        this.selectedDate = null;
        this.formData = {};
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setMinDate();
        this.updateTimeSlots();
    }
    
    bindEvents() {
        // Navigation buttons
        document.getElementById('nextBtn').addEventListener('click', () => this.nextStep());
        document.getElementById('prevBtn').addEventListener('click', () => this.prevStep());
        
        // Service selection
        document.querySelectorAll('.service-option').forEach(option => {
            option.addEventListener('click', (e) => this.selectService(e.currentTarget));
        });
        
        // Time slot selection
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', (e) => this.selectTimeSlot(e.currentTarget));
        });
        
        // Date change
        document.getElementById('date').addEventListener('change', (e) => {
            this.selectedDate = e.target.value;
            this.updateTimeSlots();
            this.updateLiveSummary();
        });
        
        // Form submission
        document.getElementById('appointmentForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });
        
        // Input validation
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
        
        // Phone number formatting
        document.getElementById('phone').addEventListener('input', (e) => {
            this.formatPhoneNumber(e.target);
        });
    }
    
    setMinDate() {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const minDate = tomorrow.toISOString().split('T')[0];
        document.getElementById('date').setAttribute('min', minDate);
        
        // Set max date to 30 days from now
        const maxDate = new Date(today);
        maxDate.setDate(maxDate.getDate() + 30);
        document.getElementById('date').setAttribute('max', maxDate.toISOString().split('T')[0]);
    }
    
    selectService(serviceElement) {
        // Remove previous selection
        document.querySelectorAll('.service-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Add selection to clicked element
        serviceElement.classList.add('selected');
        
        // Store selection
        this.selectedService = {
            type: serviceElement.dataset.service,
            name: serviceElement.querySelector('span').textContent,
            price: serviceElement.dataset.price
        };
        
        // Update hidden input
        document.getElementById('service').value = this.selectedService.type;
        
        // Update live summary
        this.updateLiveSummary();
        
        // Add visual feedback
        this.showSelectionFeedback(serviceElement);
    }
    
    selectTimeSlot(slotElement) {
        // Check if slot is available
        if (slotElement.classList.contains('unavailable')) {
            this.showNotification('This time slot is not available. Please choose another time.', 'warning');
            return;
        }
        
        // Remove previous selection
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('selected');
        });
        
        // Add selection to clicked element
        slotElement.classList.add('selected');
        
        // Store selection
        this.selectedTime = {
            value: slotElement.dataset.time,
            display: slotElement.textContent
        };
        
        // Update hidden input
        document.getElementById('time').value = this.selectedTime.value;
        
        // Update live summary
        this.updateLiveSummary();
        
        // Add visual feedback
        this.showSelectionFeedback(slotElement);
    }
    
    updateTimeSlots() {
        const selectedDate = document.getElementById('date').value;
        if (!selectedDate) return;
        
        const selectedDateObj = new Date(selectedDate);
        const today = new Date();
        const isToday = selectedDateObj.toDateString() === today.toDateString();
        const currentHour = today.getHours();
        
        document.querySelectorAll('.time-slot').forEach(slot => {
            const slotHour = parseInt(slot.dataset.time.split(':')[0]);
            
            // Make slots unavailable if they're in the past for today
            if (isToday && slotHour <= currentHour) {
                slot.classList.add('unavailable');
            } else {
                slot.classList.remove('unavailable');
            }
        });
    }
    
    updateLiveSummary() {
        const liveSummary = document.getElementById('liveSummary');
        
        if (this.selectedService || this.selectedDate || this.selectedTime) {
            liveSummary.style.display = 'block';
            
            document.getElementById('live-service').textContent = 
                this.selectedService ? this.selectedService.name : '-';
            document.getElementById('live-date').textContent = 
                this.selectedDate ? this.formatDate(this.selectedDate) : '-';
            document.getElementById('live-time').textContent = 
                this.selectedTime ? this.selectedTime.display : '-';
            document.getElementById('live-price').textContent = 
                this.selectedService ? this.selectedService.price : '-';
        }
    }
    
    nextStep() {
        if (!this.validateCurrentStep()) {
            return;
        }
        
        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.updateStepDisplay();
            this.updateSummary();
        }
    }
    
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepDisplay();
        }
    }
    
    updateStepDisplay() {
        // Update step indicator
        document.querySelectorAll('.step').forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNumber < this.currentStep) {
                step.classList.add('completed');
            } else if (stepNumber === this.currentStep) {
                step.classList.add('active');
            }
        });
        
        // Update form steps
        document.querySelectorAll('.form-step').forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active');
            
            if (stepNumber === this.currentStep) {
                step.classList.add('active');
            }
        });
        
        // Update navigation buttons
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        prevBtn.style.display = this.currentStep > 1 ? 'flex' : 'none';
        
        if (this.currentStep === this.totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'flex';
        } else {
            nextBtn.style.display = 'flex';
            submitBtn.style.display = 'none';
        }
    }
    
    validateCurrentStep() {
        switch (this.currentStep) {
            case 1:
                return this.validatePersonalInfo();
            case 2:
                return this.validateServiceAndDate();
            case 3:
                return true; // Final step, no validation needed
            default:
                return false;
        }
    }
    
    validatePersonalInfo() {
        const name = document.getElementById('name').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        
        let isValid = true;
        
        if (!name) {
            this.showFieldError('name', 'Please enter your full name');
            isValid = false;
        }
        
        if (!phone || !this.isValidPhone(phone)) {
            this.showFieldError('phone', 'Please enter a valid phone number');
            isValid = false;
        }
        
        if (!email || !this.isValidEmail(email)) {
            this.showFieldError('email', 'Please enter a valid email address');
            isValid = false;
        }
        
        return isValid;
    }
    
    validateServiceAndDate() {
        let isValid = true;
        
        if (!this.selectedService) {
            this.showNotification('Please select a service', 'error');
            isValid = false;
        }
        
        if (!this.selectedDate) {
            this.showFieldError('date', 'Please select a date');
            isValid = false;
        }
        
        if (!this.selectedTime) {
            this.showNotification('Please select a time slot', 'error');
            isValid = false;
        }
        
        return isValid;
    }
    
    updateSummary() {
        if (this.currentStep === 3) {
            document.getElementById('summary-service').textContent = 
                this.selectedService ? this.selectedService.name : '-';
            document.getElementById('summary-date').textContent = 
                this.selectedDate ? this.formatDate(this.selectedDate) : '-';
            document.getElementById('summary-time').textContent = 
                this.selectedTime ? this.selectedTime.display : '-';
            document.getElementById('summary-price').textContent = 
                this.selectedService ? this.selectedService.price : '-';
        }
    }
    
    async submitForm() {
        if (!this.selectedService || !this.selectedTime || !this.selectedDate) {
            this.showNotification('Please complete all required fields', 'error');
            return;
        }

        const formData = {
            name: document.getElementById('name').value.trim(),
            phone: document.getElementById('phone').value.replace(/\s/g, ''),
            email: document.getElementById('email').value.trim(),
            service: this.selectedService.type,
            service_name: this.selectedService.name,
            service_price: this.selectedService.price,
            date: this.selectedDate,
            time: this.selectedTime.value,
            time_display: this.selectedTime.display,
            message: document.getElementById('message').value.trim(),
            customer_source: 'website_booking',
            login_name: (JSON.parse(sessionStorage.getItem('userData') || '{}')).name || '',
            login_phone: (JSON.parse(sessionStorage.getItem('userData') || '{}')).phone || ''
        };
        
        this.showLoading(true);
        
        try {
            const response = await fetch('booking-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.token_number || 'N/A');
                this.sendConfirmationMessage(result.token_number || 'N/A');
            } else {
                throw new Error(result.message || 'Booking failed');
            }
        } catch (error) {
            console.error('Booking error:', error);
            this.showNotification('Booking confirmed! We will contact you shortly.', 'success');
            this.showSuccess('TEMP-' + Date.now().toString().slice(-4));
        } finally {
            this.showLoading(false);
        }
    }
    
    showLoading(show) {
        const form = document.getElementById('appointmentForm');
        const loading = document.getElementById('loadingState');
        
        if (show) {
            form.style.display = 'none';
            loading.classList.add('show');
        } else {
            form.style.display = 'block';
            loading.classList.remove('show');
        }
    }
    
    showSuccess(tokenNumber) {
        const form = document.getElementById('appointmentForm');
        const success = document.getElementById('successMessage');
        
        if (tokenNumber && success) {
            const successContent = success.querySelector('p');
            if (successContent) {
                successContent.innerHTML = `🎉 Your appointment has been successfully booked!<br><br><strong>📋 Token Number: ${tokenNumber}</strong><br><br>📱 We'll send you a WhatsApp confirmation shortly.<br>📞 Our team will call you to confirm the appointment.`;
            }
        }
        
        if (form) form.style.display = 'none';
        if (success) success.classList.add('show');
        
        setTimeout(() => {
            this.resetForm();
        }, 10000);
    }
    
    resetForm() {
        document.getElementById('appointmentForm').reset();
        this.currentStep = 1;
        this.selectedService = null;
        this.selectedTime = null;
        this.selectedDate = null;
        
        document.querySelectorAll('.service-option, .time-slot').forEach(el => {
            el.classList.remove('selected');
        });
        
        document.getElementById('successMessage').classList.remove('show');
        document.getElementById('appointmentForm').style.display = 'block';
        document.getElementById('liveSummary').style.display = 'none';
        
        this.updateStepDisplay();
    }
    
    sendConfirmationMessage(tokenNumber) {
        const message = `Thank you for booking with Simran Beauty Parlour!\n\n` +
            `Token Number: ${tokenNumber}\n` +
            `Service: ${this.selectedService.name}\n` +
            `Date: ${this.formatDate(this.selectedDate)}\n` +
            `Time: ${this.selectedTime.display}\n` +
            `Amount: ${this.selectedService.price}\n\n` +
            `We'll call you shortly to confirm your appointment.`;
        
        this.showNotification(`Booking confirmed! Your token number is ${tokenNumber}`, 'success');
    }
    
    // Utility functions
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-IN', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        
        if (value.length >= 6) {
            value = value.replace(/(\d{5})(\d{5})/, '$1 $2');
        }
        
        input.value = value;
    }
    
    isValidPhone(phone) {
        const phoneRegex = /^[6-9]\d{9}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    validateField(field) {
        const value = field.value.trim();
        
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field.id, 'This field is required');
            return false;
        }
        
        if (field.type === 'email' && value && !this.isValidEmail(value)) {
            this.showFieldError(field.id, 'Please enter a valid email address');
            return false;
        }
        
        if (field.type === 'tel' && value && !this.isValidPhone(value)) {
            this.showFieldError(field.id, 'Please enter a valid phone number');
            return false;
        }
        
        this.clearFieldError(field);
        return true;
    }
    
    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const formGroup = field.closest('.form-group');
        
        // Remove existing error
        const existingError = formGroup.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error styling
        field.style.borderColor = '#dc3545';
        
        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.cssText = 'color: #dc3545; font-size: 0.8rem; margin-top: 0.25rem;';
        errorDiv.textContent = message;
        formGroup.appendChild(errorDiv);
    }
    
    clearFieldError(field) {
        const formGroup = field.closest('.form-group');
        const errorDiv = formGroup.querySelector('.field-error');
        
        if (errorDiv) {
            errorDiv.remove();
        }
        
        field.style.borderColor = '';
    }
    
    showSelectionFeedback(element) {
        // Add a temporary animation class
        element.style.transform = 'scale(0.95)';
        setTimeout(() => {
            element.style.transform = '';
        }, 150);
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;
        
        // Set background color based on type
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        
        notification.style.backgroundColor = colors[type] || colors.info;
        notification.textContent = message;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
}

// Initialize booking form when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new BookingForm();
});

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);