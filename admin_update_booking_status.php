<?php
session_start(); // Start the session

// --- Admin Authentication Check ---
// Ensures only logged-in admins can access this script
if (!isset($_SESSION['book_id']) || $_SESSION['username'] !== 'Admin') {
    // Redirect to login page or show an access denied message
    header('Location: login_page.php'); // Redirect to your login page
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get booking ID and new status from POST data
    $history_id = $_POST['history_id'] ?? null;
    $new_status = $_POST['new_status'] ?? null;

    // --- Validate Data ---
    $errors = [];
    // Validate that history_id is provided and is a valid integer
    if (!$history_id || !filter_var($history_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Invalid booking ID provided.";
    }

    // Define allowed statuses to prevent arbitrary data insertion
    $allowed_statuses = ['Pending', 'Booked', 'Cancelled'];
    // Validate that the provided new_status is one of the allowed values
    if (!in_array($new_status, $allowed_statuses)) {
        $errors[] = "Invalid status provided.";
    }

    // --- Database Connection ---
    include 'connection.php';

    // Check database connection
    if (!$connection) {
        // Log the specific connection error
        error_log("Database connection failed in admin status update: " . mysqli_connect_error());
        $errors[] = "An error occurred connecting to the database.";
    }

    // --- Update Booking Status (Only if no validation errors and connection is successful) ---
    if (empty($errors) && $connection) {
        // Prepare the SQL UPDATE statement to change the booking_status for a specific history_id
        $sql = "UPDATE BookHistory SET booking_status = ? WHERE history_id = ?";
        $stmt = mysqli_prepare($connection, $sql);

        // Check if the prepared statement was successful
        if ($stmt) {
            // Bind parameters to the prepared statement
            // 's' for the new_status (string), 'i' for the history_id (integer)
            mysqli_stmt_bind_param($stmt, "si", $new_status, $history_id);

            // Execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Update was successful
                // Store a success message in the session to display on the next page
                $_SESSION['process_message'] = "Booking status updated successfully!";
                $_SESSION['message_type'] = 'success'; // Type for Bootstrap alert styling
            } else {
                // Update failed
                // Log the specific MySQL error
                error_log("Database update error in admin status update: " . mysqli_error($connection));
                $errors[] = "An error occurred updating the booking status.";
            }
            // Close the prepared statement
            mysqli_stmt_close($stmt);

        } else {
            // Prepared statement failed
            // Log the specific MySQL error
            error_log("Database prepare error for admin status update: " . mysqli_error($connection));
            $errors[] = "An internal error occurred preparing to update status.";
        }
    }

    // If there were any errors during the process (validation or database), store them in the session
    if (!empty($errors)) {
        // Combine all errors into a single message
        $_SESSION['process_message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = 'danger'; // Type for Bootstrap alert styling
    }

    // Close database connection if it was successfully opened
    if ($connection) {
        mysqli_close($connection);
    }

    // Redirect back to the admin booking list page to show the updated list and messages
    header('Location: admin_booking_list.php');
    exit(); // Stop further script execution after redirection

} else {
    // If the script is accessed directly via GET request (not form submission)
    // Redirect to the admin booking list page
    header('Location: admin_booking_list.php');
    exit(); // Stop further script execution
}
?>
