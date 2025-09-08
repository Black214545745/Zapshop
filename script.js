document.addEventListener('DOMContentLoaded', function() {
    // Get references to all pop-up elements
    const userLoginPopup = document.getElementById('userLoginPopup');
    const userRegisterPopup = document.getElementById('userRegisterPopup');
    const adminLoginPopup = document.getElementById('adminLoginPopup');

    // Get references to buttons that open pop-ups from the nav bar
    const openUserLoginPopupBtn = document.getElementById('openUserLoginPopup');
    const openUserRegisterPopupBtn = document.getElementById('openUserRegisterPopup');
    const openAdminLoginPopupBtn = document.getElementById('openAdminLoginPopup');

    // Get references to internal links that switch between user pop-ups
    const openUserRegisterFromLogin = document.getElementById('openUserRegisterFromLogin');
    const openUserLoginFromRegister = document.getElementById('openUserLoginFromRegister');

    // Function to close all pop-ups
    function closeAllPopups() {
        if (userLoginPopup) userLoginPopup.style.display = 'none';
        if (userRegisterPopup) userRegisterPopup.style.display = 'none';
        if (adminLoginPopup) adminLoginPopup.style.display = 'none';
    }

    // Function to open a specific popup
    function openSpecificPopup(popupElement) {
        closeAllPopups(); // Close any currently open popup first
        if (popupElement) {
            popupElement.style.display = 'flex';
        }
    }

    // Event listeners for closing pop-ups (using the close button inside each popup)
    document.querySelectorAll('.popup-overlay .close-btn').forEach(button => {
        button.addEventListener('click', function() {
            closeAllPopups();
        });
    });

    // Event listeners for closing pop-ups when clicking outside the content
    document.querySelectorAll('.popup-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeAllPopups();
            }
        });
    });

    // Event listeners for opening pop-ups from nav bar
    if (openUserLoginPopupBtn) {
        openUserLoginPopupBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default link behavior
            openSpecificPopup(userLoginPopup); // Open user login popup
        });
    }

    if (openUserRegisterPopupBtn) {
        openUserRegisterPopupBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default link behavior
            openSpecificPopup(userRegisterPopup); // Open user register popup
        });
    }

    if (openAdminLoginPopupBtn) {
        openAdminLoginPopupBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default link behavior
            openSpecificPopup(adminLoginPopup); // Open admin login popup
        });
    }

    // Event listeners for switching between user login/register pop-ups
    if (openUserRegisterFromLogin) {
        openUserRegisterFromLogin.addEventListener('click', function(e) {
            e.preventDefault();
            openSpecificPopup(userRegisterPopup); // Switch to user register popup
        });
    }

    if (openUserLoginFromRegister) {
        openUserLoginFromRegister.addEventListener('click', function(e) {
            e.preventDefault();
            openSpecificPopup(userLoginPopup); // Switch to user login popup
        });
    }

    // Handle form submissions (forms' action attributes are set in HTML)
    // No e.preventDefault() here for actual form submission to PHP
    
    // User Register Form: Password confirmation
    const userRegisterForm = userRegisterPopup ? userRegisterPopup.querySelector('.auth-form') : null;
    if (userRegisterForm) {
        userRegisterForm.addEventListener('submit', function(e) {
            const password = document.getElementById('user-register-password').value;
            const confirmPassword = document.getElementById('user-register-confirm-password').value;

            if (password !== confirmPassword) {
                alert('รหัสผ่านไม่ตรงกัน!');
                e.preventDefault(); // Prevent form submission if passwords don't match
            } else {
                // If passwords match, allow the form to submit to user-register.php
                // The PHP code will then handle registration and redirect.
                // We remove e.preventDefault() here so the form actually submits.
                // alert('กำลังส่งข้อมูลการสมัครสมาชิก...'); // Optional: for debugging
                // After successful registration (handled by PHP), PHP will redirect to user-login.php
                // So, no need to open a popup here on the frontend after submission.
            }
        });
    }

    // For User Login and Admin Login, forms will submit directly to their action URLs
    // You can add additional client-side validation here if needed
});
