<?php
/**
 * admin_delete_flight.php
 * This script processes the request to delete a specific flight from the database.
 * It receives the flight ID via POST and performs the deletion.
 * Access is restricted to users with the username 'Admin'.
 */

// Start the session - MUST be the very first thing in the file
session_start();

// --- Admin Authentication Check ---
// Ensures only logged-in admins can access this script.
// This assumes 'Admin' is the username for the administrator.
if (!isset($_SESSION['book_id']) || $_SESSION['username'] !== 'Admin') {
    // Redirect to login page or show an access denied message.
    $_SESSION['login_error'] = "Access denied. Please log in with an administrator account."; // Optional message
    header('Location: login_page.php'); // Redirect to your login page.
    exit(); // Stop further script execution.
}

// --- Check Request Method ---
// Ensure the script is accessed via a POST request (from the delete form).
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Retrieve and Validate Flight ID ---
    // Get the flight ID from POST data.
    $flight_id = $_POST['flight_id'] ?? null;

    // Initialize an array to collect errors.
    $errors = [];

    // Validate that flight_id is provided and is a valid positive integer.
    if (!$flight_id || !filter_var($flight_id, FILTER_VALIDATE_INT) || $flight_id <= 0) {
        $errors[] = "Invalid flight ID provided for deletion.";
        // Log invalid ID for debugging.
        error_log("Admin Delete Flight Error: Invalid flight ID received: " . ($flight_id === null ? 'NULL' : $flight_id));
    }


    // --- Database Connection ---
    // Only connect if validation passed and there are no errors yet.
    if (empty($errors)) {
        // Define database credentials.
        // **WARNING:** Hardcoded password is a severe security risk.
        include 'connection.php';

        // Check connection.
        if (!$connection) {
            // Log the specific database connection error for debugging.
            error_log("Database connection failed in admin_delete_flight.php: " . mysqli_connect_error());
            // Add a user-friendly error message.
            $errors[] = "An error occurred connecting to the database. Please try again later.";
        }
    }
    // --- End Database Connection ---


    // --- Perform Database Deletion ---
    // Proceed only if there are no errors and database connection is successful.
    if (empty($errors) && $connection) {

        // SQL query to delete the flight. Use a prepared statement.
        $sql = "DELETE FROM BookFlight WHERE book_id = ?";
        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt) {
            // Bind the flight ID parameter ('i' for integer).
            mysqli_stmt_bind_param($stmt, "i", $flight_id);

            // Execute the delete statement.
            if (mysqli_stmt_execute($stmt)) {
                // Check if any rows were affected by the delete.
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    // Deletion successful.
                    $_SESSION['delete_message'] = "Flight ID " . htmlspecialchars($flight_id) . " deleted successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    // No rows were affected, possibly because the flight ID didn't exist.
                    $_SESSION['delete_message'] = "Flight ID " . htmlspecialchars($flight_id) . " not found or could not be deleted.";
                    $_SESSION['message_type'] = 'warning'; // Indicate that the action didn't fully succeed.
                     // Log this as it might indicate an issue.
                    error_log("Admin Delete Flight Warning: Delete affected 0 rows for ID " . $flight_id . ". ID may not exist.");
                }
            } else {
                // Database deletion failed. Log the specific MySQL error.
                $db_error = mysqli_error($connection); // Get the specific error
                error_log("Database execute error in admin_delete_flight.php: " . $db_error);
                $errors[] = "An error occurred deleting the flight from the database.";
            }

            // Close the prepared statement.
            mysqli_stmt_close($stmt);

        } else {
             // Prepared statement failed. Log the specific MySQL error.
            $db_error = mysqli_error($connection); // Get the specific error
            error_log("Database prepare error in admin_delete_flight.php: " . $db_error);
            $errors[] = "An internal error occurred preparing to delete the flight.";
        }
    }

    // --- Handle Errors and Redirect ---
    // If there were any errors during validation, connection, or database operation,
    // store them in the session to display on the admin flight list page.
    if (!empty($errors)) {
        // Combine all errors into a single message for display.
        $_SESSION['delete_message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = 'danger'; // Indicate failure.
        // Log the generated errors for debugging.
        error_log("Errors generated in admin_delete_flight.php: " . print_r($errors, true));
    }

    // Close database connection if it was successfully opened.
    if (isset($connection) && $connection) {
        mysqli_close($connection);
    }

    // *** Always redirect back to the admin flight list page ***
    // The list page will display the appropriate messages from the session.
    header('Location: admin_flight_list.php');
    exit(); // Stop further script execution after redirection.

} else {
    // If the script is accessed directly via GET request (not form submission).
    // Redirect to the admin flight list page.
    header('Location: admin_flight_list.php');
    exit(); // Stop further script execution.
}
?>