<?php
session_start(); // Start the session

// --- Admin Authentication Check ---
// Assuming this page is accessed after an admin action (like adding a flight)
if (!isset($_SESSION['book_id']) || $_SESSION['username'] !== 'Admin') {
    header('Location: login_page.php'); // Redirect to login if not logged in as Admin
    exit();
}

// Retrieve success message from session
$success_message = $_SESSION['booking_success_message'] ?? 'Action completed successfully.';
unset($_SESSION['booking_success_message']); // Clear the message after displaying

// Retrieve admin user data from the session for display in the navbar
$loggedIn = true;
// $username is still retrieved, but we won't display it next to "Admin"

// Default profile picture path for Admin (no longer strictly needed for display, but keeping the variable)
$defaultProfilePicture = 'path/to/default-admin-profile-picture.png'; // <<<--- UPDATE THIS PATH

// $profilePictureUrl is also not needed for display anymore, but keeping the variable
$profilePictureUrl = isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Success - BookAFlight.com</title>
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
          .top-gradient-bar .user-info a {
              color: white;
              text-decoration: none;
              display: flex;
              align-items: center;
          }
          .top-gradient-bar .user-info a:hover {
               text-decoration: underline;
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
            background-color: rgba(255, 255, 255, 0.15);
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
        }

        .success-message-container h2 {
            font-size: 2.5rem;
            color: #4CAF50;
            margin-bottom: 30px;
        }

        .success-message-container p {
            color: #ccc;
            margin-bottom: 20px;
        }

        .return-btn {
             background-image: linear-gradient(to right, #ffb03a, #dd5b12, #3b2e8b);
             color: white;
             border: none;
             padding: 10px 30px;
             font-size: 1.2rem;
             border-radius: 5px;
             text-decoration: none;
             display: inline-block;
             margin-top: 20px;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
             transition: background-position 0.5s ease;
             background-size: 200% auto;
         }
          .return-btn:hover {
               background-position: right center;
               color: white;
               text-decoration: none;
          }
          .return-btn:focus {
               box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
               outline: none;
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
            <a href="homepage_staff.php" class="site-title">BookAFlight.com (Admin)</a> <div class="user-info">
                <?php if ($loggedIn): ?>
                     <span>Admin</span>
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
                         <a class="nav-link" href="homepage_staff.php">Home</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="about_staff.php">About</a> </li>
                     <li class="nav-item">
                         <a class="nav-link" href="edit_book_flight.php">Add Flight</a>
                     </li>
                     <li class="nav-item active"> <a class="nav-link" href="admin_flight_list.php">Flight List <span class="sr-only">(current)</span></a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="admin_booking_list.php">Booking List</a>
                     </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="success-message-container">
            <h2><?php echo htmlspecialchars($success_message); ?></h2>
            <a href="admin_flight_list.php" class="btn btn-primary return-btn">View Flight List</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>