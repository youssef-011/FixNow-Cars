<?php
// Lists saved receipts and lets the admin create a receipt for completed jobs when needed.
$pageTitle = 'Receipts';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('admin', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$receipts = [];
$pendingReceiptRequests = [];

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if (is_post_request()) {
        $action = $_POST['action'] ?? '';

        if ($action === 'generate_receipt') {
            $requestId = (int) ($_POST['request_id'] ?? 0);

            if ($requestId <= 0) {
                set_flash_message('Invalid request selected for receipt generation.', 'error');
                redirect('receipts.php');
            }

            $receiptResult = create_receipt_if_needed($db, $requestId);

            if ($receiptResult['created']) {
                set_flash_message('Receipt generated successfully.', 'success');
            } elseif (!empty($receiptResult['updated'])) {
                set_flash_message('Receipt amount updated successfully.', 'success');
            } elseif ($receiptResult['exists']) {
                set_flash_message('Receipt already exists for this request.', 'success');
            } else {
                set_flash_message($receiptResult['message'], 'error');
            }

            redirect('receipts.php');
        }
    }

    // Load all saved receipts that belong to completed service requests.
    $receiptsStatement = $db->prepare(
        "SELECT
            r.request_id,
            u.name,
            tech.name,
            s.service_name,
            r.amount,
            r.payment_status,
            r.issued_at
         FROM receipts AS r
         INNER JOIN service_requests AS sr ON r.request_id = sr.id
         INNER JOIN users AS u ON sr.user_id = u.id
         LEFT JOIN users AS tech ON sr.technician_id = tech.id
         INNER JOIN services AS s ON sr.service_id = s.id
         WHERE sr.status = 'completed'
         ORDER BY r.issued_at DESC"
    );

    if ($receiptsStatement === false) {
        $pageError = 'Unable to load receipts.';
    } else {
        $receiptsStatement->execute();
        $receiptsStatement->bind_result(
            $requestId,
            $userName,
            $technicianName,
            $serviceName,
            $amount,
            $paymentStatus,
            $issuedAt
        );

        while ($receiptsStatement->fetch()) {
            $receipts[] = [
                'request_id' => $requestId,
                'user_name' => $userName,
                'technician_name' => $technicianName,
                'service_name' => $serviceName,
                'amount' => $amount,
                'payment_status' => $paymentStatus,
                'issued_at' => $issuedAt,
            ];
        }

        $receiptsStatement->close();
    }

    if ($pageError === '') {
        // These are completed requests that still need one receipt record.
        $pendingReceiptsStatement = $db->prepare(
            "SELECT
                sr.id,
                u.name,
                tech.name,
                s.service_name,
                sr.final_price,
                sr.request_date
             FROM service_requests AS sr
             INNER JOIN users AS u ON sr.user_id = u.id
             LEFT JOIN users AS tech ON sr.technician_id = tech.id
             INNER JOIN services AS s ON sr.service_id = s.id
             LEFT JOIN receipts AS r ON r.request_id = sr.id
             WHERE sr.status = 'completed'
               AND sr.final_price IS NOT NULL
               AND r.id IS NULL
             ORDER BY sr.created_at DESC"
        );

        if ($pendingReceiptsStatement === false) {
            $pageError = 'Unable to load completed requests for receipt generation.';
        } else {
            $pendingReceiptsStatement->execute();
            $pendingReceiptsStatement->bind_result(
                $pendingRequestId,
                $pendingUserName,
                $pendingTechnicianName,
                $pendingServiceName,
                $pendingFinalPrice,
                $pendingRequestDate
            );

            while ($pendingReceiptsStatement->fetch()) {
                $pendingReceiptRequests[] = [
                    'request_id' => $pendingRequestId,
                    'user_name' => $pendingUserName,
                    'technician_name' => $pendingTechnicianName,
                    'service_name' => $pendingServiceName,
                    'final_price' => $pendingFinalPrice,
                    'request_date' => $pendingRequestDate,
                ];
            }

            $pendingReceiptsStatement->close();
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
                <span class="section-tag">Receipts</span>
                <h1>Receipts and Payments</h1>
                <p>Review saved receipts and generate any missing ones for completed requests when needed.</p>
            </div>
            <span class="status-pill">Admin Records</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="users.php">Users</a>
            <a class="btn btn-secondary" href="technicians.php">Technicians</a>
            <a class="btn btn-secondary" href="requests.php">Requests</a>
            <a class="btn btn-secondary" href="services.php">Services</a>
            <a class="btn btn-secondary" href="reports.php">Reports</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <section class="content-card">
            <h2>Saved Receipts</h2>

            <?php if (empty($receipts)): ?>
                <div class="empty-state">
                    <p>No receipts have been generated yet.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>User</th>
                                <th>Technician</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Payment Status</th>
                                <th>Issued At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($receipts as $receipt): ?>
                                <tr>
                                    <td><?php echo escape((string) $receipt['request_id']); ?></td>
                                    <td><?php echo escape($receipt['user_name']); ?></td>
                                    <td><?php echo escape($receipt['technician_name'] !== null ? $receipt['technician_name'] : 'Not assigned'); ?></td>
                                    <td><?php echo escape($receipt['service_name']); ?></td>
                                    <td><?php echo escape(number_format((float) $receipt['amount'], 2)); ?></td>
                                    <td><?php render_status_badge($receipt['payment_status']); ?></td>
                                    <td><?php echo escape($receipt['issued_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="content-card">
            <h2>Completed Requests Waiting for Receipts</h2>

            <?php if (empty($pendingReceiptRequests)): ?>
                <div class="empty-state">
                    <p>All completed requests with final prices already have receipts.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>User</th>
                                <th>Technician</th>
                                <th>Service</th>
                                <th>Final Price</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingReceiptRequests as $pendingRequest): ?>
                                <tr>
                                    <td><?php echo escape((string) $pendingRequest['request_id']); ?></td>
                                    <td><?php echo escape($pendingRequest['user_name']); ?></td>
                                    <td><?php echo escape($pendingRequest['technician_name'] !== null ? $pendingRequest['technician_name'] : 'Not assigned'); ?></td>
                                    <td><?php echo escape($pendingRequest['service_name']); ?></td>
                                    <td><?php echo escape(number_format((float) $pendingRequest['final_price'], 2)); ?></td>
                                    <td><?php echo escape($pendingRequest['request_date']); ?></td>
                                    <td>
                                        <form action="receipts.php" method="post">
                                            <input type="hidden" name="action" value="generate_receipt">
                                            <input type="hidden" name="request_id" value="<?php echo (int) $pendingRequest['request_id']; ?>">
                                            <button class="btn btn-small" type="submit">Generate Receipt</button>
                                        </form>
                                    </td>
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
