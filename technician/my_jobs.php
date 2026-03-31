<?php
// Lists only the requests assigned to the logged-in technician.
$pageTitle = 'My Jobs';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('technician', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$jobs = [];
$technicianId = current_user_id();
$allowedStatuses = ['all', 'accepted', 'in_progress', 'completed', 'cancelled'];
$selectedStatus = $_GET['status'] ?? 'all';

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if ($selectedStatus === 'all') {
        $jobsStatement = $db->prepare(
            'SELECT
                sr.id,
                s.service_name,
                c.brand,
                c.model,
                c.plate_number,
                u.name,
                sr.status,
                sr.request_date,
                sr.estimated_price,
                sr.final_price
             FROM service_requests AS sr
             INNER JOIN services AS s ON sr.service_id = s.id
             INNER JOIN cars AS c ON sr.car_id = c.id
             INNER JOIN users AS u ON sr.user_id = u.id
             WHERE sr.technician_id = ?
             ORDER BY sr.created_at DESC'
        );

        if ($jobsStatement === false) {
            $pageError = 'Unable to load your assigned jobs.';
        } else {
            $jobsStatement->bind_param('i', $technicianId);
            $jobsStatement->execute();
        }
    } else {
        $jobsStatement = $db->prepare(
            'SELECT
                sr.id,
                s.service_name,
                c.brand,
                c.model,
                c.plate_number,
                u.name,
                sr.status,
                sr.request_date,
                sr.estimated_price,
                sr.final_price
             FROM service_requests AS sr
             INNER JOIN services AS s ON sr.service_id = s.id
             INNER JOIN cars AS c ON sr.car_id = c.id
             INNER JOIN users AS u ON sr.user_id = u.id
             WHERE sr.technician_id = ? AND sr.status = ?
             ORDER BY sr.created_at DESC'
        );

        if ($jobsStatement === false) {
            $pageError = 'Unable to load your assigned jobs.';
        } else {
            $jobsStatement->bind_param('is', $technicianId, $selectedStatus);
            $jobsStatement->execute();
        }
    }

    if ($pageError === '') {
        $jobsStatement->bind_result(
            $requestId,
            $serviceName,
            $carBrand,
            $carModel,
            $plateNumber,
            $userName,
            $status,
            $requestDate,
            $estimatedPrice,
            $finalPrice
        );

        while ($jobsStatement->fetch()) {
            $jobs[] = [
                'id' => $requestId,
                'service_name' => $serviceName,
                'car_label' => $carBrand . ' ' . $carModel . ' (' . $plateNumber . ')',
                'user_name' => $userName,
                'status' => $status,
                'request_date' => $requestDate,
                'estimated_price' => $estimatedPrice,
                'final_price' => $finalPrice,
            ];
        }

        $jobsStatement->close();
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
                <span class="section-tag">My Jobs</span>
                <h1>Assigned Service Requests</h1>
                <p>Review the jobs assigned to you and open each one to update its progress and notes.</p>
            </div>
            <span class="status-pill">Jobs Module</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="available_requests.php">Available Requests</a>
            <a class="btn btn-secondary" href="profile.php">My Profile</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <section class="content-card">
            <form class="auth-form" action="my_jobs.php" method="get">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="status">Filter by Status</label>
                        <select id="status" name="status">
                            <?php foreach ($allowedStatuses as $statusOption): ?>
                                <option value="<?php echo escape($statusOption); ?>" <?php echo $selectedStatus === $statusOption ? 'selected' : ''; ?>>
                                    <?php echo escape($statusOption === 'all' ? 'All Statuses' : format_label($statusOption)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn" type="submit">Apply Filter</button>
                    </div>
                </div>
            </form>

            <?php if ($selectedStatus !== 'all'): ?>
                <div class="dashboard-actions">
                    <a class="btn btn-secondary" href="my_jobs.php">Clear Filter</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($pageError === '' && empty($jobs)): ?>
        <div class="content-card empty-state">
            <p><?php echo escape($selectedStatus !== 'all' ? 'No jobs match the selected filter.' : 'You do not have any assigned jobs yet.'); ?></p>
            <div class="dashboard-actions">
                <a class="btn" href="available_requests.php">View Available Requests</a>
            </div>
        </div>
    <?php elseif ($pageError === ''): ?>
        <section class="content-card">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Car</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Estimated Price</th>
                            <th>Final Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo escape($job['service_name']); ?></td>
                                <td><?php echo escape($job['car_label']); ?></td>
                                <td><?php echo escape($job['user_name']); ?></td>
                                <td><?php render_status_badge($job['status']); ?></td>
                                <td><?php echo escape($job['request_date']); ?></td>
                                <td><?php echo escape($job['estimated_price'] !== null ? number_format((float) $job['estimated_price'], 2) : 'Not set'); ?></td>
                                <td><?php echo escape($job['final_price'] !== null ? number_format((float) $job['final_price'], 2) : 'Not set'); ?></td>
                                <td>
                                    <a class="btn btn-small" href="update_request.php?<?php echo escape(http_build_query(['id' => (int) $job['id'], 'status' => $selectedStatus])); ?>">Update</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
