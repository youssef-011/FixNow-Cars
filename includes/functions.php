<?php
// Shared helper functions used across the FixNow Cars project.

if (!function_exists('ensure_session_started')) {
    function ensure_session_started()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('escape')) {
    function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($path)
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('is_post_request')) {
    function is_post_request()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

if (!function_exists('set_flash_message')) {
    function set_flash_message($message, $type = 'success')
    {
        ensure_session_started();
        $allowedTypes = ['success', 'error'];

        if (!in_array($type, $allowedTypes, true)) {
            $type = 'success';
        }

        $_SESSION['flash_message'] = [
            'text' => trim((string) $message),
            'type' => $type,
        ];
    }
}

if (!function_exists('get_flash_message')) {
    function get_flash_message()
    {
        ensure_session_started();
        if (empty($_SESSION['flash_message'])) {
            return null;
        }

        $flashMessage = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);

        return $flashMessage;
    }
}

if (!function_exists('render_message_box')) {
    function render_message_box($messages, $type = 'error', $autoHide = false)
    {
        if ($messages === null || $messages === '' || $messages === []) {
            return;
        }

        $validMessages = [];

        foreach ((array) $messages as $message) {
            if ($message !== null && $message !== '') {
                $validMessages[] = $message;
            }
        }

        if (empty($validMessages)) {
            return;
        }

        $allowedTypes = ['success', 'error'];

        if (!in_array($type, $allowedTypes, true)) {
            $type = 'error';
        }

        echo '<div class="flash-message flash-' . escape($type) . '"' . ($autoHide ? ' data-auto-hide="true"' : '') . '>';

        foreach ($validMessages as $message) {
            echo '<p>' . escape($message) . '</p>';
        }

        echo '</div>';
    }
}

if (!function_exists('render_flash_message')) {
    function render_flash_message($flashMessage)
    {
        if (!$flashMessage || empty($flashMessage['text'])) {
            return;
        }

        $type = $flashMessage['type'] ?? 'success';
        render_message_box($flashMessage['text'], $type, true);
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        ensure_session_started();
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user_role')) {
    function current_user_role()
    {
        ensure_session_started();
        return $_SESSION['user_role'] ?? null;
    }
}

if (!function_exists('current_user_name')) {
    function current_user_name()
    {
        ensure_session_started();
        return $_SESSION['user_name'] ?? 'User';
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id()
    {
        ensure_session_started();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    }
}

if (!function_exists('current_user_email')) {
    function current_user_email()
    {
        ensure_session_started();
        return $_SESSION['user_email'] ?? '';
    }
}

if (!function_exists('role_dashboard_path')) {
    function role_dashboard_path($role, $basePath = '')
    {
        // $basePath keeps role redirects working from both root pages and subfolders.
        $basePath = (string) $basePath;

        switch ($role) {
            case 'admin':
                return $basePath . 'admin/index.php';
            case 'technician':
                return $basePath . 'technician/index.php';
            case 'user':
                return $basePath . 'user/index.php';
            default:
                return $basePath . 'index.php';
        }
    }
}

if (!function_exists('role_dashboard_label')) {
    function role_dashboard_label($role)
    {
        switch ($role) {
            case 'admin':
                return 'Admin Dashboard';
            case 'technician':
                return 'Technician Dashboard';
            case 'user':
                return 'Dashboard';
            default:
                return 'Dashboard';
        }
    }
}

if (!function_exists('format_label')) {
    function format_label($value)
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class($status)
    {
        switch (strtolower(trim((string) $status))) {
            case 'pending':
            case 'unpaid':
                return 'status-badge status-pending';
            case 'accepted':
                return 'status-badge status-accepted';
            case 'in_progress':
                return 'status-badge status-in-progress';
            case 'completed':
            case 'paid':
                return 'status-badge status-completed';
            case 'cancelled':
                return 'status-badge status-cancelled';
            default:
                return 'status-badge';
        }
    }
}

if (!function_exists('render_status_badge')) {
    function render_status_badge($status)
    {
        $status = trim((string) $status);
        $label = $status !== '' ? format_label($status) : 'Not Set';

        echo '<span class="' . escape(status_badge_class($status)) . '">' . escape($label) . '</span>';
    }
}

// Validation helpers keep common form rules consistent with database column sizes.
if (!function_exists('is_blank')) {
    function is_blank($value)
    {
        return trim((string) $value) === '';
    }
}

if (!function_exists('validate_required_text')) {
    function validate_required_text(&$errors, $value, $label, $minLength = 1, $maxLength = null)
    {
        $value = trim((string) $value);
        $length = strlen($value);

        if ($value === '') {
            $errors[] = $label . ' is required.';
            return;
        }

        if ($length < (int) $minLength) {
            $errors[] = $label . ' must be at least ' . (int) $minLength . ' characters.';
        }

        if ($maxLength !== null && $length > (int) $maxLength) {
            $errors[] = $label . ' must not be longer than ' . (int) $maxLength . ' characters.';
        }
    }
}

if (!function_exists('validate_max_length')) {
    function validate_max_length(&$errors, $value, $label, $maxLength)
    {
        $value = trim((string) $value);

        if ($value !== '' && strlen($value) > (int) $maxLength) {
            $errors[] = $label . ' must not be longer than ' . (int) $maxLength . ' characters.';
        }
    }
}

if (!function_exists('validate_email_address')) {
    function validate_email_address(&$errors, $value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            $errors[] = 'Email address is required.';
            return;
        }

        if (strlen($value) > 100 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
    }
}

if (!function_exists('validate_phone_number')) {
    function validate_phone_number(&$errors, $value, $required = true)
    {
        $value = trim((string) $value);

        if ($value === '') {
            if ($required) {
                $errors[] = 'Phone number is required.';
            }

            return;
        }

        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $value)) {
            $errors[] = 'Phone number format is not valid.';
        }
    }
}

