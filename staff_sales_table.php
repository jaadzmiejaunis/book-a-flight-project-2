<?php
session_start(); // Start the session - MUST be the very first thing in the file

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed on staff sales table page: " . mysqli_connect_error());
    die("An error occurred connecting to the database.");
}
// --- End Database Connection ---

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

// --- Get Month Parameter ---
$selected_month = $_GET['month'] ?? date('Y-m');
$month_name = date('F Y', strtotime($selected_month));

// --- Get Sort Parameters ---
$sort_column = $_GET['sort'] ?? 'booking_date';
$sort_order = $_GET['order'] ?? 'desc';

// Validate sort parameters
$allowed_columns = ['booking_id_formatted', 'book_username', 'book_origin_state', 'book_destination_state', 'book_departure', 'book_return', 'book_class', 'book_airlines', 'book_price', 'booking_date'];
$sort_column = in_array($sort_column, $allowed_columns) ? $sort_column : 'booking_date';
$sort_order = $sort_order === 'asc' ? 'asc' : 'desc';

// --- Fetch Booking Data ---
$booking_data = [];
$error_message = '';
$total_revenue = 0;

// SQL query to get booking data
$sql = "SELECT 
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
    CONCAT('FL', LPAD(bs.status_id, 2, '0')) AS booking_id_formatted,
    bp.book_no_adult,
    bp.book_no_children,
    bp.book_food_drink,
    bpr.book_ticket_price,
    bpr.book_food_drink_price,
    bpr.book_total_price
FROM BookFlightStatus bs
LEFT JOIN BookFlightPlace fp ON bs.book_id = fp.book_id AND bs.user_id = fp.user_id
LEFT JOIN BookFlightPassenger bp ON bs.book_id = bp.book_id AND bs.user_id = bp.user_id
LEFT JOIN BookFlightPrice bpr ON bs.book_id = bpr.book_id AND bs.user_id = bpr.user_id
WHERE bs.booking_status = 'Booked' 
AND DATE_FORMAT(bs.booking_date, '%Y-%m') = ?
ORDER BY $sort_column $sort_order";

if ($stmt = mysqli_prepare($connection, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $selected_month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $booking_data[] = $row;
            $total_revenue += $row['book_price'];
        }
        mysqli_free_result($result);
    } else {
        error_log("Database query error on staff sales table: " . mysqli_error($connection));
        $error_message = "An error occurred fetching booking data. Please try again.";
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Error preparing query: " . mysqli_error($connection);
}

// Get available months for navigation
$months_sql = "SELECT DISTINCT DATE_FORMAT(booking_date, '%Y-%m') as month FROM BookFlightStatus WHERE booking_status = 'Booked' ORDER BY month DESC";
$months_result = mysqli_query($connection, $months_sql);
$available_months = [];
while ($row = mysqli_fetch_assoc($months_result)) {
    $available_months[] = $row['month'];
}

// Close database connection
mysqli_close($connection);

// Function to generate sort URL
function getSortUrl($column, $current_sort, $current_order, $month) {
    $order = 'asc';
    if ($current_sort === $column && $current_order === 'asc') {
        $order = 'desc';
    }
    return "staff_sales_table.php?month=$month&sort=$column&order=$order";
}

