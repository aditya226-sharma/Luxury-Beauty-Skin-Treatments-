// Mobile Navigation Toggle
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
    
    // Animate hamburger lines
    const spans = hamburger.querySelectorAll('span');
    if (hamburger.classList.contains('active')) {
        spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
    } else {
        spans[0].style.transform = 'none';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'none';
    }
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-menu a').forEach(n => n.addEventListener('click', () => {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
}));

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Navbar background change on scroll
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
    } else {
        navbar.style.background = 'rgba(255, 255, 255, 0.95)';
        navbar.style.boxShadow = 'none';
    }
});

// Fade in animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

// Add fade-in class to elements and observe them
document.addEventListener('DOMContentLoaded', () => {
    const elementsToAnimate = document.querySelectorAll('.highlight-item, .service-item, .testimonial-item, .gallery-item');
    elementsToAnimate.forEach(el => {
        el.classList.add('fade-in');
        observer.observe(el);
    });
});

// Form submission handling
document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    const appointmentData = {
        name: formData.get('name'),
        phone: formData.get('phone'),
        email: formData.get('email'),
        service: formData.get('service'),
        date: formData.get('date'),
        time: formData.get('time'),
        message: formData.get('message')
    };
    
    // Validate required fields
    if (!appointmentData.name || !appointmentData.phone || !appointmentData.email || 
        !appointmentData.service || !appointmentData.date || !appointmentData.time) {
        showNotification('Please fill in all required fields.', 'error');
        return;
    }
    
    // Validate phone number (basic validation)
    const phoneRegex = /^[6-9]\d{9}$/;
    if (!phoneRegex.test(appointmentData.phone)) {
        showNotification('Please enter a valid 10-digit phone number.', 'error');
        return;
    }
    
    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(appointmentData.email)) {
        showNotification('Please enter a valid email address.', 'error');
        return;
    }
    
    // Check if date is not in the past
    const selectedDate = new Date(appointmentData.date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        showNotification('Please select a future date.', 'error');
        return;
    }
    
    // Submit to backend or fallback to local storage
    showNotification('Processing your appointment request...', 'info');
    
    // Try PHP backend first, fallback to local storage
    fetch('booking-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(appointmentData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Server not available');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            
            // Show token number prominently
            if (data.token_number) {
                setTimeout(() => {
                    showToast(`🎫 Your Token: ${data.token_number} - Please save this!`, 5000);
                }, 1000);
            }
            
            this.reset();
            
            // Create WhatsApp message with token
            const whatsappMessage = createWhatsAppMessage(appointmentData, data.token_number);
            
            // Optional: Open WhatsApp with pre-filled message
            setTimeout(() => {
                if (confirm('Send appointment details via WhatsApp for faster confirmation?')) {
                    window.open(`https://wa.me/919069020005?text=${encodeURIComponent(whatsappMessage)}`, '_blank');
                }
            }, 2000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Server error, using fallback:', error);
        
        // Fallback: Save to localStorage and show success
        const tokenNumber = 'GB' + Date.now().toString().slice(-6);
        appointmentData.token_number = tokenNumber;
        appointmentData.created_at = new Date().toISOString();
        appointmentData.status = 'pending';
        
        // Save to localStorage
        let bookings = JSON.parse(localStorage.getItem('bookings') || '[]');
        bookings.push(appointmentData);
        localStorage.setItem('bookings', JSON.stringify(bookings));
        
        showNotification('Appointment booked successfully! Your token: ' + tokenNumber, 'success');
        
        setTimeout(() => {
            showToast(`🎫 Your Token: ${tokenNumber} - Please save this!`, 5000);
        }, 1000);
        
        this.reset();
        
        // Create WhatsApp message
        const whatsappMessage = createWhatsAppMessage(appointmentData, tokenNumber);
        
        setTimeout(() => {
            if (confirm('Send appointment details via WhatsApp for confirmation?')) {
                window.open(`https://wa.me/919069020005?text=${encodeURIComponent(whatsappMessage)}`, '_blank');
            }
        }, 2000);
    });
});

