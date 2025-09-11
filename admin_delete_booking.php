<?php
/**
 * admin_delete_booking.php
 * This script processes the request to delete a specific user booking from the database.
 * It receives the booking history ID via POST and performs the deletion.
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
// Ensure the script is accessed via a POST request (from the delete form on admin_booking_list.php).
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Retrieve and Validate Booking History ID ---
    // Get the booking history ID from POST data.
    $history_id = $_POST['history_id'] ?? null;

    // Initialize an array to collect errors.
    $errors = [];

    // Validate that history_id is provided and is a valid positive integer.
    if (!$history_id || !filter_var($history_id, FILTER_VALIDATE_INT) || $history_id <= 0) {
        $errors[] = "Invalid booking ID provided for deletion.";
        // Log invalid ID for debugging.
        error_log("Admin Delete Booking Error: Invalid booking history ID received: " . ($history_id === null ? 'NULL' : $history_id));
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
            error_log("Database connection failed in admin_delete_booking.php: " . mysqli_connect_error());
            // Add a user-friendly error message.
            $errors[] = "An error occurred connecting to the database. Please try again later.";
        }
    }
    // --- End Database Connection ---


    // --- Perform Database Deletion ---
    // Proceed only if there are no errors and database connection is successful.
    if (empty($errors) && $connection) {

        // SQL query to delete the booking history entry. Use a prepared statement.
        $sql = "DELETE FROM BookHistory WHERE history_id = ?";
        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt) {
            // Bind the booking history ID parameter ('i' for integer).
            mysqli_stmt_bind_param($stmt, "i", $history_id);

            // Execute the delete statement.
            if (mysqli_stmt_execute($stmt)) {
                // Check if any rows were affected by the delete.
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    // Deletion successful.
                    $_SESSION['process_message'] = "Booking ID " . htmlspecialchars($history_id) . " deleted successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    // No rows were affected, possibly because the booking history ID didn't exist.
                    $_SESSION['process_message'] = "Booking ID " . htmlspecialchars($history_id) . " not found or could not be deleted.";
                    $_SESSION['message_type'] = 'warning';
                    // Log this as it might indicate an issue.
                    error_log("Admin Delete Booking Warning: Delete affected 0 rows for ID " . $history_id . ". ID may not exist.");
                }
            } else {
                // Database deletion failed. Log the specific MySQL error.
                $db_error = mysqli_error($connection);
                error_log("Database execute error in admin_delete_booking.php: " . $db_error);
                $errors[] = "An error occurred deleting the booking from the database.";
            }

            // Close the prepared statement.
            mysqli_stmt_close($stmt);

        } else {
             // Prepared statement failed. Log the specific MySQL error.
            $db_error = mysqli_error($connection);
            error_log("Database prepare error in admin_delete_booking.php: " . $db_error);
            $errors[] = "An internal error occurred preparing to delete the booking.";
        }
    }

    // --- Handle Errors and Redirect ---
    // If there were any errors during validation, connection, or database operation,
    // store them in the session to display on the admin booking list page.
    if (!empty($errors)) {
        // If a success/warning message was already set (e.g., from a previous action), prepend errors
        // and change message type if necessary.
         if (isset($_SESSION['process_message']) && $_SESSION['message_type'] === 'success') {
              $_SESSION['process_message'] = "Errors encountered: " . implode("<br>", $errors) . "<br>" . $_SESSION['process_message'];
              $_SESSION['message_type'] = 'warning';
         } elseif (isset($_SESSION['process_message']) && $_SESSION['message_type'] === 'warning') {
              $_SESSION['process_message'] = "Errors encountered: " . implode("<br>", $errors) . "<br>" . $_SESSION['process_message'];
         }
         else {
             // No prior success message, just store the errors.
             $_SESSION['process_message'] = implode("<br>", $errors);
             $_SESSION['message_type'] = 'danger';
         }
         // Log the generated errors for debugging.
         error_log("Errors generated in admin_delete_booking.php: " . print_r($errors, true));
    }

    // Close database connection if it was successfully opened.
    if (isset($connection) && $connection) {
        mysqli_close($connection);
    }

    // *** Always redirect back to the admin booking list page ***
    // The list page will display the appropriate messages from the session.
    header('Location: admin_booking_list.php');
    exit(); // Stop further script execution after redirection.

} else {
    // If the script is accessed directly via GET request (not form submission).
    // Redirect to the admin booking list page.
    header('Location: admin_booking_list.php');
    exit(); // Stop further script execution.
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Booking List - BookAFlight.com</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #1e1e2d;
            color: #e0e0e0;
            font-family: sans-serif;
             margin: 0;
             padding: 0;
             display: flex;
             flex-direction: column;
             min-height: 100vh;
        }

        .top-gradient-bar {
            background-image: linear-gradient(to right, #3b2e8b, #ffb03a);
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .top-gradient-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
             max-width: 1140px;
             margin: 0 auto;
             flex-wrap: wrap;
        }

        .top-gradient-bar .site-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            margin-right: auto;
            white-space: nowrap;
        }
         .top-gradient-bar .site-title:hover {
              text-decoration: underline;
         }

        .top-gradient-bar .user-info {
            display: flex;
            align-items: center;
            color: white;
             flex-shrink: 0;
             margin-left: auto;
             white-space: nowrap;
        }

         .top-gradient-bar .btn-danger {
             background-color: #dc3545;
             border-color: #dc3545;
             padding: .3rem .6rem;
             font-size: .95rem;
             line-height: 1.5;
             border-radius: .2rem;
             margin-left: 10px;
         }
         .top-gradient-bar .btn-danger:hover {
             background-color: #c82333;
             border-color: #bd2130;
         }

        .navbar {
            background-color: #212529;
            padding: 0 20px;
            margin-bottom: 0;
            background-image: none;
            box-shadow: none;
            min-height: auto;
        }

        .navbar > .container {
             display: flex;
             align-items: center;
             width: 100%;
             max-width: 1140px;
             margin: 0 auto;
             padding: 0;
        }

        .navbar-brand,
        .navbar-toggler {
            display: none;
        }
        @media (max-width: 991.98px) {
             .navbar-toggler {
                 display: block;
                 padding: .25rem .75rem;
                 font-size: 1.25rem;
                 line-height: 1;
                 background-color: transparent;
                 border: 1px solid rgba(255, 255, 255, .1);
                 border-radius: .25rem;
             }
              .navbar-collapse {
                  background-color: #212529;
                  padding: 10px;
              }
               .navbar > .container {
                   justify-content: space-between;
              }
               .navbar-collapse {
                    flex-grow: 1;
               }
        }

        .navbar-nav .nav-link {
             padding: 8px 15px;
             color: white !important;
             transition: background-color 0.3s ease, text-decoration 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: underline;
            color: white !important;
        }

        .navbar-nav .nav-link:active {
             background-color: rgba(255, 255, 255, 0.2);
        }

        .page-content {
             padding: 20px;
             flex-grow: 1;
        }

        .admin-container {
            margin: 30px auto;
            max-width: 1200px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            padding: 30px;
             color: #e0e0e0;
        }

        .admin-header {
            background-image: linear-gradient(to right, #3b2e8b, #ffb03a);
            color: white;
            padding: 20px;
            margin: -30px -30px 30px -30px;
            text-align: center;
            font-size: 1.8rem;
            font-weight: bold;
             border-top-left-radius: 8px;
             border-top-right-radius: 8px;
        }

        .booking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .booking-table th, .booking-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #5a5a8a;
            color: #ccc;
        }
        .booking-table th {
            background-color: #3a3e52;
            color: #fff;
            font-weight: bold;
        }
        .booking-table tbody tr:hover {
            background-color: #343a40;
        }

        .booking-table .btn {
            padding: 5px 10px;
            margin-right: 5px;
            font-size: 0.9rem;
        }
         .booking-table .btn-secondary {
             background-color: #6c757d;
             border-color: #6c757d;
             color: white;
         }
         .booking-table .btn-secondary:hover {
             background-color: #5a6268;
             border-color: #545b62;
         }
         .booking-table .btn-danger {
             background-color: #dc3545;
             border-color: #dc3545;
         }
         .booking-table .btn-danger:hover {
             background-color: #c82333;
             border-color: #bd2130;
         }

        .booking-status-select {
            background-color: #3a3e52;
            color: #fff;
            border: 1px solid #5a5a8a;
            padding: 5px;
            border-radius: 4px;
            font-size: 0.9rem;
            appearance: none;
            background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"%3E%3Cpath fill="none" stroke="%23ced4da" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2 5 6 6 6-6"/%3E%3C/svg%3E');
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1em auto;
            padding-right: 1.5rem;
        }
        .booking-status-select:focus {
            border-color: #ffb03a;
            box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
            outline: none;
        }

         .status-Pending { color: #ffc107; }
         .status-Booked { color: #28a745; }
         .status-Cancelled { color: #dc3545; }

        .no-bookings {
            text-align: center;
            color: #ccc;
            margin-top: 20px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
             color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
         .alert-warning {
             color: #856404;
             background-color: #fff3cd;
             border-color: #ffeeba;
         }
          .alert-info {
              color: #0c5460;
              background-color: #d1ecf1;
              border-color: #bee5eb;
          }
    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container"> <a href="homepage_staff.php" class="site-title">BookAFlight.com (Admin)</a> <div class="user-info">
                <?php if ($loggedIn): ?>
                     <span>Admin</span>
                     <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container"> <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                     <li class="nav-item">
                         <a class="nav-link" href="homepage_staff.php">Home</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="about_staff.php">About</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="edit_book_flight.php">Add Flight</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="admin_flight_list.php">Flight List</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="admin_booking_list.php">Booking List</a>
                     </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="admin-container">
            <div class="admin-header">
                User Booking Database
            </div>

            <?php if (isset($process_message) && !empty($process_message)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>" role="alert">
                    <?php echo htmlspecialchars($process_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php elseif (!empty($booking_history)): ?>
                <table class="table booking-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Origin</th>
                            <th>Destination</th>
                            <th>Departure</th>
                            <th>Return</th>
                            <th>Class</th>
                            <th>Airline</th>
                            <th>Price (RM)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($booking_history as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['history_id']); ?></td>
                                <td><?php echo htmlspecialchars($booking['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($booking['book_username']); ?></td>
                                <td><?php echo htmlspecialchars($booking['book_origin_state'] . ', ' . $booking['book_origin_country']); ?></td>
                                <td><?php echo htmlspecialchars($booking['book_destination_state'] . ', ' . $booking['book_destination_country']); ?></td>
                                <td><?php echo htmlspecialchars($booking['book_departure']); ?></td>
                                <td><?php echo htmlspecialchars($booking['book_return']); ?></td>
                                <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['book_class']))); ?></td>
                                <td><?php echo htmlspecialchars($booking['book_airlines']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($booking['book_price'], 2)); ?></td>
                                <td>
                                     <span class="status-<?php echo htmlspecialchars($booking['booking_status']); ?>">
                                         <?php echo htmlspecialchars($booking['booking_status']); ?>
                                     </span>
                                </td>
                                <td>
                                     <form action="admin_update_booking_status.php" method="post" style="display:inline-block;">
                                         <input type="hidden" name="history_id" value="<?php echo htmlspecialchars($booking['history_id']); ?>">
                                         <select name="new_status" class="booking-status-select">
                                             <option value="Pending" <?php echo ($booking['booking_status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                             <option value="Booked" <?php echo ($booking['booking_status'] === 'Booked') ? 'selected' : ''; ?>>Booked</option>
                                             <option value="Cancelled" <?php echo ($booking['booking_status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                         </select>
                                         <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                                     </form>
                                      <form action="admin_delete_booking.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                         <input type="hidden" name="history_id" value="<?php echo htmlspecialchars($booking['history_id']); ?>">
                                         <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                     </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-bookings">No user bookings found.</p>
            <?php endif; ?>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>