<?php
// Shows simple aggregate reports for admins using course-level SQL queries.
$pageTitle = 'Reports';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('admin', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$totalCompletedRequests = 0;
$totalCancelledRequests = 0;
$totalRevenue = 0;
$mostRequestedServices = [];

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    $completedStatement = $db->prepare("SELECT COUNT(*) FROM service_requests WHERE status = 'completed'");
    $cancelledStatement = $db->prepare("SELECT COUNT(*) FROM service_requests WHERE status = 'cancelled'");
    $revenueStatement = $db->prepare(
        "SELECT COALESCE(SUM(final_price), 0)
         FROM service_requests
         WHERE status = 'completed' AND final_price IS NOT NULL"
    );

    if ($completedStatement === false || $cancelledStatement === false || $revenueStatement === false) {
        $pageError = 'Unable to load the report summary.';
    } else {
        // These aggregate queries keep the report simple and easy to explain.
        $completedStatement->execute();
        $completedStatement->bind_result($totalCompletedRequests);
        $completedStatement->fetch();
        $completedStatement->close();

        $cancelledStatement->execute();
        $cancelledStatement->bind_result($totalCancelledRequests);
        $cancelledStatement->fetch();
        $cancelledStatement->close();

        $revenueStatement->execute();
        $revenueStatement->bind_result($totalRevenue);
        $revenueStatement->fetch();
        $revenueStatement->close();
    }

    if ($pageError === '') {
        $servicesStatement = $db->prepare(
            'SELECT
                s.service_name,
                COUNT(sr.id) AS total_requests
             FROM services AS s
             LEFT JOIN service_requests AS sr ON sr.service_id = s.id
             GROUP BY s.id, s.service_name
             ORDER BY total_requests DESC, s.service_name ASC'
        );

        if ($servicesStatement === false) {
            $pageError = 'Unable to load the service report.';
        } else {
            $servicesStatement->execute();
            $servicesStatement->bind_result($serviceName, $totalRequests);

            while ($servicesStatement->fetch()) {
                $mostRequestedServices[] = [
                    'service_name' => $serviceName,
                    'total_requests' => $totalRequests,
                ];
            }

            $servicesStatement->close();
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
                <span class="section-tag">Reports</span>
                <h1>System Reports</h1>
                <p>Review simple request totals, total revenue, and the most requested services.</p>
            </div>
            <span class="status-pill">Admin Reports</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="users.php">Users</a>
            <a class="btn btn-secondary" href="technicians.php">Technicians</a>
            <a class="btn btn-secondary" href="services.php">Services</a>
            <a class="btn btn-secondary" href="requests.php">Requests</a>
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
                    <h3>Completed Requests</h3>
                    <p>Total completed requests: <strong><?php echo escape((string) $totalCompletedRequests); ?></strong></p>
                </article>
                <article class="dashboard-card">
                    <h3>Cancelled Requests</h3>
                    <p>Total cancelled requests: <strong><?php echo escape((string) $totalCancelledRequests); ?></strong></p>
                </article>
                <article class="dashboard-card">
                    <h3>Total Revenue</h3>
                    <p>Revenue from completed requests: <strong><?php echo escape(number_format((float) $totalRevenue, 2)); ?></strong></p>
                </article>
            </div>
        </section>

        <section class="content-card">
            <h2>Most Requested Services</h2>

            <?php if (empty($mostRequestedServices)): ?>
                <div class="empty-state">
                    <p>No services are available for reporting yet.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Total Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mostRequestedServices as $serviceReport): ?>
                                <tr>
                                    <td><?php echo escape($serviceReport['service_name']); ?></td>
                                    <td><?php echo escape((string) $serviceReport['total_requests']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
