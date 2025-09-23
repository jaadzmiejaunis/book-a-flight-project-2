<?php
session_start(); // Start the session

// Initialize error message variable
$login_error = "";
$username_value = ""; // Variable to retain username value

// --- Database Connection (Consider moving credentials out of this file for security) ---
// In a production environment, store credentials in a configuration file outside the web root.
include 'connection.php';

// Check connection
if (!$connection) {
    // Log the error and show a user-friendly message instead of the technical error
    error_log("Database connection failed: " . mysqli_connect_error());
    $login_error = "An error occurred connecting to the database. Please try again later.";
    // You might want to exit here if database connection is essential
    // exit();
}
// --- End Database Connection ---

// --- Process Login Form Submission ---
// Only process if the form was submitted via POST and connection is successful
if ($_SERVER["REQUEST_METHOD"] == "POST" && $connection) {
    // Sanitize and get form data
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Get the raw password to verify against hash

    // Retain the entered username in case of login failure
    $username_value = htmlspecialchars($username);

    // --- Basic Validation ---
    if (empty($username) || empty($password)) {
        $login_error = "Please enter both username and password.";
    } else {
        // --- Securely Fetch Hashed Password and other user data using Prepared Statement ---
        // CORRECTED: The SQL query now correctly selects 'book_user_roles'
        $sql = "SELECT book_id, book_password, book_username, book_user_roles, book_profile FROM BookUser WHERE book_username = ?";

        // Prepare the statement
        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt) {
            // Bind parameter (s = string)
            mysqli_stmt_bind_param($stmt, "s", $username);

            // Execute the statement
            mysqli_stmt_execute($stmt);

            // Get the result
            $result = mysqli_stmt_get_result($stmt);

            // Check if a user was found
            if ($row = mysqli_fetch_assoc($result)) {
                $hashed_password_from_db = $row['book_password'];
                $user_id = $row['book_id'];
                $db_username = $row['book_username'];
                $db_user_role = $row['book_user_roles']; // Get the user's role
                $db_profile_picture_url = $row['book_profile'];


                // --- Verify Password using password_verify() ---
                if (password_verify($password, $hashed_password_from_db)) {
                    // Password is correct! Log the user in.
                    // Store user data in the session
                    $_SESSION['book_id'] = $user_id;
                    $_SESSION['username'] = htmlspecialchars($db_username);
                    $_SESSION['user_role'] = $db_user_role; // Store the user role in the session
                    $_SESSION['profile_picture_url'] = htmlspecialchars($db_profile_picture_url);


                    // --- Redirect based on the stored user role ---
                    switch ($db_user_role) {
                        case 'Admin':
                            header("Location: homepage.php"); // Redirect to the main homepage to handle roles
                            break;
                        case 'Staff':
                            header("Location: homepage.php"); // Redirect to the main homepage to handle roles
                            break;
                        case 'Customer':
                        default:
                            header("Location: homepage.php"); // Redirect to the main homepage to handle roles
                            break;
                    }
                    exit(); // Stop further script execution

                } else {
                    // Password does NOT match the hash
                    $login_error = "Invalid username or password."; // Use a generic message
                }
            } else {
                // User not found
                $login_error = "Invalid username or password."; // Use a generic message
            }

            mysqli_free_result($result);
            mysqli_stmt_close($stmt);

        } else {
            // Prepared statement failed
            error_log("Database prepare error: " . mysqli_error($connection)); // Log the error
            $login_error = "An internal error occurred. Please try again.";
        }
    }
}
// --- End Process Login Form Submission ---

// Close database connection at the end of the script
if ($connection) {
    mysqli_close($connection);
}

// Variables for header (even if not logged in, needed for Guest state)
$loggedIn = isset($_SESSION['book_id']);
$user_role = $loggedIn && isset($_SESSION['book_user_roles']) ? $_SESSION['book_user_roles'] : 'Guest';
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) && !empty($_SESSION['profile_picture_url'])
    ? htmlspecialchars($_SESSION['profile_picture_url'])
    : '/college_project/book-a-flight-project-2/image_website/default_profile.png';

// Set the site title based on the user's role
$siteTitle = 'SierraFlight';
if ($user_role === 'Admin') {
    $siteTitle = 'SierraFlight (Admin)';
} elseif ($user_role === 'Staff') {
    $siteTitle = 'SierraFlight (Staff)';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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

        .header-bar {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1140px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .header-bar .site-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            margin-right: auto;
            white-space: nowrap;
        }
        .header-bar .site-title:hover {
            text-decoration: underline;
        }

        .header-bar .user-info {
            display: flex;
            align-items: center;
            color: white;
            flex-shrink: 0;
            margin-left: auto;
            white-space: nowrap;
        }
        .header-bar .user-info a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .header-bar .user-info a:hover {
            text-decoration: underline;
        }

        .header-bar .profile-picture-nav,
        .header-bar .profile-icon-nav {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
            object-fit: cover;
            border: 1px solid white;
        }
        .header-bar .profile-icon-nav {
            border: none;
        }
        .header-bar .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            padding: .3rem .6rem;
            font-size: .95rem;
            line-height: 1.5;
            border-radius: .2rem;
            margin-left: 10px;
        }
        .header-bar .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .page-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 400px;
            padding: 30px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
        }
        .login-container h2 {
            color: white;
        }
        .login-container .form-control {
            background-color: #3a3e52;
            border-color: #3a3e52;
            color: #e0e0e0;
        }
        .login-container .form-control::placeholder {
            color: #a0a0a0;
        }
        .login-container .form-control:focus {
            background-color: #3a3e52;
            border-color: #ffb03a;
            color: white;
            box-shadow: none;
        }
        .login-container .form-group label {
            color: #e0e0e0;
        }
        .login-container .btn-primary {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: background-position 0.5s ease;
            background-size: 200% auto;
        }
        .login-container .btn-primary:hover {
            background-position: right center;
            color: white;
        }
        .login-container .btn-primary:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
        }
        .error-message {
            color: #dc3545;
            margin-top: 10px;
            text-align: center;
        }
        .sign-up-link label {
            margin-right: 5px;
            color: #e0e0e0;
        }
        .sign-up-link a {
            color: #ffb03a;
        }
        .sign-up-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="header-bar">
        <div class="container">
            <a href="index.php" class="site-title"><?php echo $siteTitle; ?></a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                    <a href="profile_page.php">
                        <span>Welcome, <?php echo $username; ?>!</span>
                        <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav">
                    </a>
                    <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php else: ?>
                    <a href="login_page.php" class="nav-link">Login/Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container page-content">
        <div class="login-container">
            <h2 class="text-center mb-4">Login</h2>
            <?php
                if (!empty($login_error)) {
                    echo "<div class='error-message'>" . htmlspecialchars($login_error) . "</div>";
                }
            ?>
            <form action="login_page.php" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" required value="<?php echo $username_value; ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" name="login">Log In</button>
                <br>
                    <div class="forgot-password-link text-center">
                        <a href="forgot_password.php">Forgot password?</a>
                    </div>
                <br>
                <div class="sign-up-link text-center">
                    <label>First time user?</label>
                    <a href="sign_in_page.php">Click here!</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>