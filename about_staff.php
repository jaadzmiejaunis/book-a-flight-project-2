<?php
session_start(); // Start the session

// --- Admin Authentication Check ---
if (!isset($_SESSION['book_id']) || $_SESSION['username'] !== 'Admin') {
    header('Location: login_page.php'); // Redirect to your login page
    exit();
}

// Admin user is logged in, no need to fetch profile data for header display
$loggedIn = true; // We know they are logged in if they passed the check
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us (Admin) - BookAFlight.com</title>
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
             min-height: 100vh; /* Ensure body takes at least viewport height */
        }

        /* Container for the very top gradient bar */
        .top-gradient-bar {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            padding: 10px 20px; /* Padding for the gradient bar */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            color: white;
            display: flex; /* Use flexbox to space items */
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allow items to wrap on very small screens */
        }

        .top-gradient-bar .container {
            display: flex; /* Use flexbox within the container */
            justify-content: space-between;
            align-items: center;
            width: 100%; /* Ensure container takes full width */
             max-width: 1140px; /* Bootstrap's max-width for lg breakpoint */
             margin: 0 auto; /* Center the container */
             flex-wrap: wrap; /* Allow items inside container to wrap */
        }


        .top-gradient-bar .site-title {
            font-size: 1.5rem; /* Adjust font size */
            font-weight: bold;
            color: white;
            text-decoration: none;
            margin-right: auto; /* Push title to the left */
            white-space: nowrap; /* Prevent title from wrapping */
        }
         .top-gradient-bar .site-title:hover {
             text-decoration: underline;
         }

        /* Style for the logged-in user info in the top right of the gradient bar */
        .top-gradient-bar .user-info {
            display: flex;
            align-items: center;
            color: white;
             /* Ensure user info block stays together */
             flex-shrink: 0; /* Prevent shrinking */
             margin-left: auto; /* Ensure it's pushed to the right */
             white-space: nowrap; /* Prevent text within user-info from wrapping */
        }
         /* Styles related to profile picture/link are removed for admin */


         /* Style for the Logout button in the top right */
         .top-gradient-bar .btn-danger {
             background-color: #dc3545; /* Default Bootstrap danger color */
             border-color: #dc3545;
             padding: .3rem .6rem; /* Increased padding: .25*1.2=0.3, .5*1.2=0.6 */
             font-size: .95rem; /* Slightly increased font size */
             line-height: 1.5;
             border-radius: .2rem;
             margin-left: 10px; /* Space between text and logout button */
         }
         .top-gradient-bar .btn-danger:hover {
             background-color: #c82333;
             border-color: #bd2130;
         }


        /* Style for the navigation bar below the gradient bar */
        .navbar {
            background-color: #212529; /* Dark background color for the nav bar */
            padding: 0 20px; /* Match horizontal padding of the gradient bar */
            margin-bottom: 0; /* Remove default navbar margin */
            background-image: none;
            box-shadow: none;
            min-height: auto; /* Allow height to be determined by content */
        }

        .navbar > .container { /* Apply flex to the container inside navbar for layout */
             display: flex;
             align-items: center;
             width: 100%;
             max-width: 1140px; /* Match max-width of the top container */
             margin: 0 auto; /* Center the container */
             padding: 0; /* Remove container default padding if any */
        }

        .navbar-brand, /* Hide the navbar brand in this second row */
        .navbar-toggler {
            display: none; /* Hide brand and toggler on larger screens */
        }
         /* Show toggler on medium/small screens if needed */
        @media (max-width: 991.98px) { /* Bootstrap's lg breakpoint */
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
                  background-color: #212529; /* Background for collapsed menu */
                  padding: 10px;
              }
               /* Ensure the toggler is on the left when visible */
              .navbar > .container {
                   justify-content: space-between; /* Space between toggler and collapsed items */
              }
               .navbar-collapse {
                    flex-grow: 1; /* Allow collapse to grow */
               }
        }


        .navbar-nav .nav-link {
             padding: 8px 15px; /* Adjust padding for nav links */
             color: white !important;
             transition: background-color 0.3s ease, text-decoration 0.3s ease; /* Smooth transition */
        }

        /* Hover effect for navigation links */
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1); /* Subtle background highlight */
            text-decoration: underline; /* Add underline on hover */
            color: white !important; /* Ensure color stays white */
        }

        /* Active/Clicked effect for navigation links */
        .navbar-nav .nav-link:active {
             background-color: rgba(255, 255, 255, 0.2); /* Slightly darker background on click */
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


    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container"> <a href="homepage_staff.php" class="site-title">SierraFlight (Admin)</a> <div class="user-info">
                <?php if ($loggedIn): ?>
                     <span>Admin</span>
                     <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
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
                         <a class="nav-link" href="homepage_staff.php">Home</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="about_staff.php">About</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="edit_book_flight.php">Add Flight</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="admin_flight_list.php">Flight List</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="admin_booking_list.php">Booking List</a>
                     </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="about-container">
            <h2 class="about-header">About Our Flight Booking System</h2>
            <div class="about-content">
                <p>Welcome to the administrator section of our flight booking system. Here you can manage flights and view user bookings.</p>
                <p>Our system is designed to provide administrators with the tools needed to maintain the flight database and oversee user activity related to bookings.</p>
                <p>This platform was developed as part of a college project, focusing on building a functional web application with user authentication, database interaction, and basic flight booking capabilities, including administrative functions.</p>
                <p>We are continuously working to improve the system and add more features. Thank you for managing the system!</p>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>