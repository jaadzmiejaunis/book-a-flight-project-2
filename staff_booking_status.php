<?php
session_start(); // Start the session - MUST be the very first thing in the file

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed on staff booking status page: " . mysqli_connect_error());
    die("An error occurred connecting to the database.");
}
// --- End Database Connection ---

// --- Pagination Setup ---
$limit = 10; // Number of bookings per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page, default to 1
$page = max($page, 1); // Ensure page is not less than 1
$offset = ($page - 1) * $limit; // Calculate the offset for the SQL query

// --- Staff Authentication and Data Retrieval ---
$loggedIn = isset($_SESSION['book_id']);
$username = 'Staff Member';
$profilePictureUrl = '/college_project/book-a-flight-project-2/image_website/default_profile.png';

// Check if a user is logged in and is a staff member
if ($loggedIn) {
    $user_id = $_SESSION['book_id'];

    // Fetch user details from the database using a prepared statement for security
    $sql = "SELECT book_username, book_user_roles, book_profile FROM BookUser WHERE book_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        // Now check if the user is actually a Staff member
        if ($user['book_user_roles'] !== 'Staff') {
            header('Location: login_page.php');
            exit();
        }
        $username = htmlspecialchars($user['book_username']);
        if (!empty($user['book_profile'])) {
            $profilePictureUrl = htmlspecialchars($user['book_profile']);
        }
    } else {
        // User not found, redirect to login
        header('Location: login_page.php');
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    // Not logged in, redirect to login
    header('Location: login_page.php');
    exit();
}

// --- Fetch All Booking History ---
$booking_history = [];
$error_message = '';

// First, get the total number of bookings for pagination
$sql_total = "SELECT COUNT(DISTINCT bs.status_id) 
              FROM BookFlightStatus bs
              LEFT JOIN BookFlightPlace fp ON bs.book_id = fp.book_id AND bs.user_id = fp.user_id";

$total_result = mysqli_query($connection, $sql_total);
$total_rows = mysqli_fetch_array($total_result)[0];
$total_pages = ceil($total_rows / $limit);

// Updated SQL query to get data from BookFlightStatus with location information
$sql_history = "SELECT 
    bs.status_id,
    bs.book_id,
    bs.user_id,
    bs.book_username,
    fp.book_origin_state,
    fp.book_origin_country,
    fp.book_destination_state,
    fp.book_destination_country,
    fp.book_departure,
    fp.book_return,
    bs.book_class,
    bs.book_airlines,
    bs.book_price,
    bs.booking_date,
    bs.booking_status,
    CONCAT('FL', LPAD(bs.status_id, 2, '0')) AS booking_id_formatted
FROM BookFlightStatus bs
LEFT JOIN BookFlightPlace fp ON bs.book_id = fp.book_id AND bs.user_id = fp.user_id
ORDER BY bs.booking_date DESC
LIMIT ? OFFSET ?";

// Use a prepared statement for security
if ($stmt_history = mysqli_prepare($connection, $sql_history)) {
    // Bind the limit and offset variables
    mysqli_stmt_bind_param($stmt_history, "ii", $limit, $offset);
    mysqli_stmt_execute($stmt_history);
    $result_history = mysqli_stmt_get_result($stmt_history);

    if ($result_history) {
        while ($row = mysqli_fetch_assoc($result_history)) {
            $booking_history[] = $row;
        }
        mysqli_free_result($result_history);
    } else {
        error_log("Database query error on staff booking list: " . mysqli_error($connection));
        $error_message = "An error occurred fetching booking history. Please try again.";
    }
    mysqli_stmt_close($stmt_history);
} else {
    $error_message = "Error preparing query: " . mysqli_error($connection);
}

// --- Handle Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_id']) && isset($_POST['new_status'])) {
    $status_id = $_POST['status_id'];
    $new_status = $_POST['new_status'];
    
    // Update status in BookFlightStatus table
    $update_sql = "UPDATE BookFlightStatus SET booking_status = ? WHERE status_id = ?";
    $update_stmt = mysqli_prepare($connection, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "si", $new_status, $status_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['process_message'] = "Booking status updated successfully!";
        $_SESSION['message_type'] = "success";
        
        // Also update BookHistory table if the record exists
        $update_history_sql = "UPDATE BookHistory SET booking_status = ? WHERE history_id = ?";
        $update_history_stmt = mysqli_prepare($connection, $update_history_sql);
        mysqli_stmt_bind_param($update_history_stmt, "si", $new_status, $status_id);
        mysqli_stmt_execute($update_history_stmt);
        mysqli_stmt_close($update_history_stmt);
    } else {
        $_SESSION['process_message'] = "Error updating booking status: " . mysqli_error($connection);
        $_SESSION['message_type'] = "danger";
    }
    mysqli_stmt_close($update_stmt);
    
    // Redirect to refresh the page and show message
    $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $redirect_url = "staff_booking_status.php";
    if ($current_page > 1) {
        $redirect_url .= "?page=" . $current_page;
    }
    header("Location: " . $redirect_url);
    exit();
}

