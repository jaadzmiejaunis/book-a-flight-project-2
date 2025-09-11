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
        // Select the hashed password, book_id, book_username, and book_profile
        $sql = "SELECT book_id, book_password, book_username, book_profile FROM BookUser WHERE book_username = ?";

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
                $db_username = $row['book_username']; // Get username from DB
                $db_profile_picture_url = $row['book_profile']; // Get profile picture URL from DB


                // --- Verify Password using password_verify() ---
                // This function correctly compares a plain text password with a hash
                if (password_verify($password, $hashed_password_from_db)) {
                    // Password is correct! Log the user in.
                    // Store user data in the session using consistent keys
                    $_SESSION['book_id'] = $user_id; // Use book_id for user ID
                    $_SESSION['username'] = htmlspecialchars($db_username); // Use 'username' for consistency with homepage
                    $_SESSION['profile_picture_url'] = htmlspecialchars($db_profile_picture_url); // Use 'profile_picture_url' for consistency with homepage


                    // --- Redirect based on username ---
                    if ($db_username === 'Admin') {
                        header("Location: homepage_staff.php"); // Redirect admin to staff homepage (Assuming this exists)
                    } elseif($db_username === 'Staff'){
                        header("Location: staff_homepage.php");
                    }
                    else {
                        header("Location: index.php"); // Redirect other users to regular homepage
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

            // Close statement and result set
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
$loggedIn = isset($_SESSION['book_id']); // Re-check logged-in status after potential login attempt
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
$defaultProfilePicture = 'path/to/default-profile-picture.png'; // <<<--- UPDATE THIS PATH
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

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
         .top-gradient-bar .user-info a {
             color: white;
             text-decoration: none;
             display: flex;
             align-items: center;
         }
         .top-gradient-bar .user-info a:hover {
              text-decoration: underline;
         }

        .top-gradient-bar .profile-picture-nav,
        .top-gradient-bar .profile-icon-nav {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
            object-fit: cover;
             border: 1px solid white;
        }
         .top-gradient-bar .profile-icon-nav {
              border: none;
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

    <div class="top-gradient-bar">
        <div class="container"> <a href="index.php" class="site-title">SierraFlight</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <a href="profile_page.php">
                         Profile
                         <?php if ($profilePictureUrl === $defaultProfilePicture): ?>
                              <i class="fas fa-user-circle fa-lg profile-icon-nav"></i>
                         <?php else: ?>
                              <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav">
                         <?php endif; ?>
                     </a>
                     <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php else: ?>
                    <a href="login_page.php" class="nav-link">Login/Sign Up</a>
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
                        <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                    </li>
                     <?php if ($loggedIn): ?>
                     <li class="nav-item">
                         <a class="nav-link" href="profile_page.php">Profile</a>
                     </li>
                      <li class="nav-item">
                         <a class="nav-link" href="booking_history.php">Check Book</a>
                     </li>
                     <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="login-container">
            <h2 class="text-center mb-4">Login</h2>
            <?php
                // Display error message if set
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