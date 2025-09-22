<?php
/**
 * about.php
 * This page provides information about the flight booking system.
 * It includes the standard navigation bar which changes based on user login status.
 */

// Start the session
session_start();

// --- User Authentication and Data Retrieval ---
// Check if the user is logged in by checking for the 'book_id' session variable.
$loggedIn = isset($_SESSION['book_id']);

// Get the username from the session if logged in, otherwise set to 'Guest'.
// htmlspecialchars is used to prevent XSS when displaying the username.
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

// Get the user's role from the session. Defaults to 'Guest' if not set.
// CORRECTED: Reading 'user_role' which is set by login_page.php
$user_role = $loggedIn && isset($_SESSION['book_user_roles']) ? $_SESSION['book_user_roles'] : 'Guest';

// Define the default profile picture path.
$defaultProfilePicture = 'path/to/default-profile-picture.png'; // <<<--- UPDATE THIS PATH

// Get the profile picture URL from the session if logged in and available,
// otherwise use the default profile picture path.
// htmlspecialchars is used to prevent XSS when displaying the URL.
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

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
    <title>About Us - BookAFlight.com</title>
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

        .navbar-nav .nav-item.active .nav-link {
            font-weight: bold;
            text-decoration: none;
        }

        .page-content {
             padding: 20px;
             flex-grow: 1;
        }

        .about-container {
            margin: 30px auto;
            max-width: 800px;
            padding: 30px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
             color: #e0e0e0;
        }

        .about-header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
             font-size: 2rem;
        }

        .about-content p {
            line-height: 1.6;
            margin-bottom: 15px;
            color: #ccc;
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
            <a href="index.php" class="site-title"><?php echo $siteTitle; ?></a>
            <div class="user-info">
                <?php if ($loggedIn): // Check if user is logged in ?>
                     <a href="profile_page.php">
                         Profile
                         <?php if ($profilePictureUrl === $defaultProfilePicture): // Check if using default profile picture ?>
                             <i class="fas fa-user-circle fa-lg profile-icon-nav"></i> <?php else: ?>
                             <img src="<?php echo htmlspecialchars($profilePictureUrl); ?>" alt="Profile Picture" class="profile-picture-nav"> <?php endif; ?>
                     </a>
                     <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php else: // If not logged in ?>
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
                    <li class="nav-item active"> <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                         <?php if ($loggedIn): // Show Book a Flight only if logged in ?>
                             <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                         <?php else: // Otherwise, link to login page ?>
                             <a class="nav-link" href="login_page.php">Book a Flight</a>
                         <?php endif; ?>
                    </li>
                     <?php if ($loggedIn): // Show Profile and Check Book only if logged in ?>
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
        <div class="about-container">
            <h2 class="about-header">About Our Flight Booking System</h2>
            <div class="about-content">
                <p>Welcome to our flight booking system! We aim to provide a simple and efficient way for you to find and book flights to various destinations.</p>
                <p>Our system allows you to search for flights based on origin, destination, dates, and class. Once you find the perfect flight, you can proceed with a straightforward booking process.</p>
                <p>For registered users, we offer personalized features such as a profile page to manage your information and a history of your past bookings.</p>
                <p>This project was developed as part of a college project, focusing on building a functional web application with user authentication, database interaction, and basic flight booking capabilities.</p>
                <p>We are continuously working to improve the system and add more features. Thank you for using our flight booking system!</p>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>