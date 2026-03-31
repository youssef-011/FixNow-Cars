<?php
// Session start and starter authentication / role-guard helper functions.
require_once __DIR__ . '/functions.php';

// Start the session once here so every page uses the same auth state.
ensure_session_started();

if (!function_exists('user_has_role')) {
    function user_has_role($roles)
    {
        $roles = (array) $roles;
        return in_array(current_user_role(), $roles, true);
    }
}

if (!function_exists('login_user')) {
    function login_user($userId, $userName, $userEmail, $userRole)
    {
        ensure_session_started();
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['user_role'] = $userRole;
    }
}

if (!function_exists('logout_user')) {
    function logout_user()
    {
        ensure_session_started();
        $_SESSION = [];

        // Expire the session cookie so the old login is fully cleared in the browser.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('require_login')) {
    function require_login($redirectPath = 'login.php')
    {
        ensure_session_started();

        if (!is_logged_in()) {
            set_flash_message('Please log in to continue.', 'error');
            redirect($redirectPath);
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($roles, $redirectPath = null, $basePath = '')
    {
        ensure_session_started();

        if (!user_has_role($roles)) {
            set_flash_message('You are not allowed to access that page.', 'error');

            if ($redirectPath === null) {
                $redirectPath = is_logged_in()
                    ? role_dashboard_path(current_user_role(), $basePath)
                    : $basePath . 'index.php';
            }

            redirect($redirectPath);
        }
    }
}

if (!function_exists('redirect_logged_in_user')) {
    function redirect_logged_in_user($basePath = '')
    {
        if (is_logged_in()) {
            redirect(role_dashboard_path(current_user_role(), $basePath));
        }
    }
}
