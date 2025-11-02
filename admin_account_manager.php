<?php
session_start(); // Start the session

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed on admin user roles page: " . mysqli_connect_error());
    die("An error occurred connecting to the database.");
}
// --- End Database Connection ---

// --- Admin Authentication and Data Retrieval ---
$loggedIn = isset($_SESSION['book_id']);
$username = 'Admin';
$profilePictureUrl = '/college_project/book-a-flight-project-2/image_website/default_profile.png';
$current_admin_user_id = 0; // To store the logged-in admin's ID

if ($loggedIn) {
    $user_id = $_SESSION['book_id'];
    $current_admin_user_id = $user_id; // Store admin's ID for safety checks

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

// --- Handle INDIVIDUAL POST Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id_to_update = $_POST['user_id_to_update'] ?? 0;
    $current_view = $_POST['current_view'] ?? 'active';

    if ($user_id_to_update == $current_admin_user_id) {
        $_SESSION['process_message'] = "Error: You cannot modify your own account.";
        $_SESSION['message_type'] = "danger";
    } else {
        if ($action === 'update_role' && isset($_POST['new_role'])) {
            $new_role = $_POST['new_role'];
            $valid_roles = ['Customer', 'Staff', 'Admin'];

            if (in_array($new_role, $valid_roles)) {
                $old_role_sql = "SELECT book_user_roles FROM BookUser WHERE book_id = ?";
                $old_role_stmt = mysqli_prepare($connection, $old_role_sql);
                mysqli_stmt_bind_param($old_role_stmt, "i", $user_id_to_update);
                mysqli_stmt_execute($old_role_stmt);
                $old_role_result = mysqli_stmt_get_result($old_role_stmt);
                $old_role_row = mysqli_fetch_assoc($old_role_result);
                $old_role = $old_role_row ? $old_role_row['book_user_roles'] : '';
                mysqli_stmt_close($old_role_stmt);

                $update_sql = "UPDATE BookUser SET book_user_roles = ? WHERE book_id = ?";
                $update_stmt = mysqli_prepare($connection, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $new_role, $user_id_to_update);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['process_message'] = "User role updated successfully!";
                    $_SESSION['message_type'] = "success";

                    if ($new_role === 'Staff' && $old_role !== 'Staff') {
                        $insert_staff_sql = "INSERT INTO StaffDetails (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id";
                        $insert_staff_stmt = mysqli_prepare($connection, $insert_staff_sql);
                        mysqli_stmt_bind_param($insert_staff_stmt, "i", $user_id_to_update);
                        mysqli_stmt_execute($insert_staff_stmt);
                        mysqli_stmt_close($insert_staff_stmt);

                    } else if ($new_role !== 'Staff' && $old_role === 'Staff') {
                        $delete_staff_sql = "DELETE FROM StaffDetails WHERE user_id = ?";
                        $delete_staff_stmt = mysqli_prepare($connection, $delete_staff_sql);
                        mysqli_stmt_bind_param($delete_staff_stmt, "i", $user_id_to_update);
                        mysqli_stmt_execute($delete_staff_stmt);
                        mysqli_stmt_close($delete_staff_stmt);
                    }
                } else {
                    $_SESSION['process_message'] = "Error updating user role: " . mysqli_error($connection);
                    $_SESSION['message_type'] = "danger";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $_SESSION['process_message'] = "Invalid role selected.";
                $_SESSION['message_type'] = "danger";
            }
        } 
        elseif ($action === 'update_status' && isset($_POST['new_status'])) {
            $new_status = $_POST['new_status'];
            $valid_statuses = ['Active', 'Inactive'];

            if (in_array($new_status, $valid_statuses)) {
                $update_sql = "UPDATE BookUser SET book_user_status = ? WHERE book_id = ?";
                $update_stmt = mysqli_prepare($connection, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $new_status, $user_id_to_update);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $message = ($new_status === 'Active') ? "User set to Active!" : "User set to Inactive!";
                    $_SESSION['process_message'] = $message;
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['process_message'] = "Error updating user status: " . mysqli_error($connection);
                    $_SESSION['message_type'] = "danger";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $_SESSION['process_message'] = "Invalid status selected.";
                $_SESSION['message_type'] = "danger";
            }
        }
    }
    
    $search_query = http_build_query(['search' => $_GET['search'] ?? '']);
    header("Location: admin_account_manager.php?view=" . $current_view . "&" . $search_query);
    exit();
}
// --- End Handle POST Requests ---

