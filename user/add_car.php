<?php
// Adds a new car for the logged-in user.
$pageTitle = 'Add Car';
$basePath = '../';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

require_login($basePath . 'login.php');
require_role('user', null, $basePath);

$flashMessage = get_flash_message();
$errors = [];
$pageError = '';
$brand = '';
$model = '';
$year = '';
$plateNumber = '';
$color = '';
$notes = '';
$userId = current_user_id();

if ($db === null) {
    $pageError = $dbConnectionError ?? 'Database connection is not available.';
} elseif (is_post_request()) {
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
        $plateCheckStatement = $db->prepare('SELECT id FROM cars WHERE plate_number = ? LIMIT 1');

        if ($plateCheckStatement === false) {
            $errors[] = 'Unable to check the plate number.';
        } else {
            $plateCheckStatement->bind_param('s', $plateNumber);
            $plateCheckStatement->execute();
            $plateCheckStatement->store_result();

            if ($plateCheckStatement->num_rows > 0) {
                $errors[] = 'This plate number is already registered.';
            }

            $plateCheckStatement->close();
        }
    }

    if (empty($errors)) {
        $insertStatement = $db->prepare(
            'INSERT INTO cars (user_id, brand, model, year, plate_number, color, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        if ($insertStatement === false) {
            $errors[] = 'Unable to prepare the add car query.';
        } else {
            $yearValue = (int) $year;
            $insertStatement->bind_param('ississs', $userId, $brand, $model, $yearValue, $plateNumber, $color, $notes);

            if ($insertStatement->execute()) {
                $insertStatement->close();
                set_flash_message('Car added successfully.', 'success');
                redirect('my_cars.php');
            }

            $insertStatement->close();
            $errors[] = 'Unable to add the car. Please try again.';
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
                <span class="section-tag">Add Car</span>
                <h1>Add a New Car</h1>
                <p>Save your car details so you can choose it later when requesting a service.</p>
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
            <form class="auth-form" action="add_car.php" method="post">
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
                        <input type="number" id="year" name="year" value="<?php echo escape($year); ?>" min="1900" max="<?php echo escape((string) ((int) date('Y') + 1)); ?>" required>
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

                <button class="btn full-width" type="submit">Save Car</button>
            </form>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
