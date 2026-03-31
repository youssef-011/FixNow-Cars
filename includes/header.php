<?php
// Shared HTML header for all public and protected pages.
$basePath = $basePath ?? '';
$fullTitle = isset($pageTitle) ? $pageTitle . ' | FixNow Cars' : 'FixNow Cars';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($fullTitle); ?></title>
    <link rel="stylesheet" href="<?php echo escape($basePath . 'assets/css/style.css'); ?>">
</head>
<body>
