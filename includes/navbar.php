<?php
// Shared navigation bar with public and role-based links.
$basePath = $basePath ?? '';
$isLoggedIn = is_logged_in();
$dashboardLink = $isLoggedIn ? role_dashboard_path(current_user_role(), $basePath) : '';
$dashboardLabel = $isLoggedIn ? role_dashboard_label(current_user_role()) : '';
?>
<header class="site-header">
    <div class="container nav-wrapper">
        <a class="brand" href="<?php echo escape($basePath . 'index.php'); ?>">FixNow Cars</a>

        <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-controls="main-navigation" aria-expanded="false">
            Menu
        </button>

        <nav class="main-nav" id="main-navigation">
            <a href="<?php echo escape($basePath . 'index.php'); ?>">Home</a>

            <?php if (!$isLoggedIn): ?>
                <a href="<?php echo escape($basePath . 'login.php'); ?>">Login</a>
                <a href="<?php echo escape($basePath . 'register.php'); ?>">Register</a>
            <?php else: ?>
                <span class="nav-user"><?php echo escape(current_user_name()); ?> (<?php echo escape(format_label(current_user_role())); ?>)</span>
                <a href="<?php echo escape($dashboardLink); ?>"><?php echo escape($dashboardLabel); ?></a>
                <a href="<?php echo escape($basePath . 'logout.php'); ?>">Logout</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
