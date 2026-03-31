<?php
// Creates a new service request for one of the logged-in user's cars.
$pageTitle = 'Request Service';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('user', null, $basePath);

$flashMessage = get_flash_message();
$errors = [];
$pageError = '';
$cars = [];
$services = [];
$selectedCarId = 0;
$selectedServiceId = 0;
$problemDescription = '';
$location = '';
$userId = current_user_id();

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    $carsStatement = $db->prepare(
        'SELECT id, brand, model, year, plate_number
         FROM cars
         WHERE user_id = ?
         ORDER BY brand ASC, model ASC'
    );

    if ($carsStatement === false) {
        $pageError = 'Unable to load your cars.';
    } else {
        $carsStatement->bind_param('i', $userId);
        $carsStatement->execute();
        $carsStatement->bind_result($carId, $brand, $model, $year, $plateNumber);

        while ($carsStatement->fetch()) {
            $cars[] = [
                'id' => $carId,
                'label' => $brand . ' ' . $model . ' (' . $plateNumber . ') - ' . $year,
            ];
        }

        $carsStatement->close();
    }

    if ($pageError === '') {
        $servicesStatement = $db->prepare(
            'SELECT id, service_name, base_price
             FROM services
             ORDER BY service_name ASC'
        );

        if ($servicesStatement === false) {
            $pageError = 'Unable to load available services.';
        } else {
            $servicesStatement->execute();
            $servicesStatement->bind_result($serviceId, $serviceName, $basePrice);

            while ($servicesStatement->fetch()) {
                $services[] = [
                    'id' => $serviceId,
                    'label' => $serviceName . ' - ' . number_format((float) $basePrice, 2),
                ];
            }

            $servicesStatement->close();
        }
    }

    if ($pageError === '' && is_post_request()) {
        $selectedCarId = (int) ($_POST['car_id'] ?? 0);
        $selectedServiceId = (int) ($_POST['service_id'] ?? 0);
        $problemDescription = trim($_POST['problem_description'] ?? '');
        $location = trim($_POST['location'] ?? '');

        $userCarIds = array_column($cars, 'id');
        $serviceIds = array_column($services, 'id');

        if ($selectedCarId <= 0 || !in_array($selectedCarId, $userCarIds, true)) {
            $errors[] = 'Please choose one of your cars.';
        }

        if ($selectedServiceId <= 0 || !in_array($selectedServiceId, $serviceIds, true)) {
            $errors[] = 'Please choose a valid service.';
        }

        validate_required_text($errors, $problemDescription, 'Problem description', 10, 1000);
        validate_required_text($errors, $location, 'Location', 3, 255);

        if (empty($cars)) {
            $errors[] = 'You need to add a car before requesting a service.';
        }

        if (empty($services)) {
            $errors[] = 'No services are available right now.';
        }

        if (empty($errors)) {
            $insertStatement = $db->prepare(
                'INSERT INTO service_requests
                 (user_id, car_id, service_id, problem_description, location, request_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            if ($insertStatement === false) {
                $errors[] = 'Unable to prepare the request creation query.';
            } else {
                $requestDate = date('Y-m-d');
                $status = 'pending';
                $insertStatement->bind_param(
                    'iiissss',
                    $userId,
                    $selectedCarId,
                    $selectedServiceId,
                    $problemDescription,
                    $location,
                    $requestDate,
                    $status
                );

                if ($insertStatement->execute()) {
                    $insertStatement->close();
                    set_flash_message('Service request created successfully.', 'success');
                    redirect('my_requests.php');
                }

                $insertStatement->close();
                $errors[] = 'Unable to create the service request. Please try again.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="container narrow-container">
    <?php render_flash_message($flashMessage); ?>

    <section class="page-banner">
        <div class="dashboard-header">
            <div>
                <span class="section-tag">Request Service</span>
                <h1>Create a Service Request</h1>
                <p>Select one of your cars, choose a service, and describe the problem clearly.</p>
            </div>
            <span class="status-pill">Requests Module</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="my_cars.php">My Cars</a>
            <a class="btn btn-secondary" href="my_requests.php">My Requests</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <?php render_message_box($errors, 'error'); ?>

        <?php if (empty($cars)): ?>
            <div class="content-card empty-state">
                <h2>No Saved Cars Yet</h2>
                <p>You need at least one saved car before you can request a service.</p>
                <div class="dashboard-actions">
                    <a class="btn" href="add_car.php">Add a Car First</a>
                </div>
            </div>
        <?php elseif (empty($services)): ?>
            <div class="content-card empty-state">
                <h2>No Services Available</h2>
                <p>No services are available right now. Please try again later.</p>
            </div>
        <?php else: ?>
            <section class="content-card">
                <form class="auth-form" action="request_service.php" method="post">
                    <div class="form-group">
                        <label for="car_id">Choose Your Car</label>
                        <select id="car_id" name="car_id" required>
                            <option value="">Select a car</option>
                            <?php foreach ($cars as $car): ?>
                                <option value="<?php echo (int) $car['id']; ?>" <?php echo $selectedCarId === (int) $car['id'] ? 'selected' : ''; ?>>
                                    <?php echo escape($car['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="service_id">Choose a Service</label>
                        <select id="service_id" name="service_id" required>
                            <option value="">Select a service</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo (int) $service['id']; ?>" <?php echo $selectedServiceId === (int) $service['id'] ? 'selected' : ''; ?>>
                                    <?php echo escape($service['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="problem_description">Problem Description</label>
                        <textarea id="problem_description" name="problem_description" rows="5" placeholder="Describe the issue with your car" required><?php echo escape($problemDescription); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo escape($location); ?>" placeholder="Enter your address or service location" required>
                    </div>

                    <button class="btn full-width" type="submit">Submit Request</button>
                </form>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