if (!function_exists('validate_year_value')) {
    function validate_year_value(&$errors, $value, $label = 'Year')
    {
        $value = trim((string) $value);
        $maxYear = (int) date('Y') + 1;

        if ($value === '') {
            $errors[] = $label . ' is required.';
            return;
        }

        if (!ctype_digit($value) || (int) $value < 1900 || (int) $value > $maxYear) {
            $errors[] = 'Please enter a valid ' . strtolower($label) . '.';
        }
    }
}

if (!function_exists('validate_non_negative_decimal')) {
    function validate_non_negative_decimal(&$errors, $value, $label, $required = true)
    {
        $value = trim((string) $value);

        if ($value === '') {
            if ($required) {
                $errors[] = $label . ' is required.';
            }

            return;
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            $errors[] = $label . ' must be a valid non-negative number with up to 2 decimal places.';
            return;
        }

        if ((float) $value < 0) {
            $errors[] = $label . ' must not be negative.';
        }
    }
}

if (!function_exists('validate_allowed_value')) {
    function validate_allowed_value(&$errors, $value, $allowedValues, $message)
    {
        if (!in_array($value, (array) $allowedValues, true)) {
            $errors[] = $message;
        }
    }
}

if (!function_exists('normalize_search_term')) {
    function normalize_search_term($value, $maxLength = 100)
    {
        $value = trim((string) $value);

        if ($maxLength > 0 && strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }

        return $value;
    }
}

if (!function_exists('search_like_value')) {
    function search_like_value($value)
    {
        return '%' . normalize_search_term($value) . '%';
    }
}