// --- Fetch All Users List (GET Request with Search and View) ---
$all_users = [];
$error_message = '';
$current_view = $_GET['view'] ?? 'active'; // 'active' or 'inactive'
$search_term = $_GET['search'] ?? '';     // Search term

$sql_base = "SELECT 
                bu.book_id, 
                bu.book_username, 
                bu.book_email, 
                bu.book_user_roles, 
                bu.book_user_status, 
                bu.book_user_register_date, 
                sd.total_salary 
             FROM BookUser bu
             LEFT JOIN StaffDetails sd ON bu.book_id = sd.user_id
             WHERE bu.book_id != ?";
$sql_params = [$current_admin_user_id];
$sql_types = 'i';

if ($current_view === 'inactive') {
    $sql_base .= " AND bu.book_user_status = 'Inactive'";
} else {
    $sql_base .= " AND bu.book_user_status = 'Active'";
}

if (!empty($search_term)) {
    $sql_base .= " AND (bu.book_username LIKE ? OR bu.book_email LIKE ? OR bu.book_user_roles LIKE ? OR DATE_FORMAT(bu.book_user_register_date, '%Y-%m-%d') LIKE ?)";
    $search_like = '%' . $search_term . '%';
    array_push($sql_params, $search_like, $search_like, $search_like, $search_like);
    $sql_types .= 'ssss';
}

$sql_base .= " ORDER BY bu.book_user_roles, bu.book_username";

if ($stmt_users = mysqli_prepare($connection, $sql_base)) {
    mysqli_stmt_bind_param($stmt_users, $sql_types, ...$sql_params);
    mysqli_stmt_execute($stmt_users);
    $result_users = mysqli_stmt_get_result($stmt_users);

    if ($result_users) {
        while ($row = mysqli_fetch_assoc($result_users)) {
            $all_users[] = $row;
        }
        mysqli_free_result($result_users);
    } else {
        error_log("Database query error on admin user roles list: " . mysqli_error($connection));
        $error_message = "An error occurred fetching user data. Please try again.";
    }
    mysqli_stmt_close($stmt_users);
} else {
    $error_message = "Error preparing query: " . mysqli_error($connection);
}
// --- End Fetch All Users List ---

