<?php
// Profile page where the logged-in technician can review and update account data.
$pageTitle = 'Technician Profile';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('technician', null, $basePath);

$flashMessage = get_flash_message();
$errors = [];
$pageError = '';
$technicianId = current_user_id();
$name = '';
$email = '';
$phone = '';
$address = '';
$createdAt = '';

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    // Load the technician first so the form uses saved account values.
    $profileStatement = $db->prepare('SELECT name, email, phone, address, created_at FROM users WHERE id = ? AND role = ? LIMIT 1');
    $technicianRole = 'technician';

    if ($profileStatement === false) {
        $pageError = 'Unable to load your profile information.';
    } else {
        $profileStatement->bind_param('is', $technicianId, $technicianRole);
        $profileStatement->execute();
        $profileStatement->store_result();

        if ($profileStatement->num_rows !== 1) {
            $profileStatement->close();
            set_flash_message('Unable to find your account.', 'error');
            redirect('index.php');
        }

        $profileStatement->bind_result($savedName, $savedEmail, $savedPhone, $savedAddress, $savedCreatedAt);
        $profileStatement->fetch();
        $profileStatement->close();

        $email = $savedEmail;
        $createdAt = $savedCreatedAt;

        if (is_post_request()) {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            validate_required_text($errors, $name, 'Name', 3, 100);
            validate_phone_number($errors, $phone, true);
            validate_max_length($errors, $address, 'Address', 255);

            if (empty($errors)) {
                $updateStatement = $db->prepare('UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ? AND role = ?');

                if ($updateStatement === false) {
                    $errors[] = 'Unable to prepare the profile update query.';
                } else {
                    $updateStatement->bind_param('sssis', $name, $phone, $address, $technicianId, $technicianRole);

                    if ($updateStatement->execute()) {
                        $updateStatement->close();
                        $_SESSION['user_name'] = $name;
                        set_flash_message('Profile updated successfully.', 'success');
                        redirect('profile.php');
                    }

                    $updateStatement->close();
                    $errors[] = 'Profile update failed. Please try again.';
                }
            }
        } else {
            $name = $savedName;
            $phone = $savedPhone;
            $address = $savedAddress ?? '';
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="container">
    <?php render_flash_message($flashMessage); ?>

    <section class="page-banner">
        <div class="dashboard-header">
            <div>
                <span class="section-tag">Technician Profile</span>
                <h1>My Profile</h1>
                <p>View your account details and update your contact information.</p>
            </div>
            <span class="status-pill">Technician Account</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="available_requests.php">Available Requests</a>
            <a class="btn btn-secondary" href="my_jobs.php">My Jobs</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <?php render_message_box($errors, 'error'); ?>

        <section class="detail-grid">
            <article class="content-card">
                <h2>Current Details</h2>
                <div class="detail-list">
                    <div class="detail-item">
                        <strong>Name</strong>
                        <span><?php echo escape($name); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Email</strong>
                        <span><?php echo escape($email); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Phone</strong>
                        <span><?php echo escape($phone); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Address</strong>
                        <span><?php echo escape($address !== '' ? $address : 'Not added yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Created At</strong>
                        <span><?php echo escape(date('Y-m-d', strtotime($createdAt))); ?></span>
                    </div>
                </div>
            </article>

            <article class="content-card">
                <h2>Update Profile</h2>

                <form class="auth-form" action="profile.php" method="post">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo escape($name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo escape($email); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo escape($phone); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="4" placeholder="Enter your address"><?php echo escape($address); ?></textarea>
                    </div>

                    <button class="btn full-width" type="submit">Save Changes</button>
                </form>
            </article>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
