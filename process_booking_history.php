<?php
session_start(); // Start the session

// Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['book_id'], $_SESSION['username'])) {
    $_SESSION['booking_error'] = "Please log in to book a flight.";
    header('Location: login_page.php');
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get user details from session
    $user_id = $_SESSION['book_id'];
    $username = $_SESSION['username'];

    // Get flight_id and payment_method from POST data
    $flight_id = $_POST['flight_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;

    // --- Validate Data ---
    $errors = [];
    if (!$flight_id || !filter_var($flight_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Invalid flight selected.";
    }
    if (empty($payment_method)) {
        $errors[] = "Payment method is required.";
    }

    // --- Database Connection ---
    include 'connection.php';

    if (!$connection) {
        error_log("Database connection failed in booking process: " . mysqli_connect_error());
        $errors[] = "An error occurred connecting to the database.";
        $_SESSION['booking_error'] = implode("<br>", $errors);
        $redirect_url = 'book_a_flight.php';
        if ($flight_id && filter_var($flight_id, FILTER_VALIDATE_INT)) {
             $redirect_url = 'book_a_flight_detail.php?flight_id=' . urlencode($flight_id);
        }
        header('Location: ' . $redirect_url);
        exit();
    }
    // --- End Database Connection ---

    // --- Fetch Flight Details from Database ---
    $flight = null;
    if (empty($errors)) {
        $sql_fetch_flight = "SELECT book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price FROM BookFlight WHERE book_id = ?";
        $stmt_fetch = mysqli_prepare($connection, $sql_fetch_flight);

        if ($stmt_fetch) {
            mysqli_stmt_bind_param($stmt_fetch, "i", $flight_id);
            mysqli_stmt_execute($stmt_fetch);
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);

            if ($row_fetch = mysqli_fetch_assoc($result_fetch)) {
                $flight = $row_fetch;
            } else {
                $errors[] = "Flight not found.";
            }

            mysqli_free_result($result_fetch);
            mysqli_stmt_close($stmt_fetch);

        } else {
            error_log("Database prepare error for fetching flight in booking process: " . mysqli_error($connection));
            $errors[] = "An internal error occurred fetching flight details.";
        }
    }

    // --- Insert Booking into BookHistory Table ---
    if (empty($errors) && $flight) {
        // Define the initial status for a new booking
        $initial_status = 'Pending'; // New bookings start as Pending

        // Prepare the SQL INSERT statement, including the new 'booking_status' column
        $insert_sql = "INSERT INTO BookHistory (user_id, book_username, book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // Added booking_status

        $stmt_insert = mysqli_prepare($connection, $insert_sql);

        if ($stmt_insert) {
            // Bind parameters - now 12 parameters
            // user_id (i), username (s), origin state/country (ss), dest state/country (ss), dep date (s), ret date (s), class (s), airlines (s), price (d), status (s)
            // Total: 1i, 9s, 1d, 1s -> isssssssssds (12 characters)
            mysqli_stmt_bind_param($stmt_insert, "isssssssssds", // *** UPDATED TYPE STRING AND ADDED 's' FOR STATUS ***
                $user_id,
                $username,
                $flight['book_origin_state'],
                $flight['book_origin_country'],
                $flight['book_destination_state'],
                $flight['book_destination_country'],
                $flight['book_departure'],
                $flight['book_return'],
                $flight['book_class'],
                $flight['book_airlines'],
                $flight['book_price'],
                $initial_status // *** BINDING THE INITIAL STATUS ***
            );

            // Execute the insertion
            if (mysqli_stmt_execute($stmt_insert)) {
                // Insertion was successful!
                $_SESSION['booking_success_message'] = "TICKET FLIGHT BOOK SUCCESSFULLY!";
                header('Location: book_a_ticket_success.php'); // Redirect to the success page
                exit();
            } else {
                // Insertion failed
                error_log("Database insert error into BookHistory: " . mysqli_error($connection));
                $errors[] = "An error occurred while saving your booking to history.";
            }
            mysqli_stmt_close($stmt_insert);

        } else {
            error_log("Database prepare error for inserting booking history: " . mysqli_error($connection));
            $errors[] = "An internal error occurred preparing to save your booking.";
        }
    }

    // If there are any errors, store them in the session and redirect back
    if (!empty($errors)) {
        $_SESSION['booking_error'] = implode("<br>", $errors);
        $redirect_url = 'book_a_flight.php';
        if ($flight_id && filter_var($flight_id, FILTER_VALIDATE_INT)) {
             $redirect_url = 'book_a_flight_detail.php?flight_id=' . urlencode($flight_id);
        }
        header('Location: ' . $redirect_url);
        exit();
    }

} else {
    // If accessed directly without POST, redirect
    header('Location: book_a_flight.php');
    exit();
}

if ($connection) {
    mysqli_close($connection);
}
?>