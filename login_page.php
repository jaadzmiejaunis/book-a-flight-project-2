<?php
session_start(); // Start the session

// Initialize error message variable
$login_error = "";
$username_value = ""; // Variable to retain username value

// --- Database Connection ---
include 'connection.php';

// Check connection
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// --- Process Login Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $connection) {
    // Sanitize and get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Retain username in form
    $username_value = htmlspecialchars($username);

    // Check if fields are empty
    if (empty($username) || empty($password)) {
        $login_error = "Please enter both username and password.";
    } else {
        // --- Securely Fetch Hashed Password and other user data using Prepared Statement ---
        
        // --- MODIFICATION: Added book_user_status to the query ---
        $sql = "SELECT book_id, book_password, book_username, book_user_roles, book_profile, book_user_status FROM BookUser WHERE book_username = ?";

        // Prepare the statement
        $stmt = mysqli_prepare($connection, $sql);
        
        if ($stmt) {
            // Bind parameters (s = string)
            mysqli_stmt_bind_param($stmt, "s", $username);

            // Execute the statement
            mysqli_stmt_execute($stmt);

            // Get the result
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                // User found, get data
                $hashed_password_from_db = $row['book_password'];
                $user_id = $row['book_id'];
                $db_username = $row['book_username'];
                $db_user_role = $row['book_user_roles']; // Get the user's role
                $db_profile_picture_url = $row['book_profile'];
                $db_user_status = $row['book_user_status']; // --- Get the user's status


                // --- Verify Password using password_verify() ---
                if (password_verify($password, $hashed_password_from_db)) {
                    
                    // --- MODIFICATION: Check User Status ---
                    if ($db_user_status === 'Inactive') {
                        $login_error = 'Your account is currently Inactive. Please contact support.';
                    } else {
                        // Password is correct! Log the user in.
                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Store user data in the session
                        $_SESSION['book_id'] = $user_id;
                        $_SESSION['username'] = htmlspecialchars($db_username); // Use the username from DB for correct casing
                        $_SESSION['user_role'] = $db_user_role; // Store the user role in the session
                        $_SESSION['profile_picture_url'] = htmlspecialchars($db_profile_picture_url);
                        
                        // --- Use 'book_user_roles' for consistency with homepage.php ---
                        $_SESSION['book_user_roles'] = $db_user_role;


                        // --- NEW STAFF SESSION LOGIC ---
                        if ($db_user_role === 'Staff') {
                            // Record the login time in StaffSessions
                            $sql_start_session = "INSERT INTO StaffSessions (user_id, login_time) VALUES (?, NOW())";
                            $stmt_start = mysqli_prepare($connection, $sql_start_session);
                            mysqli_stmt_bind_param($stmt_start, "i", $user_id);
                            mysqli_stmt_execute($stmt_start);
                            
                            // Get the newly created session_id and store it
                            $_SESSION['staff_session_id'] = mysqli_insert_id($connection);
                            
                            mysqli_stmt_close($stmt_start);
                        }
                        // --- END NEW STAFF SESSION LOGIC ---


                        // --- Redirect based on the stored user role ---
                        switch ($db_user_role) {
                            case 'Admin':
                            case 'Staff':
                            case 'Customer':
                            default:
                                header("Location: homepage.php"); // Redirect to the main homepage to handle roles
                                break;
                        }
                        exit(); // Stop further script execution
                    }
                    // --- END STATUS CHECK ---

                } else {
                    // Password does NOT match the hash
                    $login_error = "Invalid username or password.";
                }
            } else {
                // Username does not exist
                $login_error = "Invalid username or password.";
            }
            
            // Close the statement
            mysqli_stmt_close($stmt);

        } else {
            // SQL statement preparation failed
            error_log("Database query error: " . mysqli_error($connection));
            $login_error = "An error occurred. Please try again later.";
        }
    }
} // End of POST request processing

// Close connection
if ($connection) {
    mysqli_close($connection);
}