if (!function_exists('create_receipt_if_needed')) {
    function create_receipt_if_needed($db, $requestId)
    {
        if (!($db instanceof mysqli) || $requestId <= 0) {
            return [
                'success' => false,
                'eligible' => false,
                'created' => false,
                'exists' => false,
                'updated' => false,
                'message' => 'Invalid receipt request.',
            ];
        }

        // The unique key on receipts.request_id keeps duplicate receipts from being saved.
        $checkStatement = $db->prepare(
            "SELECT sr.final_price, r.id, r.amount
             FROM service_requests AS sr
             LEFT JOIN receipts AS r ON r.request_id = sr.id
             WHERE sr.id = ?
               AND sr.status = 'completed'
               AND sr.final_price IS NOT NULL
             LIMIT 1"
        );

        if ($checkStatement === false) {
            return [
                'success' => false,
                'eligible' => false,
                'created' => false,
                'exists' => false,
                'updated' => false,
                'message' => 'Unable to verify receipt eligibility.',
            ];
        }

        $checkStatement->bind_param('i', $requestId);
        $checkStatement->execute();
        $checkStatement->bind_result($finalPrice, $existingReceiptId, $existingReceiptAmount);

        if (!$checkStatement->fetch()) {
            $checkStatement->close();
            return [
                'success' => true,
                'eligible' => false,
                'created' => false,
                'exists' => false,
                'updated' => false,
                'message' => 'Receipt is only available for completed requests with a final price.',
            ];
        }

        $checkStatement->close();

        if ($existingReceiptId !== null) {
            $finalPriceValue = (float) $finalPrice;
            $existingAmountValue = (float) $existingReceiptAmount;

            if (abs($finalPriceValue - $existingAmountValue) > 0.00001) {
                $updateStatement = $db->prepare('UPDATE receipts SET amount = ? WHERE request_id = ?');

                if ($updateStatement === false) {
                    return [
                        'success' => false,
                        'eligible' => true,
                        'created' => false,
                        'exists' => true,
                        'updated' => false,
                        'message' => 'Receipt exists, but its amount could not be updated.',
                    ];
                }

                $updateStatement->bind_param('di', $finalPriceValue, $requestId);

                if ($updateStatement->execute()) {
                    $updateStatement->close();
                    return [
                        'success' => true,
                        'eligible' => true,
                        'created' => false,
                        'exists' => true,
                        'updated' => true,
                        'message' => 'Receipt already existed and its amount was updated.',
                    ];
                }

                $updateStatement->close();
                return [
                    'success' => false,
                    'eligible' => true,
                    'created' => false,
                    'exists' => true,
                    'updated' => false,
                    'message' => 'Receipt exists, but its amount could not be updated.',
                ];
            }

            return [
                'success' => true,
                'eligible' => true,
                'created' => false,
                'exists' => true,
                'updated' => false,
                'message' => 'Receipt already exists for this request.',
            ];
        }

        $insertStatement = $db->prepare(
            "INSERT INTO receipts (request_id, amount, payment_status)
             VALUES (?, ?, 'unpaid')"
        );

        if ($insertStatement === false) {
            return [
                'success' => false,
                'eligible' => true,
                'created' => false,
                'exists' => false,
                'updated' => false,
                'message' => 'Unable to prepare receipt creation.',
            ];
        }

        $receiptAmount = (float) $finalPrice;
        $insertStatement->bind_param('id', $requestId, $receiptAmount);

        if ($insertStatement->execute()) {
            $insertStatement->close();
            return [
                'success' => true,
                'eligible' => true,
                'created' => true,
                'exists' => false,
                'updated' => false,
                'message' => 'Receipt created successfully.',
            ];
        }

        $errorNumber = $insertStatement->errno;
        $insertStatement->close();

        if ($errorNumber === 1062) {
            return [
                'success' => true,
                'eligible' => true,
                'created' => false,
                'exists' => true,
                'updated' => false,
                'message' => 'Receipt already exists for this request.',
            ];
        }

        return [
            'success' => false,
            'eligible' => true,
            'created' => false,
            'exists' => false,
            'updated' => false,
            'message' => 'Unable to create the receipt automatically.',
        ];
    }
}

// Add future CRUD, validation, and formatting helpers here as the project grows.
