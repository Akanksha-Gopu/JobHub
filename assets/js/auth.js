// Global Authentication AJAX Setup
$(document).ready(function () {
    // Configure jQuery AJAX to automatically attach the Bearer token
    $.ajaxSetup({
        beforeSend: function (xhr) {
            const token = localStorage.getItem('token');
            if (token) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + token);
            }
        }
    });

    // Global listener to clear token on logout button click
    $(document).on('click', '#logout-btn', function () {
        localStorage.removeItem('token');
    });

    // Global listener to clear token if any AJAX response indicates an authentication failure
    $(document).ajaxSuccess(function (event, xhr, settings, data) {
        if (data && data.status === 'error' && 
            (data.message === 'Authentication required.' || data.message === 'Unauthenticated. Please log in.')) {
            localStorage.removeItem('token');
        }
    });
});