// --- Header and Navigation Variables ---
$loggedIn = isset($_SESSION['book_id']);
$user_role = $loggedIn && isset($_SESSION['book_user_roles']) ? $_SESSION['book_user_roles'] : 'Guest'; // --- MODIFIED THIS LINE
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) && !empty($_SESSION['profile_picture_url'])
    ? htmlspecialchars($_SESSION['profile_picture_url']) 
    : '/college_project/book-a-flight-project-2/image_website/default_profile.png'; // Default path
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SierraFlight</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Using the same styles as sign_in_page.php and homepage.php for consistency */
        body {
            background-color: #1e1e2d; color: #e0e0e0; font-family: sans-serif;
            margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh;
        }
        .top-gradient-bar {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            padding: 10px 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); color: white;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
        }
        .top-gradient-bar .container {
            display: flex; justify-content: space-between; align-items: center;
            width: 100%; max-width: 1140px; margin: 0 auto; flex-wrap: wrap;
        }
        .top-gradient-bar .site-title {
            font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none;
            margin-right: auto; white-space: nowrap; display: flex; align-items: center;
        }
        .top-gradient-bar .site-title:hover { text-decoration: underline; }
        .top-gradient-bar .site-title .sierraflight-logo {
            width: 150px; height: auto; margin-right: 10px; vertical-align: middle;
        }
        .top-gradient-bar .user-info {
            display: flex; align-items: center; color: white;
            flex-shrink: 0; margin-left: auto; white-space: nowrap;
        }
        .top-gradient-bar .user-info a { color: white; text-decoration: none; display: flex; align-items: center; }
        .top-gradient-bar .user-info a:hover { text-decoration: underline; }
        .top-gradient-bar .profile-picture-nav, .top-gradient-bar .profile-icon-nav {
            width: 36px; height: 36px; border-radius: 50%; margin-left: 8px;
            vertical-align: middle; object-fit: cover; border: 1px solid white;
        }
        .top-gradient-bar .profile-icon-nav { border: none; }
        
        .navbar { background-color: #212529; padding: 0 20px; margin-bottom: 0; }
        .navbar > .container {
             display: flex; align-items: center; width: 100%;
             max-width: 1140px; margin: 0 auto; padding: 0;
        }
        /* Using navbar-toggler styles from sign_in_page.php */
        .navbar-toggler { display: none; }
        @media (max-width: 991.98px) {
             .navbar-toggler {
                 display: block; padding: .25rem .75rem; font-size: 1.25rem; line-height: 1;
                 background-color: transparent; border: 1px solid rgba(255, 255, 255, .1); border-radius: .25rem;
             }
              .navbar-collapse { background-color: #212529; padding: 10px; }
               .navbar > .container { justify-content: space-between; }
               .navbar-collapse { flex-grow: 1; }
        }
        .navbar-nav .nav-link {
             padding: 8px 15px; color: white !important;
             transition: background-color 0.3s ease, text-decoration 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: underline; color: white !important;
        }
        
        .page-content {
             padding: 20px; flex-grow: 1; display: flex;
             align-items: center; justify-content: center;
        }
        .login-container {
            max-width: 400px; padding: 30px; background-color: #282b3c;
            border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); color: #e0e0e0;
            width: 100%;
        }
        .login-container h2 { color: white; }
        .login-container .form-control {
             background-color: #3a3e52; border-color: #3a3e52; color: #e0e0e0;
         }
        .login-container .form-control::placeholder { color: #a0a0a0; }
        .login-container .form-control:focus {
              background-color: #3a3e52; border-color: #ffb03a;
              color: white; box-shadow: none;
        }
        .login-container .form-group label { color: #e0e0e0; }
        .login-container .btn-primary {
             background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
             border: none; color: white; padding: 10px 20px; font-size: 1rem;
             border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
             transition: background-position 0.5s ease; background-size: 200% auto;
        }
        .login-container .btn-primary:hover { background-position: right center; color: white; }
        .login-container p { color: #e0e0e0; }
        .login-container p a { color: #ffb03a; }
        .login-container p a:hover { text-decoration: underline; }
        
        /* Error message style from sign_in_page.php */
        .error-message {
            color: #dc3545; 
            margin-bottom: 15px; 
            text-align: left;
            padding: 10px; 
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.5); 
            border-radius: 5px;
        }
        .error-message p { /* Added for consistency if multiple errors */
             margin-bottom: 5px;
        }

    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="homepage.php" class="site-title">
                <img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo">
            </a>
            <div class="user-info">
                 <a href="login_page.php" class="nav-link">Login/Sign Up</a>
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
                    <li class="nav-item"><a class="nav-link" href="book_a_flight.php">Book a Flight</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="login-container">
            <h2 class="text-center mb-4">Login</h2>
            <?php
                if (!empty($login_error)) {
                    // Show login processing errors
                    echo "<div class='error-message'><p><i class='fas fa-exclamation-circle'></i> " . htmlspecialchars($login_error) . "</p></div>";
                }
                // --- ADDED THIS BLOCK to show messages from other pages (e.g., inactive redirect) ---
                if (isset($_SESSION['login_error'])) {
                     // Show errors passed from other pages (like homepage redirect)
                     echo "<div class='error-message'><p><i class='fas fa-exclamation-circle'></i> " . htmlspecialchars($_SESSION['login_error']) . "</p></div>";
                     unset($_SESSION['login_error']); // Clear message after displaying
                }
            ?>
            <form action="login_page.php" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required value="<?php echo $username_value; ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" name="login">Login</button>
                <p class="mt-3 text-center">Don't have an account? <a href="sign_in_page.php">Sign Up</a></p>
                <p class="mt-2 text-center" style="font-size: 0.9rem;"><a href="forgot_password.php">Forgot Password?</a></p>
            </form>
        </div>
    </div>
    
    <!-- Using a minimal footer for login page -->
    <footer class="site-footer" style="background-color: #212529; color: #a0a0a0; padding: 2rem 0; border-top: none; margin-top: auto;">
        <div class="container text-center">
            <p style="margin-bottom: 0.5rem; font-size: 0.9rem;">&copy; <?php echo date("Y"); ?> SierraFlight. All rights reserved.</p>
            <div class="social-icons" style="display: flex; justify-content: center; gap: 10px;">
                <a href="#" title="Instagram" style="color: #a0a0a0; font-size: 1.2rem;"><i class="fab fa-instagram"></i></a>
                <a href="#" title="Facebook" style="color: #a0a0a0; font-size: 1.2rem;"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Twitter" style="color: #a0a0a0; font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>