// Function to get sort indicator
function getSortIndicator($column, $current_sort, $current_order) {
    if ($current_sort === $column) {
        return $current_order === 'asc' ? '↑' : '↓';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - SierraFlight</title>
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

        .top-gradient-bar .profile-picture-nav, .top-gradient-bar .profile-icon-nav {
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
            max-width: 1400px;
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

        .month-navigation {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #3a3e52;
            border-radius: 8px;
        }

        .month-link {
            padding: 8px 16px;
            background-color: #2d3042;
            color: #e0e0e0;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            border: 1px solid #5a5a8a;
        }

        .month-link:hover {
            background-color: #ffb03a;
            color: #1e1e2d;
            text-decoration: none;
        }

        .month-link.active {
            background-color: #ffb03a;
            color: #1e1e2d;
            font-weight: bold;
        }

        .page-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .page-action-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #0D1164, #EA2264);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .page-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            color: white;
            text-decoration: none;
        }

        .print-button {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            padding: 10px 15px;
            margin-bottom: 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .sales-table th, .sales-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #5a5a8a;
            color: #ccc;
        }
        .sales-table th {
            background-color: #3a3e52;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            position: relative;
            transition: background-color 0.3s ease;
        }

        .sales-table th:hover {
            background-color: #4a4e62;
        }

        .sales-table th .sort-indicator {
            margin-left: 5px;
            color: #ffb03a;
        }

        .sales-table tbody tr:hover {
            background-color: #343a40;
        }

        .no-bookings {
            text-align: center;
            color: #ccc;
            margin-top: 20px;
            padding: 40px;
            background-color: #3a3e52;
            border-radius: 8px;
        }
        
        /* Print Styles */
        @media print {
            body {
                background-color: #fff;
                color: #000;
            }
            .top-gradient-bar, .navbar, .print-button, .month-navigation, .page-actions {
                display: none;
            }
            .container.page-content, .admin-container {
                width: 100% !important;
                max-width: none !important;
                padding: 0;
                margin: 0;
                box-shadow: none;
                background-color: #fff;
            }
            .admin-header {
                background: none;
                color: #000;
                margin: 0;
                padding: 10px 0;
            }
            .sales-table {
                width: 100% !important;
                border-collapse: collapse;
                font-size: 10pt;
            }
            .sales-table th, .sales-table td {
                color: #000;
                border: 1px solid #000;
                padding: 8px;
            }
            .sales-table th {
                background-color: #e0e0e0;
                cursor: default;
            }
            .sales-table td {
                vertical-align: top;
            }
        }
    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="homepage.php" class="site-title">SierraFlight (Staff)</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                    <span>Welcome, <?php echo $username; ?>!</span>
                    <a href="profile_page.php">
                        <?php if (empty($profilePictureUrl) || strpos($profilePictureUrl, 'default') !== false): ?>
                            <i class="fas fa-user-circle fa-lg profile-icon-nav"></i>
                        <?php else: ?>
                            <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav">
                        <?php endif; ?>
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
                        <a class="nav-link" href="staff_sales_report.php">Sales Analytics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="staff_sales_table.php">Booking Details</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="staff_booking_status.php">View Booking Status</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_booking_list.php">User Feedback</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="admin-container">
            <div class="admin-header">
                Booking Details - <?php echo $month_name; ?>
            </div>

            <!-- Month Navigation -->
            <div class="month-navigation">
                <?php foreach ($available_months as $month): ?>
                    <?php
                    $month_display = date('F Y', strtotime($month));
                    $is_active = $month === $selected_month ? 'active' : '';
                    ?>
                    <a href="staff_sales_table.php?month=<?php echo $month; ?>" 
                       class="month-link <?php echo $is_active; ?>">
                        <?php echo $month_display; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Page Actions -->
            <div class="page-actions">
                <a href="staff_sales_report.php?month=<?php echo $selected_month; ?>" class="page-action-btn">
                    <i class="fas fa-chart-bar"></i> View Analytics
                </a>
                <a href="staff_sales_table.php?month=<?php echo $selected_month; ?>" class="page-action-btn">
                    <i class="fas fa-table"></i> View Booking Details
                </a>
            </div>

            <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($booking_data)): ?>
                <!-- Detailed Booking Table -->
                <button onclick="window.print()" class="btn btn-primary print-button">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <div class="table-responsive">
                    <table class="table sales-table">
                        <thead>
                            <tr>
                                <th onclick="window.location.href='<?php echo getSortUrl('booking_id_formatted', $sort_column, $sort_order, $selected_month); ?>'">
                                    Booking ID <?php echo getSortIndicator('booking_id_formatted', $sort_column, $sort_order); ?>
                                </th>
                                <th onclick="window.location.href='<?php echo getSortUrl('book_username', $sort_column, $sort_order, $selected_month); ?>'">
                                    Username <?php echo getSortIndicator('book_username', $sort_column, $sort_order); ?>
                                </th>
                                <th onclick="window.location.href='<?php echo getSortUrl('book_origin_state', $sort_column, $sort_order, $selected_month); ?>'">
                                    Origin <?php echo getSortIndicator('book_origin_state', $sort_column, $sort_order); ?>
                                </th>
                                <th onclick="window.location.href='<?php echo getSortUrl('book_destination_state', $sort_column, $sort_order, $selected_month); ?>'">
                                    Destination <?php echo getSortIndicator('book_destination_state', $sort_column, $sort_order); ?>
                                </th>
                                <th onclick="window.location.href='<?php echo getSortUrl('book_departure', $sort_column, $sort_order, $selected_month); ?>'">
                                    Departure <?php echo getSortIndicator('book_departure', $sort_column, $sort_order); ?>
                                </th>
                                <th onclick="window.location.href='<?php echo getSortUrl('book_return', $sort_column, $sort_order, $selected_month); ?>'">
                                    Return <?php echo getSortIndicator('book_return', $sort_column, $sort_order); ?>
                                </th>
                                <th onclick="window.location.href='<?php echo getSortUrl('book_class', $sort_column, $sort_order, $selected_month); ?>'">
                                    Class <?php echo getSortIndicator('book_class', $sort_column, $sort_order); ?>
                                </th>
                                <th onclick="window.location.href='<?php echo getSortUrl('book_airlines', $sort_column, $sort_order, $selected_month); ?>'">
                                    Airlines <?php echo getSortIndicator('book_airlines', $sort_column, $sort_order); ?>
                                </th>
                                <th>Passengers</th>
                                <th>Food/Drinks</th>
                                <th onclick="window.location.href='<?php echo getSortUrl('book_price', $sort_column, $sort_order, $selected_month); ?>'">
                                    Price (RM) <?php echo getSortIndicator('book_price', $sort_column, $sort_order); ?>
                                </th>
                                <th onclick="window.location.href='<?php echo getSortUrl('booking_date', $sort_column, $sort_order, $selected_month); ?>'">
                                    Booking Date <?php echo getSortIndicator('booking_date', $sort_column, $sort_order); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($booking_data as $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['booking_id_formatted']); ?></td>
                                    <td><?php echo htmlspecialchars($data['book_username']); ?></td>
                                    <td><?php echo htmlspecialchars($data['book_origin_state'] . ', ' . $data['book_origin_country']); ?></td>
                                    <td><?php echo htmlspecialchars($data['book_destination_state'] . ', ' . $data['book_destination_country']); ?></td>
                                    <td><?php echo htmlspecialchars($data['book_departure']); ?></td>
                                    <td><?php echo htmlspecialchars($data['book_return'] ?? 'One-way'); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $data['book_class']))); ?></td>
                                    <td><?php echo htmlspecialchars($data['book_airlines']); ?></td>
                                    <td>
                                        <?php 
                                            $adults = $data['book_no_adult'] ?? 0;
                                            $children = $data['book_no_children'] ?? 0;
                                            echo $adults . ' Adult' . ($adults != 1 ? 's' : '');
                                            if ($children > 0) {
                                                echo ', ' . $children . ' Child' . ($children != 1 ? 'ren' : '');
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($data['book_food_drink'] ?? 'No'); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($data['book_price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($data['booking_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="10" style="text-align: right; font-weight: bold;">Total Revenue:</td>
                                <td colspan="2" style="font-weight: bold; color: #ffb03a;">
                                    RM <?php echo number_format($total_revenue, 2); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fas fa-table fa-3x mb-3" style="color: #ffb03a;"></i>
                    <h4>No Bookings Found</h4>
                    <p>No completed bookings found for <?php echo $month_name; ?>.</p>
                    <?php if (count($available_months) > 0): ?>
                        <p>Try selecting a different month from the navigation above.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>