// --- Handle Booking Deletion ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_status_id'])) {
    $status_id = $_POST['delete_status_id'];
    
    // Get book_id before deletion for cleaning up related tables
    $get_book_sql = "SELECT book_id, user_id FROM BookFlightStatus WHERE status_id = ?";
    $get_book_stmt = mysqli_prepare($connection, $get_book_sql);
    mysqli_stmt_bind_param($get_book_stmt, "i", $status_id);
    mysqli_stmt_execute($get_book_stmt);
    $book_result = mysqli_stmt_get_result($get_book_stmt);
    $book_data = mysqli_fetch_assoc($book_result);
    mysqli_stmt_close($get_book_stmt);
    
    if ($book_data) {
        $book_id = $book_data['book_id'];
        $user_id = $book_data['user_id'];
        
        // Delete from BookFlightStatus
        $delete_sql = "DELETE FROM BookFlightStatus WHERE status_id = ?";
        $delete_stmt = mysqli_prepare($connection, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $status_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            // Also delete from related tables
            $tables_to_clean = ['BookFlightPlace', 'BookFlightPassenger', 'BookFlightPrice'];
            foreach ($tables_to_clean as $table) {
                $clean_sql = "DELETE FROM $table WHERE book_id = ? AND user_id = ?";
                $clean_stmt = mysqli_prepare($connection, $clean_sql);
                mysqli_stmt_bind_param($clean_stmt, "si", $book_id, $user_id);
                mysqli_stmt_execute($clean_stmt);
                mysqli_stmt_close($clean_stmt);
            }
            
            $_SESSION['process_message'] = "Booking deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['process_message'] = "Error deleting booking: " . mysqli_error($connection);
            $_SESSION['message_type'] = "danger";
        }
        mysqli_stmt_close($delete_stmt);
    }
    
    // Redirect to refresh the page and show message
    $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $redirect_url = "staff_booking_status.php";
    if ($current_page > 1) {
        $redirect_url .= "?page=" . $current_page;
    }
    header("Location: " . $redirect_url);
    exit();
}

// Close database connection
mysqli_close($connection);

// --- Handle Success or Error Messages from Processing Scripts ---
$process_message = $_SESSION['process_message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

unset($_SESSION['process_message']);
unset($_SESSION['message_type']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Booking Status - SierraFlight</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain the same */
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
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
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
            display: flex;
            align-items: center;
        }
        .top-gradient-bar .site-title:hover {
            text-decoration: underline;
        }
        .top-gradient-bar .site-title .sierraflight-logo {
            width: 150px;
            height: auto;
            margin-right: 10px;
            vertical-align: middle;
        }

        .top-gradient-bar .user-info {
            display: flex;
            align-items: center;
            color: white;
            flex-shrink: 0;
            margin-left: auto;
            white-space: nowrap;
        }

        .top-gradient-bar .profile-picture-nav {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
            object-fit: cover;
            border: 1px solid white;
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
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
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
            background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"%3E%3Cpath fill="none" stroke="%23ced4da" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2 5 6 6 6-6"/%3E%3Csvg%3E');
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
        
        /* PAGINATION CSS */
        .pagination {
            justify-content: center;
        }
        .pagination .page-item .page-link {
            background-color: #3a3e52;
            border-color: #5a5a8a;
            color: #e0e0e0;
            margin: 0 2px;
            border-radius: 4px;
        }
        .pagination .page-item.active .page-link {
            background-color: #ffb03a;
            border-color: #ffb03a;
            color: #1e1e2d;
            font-weight: bold;
        }
        .pagination .page-item.disabled .page-link {
            background-color: #282b3c;
            border-color: #5a5a8a;
            color: #6c757d;
        }
        .pagination .page-item .page-link:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="homepage.php" class="site-title">
                <img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo">
                <span>(Staff)</span>
            </a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                    <span>Welcome, <?php echo $username; ?>!</span>
                    <a href="profile_page.php">
                        <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav">
                    </a>
                    <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="homepage.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="staff_sales_report.php">Sales Report</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="staff_booking_status.php">View Booking Status <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="staff_user_feedback.php">User Feedback</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile_page.php">Profile</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="admin-container">
            <div class="admin-header">
                Customer's Booking Status
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
                <div class="table-responsive">
                    <table class="table booking-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
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
                                    <td><?php echo htmlspecialchars($booking['booking_id_formatted']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['book_username']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['book_origin_state'] . ', ' . $booking['book_origin_country']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['book_destination_state'] . ', ' . $booking['book_destination_country']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['book_departure']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['book_return'] ?? 'One-way'); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['book_class']))); ?></td>
                                    <td><?php echo htmlspecialchars($booking['book_airlines']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($booking['book_price'], 2)); ?></td>
                                    <td>
                                        <span class="status-<?php echo htmlspecialchars($booking['booking_status']); ?>">
                                            <?php echo htmlspecialchars($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" style="display:inline-block;">
                                            <input type="hidden" name="status_id" value="<?php echo htmlspecialchars($booking['status_id']); ?>">
                                            <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>"> <select name="new_status" class="booking-status-select">
                                                <option value="Pending" <?php echo ($booking['booking_status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Booked" <?php echo ($booking['booking_status'] === 'Booked') ? 'selected' : ''; ?>>Booked</option>
                                                <option value="Cancelled" <?php echo ($booking['booking_status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                                        </form>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                            <input type="hidden" name="delete_status_id" value="<?php echo htmlspecialchars($booking['status_id']); ?>">
                                            <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>"> <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page > 1) ? '?page=' . ($page - 1) : '#'; ?>">Previous</a>
                            </li>

                            <?php 
                            $window = 2; // How many pages to show around the current page
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)):
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php 
                                // Show '...' for gaps
                                elseif ($i == $page - $window - 1 || $i == $page + $window + 1):
                            ?>
                                <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
                            <?php 
                                endif;
                            endfor; 
                            ?>

                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page < $total_pages) ? '?page=' . ($page + 1) : '#'; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
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