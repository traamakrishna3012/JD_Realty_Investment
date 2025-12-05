// JD Realty & Investment - JavaScript Utilities

// Password validation
function validatePassword(password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return regex.test(password);
}

// Email validation
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Phone number validation (for Indian phone numbers)
function validatePhone(phone) {
    const regex = /^[0-9]{10}$/;
    return regex.test(phone.replace(/[-\s]/g, ''));
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} animate-fade-in`;
    notification.innerHTML = `
        <strong>${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> ${message}
    `;
    
    document.body.insertBefore(notification, document.body.firstChild);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Format currency (INR)
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

// Format date
function formatDate(date) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(date).toLocaleDateString('en-IN', options);
}

// Get user initials
function getUserInitials(name) {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase();
}

// Confirm action
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed?');
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Fetch with error handling
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            body: options.body ? JSON.stringify(options.body) : null
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        showNotification('An error occurred. Please try again.', 'error');
        return null;
    }
}

// Smooth scroll to element
function smoothScroll(element) {
    element.scrollIntoView({ behavior: 'smooth' });
}

// Toggle class
function toggleClass(element, className) {
    element.classList.toggle(className);
}

// Add class
function addClass(element, className) {
    element.classList.add(className);
}

// Remove class
function removeClass(element, className) {
    element.classList.remove(className);
}

// Has class
function hasClass(element, className) {
    return element.classList.contains(className);
}

// Document ready equivalent
function ready(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        callback();
    }
}

// Export functions for use
window.JDRealty = {
    validatePassword,
    validateEmail,
    validatePhone,
    showNotification,
    formatCurrency,
    formatDate,
    getUserInitials,
    confirmAction,
    debounce,
    fetchData,
    smoothScroll,
    toggleClass,
    addClass,
    removeClass,
    hasClass,
    ready
};
