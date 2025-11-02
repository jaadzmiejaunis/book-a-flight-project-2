<?php
session_start(); // Start the session

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed on admin staff salary page: " . mysqli_connect_error());
    die("An error occurred connecting to the database.");
}
// --- End Database Connection ---

// --- Admin Authentication ---
$loggedIn = isset($_SESSION['book_id']);
$username = 'Admin';
$profilePictureUrl = '/college_project/book-a-flight-project-2/image_website/default_profile.png';
$current_admin_user_id = 0;

if ($loggedIn) {
    $user_id = $_SESSION['book_id'];
    $current_admin_user_id = $user_id; 

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

// --- NEW: Self-Healing/Backfill Logic ---
$sql_backfill = "INSERT INTO StaffDetails (user_id)
                 SELECT book_id FROM BookUser
                 WHERE book_user_roles = 'Staff'
                 AND book_id NOT IN (SELECT user_id FROM StaffDetails)";
if (!mysqli_query($connection, $sql_backfill)) {
    error_log("Failed to backfill StaffDetails: " . mysqli_error($connection));
}
// --- End Backfill ---

// --- Handle Rate Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_rate') {
    $user_id_to_update = $_POST['user_id_to_update'] ?? 0;
    $new_rate = $_POST['new_rate'] ?? 10.00;

    if ($user_id_to_update == $current_admin_user_id) {
        $_SESSION['process_message'] = "Error: You cannot modify your own salary rate.";
        $_SESSION['message_type'] = "danger";
    } else if (!is_numeric($new_rate) || $new_rate < 0) {
        $_SESSION['process_message'] = "Error: Invalid hourly rate. Must be a positive number.";
        $_SESSION['message_type'] = "danger";
    } else {
        $update_sql = "UPDATE StaffDetails SET hourly_rate = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($connection, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "di", $new_rate, $user_id_to_update);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['process_message'] = "Staff salary rate updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['process_message'] = "Error updating rate: " . mysqli_error($connection);
            $_SESSION['message_type'] = "danger";
        }
        mysqli_stmt_close($update_stmt);
    }
    
    header("Location: admin_staff_salary.php");
    exit();
}
// --- End Handle POST ---

// --- Fetch All Staff Users ---
$staff_users = [];
$error_message = '';

$sql_staff = "SELECT 
                bu.book_id, 
                bu.book_username, 
                bu.book_email, 
                sd.hourly_rate, 
                sd.total_salary 
              FROM BookUser bu
              JOIN StaffDetails sd ON bu.book_id = sd.user_id
              WHERE bu.book_user_roles = 'Staff'
              ORDER BY bu.book_username";

if ($stmt_staff = mysqli_prepare($connection, $sql_staff)) {
    mysqli_stmt_execute($stmt_staff);
    $result_staff = mysqli_stmt_get_result($stmt_staff);
    if ($result_staff) {
        while ($row = mysqli_fetch_assoc($result_staff)) {
            $staff_users[] = $row;
        }
        mysqli_free_result($result_staff);
    } else {
        $error_message = "An error occurred fetching staff data.";
    }
    mysqli_stmt_close($stmt_staff);
} else {
    $error_message = "Error preparing query: " . mysqli_error($connection);
}
// --- End Fetch Staff ---

mysqli_close($connection);

// --- Handle Success or Error Messages ---
$process_message = $_SESSION['process_message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['process_message']);
unset($_SESSION['message_type']);

$current_page_name = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff Salary - SierraFlight (Admin)</title>
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
        .account-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .account-table th, .account-table td { padding: 12px; text-align: left; border-bottom: 1px solid #5a5a8a; color: #ccc; vertical-align: middle; }
        .account-table th { background-color: #3a3e52; color: #fff; font-weight: bold; }
        .account-table tbody tr:hover { background-color: #343a40; }
        .account-table .form-control { background-color: #3a3e52; color: #fff; border: 1px solid #5a5a8a; width: 100px; display: inline-block; }
        .account-table .btn-secondary { background-color: #6c757d; border-color: #6c757d; color: white; }
        .no-accounts { text-align: center; color: #ccc; margin-top: 20px; padding: 20px; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
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
                    <li class="nav-item active"><a class="nav-link" href="admin_staff_salary.php">Staff Salary</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_salary_report.php">Salary Report</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="admin-container">
            <div class="admin-header">
                Manage Staff Salary Rates
            </div>

            <?php if (!empty($process_message)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>" role="alert">
                    <?php echo htmlspecialchars($process_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($staff_users)): ?>
                <div class="table-responsive">
                    <table class="table account-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Total Earned (RM)</th>
                                <th>Hourly Rate (RM)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['book_username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['book_email']); ?></td>
                                    <td style="color: #28a745; font-weight: bold;">
                                        <?php echo number_format($user['total_salary'], 2); ?>
                                    </td>
                                    <form method="post" action="admin_staff_salary.php">
                                        <input type="hidden" name="action" value="update_rate">
                                        <input type="hidden" name="user_id_to_update" value="<?php echo htmlspecialchars($user['book_id']); ?>">
                                        <td>
                                            <input type="number" step="0.01" min="0" name="new_rate" class="form-control" value="<?php echo htmlspecialchars($user['hourly_rate']); ?>">
                                        </td>
                                        <td>
                                            <button type="submit" class="btn btn-secondary btn-sm">Update Rate</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-accounts">No staff accounts found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>