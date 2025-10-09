<?php
session_start(); // Start the session

// --- Database Connection ---
include 'connection.php';

// Check for a logged-in user and their role
$loggedIn = isset($_SESSION['book_id']);
$user_role = 'Guest';
$username = 'Guest';
$profilePictureUrl = '/college_project/book-a-flight-project-2/image_website/default_profile.png';

// If a user is logged in, get their details from the database
if ($loggedIn) {
    $user_id = $_SESSION['book_id'];

    // Fetch user details from the database using a prepared statement for security
    $sql = "SELECT book_username, book_user_roles, book_profile FROM BookUser WHERE book_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        $username = htmlspecialchars($user['book_username']);
        $user_role = $user['book_user_roles'];
        if (!empty($user['book_profile'])) {
            $profilePictureUrl = htmlspecialchars($user['book_profile']);
        }
        // ADDED: Set the user role in the session for other pages to access
        $_SESSION['book_user_roles'] = $user_role;
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($connection); // Close the database connection

// Define functions to generate common HTML sections
function get_html_head($title) {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
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
        .jumbotron {
            background-color: #282b3c;
            color: #e0e0e0;
            text-align: center;
            padding: 100px 0;
            margin-bottom: 0;
            background-image: none;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            width: 100%;
        }
        .jumbotron .container {
            flex-grow: 0;
        }
        .jumbotron h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: white;
        }
        .jumbotron p.lead {
            font-size: 1.5rem;
            margin-bottom: 30px;
        }
        .jumbotron .btn-primary {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: background-position 0.5s ease;
            background-size: 200% auto;
        }
        .jumbotron .btn-primary:hover {
            background-position: right center;
        }
        .jumbotron .btn-secondary {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: background-position 0.5s ease;
            background-size: 200% auto;
            margin-top: 15px;
        }
        .jumbotron .btn-secondary:hover {
            background-position: right center;
            color: white;
            text-decoration: none;
        }
        .jumbotron .btn-secondary:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
            outline: none;
        }
        .alert {
            margin-top: 15px;
            padding: 10px 15px;
            border-radius: 5px;
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
        .firstRow {
            background-color: #232635ff;
            display: flex;
            align-items: center; /* This centers the columns vertically */
            padding: 20px;
            border-radius: 8px;
        }
        .firstColumn {
            display: flex;
            flex-direction: column;
            justify-content: center; /* This centers the text vertically */
            align-items: center; /* This centers the text horizontally */
            height: 100%;
            text-align: center;
        }
        .imageStaff img {
            max-width: 100%;
            height: 100%; /* Make the image fill the height of its container */
            object-fit: cover; /* This crops the image to fill the container without stretching */
            border-radius: 8px;
        }
    </style>
</head>
<body>
HTML;
}

function get_html_bottom() {
    return <<<HTML
    <footer>
        <div class="container">
            <p>Copyright &copy; 2025 SierraFlight. All Rights Reserved.</p>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
HTML;
}

// Check user role and display appropriate content
switch ($user_role) {
    case 'Admin':
        // Admin Homepage
        echo get_html_head('Admin Homepage - SierraFlight.com');

        $profile_image_html = '<img src="' . $profilePictureUrl . '" alt="Profile Picture" class="profile-picture-nav">';

        echo <<<HTML
        <div class="top-gradient-bar">
            <div class="container">
                <a href="index.php" class="site-title">SierraFlight (Admin)</a>
                <div class="user-info">
                    <span>Welcome, {$username}!</span>
                    <a href="profile_page.php">{$profile_image_html}</a>
                    <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
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
                        <li class="nav-item active"> <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about.php">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_flight_list.php">Add Flight</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_flight_list.php">Flight List</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_booking_list.php">Booking List</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile_page.php">Profile</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="jumbotron">
            <div class="container">
                <h1 class="display-4">Welcome, {$username}!</h1> <p class="lead">Admin Panel Homepage.</p>
                <p><a class="btn btn-primary btn-lg" href="edit_book_flight.php" role="button">Add New Flight</a></p>
                <p><a class="btn btn-secondary btn-lg" href="admin_flight_list.php" role="button">View Flight List</a></p>
                <p><a class="btn btn-secondary btn-lg" href="admin_booking_list.php" role="button">View Booking List</a></p>
            </div>
        </div>
HTML;
        echo get_html_bottom();
        break;

    case 'Staff':
        // Staff Homepage
        echo get_html_head('Staff Homepage - SierraFlight');

        $profile_image_html = '<img src="' . $profilePictureUrl . '" alt="Profile Picture" class="profile-picture-nav">';

        echo <<<HTML
        <div class="top-gradient-bar">
            <div class="container">
                <a href="index.php" class="site-title">SierraFlight (Staff)</a>
                <div class="user-info">
                    <span>Welcome, {$username}!</span>
                    <a href="profile_page.php">{$profile_image_html}</a>
                    <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
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
                        <li class="nav-item active"> <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about.php">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_sales_report.php">Sales Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_booking_status.php">View Booking Status</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_booking_list.php">User Feedback</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile_page.php">Profile</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div>
            <div class="container">
                <div class = "firstRow row">
                    <div class = "col firstColumn">
                        <h1 class="display-4">Welcome, {$username}!</h1> <p class="lead">Staff Panel Homepage</p>
                    </div>
                    <div class = "imageStaff col">
                        <img src = "image_website\website_image\stock_image_staff.jpg">
                    </div>
                </div>
            </div>
        </div>
        <div class="jumbotron">
            <div class="container">
                
            </div>
        </div>
HTML;
        echo get_html_bottom();
        break;

    case 'Customer':
    default:
        // Customer or Guest Homepage
        $title = $loggedIn ? 'Flight Booking' : 'SierraFlight Home';
        echo get_html_head($title);

        $profile_image_html = '';
        if ($loggedIn) {
            $profile_image_html = '<img src="' . $profilePictureUrl . '" alt="Profile Picture" class="profile-picture-nav">';
        } else {
            $profile_image_html = '<i class="fas fa-user-circle fa-lg profile-icon-nav"></i>';
        }

        echo <<<HTML
        <div class="top-gradient-bar">
            <div class="container">
                <a href="index.php" class="site-title">SierraFlight</a>
                <div class="user-info">
                    <span>Welcome, {$username}!</span>
                    <a href="profile_page.php">{$profile_image_html}</a>
                    <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
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
                        <li class="nav-item active"> <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
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
        <div class="jumbotron">
            <div class="container">
                <h1 class="display-4">Welcome, {$username}!</h1>
                <p class="lead">Find and book your next flight with ease.</p>
                <p>
                    <a class="btn btn-primary btn-lg" href="book_a_flight.php" role="button">Book a Flight Now</a>
                </p>
            </div>
        </div>
HTML;
        echo get_html_bottom();
        break;
}

?>