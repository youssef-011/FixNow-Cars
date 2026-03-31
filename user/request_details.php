<?php
// Shows the full details for one service request owned by the logged-in user.
$pageTitle = 'Request Details';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('user', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$userId = current_user_id();
$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selectedStatus = $_GET['status'] ?? 'all';
$allowedStatuses = ['all', 'pending', 'accepted', 'in_progress', 'completed', 'cancelled'];
$requestDetails = null;

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

$backLink = 'my_requests.php' . ($selectedStatus !== 'all' ? '?status=' . urlencode($selectedStatus) : '');

if ($requestId <= 0) {
    set_flash_message('Invalid request selected.', 'error');
    redirect($backLink);
}

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    $detailsStatement = $db->prepare(
        'SELECT
            sr.id,
            sr.problem_description,
            sr.location,
            sr.request_date,
            sr.status,
            sr.estimated_price,
            sr.final_price,
            sr.admin_notes,
            sr.technician_notes,
            sr.created_at,
            s.service_name,
            s.description,
            c.brand,
            c.model,
            c.year,
            c.plate_number,
            c.color,
            tech.name,
            tech.phone
         FROM service_requests AS sr
         INNER JOIN services AS s ON sr.service_id = s.id
         INNER JOIN cars AS c ON sr.car_id = c.id
         LEFT JOIN users AS tech ON sr.technician_id = tech.id
         WHERE sr.id = ? AND sr.user_id = ?
         LIMIT 1'
    );

    if ($detailsStatement === false) {
        $pageError = 'Unable to load the request details.';
    } else {
        $detailsStatement->bind_param('ii', $requestId, $userId);
        $detailsStatement->execute();
        $detailsStatement->store_result();

        if ($detailsStatement->num_rows !== 1) {
            $detailsStatement->close();
            set_flash_message('Request not found.', 'error');
            redirect($backLink);
        }

        $detailsStatement->bind_result(
            $loadedRequestId,
            $problemDescription,
            $location,
            $requestDate,
            $status,
            $estimatedPrice,
            $finalPrice,
            $adminNotes,
            $technicianNotes,
            $createdAt,
            $serviceName,
            $serviceDescription,
            $carBrand,
            $carModel,
            $carYear,
            $plateNumber,
            $carColor,
            $technicianName,
            $technicianPhone
        );
        $detailsStatement->fetch();
        $detailsStatement->close();

        $requestDetails = [
            'service_name' => $serviceName,
            'service_description' => $serviceDescription,
            'car_label' => $carBrand . ' ' . $carModel . ' (' . $plateNumber . ')',
            'car_year' => $carYear,
            'car_color' => $carColor,
            'problem_description' => $problemDescription,
            'location' => $location,
            'request_date' => $requestDate,
            'status' => $status,
            'estimated_price' => $estimatedPrice,
            'final_price' => $finalPrice,
            'admin_notes' => $adminNotes,
            'technician_notes' => $technicianNotes,
            'created_at' => $createdAt,
            'technician_name' => $technicianName,
            'technician_phone' => $technicianPhone,
        ];
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
                <span class="section-tag">Request Details</span>
                <h1>Request #<?php echo escape((string) $requestId); ?></h1>
                <p>Review the full information for this service request.</p>
            </div>
            <?php if ($requestDetails !== null): ?>
                <?php render_status_badge($requestDetails['status']); ?>
            <?php else: ?>
                <span class="status-pill">Details</span>
            <?php endif; ?>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="<?php echo escape($backLink); ?>">Back to My Requests</a>
            <a class="btn btn-secondary" href="request_service.php">New Request</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php elseif ($requestDetails !== null): ?>
        <section class="detail-grid">
            <article class="content-card">
                <h2>Service Information</h2>
                <div class="detail-list">
                    <div class="detail-item">
                        <strong>Service</strong>
                        <span><?php echo escape($requestDetails['service_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Service Description</strong>
                        <span><?php echo escape($requestDetails['service_description'] !== null ? $requestDetails['service_description'] : 'No description'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Car</strong>
                        <span><?php echo escape($requestDetails['car_label']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Year</strong>
                        <span><?php echo escape((string) $requestDetails['car_year']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Color</strong>
                        <span><?php echo escape($requestDetails['car_color'] !== null && $requestDetails['car_color'] !== '' ? $requestDetails['car_color'] : 'Not set'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Request Date</strong>
                        <span><?php echo escape($requestDetails['request_date']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Status</strong>
                        <?php render_status_badge($requestDetails['status']); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Created At</strong>
                        <span><?php echo escape(date('Y-m-d H:i', strtotime($requestDetails['created_at']))); ?></span>
                    </div>
                </div>
            </article>

            <article class="content-card">
                <h2>Request Notes</h2>
                <div class="detail-list">
                    <div class="detail-item">
                        <strong>Problem Description</strong>
                        <span><?php echo escape($requestDetails['problem_description']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Location</strong>
                        <span><?php echo escape($requestDetails['location']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Assigned Technician</strong>
                        <span><?php echo escape($requestDetails['technician_name'] !== null ? $requestDetails['technician_name'] : 'Not assigned yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Technician Phone</strong>
                        <span><?php echo escape($requestDetails['technician_phone'] !== null ? $requestDetails['technician_phone'] : 'Not available'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Admin Notes</strong>
                        <span><?php echo escape($requestDetails['admin_notes'] !== null && $requestDetails['admin_notes'] !== '' ? $requestDetails['admin_notes'] : 'No admin notes yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Technician Notes</strong>
                        <span><?php echo escape($requestDetails['technician_notes'] !== null && $requestDetails['technician_notes'] !== '' ? $requestDetails['technician_notes'] : 'No technician notes yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Estimated Price</strong>
                        <span><?php echo escape($requestDetails['estimated_price'] !== null ? number_format((float) $requestDetails['estimated_price'], 2) : 'Not set yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Final Price</strong>
                        <span><?php echo escape($requestDetails['final_price'] !== null ? number_format((float) $requestDetails['final_price'], 2) : 'Not set yet'); ?></span>
                    </div>
                </div>
            </article>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
