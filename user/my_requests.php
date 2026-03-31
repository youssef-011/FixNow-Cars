<?php
// Shows all service requests that belong to the logged-in user.
$pageTitle = 'My Requests';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('user', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$requests = [];
$userId = current_user_id();
$allowedStatuses = ['all', 'pending', 'accepted', 'in_progress', 'completed', 'cancelled'];
$selectedStatus = $_GET['status'] ?? 'all';

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if (is_post_request()) {
        $action = $_POST['action'] ?? '';
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $returnStatus = $_POST['current_status'] ?? 'all';

        if (!in_array($returnStatus, $allowedStatuses, true)) {
            $returnStatus = 'all';
        }

        $redirectTarget = 'my_requests.php' . ($returnStatus !== 'all' ? '?status=' . urlencode($returnStatus) : '');

        if ($action !== 'cancel_request') {
            set_flash_message('Invalid request action received.', 'error');
            redirect($redirectTarget);
        }

        if ($requestId <= 0) {
            set_flash_message('Invalid request selected.', 'error');
            redirect($redirectTarget);
        }

        // Users may cancel only requests that are still pending and unassigned.
        $cancelStatement = $db->prepare(
            "UPDATE service_requests
             SET status = 'cancelled'
             WHERE id = ?
               AND user_id = ?
               AND status = 'pending'
               AND technician_id IS NULL"
        );

        if ($cancelStatement === false) {
            set_flash_message('Unable to prepare the cancel query.', 'error');
            redirect($redirectTarget);
        }

        $cancelStatement->bind_param('ii', $requestId, $userId);
        $cancelStatement->execute();
        $updatedRows = $cancelStatement->affected_rows;
        $cancelStatement->close();

        if ($updatedRows > 0) {
            set_flash_message('Request cancelled successfully.', 'success');
        } else {
            set_flash_message('Only pending and unassigned requests can be cancelled.', 'error');
        }

        redirect($redirectTarget);
    }

    if ($selectedStatus === 'all') {
        $requestsStatement = $db->prepare(
            'SELECT
                sr.id,
                s.service_name,
                c.brand,
                c.model,
                c.plate_number,
                sr.status,
                sr.request_date,
                sr.technician_id,
                tech.name
             FROM service_requests AS sr
             INNER JOIN services AS s ON sr.service_id = s.id
             INNER JOIN cars AS c ON sr.car_id = c.id
             LEFT JOIN users AS tech ON sr.technician_id = tech.id
             WHERE sr.user_id = ?
             ORDER BY sr.created_at DESC'
        );

        if ($requestsStatement === false) {
            $pageError = 'Unable to load your service requests.';
        } else {
            $requestsStatement->bind_param('i', $userId);
            $requestsStatement->execute();
        }
    } else {
        $requestsStatement = $db->prepare(
            'SELECT
                sr.id,
                s.service_name,
                c.brand,
                c.model,
                c.plate_number,
                sr.status,
                sr.request_date,
                sr.technician_id,
                tech.name
             FROM service_requests AS sr
             INNER JOIN services AS s ON sr.service_id = s.id
             INNER JOIN cars AS c ON sr.car_id = c.id
             LEFT JOIN users AS tech ON sr.technician_id = tech.id
             WHERE sr.user_id = ? AND sr.status = ?
             ORDER BY sr.created_at DESC'
        );

        if ($requestsStatement === false) {
            $pageError = 'Unable to load your service requests.';
        } else {
            $requestsStatement->bind_param('is', $userId, $selectedStatus);
            $requestsStatement->execute();
        }
    }

    if ($pageError === '') {
        $requestsStatement->bind_result(
            $requestId,
            $serviceName,
            $carBrand,
            $carModel,
            $plateNumber,
            $status,
            $requestDate,
            $technicianId,
            $technicianName
        );

        while ($requestsStatement->fetch()) {
            $requests[] = [
                'id' => $requestId,
                'service_name' => $serviceName,
                'car_label' => $carBrand . ' ' . $carModel . ' (' . $plateNumber . ')',
                'status' => $status,
                'request_date' => $requestDate,
                'technician_id' => $technicianId,
                'technician_name' => $technicianName,
            ];
        }

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
                <span class="section-tag">My Requests</span>
                <h1>Service Request History</h1>
                <p>Review all of your submitted service requests and check their current progress.</p>
            </div>
            <span class="status-pill">Requests Module</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="my_cars.php">My Cars</a>
            <a class="btn" href="request_service.php">Request Service</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <section class="content-card">
            <form class="auth-form" action="my_requests.php" method="get">
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
                    <a class="btn btn-secondary" href="my_requests.php">Clear Filter</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($pageError === '' && empty($requests)): ?>
        <div class="content-card empty-state">
            <p><?php echo escape($selectedStatus !== 'all' ? 'No requests match the selected filter.' : 'You have not created any service requests yet.'); ?></p>
            <div class="dashboard-actions">
                <a class="btn" href="request_service.php">Create Your First Request</a>
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
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Assigned Technician</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo escape($request['service_name']); ?></td>
                                <td><?php echo escape($request['car_label']); ?></td>
                                <td><?php render_status_badge($request['status']); ?></td>
                                <td><?php echo escape($request['request_date']); ?></td>
                                <td><?php echo escape($request['technician_name'] !== null ? $request['technician_name'] : 'Not assigned yet'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn btn-small" href="request_details.php?<?php echo escape(http_build_query(['id' => (int) $request['id'], 'status' => $selectedStatus])); ?>">View Details</a>
                                        <?php if ($request['status'] === 'pending' && $request['technician_id'] === null): ?>
                                            <form action="my_requests.php" method="post">
                                                <input type="hidden" name="action" value="cancel_request">
                                                <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo escape($selectedStatus); ?>">
                                                <button class="btn btn-danger btn-small" type="submit">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
