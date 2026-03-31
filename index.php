<?php
// Homepage for the FixNow Cars website.
$pageTitle = 'Home';
$basePath = '';

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$flashMessage = get_flash_message();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="container">
    <?php render_flash_message($flashMessage); ?>

    <section class="hero-section">
        <div class="hero-text">
            <span class="section-tag">Car Service Platform</span>
            <h1>FixNow Cars</h1>
            <p>
                FixNow Cars helps drivers request service, technicians manage repairs,
                and admins keep every request on track.
            </p>
            <div class="hero-actions">
                <?php if (is_logged_in()): ?>
                    <a class="btn" href="<?php echo escape(role_dashboard_path(current_user_role(), $basePath)); ?>">Open Dashboard</a>
                <?php else: ?>
                    <a class="btn" href="<?php echo escape($basePath . 'register.php'); ?>">Create Account</a>
                    <a class="btn btn-secondary" href="<?php echo escape($basePath . 'login.php'); ?>">Login</a>
                <?php endif; ?>
            </div>
            <div class="hero-metrics">
                <div class="hero-stat">
                    <strong>3</strong>
                    <span>Roles</span>
                </div>
                <div class="hero-stat">
                    <strong>6</strong>
                    <span>Core tables</span>
                </div>
                <div class="hero-stat">
                    <strong>1</strong>
                    <span>Shared workflow</span>
                </div>
            </div>
        </div>

        <div class="hero-card">
            <span class="section-tag">Workflow</span>
            <h2>How It Works</h2>
            <p class="hero-card-copy">
                Every request moves through one clear flow, from booking and assignment to completion and receipt records.
            </p>
            <ul class="feature-list compact-list">
                <li>Users submit service requests for their saved cars</li>
                <li>Technicians accept jobs and update progress, notes, and prices</li>
                <li>Admins monitor requests, services, reports, and receipts</li>
            </ul>
            <p class="hero-card-note">Each role has a focused dashboard with the tools needed for daily tasks.</p>
        </div>
    </section>

    <section class="page-section">
        <div class="section-heading">
            <span class="section-tag">Core Roles</span>
            <h2>Who uses FixNow Cars?</h2>
            <p>Three roles keep the service process moving from start to finish.</p>
        </div>

        <div class="card-grid">
            <article class="info-card">
                <h3>User</h3>
                <p>Creates an account, adds cars, requests service, and follows updates.</p>
            </article>
            <article class="info-card">
                <h3>Technician</h3>
                <p>Accepts available jobs, updates progress, and records notes and prices.</p>
            </article>
            <article class="info-card">
                <h3>Admin</h3>
                <p>Manages the service list, reviews activity, and monitors the overall workflow.</p>
            </article>
        </div>
    </section>

    <section class="page-section split-layout">
        <div>
            <span class="section-tag">Technology</span>
            <h2>Built with familiar web tools</h2>
            <p>
                The site runs on PHP, MySQL, HTML, CSS, sessions, validation,
                and basic JavaScript to support the full service flow.
            </p>
        </div>

        <div class="checklist-card">
            <div class="check-item">Login, registration, and role-based dashboards</div>
            <div class="check-item">Car management and service request tracking</div>
            <div class="check-item">Technician job updates with notes and pricing</div>
            <div class="check-item">Admin reports and receipt records</div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
