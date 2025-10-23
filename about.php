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
$username = 'Guest'; // Default
if ($loggedIn && isset($_SESSION['book_id'])) {
    // --- Database Connection ---
    include 'connection.php';
    if ($connection) {
        $user_id = $_SESSION['book_id'];
        $sql = "SELECT book_username, book_user_roles, book_profile FROM BookUser WHERE book_id = ?";
        if ($stmt = mysqli_prepare($connection, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($user = mysqli_fetch_assoc($result)) {
                $username = htmlspecialchars($user['book_username']);
                $user_role = $user['book_user_roles'];
                if (!empty($user['book_profile'])) {
                    $profilePictureUrl = htmlspecialchars($user['book_profile']);
                }
                $_SESSION['book_user_roles'] = $user_role;
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_close($connection);
    }
}

// Get the user's role from the session. Defaults to 'Guest' if not set.
$user_role = $_SESSION['book_user_roles'] ?? 'Guest';

// Define the default profile picture path.
$defaultProfilePicture = '/college_project/book-a-flight-project-2/image_website/default_profile.png'; 

// Get the profile picture URL from the session if logged in and available,
// otherwise use the default profile picture path.
$profilePictureUrl = $profilePictureUrl ?? $defaultProfilePicture;

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
    <title>About Us - SierraFlight</title>
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
            display: flex; /* Added for vertical alignment */
            align-items: center; /* Added for vertical alignment */
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
            text-decoration: none;
        }
        
        .top-gradient-bar .user-info span {
            margin-right: 8px;
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
            font-size: 36px;
            color: white;
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
        }

        .about-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            padding: 30px;
            color: #e0e0e0;
        }

        .about-header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
            font-size: 2rem;
        }

        .about-content {
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .about-content h3 {
            color: #ffb03a;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="top-gradient-bar">
        <div class="container">
            <a href="homepage.php" class="site-title">
                <img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo">
                <?php if ($user_role === 'Admin'): ?>
                    <span>(Admin)</span>
                <?php elseif ($user_role === 'Staff'): ?>
                    <span>(Staff)</span>
                <?php endif; ?>
            </a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                <a href="profile_page.php">
                    <span>Welcome, <?php echo $username; ?>!</span>
                    <?php if ($profilePictureUrl === $defaultProfilePicture || empty($profilePictureUrl)): ?>
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

    <!-- --- MODIFICATION: Updated Navigation Bar Logic --- -->
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
                    <li class="nav-item active">
                        <a class="nav-link" href="about.php">About <span class="sr-only">(current)</span></a>
                    </li>
                    <?php if ($user_role === 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_account_manager.php">Account Manager</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile_page.php">Profile</a>
                        </li>
                    <?php elseif ($user_role === 'Staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_sales_report.php">Sales Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_booking_status.php">View Booking Status</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_user_feedback.php">User Feedback</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile_page.php">Profile</a>
                        </li>
                    <?php elseif ($user_role === 'Customer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="booking_history.php">Book History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile_page.php">Profile</a>
                        </li>
                    <?php else: // Guest ?>
                        <li class="nav-item">
                            <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- --- END MODIFICATION --- -->

    <div class="container page-content">
        <div class="about-container">
            <?php if ($user_role === 'Admin'): ?>
                <h2 class="about-header">About Our System (Admin View)</h2>
                <div class="about-content">
                    <p>Welcome, Admin! This section provides an overview of your responsibilities and the system's administrative features.</p>
                    <h3>Admin Responsibilities:</h3>
                    <ul>
                        <li>Manage user accounts, roles, and statuses.</li>
                        <li>Monitor site performance and user activity.</li>
                        <li>Handle critical booking issues and data management.</li>
                        <li>Access comprehensive analytics and reports.</li>
                    </ul>
                    <p>Your role is essential for ensuring the smooth and secure operation of the flight booking system. Thank you for your hard work!</p>
                </div>
            <?php elseif ($user_role === 'Staff'): ?>
                <h2 class="about-header">About Our System (Staff View)</h2>
                <div class="about-content">
                    <p>Welcome, Staff! This page outlines your role within the SierraFlight team and the tools available to you.</p>
                    <h3>Staff Responsibilities:</h3>
                    <ul>
                        <li>Process and confirm flight bookings.</li>
                        <li>Assist customers with booking inquiries and issues.</li>
                        <li>Update booking statuses and customer details.</li>
                        <li>Maintain accurate flight information.</li>
                    </ul>
                    <p>Your efforts directly contribute to a positive customer experience. Thank you for your dedication!</p>
                </div>
            <?php else: // This handles 'Customer' and 'Guest' roles ?>
                <h2 class="about-header">About Our Flight Booking System</h2>
                <div class="about-content">
                    <p>Welcome to our flight booking system! We aim to provide a simple and efficient way for you to find and book flights to various destinations.</p>
                    <p>Our system allows you to search for flights based on origin, destination, dates, and class. Once you find the perfect flight, you can proceed with a straightforward booking process.</p>
                    <p>For registered users, we offer personalized features such as a profile page to manage your information and a history of your past bookings.</p>
                    <p>This project was developed as part of a college project, focusing on building a functional web application with user authentication, database interaction, and basic flight booking capabilities.</p>
                    <p>We are continuously working to improve the system and add more features. Thank you for using our flight booking system!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
