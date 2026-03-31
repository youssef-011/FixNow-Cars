<?php
// Updates one service request assigned to the logged-in technician.
$pageTitle = 'Update Request';
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
$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$allowedStatuses = ['accepted', 'in_progress', 'completed', 'cancelled'];
$allowedJobFilters = ['all', 'accepted', 'in_progress', 'completed', 'cancelled'];
$returnStatus = $_GET['status'] ?? 'all';
$status = '';
$estimatedPrice = '';
$finalPrice = '';
$technicianNotes = '';
$requestInfo = null;

if (!in_array($returnStatus, $allowedJobFilters, true)) {
    $returnStatus = 'all';
}

$backLink = 'my_jobs.php' . ($returnStatus !== 'all' ? '?status=' . urlencode($returnStatus) : '');

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
            sr.status,
            sr.estimated_price,
            sr.final_price,
            sr.technician_notes,
            sr.request_date,
            sr.problem_description,
            sr.location,
            s.service_name,
            c.brand,
            c.model,
            c.plate_number,
            u.name
         FROM service_requests AS sr
         INNER JOIN services AS s ON sr.service_id = s.id
         INNER JOIN cars AS c ON sr.car_id = c.id
         INNER JOIN users AS u ON sr.user_id = u.id
         WHERE sr.id = ? AND sr.technician_id = ?
         LIMIT 1'
    );

    if ($detailsStatement === false) {
        $pageError = 'Unable to load the selected request.';
    } else {
        $detailsStatement->bind_param('ii', $requestId, $technicianId);
        $detailsStatement->execute();
        $detailsStatement->store_result();

        if ($detailsStatement->num_rows !== 1) {
            $detailsStatement->close();
            set_flash_message('Request not found or not assigned to you.', 'error');
            redirect($backLink);
        }

        $detailsStatement->bind_result(
            $loadedRequestId,
            $savedStatus,
            $savedEstimatedPrice,
            $savedFinalPrice,
            $savedTechnicianNotes,
            $requestDate,
            $problemDescription,
            $location,
            $serviceName,
            $carBrand,
            $carModel,
            $plateNumber,
            $userName
        );
        $detailsStatement->fetch();
        $detailsStatement->close();

        $requestInfo = [
            'service_name' => $serviceName,
            'car_label' => $carBrand . ' ' . $carModel . ' (' . $plateNumber . ')',
            'user_name' => $userName,
            'request_date' => $requestDate,
            'problem_description' => $problemDescription,
            'location' => $location,
        ];

        if (is_post_request()) {
            $status = trim($_POST['status'] ?? '');
            $estimatedPrice = trim($_POST['estimated_price'] ?? '');
            $finalPrice = trim($_POST['final_price'] ?? '');
            $technicianNotes = trim($_POST['technician_notes'] ?? '');

            validate_allowed_value($errors, $status, $allowedStatuses, 'Please choose a valid status.');
            validate_non_negative_decimal($errors, $estimatedPrice, 'Estimated price', false);
            validate_non_negative_decimal($errors, $finalPrice, 'Final price', false);
            validate_max_length($errors, $technicianNotes, 'Technician notes', 1500);

            if ($status === 'completed' && is_blank($finalPrice)) {
                $errors[] = 'Final price is required when marking a request as completed.';
            }

            if (empty($errors)) {
                $updateStatement = $db->prepare(
                    'UPDATE service_requests
                     SET
                        status = ?,
                        estimated_price = IF(? = "", NULL, ?),
                        final_price = IF(? = "", NULL, ?),
                        technician_notes = ?
                     WHERE id = ? AND technician_id = ?'
                );

                if ($updateStatement === false) {
                    $errors[] = 'Unable to prepare the update query.';
                } else {
                    $updateStatement->bind_param(
                        'ssssssii',
                        $status,
                        $estimatedPrice,
                        $estimatedPrice,
                        $finalPrice,
                        $finalPrice,
                        $technicianNotes,
                        $requestId,
                        $technicianId
                    );

                    if ($updateStatement->execute()) {
                        $updateStatement->close();

                        $successMessage = 'Request updated successfully.';

                        if ($status === 'completed' && !is_blank($finalPrice)) {
                            $receiptResult = create_receipt_if_needed($db, $requestId);

                            if ($receiptResult['created']) {
                                $successMessage .= ' Receipt generated automatically.';
                            } elseif (!empty($receiptResult['updated'])) {
                                $successMessage .= ' Receipt amount was updated automatically.';
                            } elseif ($receiptResult['exists']) {
                                $successMessage .= ' Receipt already exists for this request.';
                            } elseif (!$receiptResult['success']) {
                                $successMessage .= ' The receipt still needs to be reviewed by admin.';
                            }
                        }

                        set_flash_message($successMessage, 'success');
                        redirect($backLink);
                    }

                    $updateStatement->close();
                    $errors[] = 'Unable to update this request. Please try again.';
                }
            }
        } else {
            $status = $savedStatus;
            $estimatedPrice = $savedEstimatedPrice !== null ? (string) $savedEstimatedPrice : '';
            $finalPrice = $savedFinalPrice !== null ? (string) $savedFinalPrice : '';
            $technicianNotes = $savedTechnicianNotes ?? '';
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
                <span class="section-tag">Update Request</span>
                <h1>Request #<?php echo escape((string) $requestId); ?></h1>
                <p>Update the progress, pricing details, and technician notes for this assigned request.</p>
            </div>
            <?php if ($status !== ''): ?>
                <?php render_status_badge($status); ?>
            <?php else: ?>
                <span class="status-pill">Assigned Request</span>
            <?php endif; ?>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="<?php echo escape($backLink); ?>">Back to My Jobs</a>
            <a class="btn btn-secondary" href="available_requests.php">Available Requests</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php elseif ($requestInfo !== null): ?>
        <?php render_message_box($errors, 'error'); ?>

        <section class="detail-grid">
            <article class="content-card">
                <h2>Request Information</h2>
                <div class="detail-list">
                    <div class="detail-item">
                        <strong>User</strong>
                        <span><?php echo escape($requestInfo['user_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Service</strong>
                        <span><?php echo escape($requestInfo['service_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Car</strong>
                        <span><?php echo escape($requestInfo['car_label']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Request Date</strong>
                        <span><?php echo escape($requestInfo['request_date']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Current Status</strong>
                        <?php render_status_badge($status); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Location</strong>
                        <span><?php echo escape($requestInfo['location']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Problem Description</strong>
                        <span><?php echo escape($requestInfo['problem_description']); ?></span>
                    </div>
                </div>
            </article>

            <article class="content-card">
                <h2>Update Job</h2>

                <form class="auth-form" action="update_request.php?<?php echo escape(http_build_query(['id' => (int) $requestId, 'status' => $returnStatus])); ?>" method="post">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <?php foreach ($allowedStatuses as $allowedStatus): ?>
                                <option value="<?php echo escape($allowedStatus); ?>" <?php echo $status === $allowedStatus ? 'selected' : ''; ?>>
                                    <?php echo escape(format_label($allowedStatus)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="estimated_price">Estimated Price</label>
                        <input type="number" step="0.01" min="0" id="estimated_price" name="estimated_price" value="<?php echo escape($estimatedPrice); ?>" placeholder="Optional estimated price">
                    </div>

                    <div class="form-group">
                        <label for="final_price">Final Price</label>
                        <input type="number" step="0.01" min="0" id="final_price" name="final_price" value="<?php echo escape($finalPrice); ?>" placeholder="Optional final price">
                    </div>

                    <div class="form-group">
                        <label for="technician_notes">Technician Notes</label>
                        <textarea id="technician_notes" name="technician_notes" rows="6" placeholder="Add your repair notes or progress details"><?php echo escape($technicianNotes); ?></textarea>
                    </div>

                    <button class="btn full-width" type="submit">Save Update</button>
                </form>
            </article>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