mysqli_close($connection);

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
    <title>User Account Management - SierraFlight (Admin)</title>
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
        }
        .navbar > .container {
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 1140px;
            margin: 0 auto;
            padding: 0;
        }
        
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
        /* NOTE: No .active rule is needed to match homepage.php */
        /* --- END NAVBAR STYLE --- */
        
        .nav-tabs {
            border-bottom: 1px solid #5a5a8a;
        }
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            color: #ccc;
        }
        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link:focus {
            border-color: #3a3e52 #3a3e52 #5a5a8a;
            color: #fff;
        }
        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: #3a3e52;
            border-color: #5a5a8a #5a5a8a #3a3e52;
        }
        .search-bar .form-control {
            background-color: #3a3e52;
            color: #fff;
            border: 1px solid #5a5a8a;
        }
        .search-bar .form-control::placeholder {
            color: #aaa;
        }
        .search-bar .btn-primary {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            border: none;
            background-size: 200% auto;
            transition: all 0.5s ease;
        }
        .search-bar .btn-primary:hover {
            background-position: right center;
        }
        .page-content {
            padding: 20px;
            flex-grow: 1;
        }
        .admin-container {
            margin: 30px auto;
            max-width: 1350px; 
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
        .account-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .account-table th, .account-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #5a5a8a;
            color: #ccc;
            vertical-align: middle;
        }
        .account-table th {
            background-color: #3a3e52;
            color: #fff;
            font-weight: bold;
        }
        .account-table tbody tr:hover {
            background-color: #343a40;
        }
        .account-table .btn {
            padding: 5px 10px;
            margin-right: 5px;
            font-size: 0.9rem;
            margin-top: 5px; 
            width: 120px; 
            box-sizing: border-box; 
        }
        .account-table .btn-secondary { background-color: #6c757d; border-color: #6c757d; color: white; }
        .account-table .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .account-table .btn-success { background-color: #28a745; border-color: #28a745; }
        .status-select {
            background-color: #3a3e52;
            color: #fff;
            border: 1px solid #5a5a8a;
            padding: 5px;
            border-radius: 4px;
            font-size: 0.9rem;
            max-width: 120px; 
            margin-right: 5px;
            display: inline-block;
            width: 120px; 
            box-sizing: border-box; 
        }
        .status-Active { color: #28a745; font-weight: bold; }
        .status-Inactive { color: #ffc107; font-weight: bold; }
        .role-Admin { color: #dc3545; font-weight: bold; }
        .role-Staff { color: #ffc107; font-weight: bold; }
        .role-Customer { color: #17a2b8; font-weight: bold; }
        .no-accounts {
            text-align: center;
            color: #ccc;
            margin-top: 20px;
            padding: 20px;
        }
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
                    <li class="nav-item active"><a class="nav-link" href="admin_account_manager.php">Account Manager</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_staff_salary.php">Staff Salary</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_salary_report.php">Salary Report</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="admin-container">
            <div class="admin-header">
                User Account Management
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
            <?php endif; ?>

            <form method="GET" action="admin_account_manager.php" class="search-bar">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($current_view); ?>">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by Username, Email, Role, or Date (YYYY-MM-DD)..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                    </div>
                </div>
            </form>

            <ul class="nav nav-tabs mb-3" id="accountTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_view === 'active') ? 'active' : ''; ?>" href="admin_account_manager.php?view=active&search=<?php echo htmlspecialchars($search_term); ?>">Active Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_view === 'inactive') ? 'active' : ''; ?>" href="admin_account_manager.php?view=inactive&search=<?php echo htmlspecialchars($search_term); ?>">Inactive Users</a>
                </li>
            </ul>

            
            <div class="tab-content" id="accountTabsContent">
                <div class="tab-pane fade show active" role="tabpanel">
                    <?php if (!empty($all_users)): ?>
                        <div class="table-responsive">
                            <table class="table account-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th style="white-space: nowrap;">Registered On</th>
                                        <th>Current Role</th>
                                        <th>Total Earned (RM)</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['book_username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['book_email']); ?></td>
                                            <td style="white-space: nowrap;">
                                                <?php 
                                                    try {
                                                        $reg_date = new DateTime($user['book_user_register_date']);
                                                        echo $reg_date->format('Y-m-d H:i');
                                                    } catch (Exception $e) {
                                                        echo htmlspecialchars($user['book_user_register_date']);
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="role-<?php echo htmlspecialchars($user['book_user_roles']); ?>">
                                                    <?php echo htmlspecialchars($user['book_user_roles']); ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <?php if ($user['book_user_roles'] === 'Staff'): ?>
                                                    <span style="color: #28a745; font-weight: bold;">
                                                        <?php echo number_format($user['total_salary'], 2); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #6c757d;">N/A</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <span class="status-<?php echo htmlspecialchars($user['book_user_status']); ?>">
                                                    <?php echo htmlspecialchars($user['book_user_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" action="admin_account_manager.php?search=<?php echo htmlspecialchars($search_term); ?>" style="display: block;">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id_to_update" value="<?php echo htmlspecialchars($user['book_id']); ?>">
                                                    <input type="hidden" name="current_view" value="<?php echo htmlspecialchars($current_view); ?>">
                                                    <select name="new_role" class="status-select">
                                                        <option value="Customer" <?php echo ($user['book_user_roles'] === 'Customer') ? 'selected' : ''; ?>>Customer</option>
                                                        <option value="Staff" <?php echo ($user['book_user_roles'] === 'Staff') ? 'selected' : ''; ?>>Staff</option>
                                                        <option value="Admin" <?php echo ($user['book_user_roles'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-secondary btn-sm" style="width: 120px;">Update Role</button>
                                                </form>

                                                <form method="post" action="admin_account_manager.php?search=<?php echo htmlspecialchars($search_term); ?>" style="display: block; margin-top: 5px;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="user_id_to_update" value="<?php echo htmlspecialchars($user['book_id']); ?>">
                                                    <input type="hidden" name="current_view" value="<?php echo htmlspecialchars($current_view); ?>">
                                                    
                                                    <?php if ($current_view === 'active'): ?>
                                                        <input type="hidden" name="new_status" value="Inactive">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to set this user to INACTIVE?');">Set Inactive</button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="new_status" value="Active">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to set this user to ACTIVE?');">Set Active</button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>
                        <?php if (!empty($search_term)): ?>
                            <p class="no-accounts">No users found matching your search term "<?php echo htmlspecialchars($search_term); ?>".</p>
                        <?php elseif ($current_view === 'active'): ?>
                            <p class="no-accounts">No active user accounts found.</p>
                        <?php else: ?>
                            <p class="no-accounts">No inactive user accounts found.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>