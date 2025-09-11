<?php
session_start(); // Start the session

// --- Database Connection ---
include 'connection.php';

// Check connection
if (!$connection) {
    error_log("Database connection failed: " . mysqli_connect_error());
    $_SESSION['booking_errors'] = ["An error occurred connecting to the database. Please try again later."];
    header('Location: edit_book_flight.php');
    exit();
}

// Initialize errors array
$errors = [];

// --- Process Booking Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $originState = trim($_POST['from_state']);
    $originCountry = trim($_POST['from_country']);
    $destinationState = trim($_POST['to_state']);
    $destinationCountry = trim($_POST['to_country']);
    $departure_date = $_POST['departure_date'];
    $return_date = $_POST['return_date'] ?? null;
    $class = $_POST['class'];
    $airlines = $_POST['airlines'];
    $pricing = $_POST['pricing'];

    // --- Validation ---
    if (empty($originState)) $errors[] = "Origin state is required.";
    if (empty($originCountry)) $errors[] = "Origin country is required.";
    if (empty($destinationState)) $errors[] = "Destination state is required.";
    if (empty($destinationCountry)) $errors[] = "Destination country is required.";
    if (empty($departure_date)) $errors[] = "Departure date is required.";
    if (!empty($departure_date) && !strtotime($departure_date)) $errors[] = "Invalid departure date format.";
    if (!empty($return_date) && !strtotime($return_date)) $errors[] = "Invalid return date format.";
    if (empty($pricing)) {
        $errors[] = "Please enter pricing.";
    } elseif (!is_numeric($pricing) || $pricing < 0) {
        $errors[] = "Pricing must be a non-negative number.";
    }

    // --- Database Insertion ---
    if (empty($errors)) {
        $insert_sql = "INSERT INTO BookFlight (book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $insert_sql);

        if ($stmt) {
            $pricing_value = (double)$pricing;
            mysqli_stmt_bind_param($stmt, "ssssssssd", $originState, $originCountry, $destinationState, $destinationCountry, $departure_date, $return_date, $class, $airlines, $pricing_value);

            if (mysqli_stmt_execute($stmt)) {
                header('Location: booking_success.php');
                exit();
            } else {
                error_log("Database insert error: " . mysqli_error($connection));
                $errors[] = "An error occurred during booking. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Database prepare error: " . mysqli_error($connection));
            $errors[] = "An internal error occurred. Please try again.";
        }
    }

    // Store errors in session and redirect back
    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_form_data'] = $_POST;
        header('Location: edit_book_flight.php');
        exit();
    }
} else {
    header('Location: edit_book_flight.php');
    exit();
}

// Close database connection
if ($connection) {
    mysqli_close($connection);
}
?>