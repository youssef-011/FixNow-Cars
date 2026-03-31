<?php
// Registration page for new customer accounts.
$pageTitle = 'Register';
$basePath = '';

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

// Logged-in users should not re-open the registration page.
redirect_logged_in_user($basePath);

$errors = [];
$name = '';
$email = '';
$phone = '';
$flashMessage = get_flash_message();

if (is_post_request()) {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    validate_required_text($errors, $name, 'Name', 3, 100);
    validate_email_address($errors, $email);
    validate_phone_number($errors, $phone, true);

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif (strlen($password) > 255) {
        $errors[] = 'Password must not be longer than 255 characters.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        if ($db === null) {
            $errors[] = $dbConnectionError ?? 'Database connection is not available.';
        } else {
            $checkStatement = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');

            if ($checkStatement === false) {
                $errors[] = 'Unable to prepare the registration check query.';
            } else {
                $checkStatement->bind_param('s', $email);
                $checkStatement->execute();
                $checkStatement->store_result();

                if ($checkStatement->num_rows > 0) {
                    $errors[] = 'This email address is already registered.';
                }

                $checkStatement->close();
            }
        }
    }

    if (empty($errors) && $db !== null) {
        // Store the password as a secure hash instead of plain text.
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStatement = $db->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)');
        $defaultRole = 'user';

        if ($insertStatement === false) {
            $errors[] = 'Unable to prepare the registration insert query.';
        } else {
            $insertStatement->bind_param('sssss', $name, $email, $phone, $hashedPassword, $defaultRole);

            if ($insertStatement->execute()) {
                $insertStatement->close();
                set_flash_message('Registration completed successfully. You can now log in.', 'success');
                redirect($basePath . 'login.php');
            }

            $insertStatement->close();
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="container narrow-container">
    <?php render_flash_message($flashMessage); ?>

    <section class="auth-card">
        <div class="section-heading left-align">
            <span class="section-tag">Create Account</span>
            <h1>Register</h1>
            <p>Create your account to request service and track updates.</p>
        </div>

        <?php render_message_box($errors, 'error'); ?>

        <form class="auth-form" action="<?php echo escape($basePath . 'register.php'); ?>" method="post">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo escape($name); ?>"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo escape($email); ?>"
                    placeholder="Enter your email address"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?php echo escape($phone); ?>"
                    placeholder="Enter your phone number"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Repeat the same password"
                    required
                >
            </div>

            <button class="btn full-width" type="submit">Register</button>
        </form>

        <p class="form-note">
            This page creates standard <strong>user</strong> accounts only.
        </p>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
