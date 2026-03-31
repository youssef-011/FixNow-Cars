<?php
// Lets admins create technician accounts and review existing technicians.
$pageTitle = 'Technicians';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('admin', null, $basePath);

$flashMessage = get_flash_message();
$errors = [];
$pageError = '';
$technicians = [];
$searchTerm = normalize_search_term($_GET['q'] ?? '', 100);
$formName = '';
$formEmail = '';
$formPhone = '';
$formAddress = '';

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if (is_post_request()) {
        $action = $_POST['action'] ?? '';

        if ($action !== 'create_technician') {
            $errors[] = 'Invalid technician form action.';
        } else {
            $formName = trim($_POST['name'] ?? '');
            $formEmail = strtolower(trim($_POST['email'] ?? ''));
            $formPhone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $formAddress = trim($_POST['address'] ?? '');

            validate_required_text($errors, $formName, 'Name', 3, 100);
            validate_email_address($errors, $formEmail);
            validate_phone_number($errors, $formPhone, true);
            validate_max_length($errors, $formAddress, 'Address', 255);

            if ($password === '') {
                $errors[] = 'Password is required.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } elseif (strlen($password) > 255) {
                $errors[] = 'Password must not be longer than 255 characters.';
            }

            if (empty($errors)) {
                $checkStatement = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');

                if ($checkStatement === false) {
                    $errors[] = 'Unable to prepare the email check query.';
                } else {
                    $checkStatement->bind_param('s', $formEmail);
                    $checkStatement->execute();
                    $checkStatement->store_result();

                    if ($checkStatement->num_rows > 0) {
                        $errors[] = 'This email address is already registered.';
                    }

                    $checkStatement->close();
                }
            }

            if (empty($errors)) {
                // New technician accounts are stored in users with the technician role.
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $technicianRole = 'technician';
                $insertStatement = $db->prepare(
                    'INSERT INTO users (name, email, phone, password, role, address)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );

                if ($insertStatement === false) {
                    $errors[] = 'Unable to prepare the technician insert query.';
                } else {
                    $insertStatement->bind_param('ssssss', $formName, $formEmail, $formPhone, $hashedPassword, $technicianRole, $formAddress);

                    if ($insertStatement->execute()) {
                        $insertStatement->close();
                        set_flash_message('Technician account created successfully.', 'success');
                        redirect('technicians.php');
                    }

                    $insertStatement->close();
                    $errors[] = 'Unable to create the technician account. Please try again.';
                }
            }
        }
    }

    if ($searchTerm === '') {
        $techniciansStatement = $db->prepare(
            "SELECT name, email, phone, created_at
             FROM users
             WHERE role = 'technician'
             ORDER BY created_at DESC"
        );

        if ($techniciansStatement === false) {
            $pageError = 'Unable to load technicians.';
        } else {
            $techniciansStatement->execute();
        }
    } else {
        $techniciansStatement = $db->prepare(
            "SELECT name, email, phone, created_at
             FROM users
             WHERE role = 'technician'
               AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)
             ORDER BY created_at DESC"
        );

        if ($techniciansStatement === false) {
            $pageError = 'Unable to load technicians.';
        } else {
            $searchLike = search_like_value($searchTerm);
            $techniciansStatement->bind_param('sss', $searchLike, $searchLike, $searchLike);
            $techniciansStatement->execute();
        }
    }

    if ($pageError === '') {
        $techniciansStatement->bind_result($technicianName, $technicianEmail, $technicianPhone, $createdAt);

        while ($techniciansStatement->fetch()) {
            $technicians[] = [
                'name' => $technicianName,
                'email' => $technicianEmail,
                'phone' => $technicianPhone,
                'created_at' => $createdAt,
            ];
        }

        $techniciansStatement->close();
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
                <span class="section-tag">Technicians</span>
                <h1>Technician Accounts</h1>
                <p>Create technician accounts and review the ones already available.</p>
            </div>
            <span class="status-pill">Admin Monitoring</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="users.php">Users</a>
            <a class="btn btn-secondary" href="services.php">Services</a>
            <a class="btn btn-secondary" href="requests.php">Requests</a>
            <a class="btn btn-secondary" href="reports.php">Reports</a>
            <a class="btn btn-secondary" href="receipts.php">Receipts</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <?php render_message_box($errors, 'error'); ?>

        <section class="content-card">
            <h2>Add Technician</h2>
            <p class="form-note">Create a technician account so it can be used immediately for login and job handling.</p>

            <form class="auth-form" action="technicians.php" method="post">
                <input type="hidden" name="action" value="create_technician">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo escape($formName); ?>" placeholder="Enter technician name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo escape($formEmail); ?>" placeholder="Enter technician email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo escape($formPhone); ?>" placeholder="Enter technician phone number" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                    </div>

                    <div class="form-group full-span">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="4" placeholder="Optional address"><?php echo escape($formAddress); ?></textarea>
                    </div>
                </div>

                <button class="btn" type="submit">Create Technician</button>
            </form>
        </section>

        <section class="content-card">
            <h2>Search Technicians</h2>
            <form class="auth-form" action="technicians.php" method="get">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="q">Search Technicians</label>
                        <input type="text" id="q" name="q" value="<?php echo escape($searchTerm); ?>" placeholder="Search by name, email, or phone">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn" type="submit">Apply Search</button>
                    </div>
                </div>
            </form>

            <?php if ($searchTerm !== ''): ?>
                <div class="dashboard-actions">
                    <a class="btn btn-secondary" href="technicians.php">Clear Search</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($pageError === '' && empty($technicians)): ?>
        <div class="content-card empty-state">
            <p><?php echo escape($searchTerm !== '' ? 'No technicians match the current search.' : 'No technician accounts have been added yet.'); ?></p>
        </div>
    <?php elseif ($pageError === ''): ?>
        <section class="content-card">
            <h2>Existing Technicians</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($technicians as $technician): ?>
                            <tr>
                                <td><?php echo escape($technician['name']); ?></td>
                                <td><?php echo escape($technician['email']); ?></td>
                                <td><?php echo escape($technician['phone'] !== null && $technician['phone'] !== '' ? $technician['phone'] : 'Not set'); ?></td>
                                <td><?php echo escape(date('Y-m-d', strtotime($technician['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
