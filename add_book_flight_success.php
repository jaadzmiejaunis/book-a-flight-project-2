<?php
/**
 * add_book_flight_success.php
 * This page is displayed to the administrator after successfully adding a new flight.
 * It shows a success message and provides links back to the admin homepage and add flight page.
 * Access is restricted to users with the username 'Admin'.
 */

// Start the session - MUST be the very first thing in the file
session_start();

// --- Admin Authentication Check ---
// Check if the user is logged in AND if their username is 'Admin'.
// This assumes 'Admin' is the designated username for administrative access.
if (!isset($_SESSION['book_id']) || $_SESSION['username'] !== 'Admin') {
    // If not logged in as Admin, redirect to the login page.
    $_SESSION['login_error'] = "Access denied. Please log in with an administrator account."; // Optional message
    header('Location: login_page.php'); // Redirect to your login page.
    exit(); // Stop further script execution.
}

// --- Retrieve Success Message ---
// Get the success message from the session, set by admin_update_flight.php upon successful addition.
// Provide a default message if the session variable is not set (e.g., if accessed directly).
// htmlspecialchars is used to prevent XSS when displaying the message.
$success_message = htmlspecialchars($_SESSION['add_message'] ?? 'Flight added successfully.');

// Clear the success message and type from the session after retrieving them
// so they don't persist if the admin navigates back to this page.
unset($_SESSION['add_message']);
unset($_SESSION['message_type']); // Assuming type was also set by the processing script.


// --- Admin User Data Retrieval for Navbar ---
// We know the user is logged in as Admin if they passed the check above.
$loggedIn = true;
$username = htmlspecialchars($_SESSION['username']); // Get admin username from session.

// Define the default profile picture path for Admin (if applicable).
// **NOTE:** Update this path if Admin has a specific picture, otherwise it will use a generic icon via CSS.
$defaultAdminProfilePicture = 'path/to/default-admin-profile-picture.png'; // <<<--- UPDATE THIS PATH

// Get profile picture URL from session if available, otherwise use the default admin picture path.
$profilePictureUrl = isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultAdminProfilePicture;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Added Successfully - BookAFlight.com</title>
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
            text-align: center;
        }

        .top-gradient-bar {
            background-image: linear-gradient(to right, #3b2e8b, #ffb03a);
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

        .top-gradient-bar .user-info .nav-link {
             color: white;
             padding: .375rem .75rem;
        }

        .top-gradient-bar .profile-picture-nav,
        .top-gradient-bar .profile-icon-nav {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 8px;
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

        .navbar-nav .nav-item.active .nav-link {
            font-weight: bold;
            text-decoration: none;
        }

        .page-content {
             padding: 20px;
             flex-grow: 1;
             display: flex;
             align-items: center;
             justify-content: center;
        }

        .success-message-container {
            max-width: 500px;
            padding: 30px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            color: #e0e0e0;
        }

        .success-message-container h2 {
            font-size: 2.5rem;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .success-message-container p {
            color: #ccc;
            margin-bottom: 30px;
        }

        .success-buttons {
             margin-top: 20px;
        }
         .success-buttons a.btn {
             margin: 10px;
             padding: 10px 20px;
             font-size: 1.1rem;
             border-radius: 5px;
             text-decoration: none;
             display: inline-block;
         }

         .btn-secondary {
              background-color: #6c757d;
              color: white;
              border: none;
              box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
              transition: background-color 0.3s ease;
         }
          .btn-secondary:hover {
               background-color: #5a6268;
               color: white;
               text-decoration: none;
          }
           .btn-secondary:focus {
               box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.5);
               outline: none;
           }

        .btn-primary {
             background-image: linear-gradient(to right, #ffb03a, #dd5b12, #3b2e8b);
             color: white;
             border: none;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
             transition: background-position 0.5s ease;
             background-size: 200% auto;
        }
         .btn-primary:hover {
               background-position: right center;
               color: white;
               text-decoration: none;
          }
           .btn-primary:focus {
             box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
             outline: none;
         }

         .alert {
             margin-top: 20px;
             margin-bottom: 20px;
             text-align: left;
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
        <div class="container"> <span class="site-title">Admin Panel</span>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <?php if ($profilePictureUrl !== $defaultAdminProfilePicture): ?>
                         <img src="<?php echo htmlspecialchars($profilePictureUrl); ?>" alt="Admin Picture" class="profile-picture-nav">
                     <?php else: ?>
                         <i class="fas fa-user-cog fa-lg profile-icon-nav"></i> <?php endif; ?>
                     <span class="nav-link">Welcome, <?php echo htmlspecialchars($username); ?></span>
                     <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container"> <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAdmin" aria-controls="navbarNavAdmin" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAdmin">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item active"> <a class="nav-link" href="homepage_staff.php">Admin Home <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                         <a class="nav-link" href="edit_book_flight.php">Add New Flight</a>
                    </li>
                    <li class="nav-item">
                         <a class="nav-link" href="admin_flight_list.php">View Flight List</a>
                    </li>
                     <li class="nav-item">
                         <a class="nav-link" href="admin_booking_list.php">View Booking List</a>
                     </li>
                     </ul>
                 </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="success-message-container">
            <h2><i class="fas fa-check-circle" style="color: #4CAF50; margin-right: 10px;"></i><?php echo htmlspecialchars($success_message); ?></h2>
            <p>The new flight has been successfully added to the database.</p>
            <div class="success-buttons">
                 <a href="homepage_staff.php" class="btn btn-secondary">Go to Admin Homepage</a>
                 <a href="edit_book_flight.php" class="btn btn-primary">Add More Flights</a>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

</body>
</html>