<?php
// Manages the service catalog with simple add, edit, and delete actions.
$pageTitle = 'Services';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('admin', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$errors = [];
$services = [];
$editServiceId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$searchTerm = normalize_search_term($_GET['q'] ?? '', 100);
$serviceName = '';
$description = '';
$basePrice = '';

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if (is_post_request()) {
        $action = $_POST['action'] ?? '';
        $returnSearchTerm = normalize_search_term($_POST['return_q'] ?? '', 100);
        $redirectTarget = 'services.php' . ($returnSearchTerm !== '' ? '?q=' . urlencode($returnSearchTerm) : '');

        if ($action === 'delete') {
            $serviceId = (int) ($_POST['service_id'] ?? 0);

            if ($serviceId <= 0) {
                set_flash_message('Invalid service selected.', 'error');
                redirect($redirectTarget);
            }

            // Prevent deleting a service that is already linked to saved requests.
            $requestCheckStatement = $db->prepare('SELECT COUNT(*) FROM service_requests WHERE service_id = ?');

            if ($requestCheckStatement === false) {
                set_flash_message('Unable to check linked requests.', 'error');
                redirect($redirectTarget);
            }

            $requestCheckStatement->bind_param('i', $serviceId);
            $requestCheckStatement->execute();
            $requestCheckStatement->bind_result($linkedRequestsCount);
            $requestCheckStatement->fetch();
            $requestCheckStatement->close();

            if ($linkedRequestsCount > 0) {
                set_flash_message('This service cannot be deleted because it is linked to service requests.', 'error');
                redirect($redirectTarget);
            }

            $deleteStatement = $db->prepare('DELETE FROM services WHERE id = ?');

            if ($deleteStatement === false) {
                set_flash_message('Unable to prepare the delete query.', 'error');
                redirect($redirectTarget);
            }

            $deleteStatement->bind_param('i', $serviceId);
            $deleteStatement->execute();
            $deletedRows = $deleteStatement->affected_rows;
            $deleteStatement->close();

            if ($deletedRows > 0) {
                set_flash_message('Service deleted successfully.', 'success');
            } else {
                set_flash_message('Unable to delete this service.', 'error');
            }

            redirect($redirectTarget);
        }

        if ($action === 'save') {
            $postedServiceId = (int) ($_POST['service_id'] ?? 0);
            $serviceName = trim($_POST['service_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $basePrice = trim($_POST['base_price'] ?? '');
            $isEditing = $postedServiceId > 0;

            validate_required_text($errors, $serviceName, 'Service name', 3, 100);
            validate_required_text($errors, $description, 'Description', 10, 1000);
            validate_non_negative_decimal($errors, $basePrice, 'Base price', true);

            if (empty($errors)) {
                $checkStatement = $db->prepare('SELECT id FROM services WHERE service_name = ? AND id <> ? LIMIT 1');

                if ($checkStatement === false) {
                    $errors[] = 'Unable to check the service name.';
                } else {
                    $checkStatement->bind_param('si', $serviceName, $postedServiceId);
                    $checkStatement->execute();
                    $checkStatement->store_result();

                    if ($checkStatement->num_rows > 0) {
                        $errors[] = 'A service with this name already exists.';
                    }

                    $checkStatement->close();
                }
            }

            if (empty($errors)) {
                if ($isEditing) {
                    $saveStatement = $db->prepare(
                        'UPDATE services
                         SET service_name = ?, description = ?, base_price = ?
                         WHERE id = ?'
                    );

                    if ($saveStatement === false) {
                        $errors[] = 'Unable to prepare the service update query.';
                    } else {
                        $basePriceValue = (float) $basePrice;
                        $saveStatement->bind_param('ssdi', $serviceName, $description, $basePriceValue, $postedServiceId);

                        if ($saveStatement->execute()) {
                            $saveStatement->close();
                            set_flash_message('Service updated successfully.', 'success');
                            redirect($redirectTarget);
                        }

                        $saveStatement->close();
                        $errors[] = 'Unable to update this service. Please try again.';
                    }
                } else {
                    $saveStatement = $db->prepare(
                        'INSERT INTO services (service_name, description, base_price)
                         VALUES (?, ?, ?)'
                    );

                    if ($saveStatement === false) {
                        $errors[] = 'Unable to prepare the add service query.';
                    } else {
                        $basePriceValue = (float) $basePrice;
                        $saveStatement->bind_param('ssd', $serviceName, $description, $basePriceValue);

                        if ($saveStatement->execute()) {
                            $saveStatement->close();
                            set_flash_message('Service added successfully.', 'success');
                            redirect($redirectTarget);
                        }

                        $saveStatement->close();
                        $errors[] = 'Unable to add this service. Please try again.';
                    }
                }
            }

            $editServiceId = $postedServiceId;
        }
    }

    if ($editServiceId > 0 && !is_post_request()) {
        $editStatement = $db->prepare('SELECT service_name, description, base_price FROM services WHERE id = ? LIMIT 1');

        if ($editStatement === false) {
            $pageError = 'Unable to load the selected service.';
        } else {
            $editStatement->bind_param('i', $editServiceId);
            $editStatement->execute();
            $editStatement->store_result();

            if ($editStatement->num_rows !== 1) {
                $editStatement->close();
                set_flash_message('Service not found.', 'error');
                redirect('services.php');
            }

            $editStatement->bind_result($savedServiceName, $savedDescription, $savedBasePrice);
            $editStatement->fetch();
            $editStatement->close();

            $serviceName = $savedServiceName;
            $description = $savedDescription ?? '';
            $basePrice = (string) $savedBasePrice;
        }
    }

    if ($pageError === '') {
        if ($searchTerm === '') {
            $servicesStatement = $db->prepare(
                'SELECT id, service_name, description, base_price, created_at
                 FROM services
                 ORDER BY created_at DESC'
            );

            if ($servicesStatement === false) {
                $pageError = 'Unable to load services.';
            } else {
                $servicesStatement->execute();
            }
        } else {
            $servicesStatement = $db->prepare(
                'SELECT id, service_name, description, base_price, created_at
                 FROM services
                 WHERE service_name LIKE ? OR description LIKE ?
                 ORDER BY created_at DESC'
            );

            if ($servicesStatement === false) {
                $pageError = 'Unable to load services.';
            } else {
                $searchLike = search_like_value($searchTerm);
                $servicesStatement->bind_param('ss', $searchLike, $searchLike);
                $servicesStatement->execute();
            }
        }

        if ($pageError === '') {
            $servicesStatement->bind_result($id, $loadedServiceName, $loadedDescription, $loadedBasePrice, $createdAt);

            while ($servicesStatement->fetch()) {
                $services[] = [
                    'id' => $id,
                    'service_name' => $loadedServiceName,
                    'description' => $loadedDescription,
                    'base_price' => $loadedBasePrice,
                    'created_at' => $createdAt,
                ];
            }

            $servicesStatement->close();
        }
    }
}

$formTitle = $editServiceId > 0 ? 'Edit Service' : 'Add Service';
$submitLabel = $editServiceId > 0 ? 'Update Service' : 'Add Service';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="container">
    <?php render_flash_message($flashMessage); ?>

    <section class="page-banner">
        <div class="dashboard-header">
            <div>
                <span class="section-tag">Services</span>
                <h1>Service Catalog</h1>
                <p>Add, update, and safely manage the services available in FixNow Cars.</p>
            </div>
            <span class="status-pill">Admin Management</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="users.php">Users</a>
            <a class="btn btn-secondary" href="technicians.php">Technicians</a>
            <a class="btn btn-secondary" href="requests.php">Requests</a>
            <a class="btn btn-secondary" href="reports.php">Reports</a>
            <a class="btn btn-secondary" href="receipts.php">Receipts</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <?php render_message_box($errors, 'error'); ?>

        <section class="content-card">
            <form class="auth-form" action="services.php" method="get">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="q">Search Services</label>
                        <input type="text" id="q" name="q" value="<?php echo escape($searchTerm); ?>" placeholder="Search by service name or description">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn" type="submit">Apply Search</button>
                    </div>
                </div>
            </form>

            <?php if ($searchTerm !== ''): ?>
                <div class="dashboard-actions">
                    <a class="btn btn-secondary" href="services.php">Clear Search</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="detail-grid">
            <article class="content-card">
                <h2><?php echo escape($formTitle); ?></h2>

                <form class="auth-form" action="services.php<?php echo $editServiceId > 0 ? '?edit=' . (int) $editServiceId . ($searchTerm !== '' ? '&q=' . urlencode($searchTerm) : '') : ($searchTerm !== '' ? '?q=' . urlencode($searchTerm) : ''); ?>" method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="service_id" value="<?php echo (int) $editServiceId; ?>">
                    <input type="hidden" name="return_q" value="<?php echo escape($searchTerm); ?>">

                    <div class="form-group">
                        <label for="service_name">Service Name</label>
                        <input type="text" id="service_name" name="service_name" value="<?php echo escape($serviceName); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" required><?php echo escape($description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="base_price">Base Price</label>
                        <input type="number" step="0.01" min="0" id="base_price" name="base_price" value="<?php echo escape($basePrice); ?>" required>
                    </div>

                    <button class="btn full-width" type="submit"><?php echo escape($submitLabel); ?></button>
                </form>

                <?php if ($editServiceId > 0): ?>
                    <div class="dashboard-actions">
                        <a class="btn btn-secondary" href="services.php<?php echo $searchTerm !== '' ? '?q=' . urlencode($searchTerm) : ''; ?>">Cancel Edit</a>
                    </div>
                <?php endif; ?>
            </article>

            <article class="content-card">
                <h2>All Services</h2>

                <?php if (empty($services)): ?>
                    <div class="empty-state">
                        <p><?php echo escape($searchTerm !== '' ? 'No services match the current search.' : 'No services exist yet. Add the first one using the form.'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Description</th>
                                    <th>Base Price</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo escape($service['service_name']); ?></td>
                                        <td><?php echo escape($service['description'] !== null ? $service['description'] : 'No description'); ?></td>
                                        <td><?php echo escape(number_format((float) $service['base_price'], 2)); ?></td>
                                        <td><?php echo escape(date('Y-m-d', strtotime($service['created_at']))); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="btn btn-small" href="services.php?<?php echo escape(http_build_query(['edit' => (int) $service['id'], 'q' => $searchTerm])); ?>">Edit</a>
                                                <form action="services.php" method="post">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                                                    <input type="hidden" name="return_q" value="<?php echo escape($searchTerm); ?>">
                                                    <button class="btn btn-danger btn-small" type="submit">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
