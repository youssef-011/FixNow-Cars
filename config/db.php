<?php
// Central MySQLi database connection for the FixNow Cars project.
// Update these values to match your local XAMPP or WAMP database settings.

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'fixnow_cars';

$db = null;
$dbConnectionError = null;

if (!class_exists('mysqli')) {
    $dbConnectionError = 'The MySQLi extension is not enabled on this server.';
    return;
}

$db = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($db->connect_error) {
    $dbConnectionError = 'Database connection failed. Check the database settings and import the SQL file first.';
    $db = null;
} else {
    $db->set_charset('utf8mb4');
}
