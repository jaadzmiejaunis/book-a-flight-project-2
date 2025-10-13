<?php
session_start(); // Start the session

// Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['book_id'])) {
    header('Location: login_page.php'); // Replace 'login_page.php' with your actual login page file
    exit();
}

// --- Database Connection ---
// In a production environment, store credentials in a configuration file outside the web root.
include 'connection.php';

// Check connection
if (!$connection) {
    // Log the error to the server logs
    error_log("Database connection failed: " . mysqli_connect_error());
    // Display a generic error message to the user
    die("An error occurred connecting to the database. Please try again later.");
}
// --- End Database Connection ---

// Get the logged-in user's ID from the session
$user_id = $_SESSION['book_id'];

// Define the default profile picture path (web-accessible)
$defaultProfilePicture = '/college_project/book-a-flight-project-2/image_website/default_profile.png';

// Fetch user data from the database
$sql = "SELECT book_username, book_email, book_profile FROM BookUser WHERE book_id = ?";
$stmt = mysqli_prepare($connection, $sql);

$username = ''; // Initialize variables to prevent errors if fetch fails
$email = '';
$profilePictureUrl = $defaultProfilePicture; // Set initial profile picture to default

if ($stmt) {
    // Bind the user ID parameter to the prepared statement
    mysqli_stmt_bind_param($stmt, "i", $user_id); // 'i' for integer user_id

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Get the result of the execution
    $result = mysqli_stmt_get_result($stmt);

    // Check if a user was found
    if ($user = mysqli_fetch_assoc($result)) {
        // User data fetched successfully
        $username = htmlspecialchars($user['book_username']);
        $email = htmlspecialchars($user['book_email']);
        // Use fetched profile picture URL if available and not empty, otherwise use default
        if (!empty($user['book_profile'])) {
             $profilePictureUrl = htmlspecialchars($user['book_profile']);
        } else {
             $profilePictureUrl = $defaultProfilePicture;
        }

    } else {
        // User not found in the database (shouldn't happen if login works correctly)
        error_log("User ID " . $user_id . " not found in database.");
        // Redirect to an error page or logout
        header('Location: log_out_page.php'); // Log out the user if their data isn't found
        exit();
    }

    // Close the prepared statement
    mysqli_stmt_close($stmt);
} else {
    // Prepared statement failed (e.g., syntax error in SQL query or 'BookUser' table/columns don't exist)
    error_log("Database prepare error: " . mysqli_error($connection)); // Log the specific MySQL error
    // Display a generic error message to the user
    die("An internal error occurred. Please try again.");
}

// Close database connection
mysqli_close($connection);

// Check for success or error messages from profile_update_page.php
$success_message = $_SESSION['profile_success_message'] ?? '';
$error_message = $_SESSION['profile_error_message'] ?? '';
unset($_SESSION['profile_success_message']); // Clear the messages after displaying
unset($_SESSION['profile_error_message']);

// --- Get Profile Picture URL for Navbar from Session ---
// This ensures the navbar picture updates immediately after a successful upload
// The session variable is set in profile_update_page.php
$navbarProfilePictureUrl = $_SESSION['profile_picture_url'] ?? $profilePictureUrl;

