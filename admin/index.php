<?php
// Main dashboard for logged-in admins.
$pageTitle = 'Admin Dashboard';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('admin', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$totalUsers = 0;
$totalTechnicians = 0;
$totalServices = 0;
$totalRequests = 0;

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    $usersStatement = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $techniciansStatement = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'technician'");
    $servicesStatement = $db->prepare('SELECT COUNT(*) FROM services');
    $requestsStatement = $db->prepare('SELECT COUNT(*) FROM service_requests');

    if ($usersStatement === false || $techniciansStatement === false || $servicesStatement === false || $requestsStatement === false) {
        $pageError = 'Unable to load the dashboard summary.';
    } else {
        $usersStatement->execute();
        $usersStatement->bind_result($totalUsers);
        $usersStatement->fetch();
        $usersStatement->close();

        $techniciansStatement->execute();
        $techniciansStatement->bind_result($totalTechnicians);
        $techniciansStatement->fetch();
        $techniciansStatement->close();

        $servicesStatement->execute();
        $servicesStatement->bind_result($totalServices);
        $servicesStatement->fetch();
        $servicesStatement->close();

        $requestsStatement->execute();
        $requestsStatement->bind_result($totalRequests);
        $requestsStatement->fetch();
        $requestsStatement->close();
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
                <span class="section-tag">Admin Area</span>
                <h1>Welcome, <?php echo escape(current_user_name()); ?></h1>
                <p>Monitor accounts, services, and service requests from one dashboard.</p>
            </div>
            <span class="status-pill">Role: Admin</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="users.php">Users</a>
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
        <section class="page-section">
            <div class="summary-grid">
                <article class="dashboard-card">
                    <h3>Total Users</h3>
                    <p class="card-value"><?php echo escape((string) $totalUsers); ?></p>
                    <p class="card-caption">Registered user account(s).</p>
                </article>
                <article class="dashboard-card">
                    <h3>Total Technicians</h3>
                    <p class="card-value"><?php echo escape((string) $totalTechnicians); ?></p>
                    <p class="card-caption">Technician accounts available for job handling.</p>
                </article>
                <article class="dashboard-card">
                    <h3>Total Services</h3>
                    <p class="card-value"><?php echo escape((string) $totalServices); ?></p>
                    <p class="card-caption">Service type(s) available in the FixNow Cars catalog.</p>
                </article>
                <article class="dashboard-card">
                    <h3>Total Requests</h3>
                    <p class="card-value"><?php echo escape((string) $totalRequests); ?></p>
                    <p class="card-caption">Service requests recorded across the platform.</p>
                </article>
            </div>
        </section>
    <?php endif; ?>

    <section class="page-section">
        <div class="card-grid">
            <article class="dashboard-card">
                <h3>Manage Users</h3>
                <p>Review user accounts and their contact details.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="users.php">Open Users</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>Manage Technicians</h3>
                <p>Review technician accounts and their contact details.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="technicians.php">Open Technicians</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>Manage Services</h3>
                <p>Add, update, and safely remove service items.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="services.php">Open Services</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>Monitor Requests</h3>
                <p>Review all requests, apply filters, and check their progress.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="requests.php">Open Requests</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>View Reports</h3>
                <p>See totals for completed jobs, cancelled jobs, revenue, and service demand.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="reports.php">Open Reports</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>Manage Receipts</h3>
                <p>Review receipts and create any missing ones for completed requests.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="receipts.php">Open Receipts</a>
                </div>
            </article>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
