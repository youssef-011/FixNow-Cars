<?php
// Shows all service requests and lets the admin filter or inspect them in detail.
$pageTitle = 'Requests';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('admin', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$requests = [];
$allowedStatuses = ['all', 'pending', 'accepted', 'in_progress', 'completed', 'cancelled'];
$selectedStatus = $_GET['status'] ?? 'all';
$searchTerm = normalize_search_term($_GET['q'] ?? '', 100);

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if ($selectedStatus === 'all' && $searchTerm === '') {
        $requestsStatement = $db->prepare(
            'SELECT
                sr.id,
                u.name,
                tech.name,
                s.service_name,
                c.brand,
                c.model,
                c.plate_number,
                sr.status,
                sr.request_date,
                sr.estimated_price,
                sr.final_price
             FROM service_requests AS sr
             INNER JOIN users AS u ON sr.user_id = u.id
             LEFT JOIN users AS tech ON sr.technician_id = tech.id
             INNER JOIN services AS s ON sr.service_id = s.id
             INNER JOIN cars AS c ON sr.car_id = c.id
             ORDER BY sr.created_at DESC'
        );

        if ($requestsStatement === false) {
            $pageError = 'Unable to load service requests.';
        } else {
            $requestsStatement->execute();
        }
    } elseif ($selectedStatus !== 'all' && $searchTerm === '') {
        $requestsStatement = $db->prepare(
            'SELECT
                sr.id,
                u.name,
                tech.name,
                s.service_name,
                c.brand,
                c.model,
                c.plate_number,
                sr.status,
                sr.request_date,
                sr.estimated_price,
                sr.final_price
             FROM service_requests AS sr
             INNER JOIN users AS u ON sr.user_id = u.id
             LEFT JOIN users AS tech ON sr.technician_id = tech.id
             INNER JOIN services AS s ON sr.service_id = s.id
             INNER JOIN cars AS c ON sr.car_id = c.id
             WHERE sr.status = ?
             ORDER BY sr.created_at DESC'
        );

        if ($requestsStatement === false) {
            $pageError = 'Unable to load service requests.';
        } else {
            $requestsStatement->bind_param('s', $selectedStatus);
            $requestsStatement->execute();
        }
    } elseif ($selectedStatus === 'all' && $searchTerm !== '') {
        $requestsStatement = $db->prepare(
            "SELECT
                sr.id,
                u.name,
                tech.name,
                s.service_name,
                c.brand,
                c.model,
                c.plate_number,
                sr.status,
                sr.request_date,
                sr.estimated_price,
                sr.final_price
             FROM service_requests AS sr
             INNER JOIN users AS u ON sr.user_id = u.id
             LEFT JOIN users AS tech ON sr.technician_id = tech.id
             INNER JOIN services AS s ON sr.service_id = s.id
             INNER JOIN cars AS c ON sr.car_id = c.id
             WHERE
                u.name LIKE ?
                OR IFNULL(tech.name, '') LIKE ?
                OR s.service_name LIKE ?
                OR c.brand LIKE ?
                OR c.model LIKE ?
                OR c.plate_number LIKE ?
             ORDER BY sr.created_at DESC"
        );

        if ($requestsStatement === false) {
            $pageError = 'Unable to load service requests.';
        } else {
            $searchLike = search_like_value($searchTerm);
            $requestsStatement->bind_param('ssssss', $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike);
            $requestsStatement->execute();
        }
    } else {
        $requestsStatement = $db->prepare(
            "SELECT
                sr.id,
                u.name,
                tech.name,
                s.service_name,
                c.brand,
                c.model,
                c.plate_number,
                sr.status,
                sr.request_date,
                sr.estimated_price,
                sr.final_price
             FROM service_requests AS sr
             INNER JOIN users AS u ON sr.user_id = u.id
             LEFT JOIN users AS tech ON sr.technician_id = tech.id
             INNER JOIN services AS s ON sr.service_id = s.id
             INNER JOIN cars AS c ON sr.car_id = c.id
             WHERE sr.status = ?
               AND (
                    u.name LIKE ?
                    OR IFNULL(tech.name, '') LIKE ?
                    OR s.service_name LIKE ?
                    OR c.brand LIKE ?
                    OR c.model LIKE ?
                    OR c.plate_number LIKE ?
               )
             ORDER BY sr.created_at DESC"
        );

        if ($requestsStatement === false) {
            $pageError = 'Unable to load service requests.';
        } else {
            $searchLike = search_like_value($searchTerm);
            $requestsStatement->bind_param('sssssss', $selectedStatus, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike);
            $requestsStatement->execute();
        }
    }

    if ($pageError === '') {
        $requestsStatement->bind_result(
            $requestId,
            $userName,
            $technicianName,
            $serviceName,
            $carBrand,
            $carModel,
            $plateNumber,
            $status,
            $requestDate,
            $estimatedPrice,
            $finalPrice
        );

        while ($requestsStatement->fetch()) {
            $requests[] = [
                'id' => $requestId,
                'user_name' => $userName,
                'technician_name' => $technicianName,
                'service_name' => $serviceName,
                'car_label' => $carBrand . ' ' . $carModel . ' (' . $plateNumber . ')',
                'status' => $status,
                'request_date' => $requestDate,
                'estimated_price' => $estimatedPrice,
                'final_price' => $finalPrice,
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
                <span class="section-tag">Requests</span>
                <h1>All Service Requests</h1>
                <p>Review all service requests, apply filters, and open any request for full details.</p>
            </div>
            <span class="status-pill">Admin Monitoring</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="users.php">Users</a>
            <a class="btn btn-secondary" href="technicians.php">Technicians</a>
            <a class="btn btn-secondary" href="services.php">Services</a>
            <a class="btn btn-secondary" href="reports.php">Reports</a>
            <a class="btn btn-secondary" href="receipts.php">Receipts</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <section class="content-card">
            <form class="auth-form" action="requests.php" method="get">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="q">Search Requests</label>
                        <input type="text" id="q" name="q" value="<?php echo escape($searchTerm); ?>" placeholder="Search by user, technician, service, or car">
                    </div>
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

            <?php if ($selectedStatus !== 'all' || $searchTerm !== ''): ?>
                <div class="dashboard-actions">
                    <a class="btn btn-secondary" href="requests.php">Clear Filters</a>
                </div>
            <?php endif; ?>
        </section>

        <?php if (empty($requests)): ?>
            <div class="content-card empty-state">
                <p>No service requests match the current filters.</p>
            </div>
        <?php else: ?>
            <section class="content-card">
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Technician</th>
                                <th>Service</th>
                                <th>Car</th>
                                <th>Status</th>
                                <th>Request Date</th>
                                <th>Estimated Price</th>
                                <th>Final Price</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo escape($request['user_name']); ?></td>
                                    <td><?php echo escape($request['technician_name'] !== null ? $request['technician_name'] : 'Not assigned yet'); ?></td>
                                    <td><?php echo escape($request['service_name']); ?></td>
                                    <td><?php echo escape($request['car_label']); ?></td>
                                    <td><?php render_status_badge($request['status']); ?></td>
                                    <td><?php echo escape($request['request_date']); ?></td>
                                    <td><?php echo escape($request['estimated_price'] !== null ? number_format((float) $request['estimated_price'], 2) : 'Not set'); ?></td>
                                    <td><?php echo escape($request['final_price'] !== null ? number_format((float) $request['final_price'], 2) : 'Not set'); ?></td>
                                    <td>
                                        <a class="btn btn-small" href="request_details.php?<?php echo escape(http_build_query(['id' => (int) $request['id'], 'status' => $selectedStatus, 'q' => $searchTerm])); ?>">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
