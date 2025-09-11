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

    // Get all the form data from the POST request
    $flight_id = $_POST['flight_id'] ?? null;
    $origin_state = trim($_POST['origin_state'] ?? '');
    $origin_country = trim($_POST['origin_country'] ?? '');
    $destination_state = trim($_POST['destination_state'] ?? '');
    $destination_country = trim($_POST['destination_country'] ?? '');
    $departure_date = $_POST['departure_date'] ?? null;
    $return_date = $_POST['return_date'] ?? null;
    $class = $_POST['class'] ?? '';
    $airlines = $_POST['airlines'] ?? '';
    $price = $_POST['price'] ?? null; // Price is expected as a number

    // --- Validate Data ---
    $errors = [];

    // Validate flight_id
    if (!$flight_id || !filter_var($flight_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Invalid flight ID provided for update.";
    }

    // Basic validation for required fields (adjust as needed)
    if (empty($origin_state)) { $errors[] = "Origin State is required."; }
    if (empty($origin_country)) { $errors[] = "Origin Country is required."; }
    if (empty($destination_state)) { $errors[] = "Destination State is required."; }
    if (empty($destination_country)) { $errors[] = "Destination Country is required."; }
    if (empty($departure_date)) { $errors[] = "Departure Date is required."; }
    if (empty($return_date)) { $errors[] = "Return Date is required."; }
    if (empty($class)) { $errors[] = "Class is required."; }
    if (empty($airlines)) { $errors[] = "Airline is required."; }
    // Validate price is a valid number
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Valid Price is required.";
    }

    // You might add more specific date validation here if needed

    // --- Database Connection ---
    include 'connection.php';

    // Check database connection
    if (!$connection) {
        error_log("Database connection failed in admin update flight: " . mysqli_connect_error());
        $errors[] = "An error occurred connecting to the database.";
    }

    // --- Perform Update (Only if no validation errors and connection is successful) ---
    if (empty($errors) && $connection) {
        // Prepare the SQL UPDATE statement
        $sql = "UPDATE BookFlight SET book_origin_state = ?, book_origin_country = ?, book_destination_state = ?, book_destination_country = ?, book_departure = ?, book_return = ?, book_class = ?, book_airlines = ?, book_price = ? WHERE book_id = ?";

        $stmt = mysqli_prepare($connection, $sql);

        // Check if the prepared statement was successful
        if ($stmt) {
            // Bind parameters to the prepared statement
            // ssssssssd i (9 strings, 1 double, 1 integer) - Total 11 parameters
            mysqli_stmt_bind_param($stmt, "ssssssssdi",
                $origin_state,
                $origin_country,
                $destination_state,
                $destination_country,
                $departure_date,
                $return_date,
                $class,
                $airlines,
                $price,
                $flight_id // The WHERE clause parameter
            );

            // Execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Update was successful
                // Check if any rows were affected (meaning the flight ID existed)
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $_SESSION['update_message'] = "Flight updated successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    // No rows affected - flight ID might have been valid but didn't exist or no changes were made
                    $_SESSION['update_message'] = "Flight with ID " . htmlspecialchars($flight_id) . " found, but no changes were made or saved.";
                    $_SESSION['message_type'] = 'info'; // Use info type for no changes
                }
            } else {
                // Update failed
                error_log("Database update error in admin update flight: " . mysqli_error($connection));
                $errors[] = "An error occurred updating the flight.";
            }
            // Close the prepared statement
            mysqli_stmt_close($stmt);

        } else {
            // Prepared statement failed
            error_log("Database prepare error for admin update flight: " . mysqli_error($connection));
            $errors[] = "An internal error occurred preparing to update flight.";
        }
    }

    // If there were any errors during the process, store them in the session
    if (!empty($errors)) {
        $_SESSION['update_message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = 'danger';
        // We are no longer redirecting back to the edit page on error,
        // so we don't need to store form data in session here.
    }

    // Close database connection if it was successfully opened
    if ($connection) {
        mysqli_close($connection);
    }

    // *** Always redirect back to the admin flight list page ***
    header('Location: admin_flight_list.php');
    exit(); // Stop further script execution after redirection

} else {
    // If the script is accessed directly via GET request (not form submission)
    // Redirect to the admin flight list page
    header('Location: admin_flight_list.php');
    exit(); // Stop further script execution
}
?>