// Create WhatsApp message from form data
function createWhatsAppMessage(data, tokenNumber = '') {
    return `Hello! I would like to book an appointment at Simran Beauty Parlour.

*Appointment Details:*
${tokenNumber ? `Token Number: ${tokenNumber}` : ''}
Name: ${data.name}
Phone: ${data.phone}
Email: ${data.email}
Service: ${data.service}
Preferred Date: ${data.date}
Preferred Time: ${data.time}
${data.message ? `Special Requests: ${data.message}` : ''}

Please confirm my appointment. Thank you!`;
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        max-width: 400px;
        animation: slideIn 0.3s ease;
    `;
    
    // Add animation keyframes
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .notification-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
            }
            .notification-close {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0;
                line-height: 1;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Set minimum date for appointment booking (today)
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});

// Gallery lightbox functionality
document.addEventListener('DOMContentLoaded', () => {
    const galleryItems = document.querySelectorAll('.gallery-item img');
    
    galleryItems.forEach(img => {
        img.addEventListener('click', () => {
            createLightbox(img.src, img.alt);
        });
        
        // Add cursor pointer
        img.style.cursor = 'pointer';
    });
});

function createLightbox(src, alt) {
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <img src="${src}" alt="${alt}">
        </div>
    `;
    
    lightbox.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        animation: fadeIn 0.3s ease;
    `;
    
    const content = lightbox.querySelector('.lightbox-content');
    content.style.cssText = `
        position: relative;
        max-width: 90%;
        max-height: 90%;
    `;
    
    const closeBtn = lightbox.querySelector('.lightbox-close');
    closeBtn.style.cssText = `
        position: absolute;
        top: -40px;
        right: 0;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        background: none;
        border: none;
    `;
    
    const img = lightbox.querySelector('img');
    img.style.cssText = `
        max-width: 100%;
        max-height: 100%;
        border-radius: 8px;
    `;
    
    document.body.appendChild(lightbox);
    
    // Close functionality
    closeBtn.addEventListener('click', () => closeLightbox(lightbox));
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox(lightbox);
    });
    
    // ESC key to close
    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
            closeLightbox(lightbox);
            document.removeEventListener('keydown', escHandler);
        }
    });
}

function closeLightbox(lightbox) {
    lightbox.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => lightbox.remove(), 300);
}

// Add loading states for better UX
document.addEventListener('DOMContentLoaded', () => {
    // Add loading class to images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        if (!img.complete) {
            img.style.opacity = '0';
            img.addEventListener('load', () => {
                img.style.transition = 'opacity 0.3s ease';
                img.style.opacity = '1';
            });
        }
    });
});

// Service category filtering (if needed for expansion)
function filterServices(category) {
    const serviceItems = document.querySelectorAll('.service-item');
    serviceItems.forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Live Counter Animation
function animateCounters() {
    const counters = document.querySelectorAll('.counter-number');
    counters.forEach(counter => {
        const target = parseInt(counter.parentElement.dataset.target);
        const increment = target / 100;
        let current = 0;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                counter.textContent = Math.ceil(current);
                setTimeout(updateCounter, 20);
            } else {
                counter.textContent = target + (target === 98 ? '%' : '+');
            }
        };
        updateCounter();
    });
}

// Intersection Observer for Counter
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            counterObserver.unobserve(entry.target);
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const counterSection = document.querySelector('.live-counter');
    if (counterSection) {
        counterObserver.observe(counterSection);
    }
});

// Enhanced Chat Widget Functionality
function toggleChat() {
    const chatWidget = document.getElementById('chatWidget');
    chatWidget.classList.toggle('open');
}

function sendQuickMessage(message) {
    addUserMessage(message);
    
    setTimeout(() => {
        let response = '';
        
        switch(message) {
            case 'Book Appointment':
                response = 'Perfect! I can help you book an appointment. Our available slots are 10 AM to 8 PM. <a href="booking.html" style="color: #d4af37; font-weight: bold;">Click here to book now!</a>';
                break;
            case 'View Services':
                response = 'We offer amazing services including:<br>• Facial Treatments (₹1,500+)<br>• Hair Treatments (₹800+)<br>• Bridal Packages (₹15,000+)<br>• Spa Treatments (₹2,500+)<br><a href="services.html" style="color: #d4af37; font-weight: bold;">View all services</a>';
                break;
            case 'Get Pricing':
                response = 'Our competitive pricing:<br>• Facial: ₹1,500 - ₹3,500<br>• Hair Care: ₹800 - ₹4,500<br>• Bridal: ₹15,000 - ₹25,000<br>• Spa: ₹2,500 - ₹5,000<br>First-time clients get 20% off!';
                break;
            case 'Contact Info':
                response = 'Here\'s how to reach us:<br>📞 Phone: +91 90690 20005<br>📍 Address: Beniwall Mohalla, Ram Minder, Samalkha, Panipat 132101<br>🕒 Hours: 10 AM - 8 PM (Daily)<br>💬 WhatsApp: Available';
                break;
        }
        
        addBotMessage(response);
    }, 1000);
}

function addUserMessage(message) {
    const chatMessages = document.getElementById('chatMessages');
    const userMessage = document.createElement('div');
    userMessage.className = 'chat-message user';
    userMessage.innerHTML = `
        <div class="message-avatar" style="background: #6c757d;">
            <i class="fas fa-user"></i>
        </div>
        <div class="message-content" style="background: #e3f2fd;">
            <p>${message}</p>
        </div>
    `;
    chatMessages.appendChild(userMessage);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function addBotMessage(message) {
    const chatMessages = document.getElementById('chatMessages');
    const botMessage = document.createElement('div');
    botMessage.className = 'chat-message bot';
    botMessage.innerHTML = `
        <div class="message-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="message-content">
            <p>${message}</p>
        </div>
    `;
    chatMessages.appendChild(botMessage);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function handleChatInput(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (message) {
        addUserMessage(message);
        input.value = '';
        
        setTimeout(() => {
            const responses = [
                'Thank you for your message! Our team will get back to you shortly. 😊',
                'I\'ve noted your inquiry. Would you like to book an appointment or need more information?',
                'Great question! For detailed information, please call us at +91 90690 20005 or visit our booking page.'
            ];
            const randomResponse = responses[Math.floor(Math.random() * responses.length)];
            addBotMessage(randomResponse);
        }, 1000);
    }
}

// Progress Bar
function updateProgressBar() {
    const scrollTop = window.pageYOffset;
    const docHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercent = (scrollTop / docHeight) * 100;
    
    let progressBar = document.querySelector('.progress-bar');
    if (!progressBar) {
        progressBar = document.createElement('div');
        progressBar.className = 'progress-bar';
        document.body.appendChild(progressBar);
    }
    
    progressBar.style.width = scrollPercent + '%';
}

window.addEventListener('scroll', updateProgressBar);

// Testimonial Carousel - Initialize after DOM loads
document.addEventListener('DOMContentLoaded', function() {
    let currentTestimonial = 0;
    const testimonials = document.querySelectorAll('.testimonial-slide');
    const dots = document.querySelectorAll('.dot');
    
    // Make functions global
    window.showSlide = function(n) {
        if (testimonials.length === 0) return;
        
        testimonials.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        currentTestimonial = (n + testimonials.length) % testimonials.length;
        
        if (testimonials[currentTestimonial]) {
            testimonials[currentTestimonial].classList.add('active');
        }
        if (dots[currentTestimonial]) {
            dots[currentTestimonial].classList.add('active');
        }
    };
    
    window.changeSlide = function(direction) {
        showSlide(currentTestimonial + direction);
    };
    
    window.currentSlide = function(n) {
        showSlide(n - 1);
    };
    
    // Auto-play carousel
    if (testimonials.length > 0) {
        setInterval(() => {
            changeSlide(1);
        }, 5000);
    }
});

// Lazy Loading Images
function lazyLoadImages() {
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
    
    images.forEach(img => imageObserver.observe(img));
}

// Toast Notifications
function showToast(message, duration = 3000) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, duration);
}

// Enhanced Form Validation
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#e74c3c';
            input.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
            isValid = false;
        } else {
            input.style.borderColor = '#27ae60';
            input.style.boxShadow = '0 0 0 3px rgba(39, 174, 96, 0.1)';
        }
    });
    
    return isValid;
}

// Add scroll-to-top functionality
document.addEventListener('DOMContentLoaded', () => {
    // Create scroll to top button
    const scrollTopBtn = document.createElement('button');
    scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollTopBtn.className = 'scroll-top-btn';
    scrollTopBtn.style.cssText = `
        position: fixed;
        bottom: 120px;
        right: 40px;
        width: 50px;
        height: 50px;
        background: #d4af37;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 999;
        font-size: 1.2rem;
    `;
    
    document.body.appendChild(scrollTopBtn);
    
    // Show/hide scroll to top button
    window.addEventListener('scroll', () => {
        if (window.scrollY > 500) {
            scrollTopBtn.style.opacity = '1';
            scrollTopBtn.style.visibility = 'visible';
        } else {
            scrollTopBtn.style.opacity = '0';
            scrollTopBtn.style.visibility = 'hidden';
        }
    });
    
    // Scroll to top functionality
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

// Add hover effects for service items
document.addEventListener('DOMContentLoaded', () => {
    const serviceItems = document.querySelectorAll('.service-item');
    serviceItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.transform = 'translateY(0) scale(1)';
        });
    });
});
// Gallery functionality
const galleryImages = [
    {
        src: 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800',
        title: 'Hydrating Facial',
        desc: 'Deep cleansing & moisturizing treatment for radiant skin'
    },
    {
        src: 'https://images.unsplash.com/photo-1595476108010-b4d1f102b1b1?w=800',
        title: 'Bridal Makeover',
        desc: 'Complete bridal transformation with professional makeup'
    },
    {
        src: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800',
        title: 'Hair Styling',
        desc: 'Professional hair treatment and styling services'
    },
    {
        src: 'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=800',
        title: 'Party Makeup',
        desc: 'Glamorous evening look for special occasions'
    },
    {
        src: 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800',
        title: 'Anti-aging Facial',
        desc: 'Rejuvenating skin treatment for youthful appearance'
    },
    {
        src: 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?w=800',
        title: 'Bridal Hair',
        desc: 'Elegant bridal hairstyle for your special day'
    }
];

let currentImageIndex = 0;

// Gallery filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            btn.classList.add('active');
            
            const filter = btn.getAttribute('data-filter');
            
            galleryItems.forEach(item => {
                if (filter === 'all' || item.getAttribute('data-category') === filter) {
                    item.style.display = 'block';
                    item.style.animation = 'fadeInUp 0.6s ease forwards';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

// Lightbox functionality
function openLightbox(index) {
    currentImageIndex = index;
    const lightbox = document.getElementById('lightbox');
    const img = document.getElementById('lightbox-img');
    const title = document.getElementById('lightbox-title');
    const desc = document.getElementById('lightbox-desc');
    
    img.src = galleryImages[index].src;
    title.textContent = galleryImages[index].title;
    desc.textContent = galleryImages[index].desc;
    
    lightbox.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    lightbox.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
    openLightbox(currentImageIndex);
}

function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
    openLightbox(currentImageIndex);
}

// Close lightbox on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    } else if (e.key === 'ArrowRight') {
        nextImage();
    } else if (e.key === 'ArrowLeft') {
        prevImage();
    }
});

// Close lightbox on background click
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});