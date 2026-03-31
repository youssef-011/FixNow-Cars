<?php
// Main dashboard for logged-in technicians.
$pageTitle = 'Technician Dashboard';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('technician', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$assignedJobsCount = 0;
$activeJobsCount = 0;
$availableRequestsCount = 0;
$technicianId = current_user_id();

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    $assignedStatement = $db->prepare(
        "SELECT
            COUNT(*),
            COALESCE(SUM(CASE WHEN status IN ('accepted', 'in_progress') THEN 1 ELSE 0 END), 0)
         FROM service_requests
         WHERE technician_id = ?"
    );

    if ($assignedStatement === false) {
        $pageError = 'Unable to load your jobs summary.';
    } else {
        $assignedStatement->bind_param('i', $technicianId);
        $assignedStatement->execute();
        $assignedStatement->bind_result($assignedJobsCount, $activeJobsCount);
        $assignedStatement->fetch();
        $assignedStatement->close();
    }

    if ($pageError === '') {
        $availableStatement = $db->prepare(
            "SELECT COUNT(*)
             FROM service_requests
             WHERE technician_id IS NULL AND status = 'pending'"
        );

        if ($availableStatement === false) {
            $pageError = 'Unable to load available requests.';
        } else {
            $availableStatement->execute();
            $availableStatement->bind_result($availableRequestsCount);
            $availableStatement->fetch();
            $availableStatement->close();
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
                <span class="section-tag">Technician Area</span>
                <h1>Welcome, <?php echo escape(current_user_name()); ?></h1>
                <p>Use this dashboard to review available service requests and manage the jobs assigned to you.</p>
            </div>
            <span class="status-pill">Role: Technician</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="profile.php">My Profile</a>
            <a class="btn btn-secondary" href="available_requests.php">Available Requests</a>
            <a class="btn btn-secondary" href="my_jobs.php">My Jobs</a>
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
                    <h3>Assigned Jobs</h3>
                    <p class="card-value"><?php echo escape((string) $assignedJobsCount); ?></p>
                    <p class="card-caption">Request(s) assigned to your technician account.</p>
                </article>
                <article class="dashboard-card">
                    <h3>Active Jobs</h3>
                    <p class="card-value"><?php echo escape((string) $activeJobsCount); ?></p>
                    <p class="card-caption">Job(s) that are accepted or still in progress.</p>
                </article>
                <article class="dashboard-card">
                    <h3>Available Requests</h3>
                    <p class="card-value"><?php echo escape((string) $availableRequestsCount); ?></p>
                    <p class="card-caption">Pending request(s) are still available for technicians to accept.</p>
                </article>
            </div>
        </section>
    <?php endif; ?>

    <section class="page-section">
        <div class="card-grid">
            <article class="dashboard-card">
                <h3>Available Requests</h3>
                <p>Review pending service requests that do not yet have a technician assigned.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="available_requests.php">Open Requests</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>My Jobs</h3>
                <p>Track the requests assigned to you and update their progress and pricing details.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="my_jobs.php">Open My Jobs</a>
                </div>
            </article>
            <article class="dashboard-card">
                <h3>Profile</h3>
                <p>Keep your technician account information updated so admins and users can identify you correctly.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="profile.php">Open Profile</a>
                </div>
            </article>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
