<?php
// Main dashboard for logged-in users.
$pageTitle = 'User Dashboard';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('user', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$carCount = 0;
$requestCount = 0;
$pendingCount = 0;
$userId = current_user_id();

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    $carStatement = $db->prepare('SELECT COUNT(*) FROM cars WHERE user_id = ?');

    if ($carStatement === false) {
        $pageError = 'Unable to load your cars summary.';
    } else {
        $carStatement->bind_param('i', $userId);
        $carStatement->execute();
        $carStatement->bind_result($carCount);
        $carStatement->fetch();
        $carStatement->close();
    }

    if ($pageError === '') {
        $requestStatement = $db->prepare(
            "SELECT COUNT(*), COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0)
             FROM service_requests
             WHERE user_id = ?"
        );

        if ($requestStatement === false) {
            $pageError = 'Unable to load your request summary.';
        } else {
            $requestStatement->bind_param('i', $userId);
            $requestStatement->execute();
            $requestStatement->bind_result($requestCount, $pendingCount);
            $requestStatement->fetch();
            $requestStatement->close();
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
                <span class="section-tag">User Area</span>
                <h1>Welcome, <?php echo escape(current_user_name()); ?></h1>
                <p>Manage your profile, cars, and service requests from one place.</p>
            </div>
            <span class="status-pill">Role: User</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="profile.php">My Profile</a>
            <a class="btn btn-secondary" href="my_cars.php">My Cars</a>
            <a class="btn btn-secondary" href="request_service.php">Request Service</a>
            <a class="btn btn-secondary" href="my_requests.php">My Requests</a>
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
                    <h3>My Cars</h3>
                    <p class="card-value"><?php echo escape((string) $carCount); ?></p>
                    <p class="card-caption">Saved cars ready for future service requests.</p>
                </article>
                <article class="dashboard-card">
                    <h3>All Requests</h3>
                    <p class="card-value"><?php echo escape((string) $requestCount); ?></p>
                    <p class="card-caption">Service requests you have submitted.</p>
                </article>
                <article class="dashboard-card">
                    <h3>Pending Requests</h3>
                    <p class="card-value"><?php echo escape((string) $pendingCount); ?></p>
                    <p class="card-caption">Requests still waiting for technician action.</p>
                </article>
            </div>
        </section>
    <?php endif; ?>

    <section class="page-section">
        <div class="card-grid">
            <article class="dashboard-card">
                <h3>Profile</h3>
                <p>Review and update your contact information.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="profile.php">Open Profile</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>Cars</h3>
                <p>Add cars, update details, and keep your vehicle list organized.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="my_cars.php">Manage Cars</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>Request Service</h3>
                <p>Create a new service request by selecting one of your cars and a service type.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="request_service.php">New Request</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>My Requests</h3>
                <p>Track your service requests and open the details of each one.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="my_requests.php">View Requests</a>
                </div>
            </article>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
