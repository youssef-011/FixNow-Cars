<?php
// Shows the full details for one service request so the admin can inspect the workflow clearly.
$pageTitle = 'Request Details';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('admin', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selectedStatus = $_GET['status'] ?? 'all';
$searchTerm = normalize_search_term($_GET['q'] ?? '', 100);
$allowedStatuses = ['all', 'pending', 'accepted', 'in_progress', 'completed', 'cancelled'];
$requestDetails = null;
$backLink = 'requests.php';

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

$backParameters = [];

if ($selectedStatus !== 'all') {
    $backParameters['status'] = $selectedStatus;
}

if ($searchTerm !== '') {
    $backParameters['q'] = $searchTerm;
}

if (!empty($backParameters)) {
    $backLink .= '?' . http_build_query($backParameters);
}

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
            u.name,
            u.email,
            u.phone,
            tech.name,
            tech.email,
            tech.phone,
            r.amount,
            r.payment_status,
            r.issued_at
         FROM service_requests AS sr
         INNER JOIN services AS s ON sr.service_id = s.id
         INNER JOIN cars AS c ON sr.car_id = c.id
         INNER JOIN users AS u ON sr.user_id = u.id
         LEFT JOIN users AS tech ON sr.technician_id = tech.id
         LEFT JOIN receipts AS r ON r.request_id = sr.id
         WHERE sr.id = ?
         LIMIT 1'
    );

    if ($detailsStatement === false) {
        $pageError = 'Unable to load the request details.';
    } else {
        $detailsStatement->bind_param('i', $requestId);
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
            $userName,
            $userEmail,
            $userPhone,
            $technicianName,
            $technicianEmail,
            $technicianPhone,
            $receiptAmount,
            $paymentStatus,
            $issuedAt
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
            'user_name' => $userName,
            'user_email' => $userEmail,
            'user_phone' => $userPhone,
            'technician_name' => $technicianName,
            'technician_email' => $technicianEmail,
            'technician_phone' => $technicianPhone,
            'receipt_amount' => $receiptAmount,
            'payment_status' => $paymentStatus,
            'issued_at' => $issuedAt,
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
                <p>Inspect the full workflow details for this service request.</p>
            </div>
            <?php if ($requestDetails !== null): ?>
                <?php render_status_badge($requestDetails['status']); ?>
            <?php else: ?>
                <span class="status-pill">Details</span>
            <?php endif; ?>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="<?php echo escape($backLink); ?>">Back to Requests</a>
            <a class="btn btn-secondary" href="receipts.php">Receipts</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php elseif ($requestDetails !== null): ?>
        <section class="detail-grid">
            <article class="content-card">
                <h2>Request Summary</h2>
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
                <h2>User and Technician</h2>
                <div class="detail-list">
                    <div class="detail-item">
                        <strong>User</strong>
                        <span><?php echo escape($requestDetails['user_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>User Email</strong>
                        <span><?php echo escape($requestDetails['user_email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>User Phone</strong>
                        <span><?php echo escape($requestDetails['user_phone'] !== null && $requestDetails['user_phone'] !== '' ? $requestDetails['user_phone'] : 'Not set'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Technician</strong>
                        <span><?php echo escape($requestDetails['technician_name'] !== null ? $requestDetails['technician_name'] : 'Not assigned yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Technician Email</strong>
                        <span><?php echo escape($requestDetails['technician_email'] !== null ? $requestDetails['technician_email'] : 'Not available'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Technician Phone</strong>
                        <span><?php echo escape($requestDetails['technician_phone'] !== null ? $requestDetails['technician_phone'] : 'Not available'); ?></span>
                    </div>
                </div>
            </article>
        </section>

        <section class="detail-grid">
            <article class="content-card">
                <h2>Notes and Pricing</h2>
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
                        <strong>Estimated Price</strong>
                        <span><?php echo escape($requestDetails['estimated_price'] !== null ? number_format((float) $requestDetails['estimated_price'], 2) : 'Not set yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Final Price</strong>
                        <span><?php echo escape($requestDetails['final_price'] !== null ? number_format((float) $requestDetails['final_price'], 2) : 'Not set yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Admin Notes</strong>
                        <span><?php echo escape($requestDetails['admin_notes'] !== null && $requestDetails['admin_notes'] !== '' ? $requestDetails['admin_notes'] : 'No admin notes yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Technician Notes</strong>
                        <span><?php echo escape($requestDetails['technician_notes'] !== null && $requestDetails['technician_notes'] !== '' ? $requestDetails['technician_notes'] : 'No technician notes yet'); ?></span>
                    </div>
                </div>
            </article>

            <article class="content-card">
                <h2>Receipt Information</h2>
                <div class="detail-list">
                    <div class="detail-item">
                        <strong>Receipt Amount</strong>
                        <span><?php echo escape($requestDetails['receipt_amount'] !== null ? number_format((float) $requestDetails['receipt_amount'], 2) : 'Receipt not created yet'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Payment Status</strong>
                        <?php if ($requestDetails['payment_status'] !== null): ?>
                            <?php render_status_badge($requestDetails['payment_status']); ?>
                        <?php else: ?>
                            <span>Not available</span>
                        <?php endif; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Issued At</strong>
                        <span><?php echo escape($requestDetails['issued_at'] !== null ? $requestDetails['issued_at'] : 'Not issued yet'); ?></span>
                    </div>
                </div>
            </article>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
