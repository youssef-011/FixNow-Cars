<?php
// Login page for all project roles.
$pageTitle = 'Login';
$basePath = '';

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

// Logged-in users should go straight to the correct dashboard.
redirect_logged_in_user($basePath);

$errors = [];
$email = '';
$flashMessage = get_flash_message();

if (is_post_request()) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    validate_email_address($errors, $email);

    validate_required_text($errors, $password, 'Password', 1, 255);

    if (empty($errors)) {
        if ($db === null) {
            $errors[] = $dbConnectionError ?? 'Database connection is not available.';
        } else {
            $statement = $db->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');

            if ($statement === false) {
                $errors[] = 'Unable to prepare the login query.';
            } else {
                $statement->bind_param('s', $email);
                $statement->execute();
                $statement->store_result();

                if ($statement->num_rows === 1) {
                    $statement->bind_result($userId, $userName, $userEmail, $userPassword, $userRole);
                    $statement->fetch();
                } else {
                    $userId = null;
                }

                $statement->close();

                // Compare the entered password with the stored password hash.
                if ($userId !== null && password_verify($password, $userPassword)) {
                    login_user($userId, $userName, $userEmail, $userRole);
                    set_flash_message('Welcome back, ' . $userName . '!', 'success');
                    redirect(role_dashboard_path($userRole, $basePath));
                } else {
                    $errors[] = 'Incorrect email or password.';
                }
            }
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
            <span class="section-tag">Account Access</span>
            <h1>Login</h1>
            <p>Sign in to continue to your dashboard.</p>
        </div>

        <?php render_message_box($errors, 'error'); ?>

        <form class="auth-form" action="<?php echo escape($basePath . 'login.php'); ?>" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo escape($email); ?>"
                    placeholder="example@fixnow.com"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button class="btn full-width" type="submit">Login</button>
        </form>

        <div class="helper-panel">
            <h3>Admin Test Account</h3>
            <p>After importing the database, you can sign in with the seeded admin account using the password: <strong>password</strong></p>
            <ul class="feature-list compact-list">
                <li>Admin: admin@fixnow.com</li>
            </ul>
        </div>

        <p class="form-note">
            New here?
            <a href="<?php echo escape($basePath . 'register.php'); ?>">Create a user account</a>
            to start using the website.
        </p>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
