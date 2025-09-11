<?php

require_once 'config.php';

// Global variable.
$connection = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Error fallback.
if ($connection->connect_error) {
    error_log("Connection failed: " . $connection->connect_error);
    die("An error occurred connecting to the database.");
}

?>