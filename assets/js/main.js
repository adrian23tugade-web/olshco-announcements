/**
 * OLSHCO Student Announcement System
 * Professional JavaScript Structure - Version 2.0
 */

// Document Ready
$(document).ready(function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Character counter for textareas
    $('textarea[data-maxlength]').on('input', function() {
        var maxLength = $(this).data('maxlength');
        var currentLength = $(this).val().length;
        var remaining = maxLength - currentLength;
        
        var counter = $(this).next('.character-counter');
        if (counter.length === 0) {
            counter = $('<div class="character-counter text-muted small mt-1"></div>');
            $(this).after(counter);
        }
        
        counter.text(remaining + ' characters remaining');
        
        if (remaining < 50) {
            counter.css('color', 'orange');
        }
        if (remaining < 10) {
            counter.css('color', 'red');
        }
    });
    
    // Initialize chatbot if exists
    initChatbot();
});

/**
 * Chatbot Initialization
 */
function initChatbot() {
    const chatToggle = document.getElementById('chatbotToggle');
    const chatWindow = document.getElementById('chatbotWindow');
    const chatClose = document.getElementById('chatbotClose');
    const chatInput = document.getElementById('chatbotInput');
    const chatSend = document.getElementById('chatbotSend');
    const chatBody = document.getElementById('chatbotBody');
    
    if (!chatToggle || !chatWindow) return;
    
    chatToggle.addEventListener('click', function() {
        chatWindow.classList.toggle('active');
    });
    
    if (chatClose) {
        chatClose.addEventListener('click', function() {
            chatWindow.classList.remove('active');
        });
    }
    
    if (chatSend && chatInput && chatBody) {
        chatSend.addEventListener('click', sendChatMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendChatMessage();
            }
        });
    }
}

/**
 * Send Chat Message
 */
function sendChatMessage() {
    const chatInput = document.getElementById('chatbotInput');
    const chatBody = document.getElementById('chatbotBody');
    const message = chatInput.value.trim();
    
    if (!message) return;
    
    // Add user message
    addChatMessage(message, 'user');
    chatInput.value = '';
    
    // Show typing indicator
    addChatMessage('Typing...', 'bot', true);
    
    // Send to API
    fetch('chat-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        // Remove typing indicator
        const typingIndicator = document.querySelector('.typing-indicator');
        if (typingIndicator) typingIndicator.remove();
        
        if (data.success) {
            addChatMessage(data.reply, 'bot');
        } else {
            addChatMessage('Sorry, I encountered an error. Please try again.', 'bot');
        }
    })
    .catch(error => {
        const typingIndicator = document.querySelector('.typing-indicator');
        if (typingIndicator) typingIndicator.remove();
        addChatMessage('Sorry, I cannot connect to the server.', 'bot');
    });
}

/**
 * Add Chat Message
 */
function addChatMessage(text, sender, isTyping = false) {
    const chatBody = document.getElementById('chatbotBody');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chatbot-message ${sender}`;
    
    if (isTyping) {
        messageDiv.classList.add('typing-indicator');
    }
    
    messageDiv.textContent = text;
    chatBody.appendChild(messageDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/**
 * Toggle Password Visibility
 */
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = fieldId === 'password' ? 
        document.getElementById('passwordToggleIcon') : 
        document.getElementById('confirmToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

/**
 * Password Strength Checker
 */
function checkPasswordStrength(password) {
    var strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    return strength;
}

/**
 * Format Date
 */
function formatDate(dateString) {
    var options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Show Loading Spinner
 */
function showLoading(selector) {
    $(selector).html('<div class="text-center"><div class="spinner-border text-maroon" role="status"><span class="visually-hidden">Loading...</span></div></div>');
}

/**
 * Hide Loading Spinner
 */
function hideLoading(selector, content) {
    $(selector).html(content);
}

/**
 * Confirm Action
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * AJAX Request with CSRF Token
 */
function ajaxRequest(url, data, successCallback, errorCallback) {
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: successCallback,
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            if (errorCallback) {
                errorCallback(xhr, status, error);
            }
        }
    });
}

/**
 * Validate Email Format
 */
function isValidEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Get Password Strength
 */
function getPasswordStrength(password) {
    var strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    return strength;
}

/**
 * Show Notification Toast
 */
function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    if ($('#toast-container').length === 0) {
        $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
    }
    
    var toastId = 'toast-' + Date.now();
    var bgClass = type === 'success' ? 'bg-success' : 
                  type === 'error' ? 'bg-danger' : 
                  type === 'warning' ? 'bg-warning' : 'bg-info';
    
    var toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgClass} text-white">
                <strong class="me-auto">${type.toUpperCase()}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    $('#toast-container').append(toastHtml);
    var toast = new bootstrap.Toast(document.getElementById(toastId));
    toast.show();
    
    // Remove toast after it's hidden
    document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

/**
 * Debounce Function for Search Inputs
 */
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

/**
 * Edit User Function (for admin panel)
 */
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_first_name').value = user.first_name || '';
    document.getElementById('edit_last_name').value = user.last_name || '';
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_user_type').value = user.user_type;
    document.getElementById('edit_status').value = user.status;
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

/**
 * Reset Password Function (for admin panel)
 */
function resetPassword(id, username) {
    document.getElementById('reset_user_id').value = id;
    document.getElementById('reset_username').innerText = username;
    
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}