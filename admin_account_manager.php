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

    // --- CRITICAL SAFETY CHECK: Prevent admin from modifying their own account ---
    if ($user_id_to_update == $current_admin_user_id) {
        $_SESSION['process_message'] = "Error: You cannot modify your own account.";
        $_SESSION['message_type'] = "danger";
    } else {
        // --- Handle Role Update ---
        if ($action === 'update_role' && isset($_POST['new_role'])) {
            $new_role = $_POST['new_role'];
            $valid_roles = ['Customer', 'Staff', 'Admin'];

            if (in_array($new_role, $valid_roles)) {
                $update_sql = "UPDATE BookUser SET book_user_roles = ? WHERE book_id = ?";
                $update_stmt = mysqli_prepare($connection, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $new_role, $user_id_to_update);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['process_message'] = "User role updated successfully!";
                    $_SESSION['message_type'] = "success";
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
        // --- Handle Status Update (Active, Inactive) ---
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
    
    // Redirect to refresh the page, preserving search and view state
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

// Base SQL: Fetch all users EXCEPT the currently logged-in admin
$sql_base = "SELECT book_id, book_username, book_email, book_user_roles, book_user_status, book_user_register_date
             FROM BookUser 
             WHERE book_id != ?";
$sql_params = [$current_admin_user_id];
$sql_types = 'i';

// Add view filter
if ($current_view === 'inactive') {
    $sql_base .= " AND book_user_status = 'Inactive'";
} else {
    // Default view is 'active'
    $sql_base .= " AND book_user_status = 'Active'";
}

// --- MODIFICATION: Added book_user_register_date to search query ---
if (!empty($search_term)) {
    // Using DATE_FORMAT to match YYYY-MM-DD text searches
    $sql_base .= " AND (book_username LIKE ? OR book_email LIKE ? OR book_user_roles LIKE ? OR DATE_FORMAT(book_user_register_date, '%Y-%m-%d') LIKE ?)";
    $search_like = '%' . $search_term . '%';
    array_push($sql_params, $search_like, $search_like, $search_like, $search_like);
    $sql_types .= 'ssss'; // Updated from 'sss' to 'ssss'
}
// --- End Modification ---

$sql_base .= " ORDER BY book_user_roles, book_username";

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
    <title>User Account Management - SierraFlight (Admin)</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Copied styles from admin_employee_accounts.php for consistency */
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

        /* Tab styles */
        .nav-tabs {
            border-bottom: 1px solid #5a5a8a;
        }
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-top-left-radius: .25rem;
            border-top-right-radius: .25rem;
            color: #ccc;
            background-color: transparent;
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
        
        /* Search Bar */
        .search-bar {
            margin-bottom: 20px;
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
        .account-table .btn-secondary:hover { background-color: #5a6268; border-color: #545b62; }
        .account-table .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .account-table .btn-danger:hover { background-color: #c82333; border-color: #bd2130; }
        .account-table .btn-success { background-color: #28a745; border-color: #28a745; }
        .account-table .btn-success:hover { background-color: #218838; border-color: #1e7e34; }

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
                <?php if ($loggedIn): ?>
                    <span>Welcome, <?php echo $username; ?>!</span>
                    <a href="profile_page.php">
                        <?php if (empty($profilePictureUrl) || strpos($profilePictureUrl, 'default') !== false): ?>
                            <i class="fas fa-user-circle fa-lg profile-icon-nav" style="color: white; font-size: 36px; margin-left: 8px;"></i>
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
                        <a class="nav-link" href="admin_account_manager.php">Account Manager <span class="sr-only">(current)</span></a>
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

            <!-- Search Bar -->
            <form method="GET" action="admin_account_manager.php" class="search-bar">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($current_view); ?>">
                <div class="input-group">
                    <!-- --- MODIFICATION: Updated placeholder text --- -->
                    <input type="text" name="search" class="form-control" placeholder="Search by Username, Email, Role, or Date (YYYY-MM-DD)..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                    </div>
                </div>
            </form>

            <!-- Tabs Navigation -->
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
                                        <th style="width: 30px;"><input type="checkbox" id="select-all-checkbox"></th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th style="white-space: nowrap;">Registered On</th>
                                        <th>Current Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_users as $user): ?>
                                        <tr>
                                            <td><input type="checkbox" class="user-checkbox" name="user_ids[]" value="<?php echo htmlspecialchars($user['book_id']); ?>"></td>
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
                                                <span class="status-<?php echo htmlspecialchars($user['book_user_status']); ?>">
                                                    <?php echo htmlspecialchars($user['book_user_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <!-- Form for Role Update -->
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

                                                <!-- Form for Status Update -->
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all-checkbox');
            const userCheckboxes = document.querySelectorAll('.user-checkbox');

            if (selectAll) {
                selectAll.addEventListener('click', function() {
                    userCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }
        });
    </script>
</body>
</html>

