<?php
// Lists the logged-in user's cars and handles simple delete requests.
$pageTitle = 'My Cars';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('user', null, $basePath);

$flashMessage = get_flash_message();
$pageError = '';
$cars = [];
$userId = current_user_id();

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    if (is_post_request()) {
        $action = $_POST['action'] ?? '';
        $carId = (int) ($_POST['car_id'] ?? 0);

        // Require an explicit action name so random POST data cannot trigger deletion.
        if ($action !== 'delete_car') {
            set_flash_message('Invalid car action received.', 'error');
            redirect('my_cars.php');
        }

        if ($carId <= 0) {
            set_flash_message('Invalid car selected for deletion.', 'error');
            redirect('my_cars.php');
        }

        // Prevent deleting a car that is already linked to saved requests.
        $requestCheckStatement = $db->prepare('SELECT COUNT(*) FROM service_requests WHERE car_id = ? AND user_id = ?');

        if ($requestCheckStatement === false) {
            set_flash_message('Unable to check related service requests.', 'error');
            redirect('my_cars.php');
        }

        $requestCheckStatement->bind_param('ii', $carId, $userId);
        $requestCheckStatement->execute();
        $requestCheckStatement->bind_result($requestCount);
        $requestCheckStatement->fetch();
        $requestCheckStatement->close();

        if ($requestCount > 0) {
            set_flash_message('You cannot delete a car that already has service requests.', 'error');
            redirect('my_cars.php');
        }

        $deleteStatement = $db->prepare('DELETE FROM cars WHERE id = ? AND user_id = ?');

        if ($deleteStatement === false) {
            set_flash_message('Unable to prepare the delete query.', 'error');
            redirect('my_cars.php');
        }

        $deleteStatement->bind_param('ii', $carId, $userId);
        $deleteStatement->execute();
        $deletedRows = $deleteStatement->affected_rows;
        $deleteStatement->close();

        if ($deletedRows > 0) {
            set_flash_message('Car deleted successfully.', 'success');
        } else {
            set_flash_message('Unable to delete this car.', 'error');
        }

        redirect('my_cars.php');
    }

    $carsStatement = $db->prepare(
        'SELECT id, brand, model, year, plate_number, color, notes, created_at
         FROM cars
         WHERE user_id = ?
         ORDER BY created_at DESC'
    );

    if ($carsStatement === false) {
        $pageError = 'Unable to load your cars.';
    } else {
        $carsStatement->bind_param('i', $userId);
        $carsStatement->execute();
        $carsStatement->bind_result($id, $brand, $model, $year, $plateNumber, $color, $notes, $createdAt);

        while ($carsStatement->fetch()) {
            $cars[] = [
                'id' => $id,
                'brand' => $brand,
                'model' => $model,
                'year' => $year,
                'plate_number' => $plateNumber,
                'color' => $color,
                'notes' => $notes,
                'created_at' => $createdAt,
            ];
        }

        $carsStatement->close();
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
                <span class="section-tag">My Cars</span>
                <h1>Saved Cars</h1>
                <p>View, edit, and manage the cars that belong to your account.</p>
            </div>
            <span class="status-pill">Cars Module</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="profile.php">My Profile</a>
            <a class="btn" href="add_car.php">Add Car</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php elseif (empty($cars)): ?>
        <div class="content-card empty-state">
            <p>You have not added any cars yet. Start by adding your first car.</p>
            <div class="dashboard-actions">
                <a class="btn" href="add_car.php">Add Your First Car</a>
            </div>
        </div>
    <?php else: ?>
        <section class="content-card">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Year</th>
                            <th>Plate Number</th>
                            <th>Color</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cars as $car): ?>
                            <tr>
                                <td><?php echo escape($car['brand']); ?></td>
                                <td><?php echo escape($car['model']); ?></td>
                                <td><?php echo escape((string) $car['year']); ?></td>
                                <td><?php echo escape($car['plate_number']); ?></td>
                                <td><?php echo escape($car['color'] !== '' ? $car['color'] : 'Not set'); ?></td>
                                <td><?php echo escape($car['notes'] !== '' ? $car['notes'] : 'No notes'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn btn-small" href="edit_car.php?id=<?php echo (int) $car['id']; ?>">Edit</a>
                                        <form action="my_cars.php" method="post">
                                            <input type="hidden" name="action" value="delete_car">
                                            <input type="hidden" name="car_id" value="<?php echo (int) $car['id']; ?>">
                                            <button class="btn btn-danger btn-small" type="submit">Delete</button>
                                        </form>
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
