<?php
session_start(); // Start the session - MUST be the very first thing in the file

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed on staff sales report page: " . mysqli_connect_error());
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

// --- Fetch Statistics Data ---
$error_message = '';
$total_revenue = 0;
$total_bookings = 0;
$total_passengers = 0;

// Statistics arrays
$airline_stats = [];
$class_stats = [];
$route_stats = [];
$monthly_stats = [];
$food_drink_stats = ['Yes' => 0, 'No' => 0];

// SQL query to get statistics data
$sql = "SELECT 
    bs.book_airlines,
    bs.book_class,
    bs.book_price,
    bs.booking_date,
    fp.book_origin_state,
    fp.book_destination_state,
    bp.book_no_adult,
    bp.book_no_children,
    bp.book_food_drink
FROM BookFlightStatus bs
LEFT JOIN BookFlightPlace fp ON bs.book_id = fp.book_id AND bs.user_id = fp.user_id
LEFT JOIN BookFlightPassenger bp ON bs.book_id = bp.book_id AND bs.user_id = bp.user_id
WHERE bs.booking_status = 'Booked' 
AND DATE_FORMAT(bs.booking_date, '%Y-%m') = ?";

if ($stmt = mysqli_prepare($connection, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $selected_month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $total_bookings++;
            $total_revenue += $row['book_price'];
            
            // Calculate total passengers
            $adults = $row['book_no_adult'] ?? 0;
            $children = $row['book_no_children'] ?? 0;
            $total_passengers += ($adults + $children);
            
            // Collect statistics
            $airline = $row['book_airlines'];
            $class = $row['book_class'];
            $route = $row['book_origin_state'] . ' to ' . $row['book_destination_state'];
            $month = date('F Y', strtotime($row['booking_date']));
            $food_drink = $row['book_food_drink'] ?? 'No';
            
            // Airline statistics
            if (!isset($airline_stats[$airline])) {
                $airline_stats[$airline] = ['count' => 0, 'revenue' => 0];
            }
            $airline_stats[$airline]['count']++;
            $airline_stats[$airline]['revenue'] += $row['book_price'];
            
            // Class statistics
            if (!isset($class_stats[$class])) {
                $class_stats[$class] = ['count' => 0, 'revenue' => 0];
            }
            $class_stats[$class]['count']++;
            $class_stats[$class]['revenue'] += $row['book_price'];
            
            // Route statistics
            if (!isset($route_stats[$route])) {
                $route_stats[$route] = ['count' => 0, 'revenue' => 0];
            }
            $route_stats[$route]['count']++;
            $route_stats[$route]['revenue'] += $row['book_price'];
            
            // Monthly statistics
            if (!isset($monthly_stats[$month])) {
                $monthly_stats[$month] = ['count' => 0, 'revenue' => 0];
            }
            $monthly_stats[$month]['count']++;
            $monthly_stats[$month]['revenue'] += $row['book_price'];
            
            // Food and drink statistics
            $food_drink_stats[$food_drink]++;
        }
        mysqli_free_result($result);
    } else {
        error_log("Database query error on staff sales report: " . mysqli_error($connection));
        $error_message = "An error occurred fetching statistics data. Please try again.";
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

// Calculate averages
$average_booking_value = $total_bookings > 0 ? $total_revenue / $total_bookings : 0;
$average_passengers_per_booking = $total_bookings > 0 ? $total_passengers / $total_bookings : 0;

// Sort statistics by revenue (descending)
uasort($airline_stats, function($a, $b) { return $b['revenue'] - $a['revenue']; });
uasort($class_stats, function($a, $b) { return $b['revenue'] - $a['revenue']; });
uasort($route_stats, function($a, $b) { return $b['revenue'] - $a['revenue']; });

// Close database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics - SierraFlight</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #3a3e52, #2d3042);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #ffb03a;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #ffb03a;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #ccc;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background-color: #3a3e52;
            padding: 25px;
            border-radius: 10px;
            height: 350px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .chart-title {
            text-align: center;
            margin-bottom: 20px;
            color: #ffb03a;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .no-data {
            text-align: center;
            color: #ccc;
            margin-top: 20px;
            padding: 40px;
            background-color: #3a3e52;
            border-radius: 8px;
        }

        /* Print Styles for Statistics */
        @media print {
            body {
                background-color: #fff !important;
                color: #000 !important;
                font-size: 12pt;
            }
            
            .top-gradient-bar, .navbar, .print-button, .month-navigation, .page-actions {
                display: none !important;
            }
            
            .container.page-content, .admin-container {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                background-color: #fff !important;
                color: #000 !important;
            }
            
            .admin-header {
                background: none !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 10px 0 !important;
                border: none !important;
            }
            
            .stats-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 15px !important;
                margin-bottom: 20px !important;
                page-break-inside: avoid;
            }
            
            .stat-card {
                background: #f8f9fa !important;
                border: 1px solid #dee2e6 !important;
                border-left: 4px solid #007bff !important;
                color: #000 !important;
                padding: 15px !important;
            }
            
            .stat-value {
                color: #007bff !important;
                font-size: 1.5rem !important;
            }
            
            .stat-label {
                color: #6c757d !important;
            }
            
            .charts-container {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 20px !important;
                margin-bottom: 20px !important;
                page-break-inside: avoid;
            }
            
            .chart-card {
                background: #fff !important;
                border: 1px solid #dee2e6 !important;
                height: 300px !important;
                page-break-inside: avoid;
            }
            
            .chart-title {
                color: #000 !important;
                font-size: 1.1rem !important;
            }
            
            /* Ensure charts print properly */
            canvas {
                max-width: 100% !important;
                height: auto !important;
            }
            
            /* Add print header */
            @page {
                margin: 1cm;
                size: landscape;
            }
            
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            
            .print-header h1 {
                color: #000;
                font-size: 18pt;
                margin: 0;
            }
            
            .print-header .subtitle {
                color: #666;
                font-size: 12pt;
                margin: 5px 0 0 0;
            }
        }

        /* Hide print header on screen */
        .print-header {
            display: none;
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
                    <li class="nav-item active">
                        <a class="nav-link" href="staff_sales_report.php">Sales Report <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="staff_booking_status.php">View Booking Status</a>
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
            <div class="print-header">
                <h1>SierraFlight - Sales Analytics Report</h1>
                <div class="subtitle"><?php echo $month_name; ?> | Generated on: <?php echo date('F j, Y'); ?></div>
            </div>

            <div class="admin-header">
                Sales Analytics - <?php echo $month_name; ?>
            </div>

            <div class="month-navigation">
                <?php foreach ($available_months as $month): ?>
                    <?php
                    $month_display = date('F Y', strtotime($month));
                    $is_active = $month === $selected_month ? 'active' : '';
                    ?>
                    <a href="staff_sales_report.php?month=<?php echo $month; ?>" 
                       class="month-link <?php echo $is_active; ?>">
                        <?php echo $month_display; ?>
                    </a>
                <?php endforeach; ?>
            </div>

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

            <?php if ($total_bookings > 0): ?>
                <button onclick="window.print()" class="btn btn-primary print-button">
                    <i class="fas fa-print"></i> Print Analytics Report
                </button>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_bookings; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">RM <?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_passengers; ?></div>
                        <div class="stat-label">Total Passengers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">RM <?php echo number_format($average_booking_value, 2); ?></div>
                        <div class="stat-label">Average Booking Value</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($average_passengers_per_booking, 1); ?></div>
                        <div class="stat-label">Avg Passengers/Booking</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($airline_stats); ?></div>
                        <div class="stat-label">Airlines Used</div>
                    </div>
                </div>

                <div class="print-only" style="display: none;">
                    <div style="margin-bottom: 30px; page-break-inside: avoid;">
                        <h3 style="color: #000; border-bottom: 2px solid #000; padding-bottom: 5px;">Top Airlines Performance</h3>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr style="background-color: #f8f9fa;">
                                    <th style="border: 1px solid #000; padding: 8px; text-align: left;">Airline</th>
                                    <th style="border: 1px solid #000; padding: 8px; text-align: center;">Bookings</th>
                                    <th style="border: 1px solid #000; padding: 8px; text-align: right;">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($airline_stats, 0, 5) as $airline => $stats): ?>
                                    <tr>
                                        <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($airline); ?></td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo $stats['count']; ?></td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: right;">RM <?php echo number_format($stats['revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-bottom: 30px; page-break-inside: avoid;">
                        <h3 style="color: #000; border-bottom: 2px solid #000; padding-bottom: 5px;">Travel Class Distribution</h3>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr style="background-color: #f8f9fa;">
                                    <th style="border: 1px solid #000; padding: 8px; text-align: left;">Class</th>
                                    <th style="border: 1px solid #000; padding: 8px; text-align: center;">Bookings</th>
                                    <th style="border: 1px solid #000; padding: 8px; text-align: right;">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_stats as $class => $stats): ?>
                                    <tr>
                                        <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $class))); ?></td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo $stats['count']; ?></td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: right;">RM <?php echo number_format($stats['revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="charts-container">
                    <div class="chart-card">
                        <div class="chart-title">Revenue by Airline</div>
                        <canvas id="airlineChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title">Bookings by Travel Class</div>
                        <canvas id="classChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title">Monthly Revenue Trend</div>
                        <canvas id="monthlyChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title">Food & Drinks Selection</div>
                        <canvas id="foodDrinkChart"></canvas>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-bar fa-3x mb-3" style="color: #ffb03a;"></i>
                    <h4>No Data Available</h4>
                    <p>No completed bookings found for <?php echo $month_name; ?>.</p>
                    <?php if (count($available_months) > 0): ?>
                        <p>Try selecting a different month from the navigation above.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Chart.js configurations
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($total_bookings > 0): ?>
            
            // Store chart instances for printing
            let charts = [];

            // Airline Revenue Chart
            const airlineCtx = document.getElementById('airlineChart').getContext('2d');
            const airlineChart = new Chart(airlineCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($airline) { return "'" . addslashes($airline) . "'"; }, array_keys($airline_stats))); ?>],
                    datasets: [{
                        label: 'Revenue (RM)',
                        data: [<?php echo implode(', ', array_map(function($stats) { return $stats['revenue']; }, array_values($airline_stats))); ?>],
                        backgroundColor: 'rgba(255, 176, 58, 0.8)',
                        borderColor: 'rgba(255, 176, 58, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'RM ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            charts.push(airlineChart);

            // Travel Class Chart
            const classCtx = document.getElementById('classChart').getContext('2d');
            const classChart = new Chart(classCtx, {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($class) { return "'" . ucwords(str_replace('_', ' ', addslashes($class))) . "'"; }, array_keys($class_stats))); ?>],
                    datasets: [{
                        data: [<?php echo implode(', ', array_map(function($stats) { return $stats['count']; }, array_values($class_stats))); ?>],
                        backgroundColor: [
                            'rgba(255, 176, 58, 0.8)',
                            'rgba(52, 152, 219, 0.8)',
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(155, 89, 182, 0.8)',
                            'rgba(241, 196, 15, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            charts.push(classChart);

            // Monthly Revenue Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($month) { return "'" . addslashes($month) . "'"; }, array_keys($monthly_stats))); ?>],
                    datasets: [{
                        label: 'Monthly Revenue (RM)',
                        data: [<?php echo implode(', ', array_map(function($stats) { return $stats['revenue']; }, array_values($monthly_stats))); ?>],
                        borderColor: 'rgba(255, 176, 58, 1)',
                        backgroundColor: 'rgba(255, 176, 58, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'RM ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            charts.push(monthlyChart);

            // Food & Drink Chart
            const foodDrinkCtx = document.getElementById('foodDrinkChart').getContext('2d');
            const foodDrinkChart = new Chart(foodDrinkCtx, {
                type: 'doughnut',
                data: {
                    labels: ['With Food/Drinks', 'Without Food/Drinks'],
                    datasets: [{
                        data: [<?php echo $food_drink_stats['Yes'] . ', ' . $food_drink_stats['No']; ?>],
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(231, 76, 60, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            charts.push(foodDrinkChart);

            // Ensure charts are ready before printing
            window.addEventListener('beforeprint', () => {
                charts.forEach(chart => {
                    chart.resize();
                });
            });

            <?php endif; ?>
        });

        // Show print-only elements when printing
        window.addEventListener('beforeprint', () => {
            document.querySelectorAll('.print-only').forEach(el => {
                el.style.display = 'block';
            });
        });

        window.addEventListener('afterprint', () => {
            document.querySelectorAll('.print-only').forEach(el => {
                el.style.display = 'none';
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>