<?php
// Edits one car that belongs to the logged-in user.
$pageTitle = 'Edit Car';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('user', null, $basePath);

$flashMessage = get_flash_message();
$errors = [];
$pageError = '';
$userId = current_user_id();
$carId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$brand = '';
$model = '';
$year = '';
$plateNumber = '';
$color = '';
$notes = '';

if ($carId <= 0) {
    set_flash_message('Invalid car selected.', 'error');
    redirect('my_cars.php');
}

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} else {
    $carStatement = $db->prepare(
        'SELECT brand, model, year, plate_number, color, notes
         FROM cars
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );

    if ($carStatement === false) {
        $pageError = 'Unable to load the selected car.';
    } else {
        $carStatement->bind_param('ii', $carId, $userId);
        $carStatement->execute();
        $carStatement->store_result();

        if ($carStatement->num_rows !== 1) {
            $carStatement->close();
            set_flash_message('Car not found.', 'error');
            redirect('my_cars.php');
        }

        $carStatement->bind_result($savedBrand, $savedModel, $savedYear, $savedPlateNumber, $savedColor, $savedNotes);
        $carStatement->fetch();
        $carStatement->close();

        if (is_post_request()) {
            $brand = trim($_POST['brand'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $year = trim($_POST['year'] ?? '');
            $plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
            $color = trim($_POST['color'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            validate_required_text($errors, $brand, 'Brand', 1, 50);
            validate_required_text($errors, $model, 'Model', 1, 50);
            validate_year_value($errors, $year, 'Year');
            validate_required_text($errors, $plateNumber, 'Plate number', 3, 30);
            validate_max_length($errors, $color, 'Color', 30);
            validate_max_length($errors, $notes, 'Notes', 1000);

            if (!is_blank($plateNumber) && preg_match('/^[A-Z0-9\-\s]{3,30}$/', $plateNumber) !== 1) {
                $errors[] = 'Plate number format is not valid.';
            }

            if (empty($errors)) {
                $plateCheckStatement = $db->prepare('SELECT id FROM cars WHERE plate_number = ? AND id <> ? LIMIT 1');

                if ($plateCheckStatement === false) {
                    $errors[] = 'Unable to check the plate number.';
                } else {
                    $plateCheckStatement->bind_param('si', $plateNumber, $carId);
                    $plateCheckStatement->execute();
                    $plateCheckStatement->store_result();

                    if ($plateCheckStatement->num_rows > 0) {
                        $errors[] = 'This plate number is already registered.';
                    }

                    $plateCheckStatement->close();
                }
            }

            if (empty($errors)) {
                $updateStatement = $db->prepare(
                    'UPDATE cars
                     SET brand = ?, model = ?, year = ?, plate_number = ?, color = ?, notes = ?
                     WHERE id = ? AND user_id = ?'
                );

                if ($updateStatement === false) {
                    $errors[] = 'Unable to prepare the update query.';
                } else {
                    $yearValue = (int) $year;
                    $updateStatement->bind_param('ssisssii', $brand, $model, $yearValue, $plateNumber, $color, $notes, $carId, $userId);

                    if ($updateStatement->execute()) {
                        $updateStatement->close();
                        set_flash_message('Car updated successfully.', 'success');
                        redirect('my_cars.php');
                    }

                    $updateStatement->close();
                    $errors[] = 'Unable to update this car. Please try again.';
                }
            }
        } else {
            $brand = $savedBrand;
            $model = $savedModel;
            $year = $savedYear;
            $plateNumber = $savedPlateNumber;
            $color = $savedColor ?? '';
            $notes = $savedNotes ?? '';
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
                <span class="section-tag">Edit Car</span>
                <h1>Update Car Information</h1>
                <p>Edit the saved details for one of your cars.</p>
            </div>
            <span class="status-pill">Cars Module</span>
        </div>

        <div class="dashboard-actions">
            <a class="btn btn-secondary" href="index.php">Dashboard</a>
            <a class="btn btn-secondary" href="my_cars.php">Back to My Cars</a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="flash-message flash-error">
            <p><?php echo escape($pageError); ?></p>
        </div>
    <?php else: ?>
        <?php render_message_box($errors, 'error'); ?>

        <section class="content-card">
            <form class="auth-form" action="edit_car.php?id=<?php echo (int) $carId; ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" value="<?php echo escape($brand); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" value="<?php echo escape($model); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="number" id="year" name="year" value="<?php echo escape((string) $year); ?>" min="1900" max="<?php echo escape((string) ((int) date('Y') + 1)); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="plate_number">Plate Number</label>
                        <input type="text" id="plate_number" name="plate_number" value="<?php echo escape($plateNumber); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="text" id="color" name="color" value="<?php echo escape($color); ?>">
                    </div>

                    <div class="form-group full-span">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Optional notes about the car"><?php echo escape($notes); ?></textarea>
                    </div>
                </div>

                <button class="btn full-width" type="submit">Update Car</button>
            </form>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
