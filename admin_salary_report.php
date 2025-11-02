<?php
session_start(); // Start the session

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}
// --- End Database Connection ---

// --- Admin Authentication ---
$loggedIn = isset($_SESSION['book_id']);
$username = 'Admin';
$profilePictureUrl = '/college_project/book-a-flight-project-2/image_website/default_profile.png';

if ($loggedIn) {
    $user_id = $_SESSION['book_id'];
    $sql = "SELECT book_username, book_user_roles, book_profile FROM BookUser WHERE book_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if ($user['book_user_roles'] !== 'Admin') {
            header('Location: login_page.php');
            exit();
        }
        $username = htmlspecialchars($user['book_username']);
        if (!empty($user['book_profile'])) {
            $profilePictureUrl = htmlspecialchars($user['book_profile']);
        }
    } else {
        header('Location: login_page.php');
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    header('Location: login_page.php');
    exit();
}
// --- End Admin Authentication ---

// --- Get Month Parameter ---
$selected_month = $_GET['month'] ?? date('Y-m');
$month_name = date('F Y', strtotime($selected_month));

// --- Fetch Monthly Salary Data ---
$salary_data = [];
$error_message = '';
$total_paid_this_month = 0;

$sql = "SELECT 
            bu.book_username,
            sd.hourly_rate,
            SUM(ss.duration_seconds) as total_seconds,
            SUM(ss.earned_salary) as total_earned
        FROM StaffSessions ss
        JOIN BookUser bu ON ss.user_id = bu.book_id
        JOIN StaffDetails sd ON bu.book_id = sd.user_id
        WHERE DATE_FORMAT(ss.logout_time, '%Y-%m') = ?
        GROUP BY ss.user_id, bu.book_username, sd.hourly_rate
        ORDER BY total_earned DESC";

if ($stmt = mysqli_prepare($connection, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $selected_month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $salary_data[] = $row;
            $total_paid_this_month += $row['total_earned'];
        }
        mysqli_free_result($result);
    } else {
        $error_message = "An error occurred fetching salary data: " . mysqli_error($connection);
        error_log("Salary Report SQL Error: " . mysqli_error($connection));
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Error preparing query: " . mysqli_error($connection);
    error_log("Salary Report Prepare Error: " . mysqli_error($connection));
}

// Get available months for navigation
$months_sql = "SELECT DISTINCT DATE_FORMAT(logout_time, '%Y-%m') as month FROM StaffSessions WHERE logout_time IS NOT NULL ORDER BY month DESC";
$months_result = mysqli_query($connection, $months_sql);
$available_months = [];
while ($row = mysqli_fetch_assoc($months_result)) {
    $available_months[] = $row['month'];
}

mysqli_close($connection);

