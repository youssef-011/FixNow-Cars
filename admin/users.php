<?php
// Shows all normal users for monitoring purposes.
$pageTitle = 'Users';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('admin', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$users = [];
$searchTerm = normalize_search_term($_GET['q'] ?? '', 100);

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if ($searchTerm === '') {
        $usersStatement = $db->prepare(
            "SELECT name, email, phone, created_at
             FROM users
             WHERE role = 'user'
             ORDER BY created_at DESC"
        );

        if ($usersStatement === false) {
            $pageError = 'Unable to load users.';
        } else {
            $usersStatement->execute();
        }
    } else {
        $usersStatement = $db->prepare(
            "SELECT name, email, phone, created_at
             FROM users
             WHERE role = 'user'
               AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)
             ORDER BY created_at DESC"
        );

        if ($usersStatement === false) {
            $pageError = 'Unable to load users.';
        } else {
            $searchLike = search_like_value($searchTerm);
            $usersStatement->bind_param('sss', $searchLike, $searchLike, $searchLike);
            $usersStatement->execute();
        }
    }

    if ($pageError === '') {
        $usersStatement->bind_result($name, $email, $phone, $createdAt);

        while ($usersStatement->fetch()) {
            $users[] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'created_at' => $createdAt,
            ];
        }

        $usersStatement->close();
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
                <span class="section-tag">Users</span>
                <h1>Registered Users</h1>
                <p>Review all registered user accounts and their contact details.</p>
            </div>
            <span class="status-pill">Admin Monitoring</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="technicians.php">Technicians</a>
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
        <section class="content-card">
            <form class="auth-form" action="users.php" method="get">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="q">Search Users</label>
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
                    <a class="btn btn-secondary" href="users.php">Clear Search</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($pageError === '' && empty($users)): ?>
        <div class="content-card empty-state">
            <p><?php echo escape($searchTerm !== '' ? 'No users match the current search.' : 'No normal users have registered yet.'); ?></p>
        </div>
    <?php elseif ($pageError === ''): ?>
        <section class="content-card">
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
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo escape($user['name']); ?></td>
                                <td><?php echo escape($user['email']); ?></td>
                                <td><?php echo escape($user['phone'] !== null && $user['phone'] !== '' ? $user['phone'] : 'Not set'); ?></td>
                                <td><?php echo escape(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
