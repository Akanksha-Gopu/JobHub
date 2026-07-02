// Main Portal Controller Handler - AJAX Operations

$(document).ready(function () {
  // Check if session is already active (Auto-redirect)
  $.ajax({
    url: "api/controllers/auth.php?action=me",
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        redirectUser(response.data.role);
      }
    },
  });

  // --- LOGIN FORM SUBMISSION ---
  $("#login-form").submit(function (e) {
    e.preventDefault();
    clearAlerts();

    if (!validateLoginForm()) {
      showAlert("danger", "Please enter a valid email and password.");
      return;
    }

    const email = $("#login-email").val().trim();
    const password = $("#login-password").val();

    // Submit via AJAX
    $.ajax({
      url: "api/controllers/auth.php?action=login",
      type: "POST",
      data: {
        email: email,
        password: password,
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          showAlert("success", response.message + " Redirecting...");
          setTimeout(function () {
            redirectUser(response.data.role);
          }, 1000);
        } else {
          showAlert("danger", response.message);
        }
      },
      error: function () {
        showAlert(
          "danger",
          "An error occurred on the server. Please try again later.",
        );
      },
    });
  });

  // --- REGISTRATION FORM SUBMISSION ---
  $("#register-form").submit(function (e) {
    e.preventDefault();
    clearAlerts();

    if (!validateRegisterForm()) {
      showAlert("danger", "Please fix errors before submitting.");
      return;
    }

    const role = $('input[name="role"]:checked').val();
    const fullName = $("#reg-name").val().trim();
    const email = $("#reg-email").val().trim();
    const password = $("#reg-password").val();
    const confirmPassword = $("#reg-confirm-password").val();

    // Employer dynamic parameters
    const companyName = $("#company-name").val()
      ? $("#company-name").val().trim()
      : "";
    const companyWebsite = $("#company-website").val()
      ? $("#company-website").val().trim()
      : "";

    // Submit registration data
    $.ajax({
      url: "api/controllers/auth.php?action=register",
      type: "POST",
      data: {
        role: role,
        full_name: fullName,
        email: email,
        password: password,
        confirm_password: confirmPassword,
        company_name: companyName,
        company_website: companyWebsite,
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          showAlert("success", response.message);
          setTimeout(function () {
            // Switch to login view
            $("#toggle-to-login").trigger("click");
          }, 1500);
        } else {
          showAlert("danger", response.message);
        }
      },
      error: function () {
        showAlert(
          "danger",
          "Server registration failed. Please check inputs and try again.",
        );
      },
    });
  });
});

// Helper: Redirect to appropriate views directory pages
function redirectUser(role) {
  if (role === "seeker") {
    window.location.href = "views/jobs.html";
  } else if (role === "employer") {
    window.location.href = "views/dashboard-employer.html";
  } else if (role === "admin") {
    window.location.href = "views/admin.html";
  }
}

// Helper: Show Bootstrap alert messages
function showAlert(type, message) {
  const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fa-solid ${type === "success" ? "fa-circle-check" : "fa-circle-exclamation"} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
  $("#alert-container").html(alertHtml).removeClass("d-none");
}