$current_page_name = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Salary Report - SierraFlight (Admin)</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #1e1e2d; color: #e0e0e0; font-family: sans-serif; display: flex; flex-direction: column; min-height: 100vh; }
        .top-gradient-bar { background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60); padding: 10px 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .top-gradient-bar .container { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1140px; margin: 0 auto; flex-wrap: wrap; }
        .top-gradient-bar .site-title { font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none; margin-right: auto; white-space: nowrap; display: flex; align-items: center; }
        .top-gradient-bar .site-title .sierraflight-logo { width: 150px; height: auto; margin-right: 10px; vertical-align: middle; }
        .top-gradient-bar .user-info { display: flex; align-items: center; color: white; flex-shrink: 0; margin-left: auto; white-space: nowrap; }
        .top-gradient-bar .profile-picture-nav, .top-gradient-bar .profile-icon-nav { width: 36px; height: 36px; border-radius: 50%; margin-left: 8px; vertical-align: middle; object-fit: cover; border: 1px solid white; }
        .top-gradient-bar .btn-danger { background-color: #dc3545; border-color: #dc3545; padding: .3rem .6rem; font-size: .95rem; line-height: 1.5; border-radius: .2rem; margin-left: 10px; }
        .navbar { background-color: #212529; padding: 0 20px; margin-bottom: 0; }
        .navbar > .container { display: flex; align-items: center; width: 100%; max-width: 1140px; margin: 0 auto; padding: 0; }
        
        /* --- START NAVBAR STYLE --- */
        .navbar-nav .nav-link {
            padding: 8px 15px; color: white !important;
            transition: background-color 0.3s ease, text-decoration 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: underline;
            color: white !important;
        }
        /* NOTE: .active rule is now in the nav HTML */
        /* --- END NAVBAR STYLE --- */
        
        .page-content { padding: 20px; flex-grow: 1; }
        .admin-container { margin: 30px auto; max-width: 1200px; background-color: #282b3c; border-radius: 8px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5); padding: 30px; color: #e0e0e0; }
        .admin-header { background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60); color: white; padding: 20px; margin: -30px -30px 30px -30px; text-align: center; font-size: 1.8rem; font-weight: bold; border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .month-navigation { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; padding: 15px; background-color: #3a3e52; border-radius: 8px; }
        .month-link { padding: 8px 16px; background-color: #2d3042; color: #e0e0e0; text-decoration: none; border-radius: 5px; transition: all 0.3s ease; border: 1px solid #5a5a8a; }
        .month-link:hover { background-color: #ffb03a; color: #1e1e2d; text-decoration: none; }
        .month-link.active { background-color: #ffb03a; color: #1e1e2d; font-weight: bold; }
        .account-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .account-table th, .account-table td { padding: 12px; text-align: left; border-bottom: 1px solid #5a5a8a; color: #ccc; vertical-align: middle; }
        .account-table th { background-color: #3a3e52; color: #fff; font-weight: bold; }
        .account-table tbody tr:hover { background-color: #343a40; }
        .account-table tfoot td { font-weight: bold; font-size: 1.1rem; color: #fff; }
        .no-data { text-align: center; color: #ccc; margin-top: 20px; padding: 40px; background-color: #3a3e52; border-radius: 8px; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        
        .print-button {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            padding: 10px 15px;
            margin-bottom: 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        
        @media print {
            body {
                background-color: #fff !important;
                color: #000 !important;
            }
            .top-gradient-bar, .navbar, .month-navigation, .print-button, .page-content > .admin-container > .alert {
                display: none !important;
            }
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 2rem;
            }
            .print-header h2 { color: #000; }
            .print-header p { color: #333; font-size: 1.1rem; }
            
            .page-content, .admin-container {
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
                margin: 0 0 20px 0 !important;
                padding: 0 !important;
                text-align: left;
                border-bottom: 2px solid #000;
            }
            .account-table {
                width: 100% !important;
                border-collapse: collapse;
                font-size: 10pt;
            }
            .account-table th, .account-table td {
                color: #000 !important;
                border: 1px solid #000 !important;
                padding: 8px;
            }
            .account-table th {
                background-color: #e0e0e0 !important;
            }
            .account-table tfoot td {
                color: #000 !important;
                font-weight: bold;
            }
            .no-data {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="homepage.php" class="site-title">
                <img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo">
                <span>(Admin)</span>
            </a>
            <div class="user-info">
                <span>Welcome, <?php echo $username; ?>!</span>
                <a href="profile_page.php">
                    <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav">
                </a>
                <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
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
                    <li class="nav-item"><a class="nav-link" href="homepage.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_account_manager.php">Account Manager</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_staff_salary.php">Staff Salary</a></li>
                    <li class="nav-item active"><a class="nav-link" href="admin_salary_report.php">Salary Report</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="admin-container">
            <div class="print-header" style="display:none;">
                <h2>SierraFlight - Staff Salary Report</h2>
                <p><strong>Month:</strong> <?php echo $month_name; ?></p>
            </div>
            
            <div class="admin-header">
                Monthly Staff Salary Report - <?php echo $month_name; ?>
            </div>

            <div class="month-navigation">
                <?php if (!empty($available_months)): ?>
                    <?php foreach ($available_months as $month): ?>
                        <?php
                        $month_display = date('F Y', strtotime($month));
                        $is_active = $month === $selected_month ? 'active' : '';
                        ?>
                        <a href="admin_salary_report.php?month=<?php echo $month; ?>" 
                           class="month-link <?php echo $is_active; ?>">
                            <?php echo $month_display; ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-accounts" style="margin: 0; padding: 10px;">No salary data found for any month.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($salary_data)): ?>
                <button onclick="window.print()" class="btn btn-primary print-button">
                    <i class="fas fa-print"></i> Print Report
                </button>
            
                <div class="table-responsive">
                    <table class="table account-table">
                        <thead>
                            <tr>
                                <th>Staff Username</th>
                                <th>Hourly Rate (RM)</th>
                                <th>Total Hours Worked</th>
                                <th>Total Earned (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salary_data as $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['book_username']); ?></td>
                                    <td><?php echo number_format($data['hourly_rate'], 2); ?></td>
                                    <td>
                                        <?php 
                                            // Convert total seconds to H:M:S format
                                            $hours = floor($data['total_seconds'] / 3600);
                                            $minutes = floor(($data['total_seconds'] % 3600) / 60);
                                            $seconds = $data['total_seconds'] % 60;
                                            echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                        ?>
                                    </td>
                                    <td style="color: #28a745; font-weight: bold;">
                                        <?php echo number_format($data['total_earned'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right;">Total Paid This Month:</td>
                                <td style="color: #ffb03a; font-size: 1.2rem;"><?php echo number_format($total_paid_this_month, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-file-invoice-dollar fa-3x mb-3" style="color: #ffb03a;"></i>
                    <h4>No Salary Data Found</h4>
                    <p>No staff salary data found for <?php echo $month_name; ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>