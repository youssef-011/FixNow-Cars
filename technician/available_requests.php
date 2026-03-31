<?php
// Shows unassigned pending requests and lets a technician accept one.
$pageTitle = 'Available Requests';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('technician', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$requests = [];
$technicianId = current_user_id();

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if (is_post_request()) {
        $action = $_POST['action'] ?? '';
        $requestId = (int) ($_POST['request_id'] ?? 0);

        // Accept jobs only from the expected POST action on this page.
        if ($action !== 'accept_request') {
            set_flash_message('Invalid request action received.', 'error');
            redirect('available_requests.php');
        }

        if ($requestId <= 0) {
            set_flash_message('Invalid request selected.', 'error');
            redirect('available_requests.php');
        }

        // Accept the request only if it is still pending and not assigned.
        $acceptStatement = $db->prepare(
            "UPDATE service_requests
             SET technician_id = ?, status = 'accepted'
             WHERE id = ? AND technician_id IS NULL AND status = 'pending'"
        );

        if ($acceptStatement === false) {
            set_flash_message('Unable to prepare the accept query.', 'error');
            redirect('available_requests.php');
        }

        $acceptStatement->bind_param('ii', $technicianId, $requestId);
        $acceptStatement->execute();
        $updatedRows = $acceptStatement->affected_rows;
        $acceptStatement->close();

        if ($updatedRows > 0) {
            set_flash_message('Request accepted successfully.', 'success');
            redirect('my_jobs.php');
        }

        set_flash_message('This request is no longer available.', 'error');
        redirect('available_requests.php');
    }

    $requestsStatement = $db->prepare(
        "SELECT
            sr.id,
            u.name,
            c.brand,
            c.model,
            c.plate_number,
            s.service_name,
            sr.request_date,
            sr.location
         FROM service_requests AS sr
         INNER JOIN users AS u ON sr.user_id = u.id
         INNER JOIN cars AS c ON sr.car_id = c.id
         INNER JOIN services AS s ON sr.service_id = s.id
         WHERE sr.technician_id IS NULL AND sr.status = 'pending'
         ORDER BY sr.created_at ASC"
    );

    if ($requestsStatement === false) {
        $pageError = 'Unable to load available requests.';
    } else {
        $requestsStatement->execute();
        $requestsStatement->bind_result(
            $requestId,
            $userName,
            $carBrand,
            $carModel,
            $plateNumber,
            $serviceName,
            $requestDate,
            $location
        );

        while ($requestsStatement->fetch()) {
            $requests[] = [
                'id' => $requestId,
                'user_name' => $userName,
                'car_label' => $carBrand . ' ' . $carModel . ' (' . $plateNumber . ')',
                'service_name' => $serviceName,
                'request_date' => $requestDate,
                'location' => $location,
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
                <span class="section-tag">Available Requests</span>
                <h1>Available Requests</h1>
                <p>Review open service requests and accept one if you are ready to handle it.</p>
            </div>
            <span class="status-pill">Requests Module</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="my_jobs.php">My Jobs</a>
            <a class="btn btn-secondary" href="profile.php">My Profile</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php elseif (empty($requests)): ?>
        <div class="content-card empty-state">
            <p>There are no pending requests available right now.</p>
        </div>
    <?php else: ?>
        <section class="content-card">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Car</th>
                            <th>Service</th>
                            <th>Request Date</th>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo escape($request['user_name']); ?></td>
                                <td><?php echo escape($request['car_label']); ?></td>
                                <td><?php echo escape($request['service_name']); ?></td>
                                <td><?php echo escape($request['request_date']); ?></td>
                                <td><?php echo escape($request['location']); ?></td>
                                <td>
                                    <form action="available_requests.php" method="post">
                                        <input type="hidden" name="action" value="accept_request">
                                        <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                        <button class="btn btn-small" type="submit">Accept Request</button>
                                    </form>
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