// Fallback to default if session variable is empty
if (empty($navbarProfilePictureUrl)) {
    $navbarProfilePictureUrl = $defaultProfilePicture;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - SierraFlight</title>
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

         .profile-container {
             max-width: 600px;
             background-color: #282b3c;
             border-radius: 8px;
             box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
             overflow: hidden;
             padding: 30px;
         }

         .profile-header {
              background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
              color: white;
              padding: 20px;
              margin: -30px -30px 30px -30px;
              text-align: center;
              font-size: 1.8rem;
              font-weight: bold;
         }

         .form-control {
              background-color: #3a3e52;
              color: #fff;
              border: 1px solid #5a5a8a;
         }
          .form-control::placeholder {
              color: #ccc;
          }
          .form-control:focus {
              border-color: #ffb03a;
              box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
              outline: none;
          }

         .form-group label {
             color: #e0e0e0;
             margin-bottom: .5rem;
         }

         .btn-primary {
              background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
              color: white;
              border: none;
              padding: 10px 20px;
              font-size: 1rem;
              border-radius: 5px;
              box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
              transition: background-position 0.5s ease;
              background-size: 200% auto;
              display: block;
              width: 100%;
              margin-top: 20px;
         }
          .btn-primary:hover {
               background-position: right center;
               color: white;
           }
            .btn-primary:focus {
              box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
              outline: none;
           }

          .profile-picture {
              width: 80px;
              height: 80px;
              border-radius: 50%;
              object-fit: cover;
              margin-bottom: 20px;
              display: block;
              margin-left: auto;
              margin-right: auto;
              border: 3px solid #6a82fb;
           }

          .custom-file-input {
               color: transparent;
          }
          .custom-file-input::-webkit-file-upload-button {
               visibility: hidden;
          }
          .custom-file-input::before {
               content: 'Choose File';
               display: inline-block;
               background: #007bff;
               color: #fff;
               border: 1px solid #007bff;
               border-radius: .25rem;
               padding: .375rem .75rem;
               outline: none;
               white-space: nowrap;
               -webkit-user-select: none;
               cursor: pointer;
               font-weight: 700;
               font-size: 10pt;
               margin-right: 5px;
           }
          .custom-file-input:hover::before {
               background: #0056b3;
               border-color: #0056b3;
           }
          .custom-file-input:active::before {
               background: #0056b3;
               border-color: #0056b3;
           }
            .custom-file-input:disabled::before {
               background: #6c757d;
               border-color: #6c757d;
               cursor: not-allowed;
           }

           .custom-file-label {
               overflow: hidden;
               white-space: nowrap;
               text-overflow: ellipsis;
               background-color: #3a3e52;
               color: #ccc;
               border: 1px solid #5a5a8a;
               line-height: 1.5;
               padding: .375rem .75rem;
           }
           .custom-file-label::after {
                content: "Choose file..." !important;
                display: none;
           }
            .custom-file-input:focus ~ .custom-file-label {
                border-color: #ffb03a;
                box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
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
     </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="index.php" class="site-title">
                <img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo">
            </a>
            <div class="user-info">
                <?php if (isset($_SESSION['book_id'])): ?>
                     <a href="profile_page.php">
                         <span>Welcome, <?php echo $username; ?>!</span>
                         <?php if ($navbarProfilePictureUrl === $defaultProfilePicture || empty($navbarProfilePictureUrl)): ?>
                              <i class="fas fa-user-circle fa-lg profile-icon-nav"></i>
                         <?php else: ?>
                              <img src="<?php echo htmlspecialchars($navbarProfilePictureUrl); ?>" alt="Profile Picture" class="profile-picture-nav">
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                         <?php if (isset($_SESSION['book_id'])): ?>
                              <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                         <?php else: ?>
                              <a class="nav-link" href="login_page.php">Book a Flight</a>
                         <?php endif; ?>
                    </li>
                     <?php if (isset($_SESSION['book_id'])): ?>
                     <li class="nav-item active">
                         <a class="nav-link" href="profile_page.php">Profile <span class="sr-only">(current)</span></a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="booking_history.php">Book History</a>
                     </li>
                     <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="profile-container">
            <div class="profile-header">
                User Profile
            </div>

            <?php
                 // Display success or error messages
                 if ($success_message) {
                     echo "<div class='alert alert-success' role='alert'>" . htmlspecialchars($success_message) . "</div>";
                 }
                 if ($error_message) {
                     echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error_message) . "</div>";
                 }
            ?>

            <img src="<?php echo htmlspecialchars($profilePictureUrl); ?>" alt="Profile Picture" class="profile-picture">

            <form action="profile_update_page.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="profile_picture" name="profile_picture" accept="image/*">
                        <label class="custom-file-label" for="profile_picture">Choose file...</label>
                    </div>
                     <small class="form-text text-muted">Choose an image file (JPG, PNG) to upload.</small>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-4">Save Changes</button>
            </form>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    // Script to display the chosen file name in the custom file input label
    $('.custom-file-input').on('change', function() {
      let fileName = $(this).val().split('\\').pop();
      $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
    </script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

</body>
</html>