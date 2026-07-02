// Client-Side Validations for Job Board

$(document).ready(function() {
    
    // --- VIEW SWITCHING LOGIC ---
    
    // Switch to Register Form
    $('#toggle-to-register').click(function(e) {
        e.preventDefault();
        $('#login-view').addClass('d-none');
        $('#register-view').removeClass('d-none');
        clearAlerts();
        resetForms();
    });

    // Switch to Login Form
    $('#toggle-to-login').click(function(e) {
        e.preventDefault();
        $('#register-view').addClass('d-none');
        $('#login-view').removeClass('d-none');
        clearAlerts();
        resetForms();
    });

    // --- DYNAMIC ROLE-SPECIFIC FIELDS ---
    
    $('input[name="role"]').change(function() {
        if ($(this).val() === 'employer') {
            $('#employer-fields').removeClass('d-none');
            $('#company-name').attr('required', true);
        } else {
            $('#employer-fields').addClass('d-none');
            $('#company-name').removeAttr('required').val('');
            $('#company-website').val('');
        }
    });

    // --- FORM SUBMIT VALIDATION GATES ---

    // Real-time input validation listener
    $('.form-control').on('input', function() {
        if ($(this).val().trim() !== '') {
            $(this).removeClass('is-invalid');
        }
    });
});

// Helper: Clear UI Alerts
function clearAlerts() {
    $('#alert-container').addClass('d-none').removeClass('alert alert-danger alert-success').html('');
}

// Helper: Reset all input states
function resetForms() {
    // Target only the known forms by ID — avoid accidentally resetting other forms on the page
    $('#login-form, #register-form').each(function() {
        this.reset();
    });
    $('.form-control').removeClass('is-invalid');
    // Set default role back to seeker
    $('#role-seeker').prop('checked', true).trigger('change');
}

// Validation function: Login Form
function validateLoginForm() {
    let isValid = true;
    const email = $('#login-email');
    const password = $('#login-password');

    // Email Check
    if (email.val().trim() === '' || !isValidEmail(email.val())) {
        email.addClass('is-invalid');
        isValid = false;
    } else {
        email.removeClass('is-invalid');
    }

    // Password Check
    if (password.val() === '') {
        password.addClass('is-invalid');
        isValid = false;
    } else {
        password.removeClass('is-invalid');
    }

    return isValid;
}

// Validation function: Registration Form
function validateRegisterForm() {
    let isValid = true;
    const fullName = $('#reg-name');
    const email = $('#reg-email');
    const password = $('#reg-password');
    const confirmPassword = $('#reg-confirm-password');
    const role = $('input[name="role"]:checked').val();
    const companyName = $('#company-name');

    // 1. Full Name Check
    if (fullName.val().trim() === '') {
        fullName.addClass('is-invalid');
        isValid = false;
    } else {
        fullName.removeClass('is-invalid');
    }

    // 2. Email Check
    if (email.val().trim() === '' || !isValidEmail(email.val())) {
        email.addClass('is-invalid');
        isValid = false;
    } else {
        email.removeClass('is-invalid');
    }

    // 3. Password Check (min 6 characters)
    if (password.val().length < 6) {
        password.addClass('is-invalid');
        isValid = false;
    } else {
        password.removeClass('is-invalid');
    }

    // 4. Password Match Check
    if (confirmPassword.val() === '' || confirmPassword.val() !== password.val()) {
        confirmPassword.addClass('is-invalid');
        isValid = false;
    } else {
        confirmPassword.removeClass('is-invalid');
    }

    // 5. Employer Specific Fields
    if (role === 'employer') {
        if (companyName.val().trim() === '') {
            companyName.addClass('is-invalid');
            isValid = false;
        } else {
            companyName.removeClass('is-invalid');
        }
    }

    return isValid;
}

// Helper: Email validation regex
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
