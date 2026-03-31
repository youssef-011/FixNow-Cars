<?php
// Logout handler that clears the current session and redirects to the homepage.
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// Clear the authenticated session data before sending the user home.
logout_user();
set_flash_message('You have been logged out successfully.', 'success');
redirect('index.php');
