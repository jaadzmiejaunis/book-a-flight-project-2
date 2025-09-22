<?php
session_start(); // Start the session

// --- Admin Authentication Check ---
// Ensures only logged-in admins can access this script for adding flights
if (!isset($_SESSION['book_id']) || !isset($_SESSION['book_user_roles']) || $_SESSION['book_user_roles'] !== 'Admin') {
    // Redirect to login page or show an access denied message
    header('Location: login_page.php');
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get all the form data from the POST request
    $origin_state = trim($_POST['origin_state'] ?? '');
    $origin_country = trim($_POST['origin_country'] ?? '');
    $destination_state = trim($_POST['destination_state'] ?? '');
    $destination_country = trim($_POST['destination_country'] ?? '');
    $departure_date = $_POST['departure_date'] ?? null;
    $return_date = $_POST['return_date'] ?? null;
    $class = $_POST['class'] ?? '';
    $airlines = $_POST['airlines'] ?? '';
    $price = $_POST['pricing'] ?? null; // The form field name is 'pricing'

    // --- Validate Data ---
    $errors = [];

    // Basic validation for required fields
    if (empty($origin_state)) { $errors[] = "Origin State is required."; }
    if (empty($origin_country)) { $errors[] = "Origin Country is required."; }
    if (empty($destination_state)) { $errors[] = "Destination State is required."; }
    if (empty($destination_country)) { $errors[] = "Destination Country is required."; }
    if (empty($departure_date)) { $errors[] = "Departure Date is required."; }
    if (empty($class)) { $errors[] = "Class is required."; }
    if (empty($airlines)) { $errors[] = "Airline is required."; }
    // Validate price is a valid number
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Valid Price is required.";
    }

    // --- Database Connection ---
    include 'connection.php';

    // Check database connection
    if (!$connection) {
        error_log("Database connection failed in booking process: " . mysqli_connect_error());
        $errors[] = "An error occurred connecting to the database.";
    }

    // --- Perform Insertion (Only if no validation errors and connection is successful) ---
    if (empty($errors) && $connection) {
        // SQL INSERT statement with placeholders (?) to prevent SQL injection
        $sql = "INSERT INTO BookFlight (book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt) {
            // Bind parameters to the prepared statement
            // sssssssd (8 strings, 1 double) - Total 9 parameters
            mysqli_stmt_bind_param($stmt, "ssssssssd",
                $origin_state,
                $origin_country,
                $destination_state,
                $destination_country,
                $departure_date,
                $return_date,
                $class,
                $airlines,
                $price
            );

            // Execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Insertion was successful
                $_SESSION['update_message'] = "New flight added successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                // Insertion failed
                error_log("Database insert error in booking process: " . mysqli_error($connection));
                $errors[] = "An error occurred adding the new flight.";
            }
            // Close the prepared statement
            mysqli_stmt_close($stmt);

        } else {
            // Prepared statement failed
            error_log("Database prepare error for booking process: " . mysqli_error($connection));
            $errors[] = "An internal error occurred preparing to add a new flight.";
        }
    }

    // If there were any errors during the process, store them in the session
    if (!empty($errors)) {
        $_SESSION['update_message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = 'danger';
    }

    // Close database connection
    if ($connection) {
        mysqli_close($connection);
    }

    // Always redirect back to the admin flight list page
    header('Location: admin_flight_list.php');
    exit();

} else {
    // If the script is accessed directly via GET request (not form submission)
    // Redirect to the admin flight list page
    header('Location: admin_flight_list.php');
    exit();
}
?>