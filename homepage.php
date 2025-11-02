<?php
session_start(); // Start the session

// --- Database Connection ---
include 'connection.php';

// Check for a logged-in user and their role
$loggedIn = isset($_SESSION['book_id']);
$user_role = 'Guest';
$username = 'Guest';
$profilePictureUrl = '/college_project/book-a-flight-project-2/image_website/default_profile.png';
$five_star_reviews = []; // Initialize array for reviews

// If a user is logged in, get their details from the database
if ($loggedIn) {
    $user_id = $_SESSION['book_id'];
    $sql = "SELECT book_username, book_user_roles, book_profile, book_user_status FROM BookUser WHERE book_id = ?";
    if ($stmt = mysqli_prepare($connection, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($user = mysqli_fetch_assoc($result)) {
            
            if ($user['book_user_status'] === 'Inactive' || $user['book_user_status'] === 'Deactivated') {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['login_error'] = 'Your account is currently ' . strtolower($user['book_user_status']) . '. Please contact support for assistance.';
                header('Location: login_page.php');
                exit();
            }

            $username = htmlspecialchars($user['book_username']);
            $user_role = $user['book_user_roles'];
            if (!empty($user['book_profile'])) {
                $profilePictureUrl = htmlspecialchars($user['book_profile']);
            }
            $_SESSION['book_user_roles'] = $user_role;
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Fetch 5-Star Reviews for Carousel (only for Customer/Guest view) ---
if ($user_role === 'Customer' || $user_role === 'Guest') {
    $review_sql = "SELECT r.comment, u.book_username 
                   FROM BookReviews r
                   JOIN BookUser u ON r.user_id = u.book_id
                   WHERE r.rating = 5 AND r.comment IS NOT NULL AND r.comment != ''
                   ORDER BY r.review_date DESC
                   LIMIT 5";
    if ($review_result = mysqli_query($connection, $review_sql)) {
        while ($row = mysqli_fetch_assoc($review_result)) {
            $five_star_reviews[] = $row;
        }
    }
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
            display: flex;
            align-items: center;
        }
        .top-gradient-bar .site-title:hover { text-decoration: underline; }
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
        .top-gradient-bar .profile-picture-nav {
            width: 36px; height: 36px; border-radius: 50%; margin-left: 8px;
            vertical-align: middle; object-fit: cover; border: 1px solid white;
        }
        .top-gradient-bar .btn-danger {
            background-color: #dc3545; border-color: #dc3545; padding: .3rem .6rem;
            font-size: .95rem; line-height: 1.5; border-radius: .2rem; margin-left: 10px;
        }
        .navbar { background-color: #212529; padding: 0 20px; margin-bottom: 0; }
        
        .navbar-nav .nav-link {
            padding: 8px 15px;
            color: white !important;
            transition: background-color 0.3s ease, text-decoration 0.3s ease;
            text-decoration: none;
            background-color: transparent;
        }
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: underline;
        }
        .navbar-nav .nav-item.active .nav-link {
            background-color: transparent !important;
            text-decoration: none;
        }
        .navbar-nav .nav-link:active {
            background-color: rgba(255, 255, 255, 0.2); 
        }
        
        .page-content {
            display: flex; align-items: center; justify-content: center;
            flex-grow: 1; padding: 2rem 0;
        }
        .jumbotron {
            background-color: transparent; color: #e0e0e0; text-align: center;
            padding-top: 5rem; padding-bottom: 5rem;
        }
        .jumbotron h1 { font-size: 3.5rem; margin-bottom: 20px; color: white; font-weight: 300; }
        .jumbotron p.lead { font-size: 1.5rem; margin-bottom: 30px; }
        .btn-primary, .btn-secondary {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            border: none; color: white !important; padding: 12px 30px; font-size: 1.2rem;
            border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.5s ease; background-size: 200% auto; text-decoration: none;
        }
        .btn-primary:hover, .btn-secondary:hover { background-position: right center; text-decoration: none; }
        .top-gradient-bar .site-title .sierraflight-logo {
            width: 150px; height: auto; margin-right: 10px; vertical-align: middle;
        }
        .staff-welcome-banner {
            background-color: #232635; border-radius: 15px; padding: 0;
            overflow: hidden; display: flex; align-items: center;
            box-shadow: 0 8px 16px rgba(0,0,0,0.4);
        }
        .staff-welcome-banner .text-content { padding: 2rem 3rem; text-align: left; }
        /* This rule is for the customer/guest button */
        .staff-welcome-banner .text-content p a.btn-lg { font-size: 1.2rem; }
        
        .staff-welcome-banner .image-content img { width: 100%; height: 100%; object-fit: cover; display: block; }
        @media (max-width: 991.98px) {
            .staff-welcome-banner { flex-direction: column; text-align: center; }
            .staff-welcome-banner .text-content { text-align: center; }
        }

        /* Review Carousel Section */
        .review-carousel-section {
            background-color: #212529;
            padding: 4rem 0;
            width: 100%;
        }
        .review-carousel-section h2 {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
            font-weight: 300;
        }
        #reviewCarousel .carousel-item {
            padding: 2rem 5rem;
            text-align: center;
        }
        #reviewCarousel .rating-stars {
            color: #ffc107;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        #reviewCarousel blockquote {
            font-size: 1.25rem;
            font-style: italic;
            color: #e0e0e0;
        }
        #reviewCarousel .blockquote-footer {
            margin-top: 1rem;
            color: #ffb03a;
            font-size: 1rem;
            font-style: normal;
        }
        #reviewCarousel .carousel-indicators li {
            background-color: #ffb03a;
        }

        .site-footer {
            background-color: #212529;
            color: #a0a0a0; padding: 4rem 0; border-top: none;
        }
        .site-footer h6 { color: #ffffff; font-weight: bold; margin-bottom: 1.5rem; }
        .site-footer a { color: #a0a0a0; text-decoration: none; }
        .site-footer a:hover { color: #ffb03a; text-decoration: underline; }
        .site-footer .list-unstyled li { margin-bottom: 0.75rem; }
        .social-icons a {
            display: inline-flex; justify-content: center; align-items: center;
            width: 36px; height: 36px; border-radius: 50%; background-color: #3a3e52;
            color: #e0e0e0; margin: 0 5px 5px 0; transition: background-color 0.3s;
        }
        .social-icons a:hover { background-color: #ffb03a; color: #1e1e2d; text-decoration: none; }
        .back-to-top {
            position: fixed; bottom: 25px; right: 25px;
            display: inline-flex; justify-content: center; align-items: center;
            width: 50px; height: 50px; border-radius: 50%; background-color: #EA2264;
            color: #fff; text-decoration: none; box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: opacity 0.3s, visibility 0.3s; z-index: 1000; opacity: 0; visibility: hidden;
        }
        .back-to-top.show { opacity: 1; visibility: visible; }
    </style>
</head>
<body>
HTML;
}

function get_html_footer($loggedIn, $user_role) {
    $account_links = '';
    if ($loggedIn) {
        $account_links = '<li><a href="profile_page.php">My Profile</a></li>';
        if ($user_role === 'Customer') {
            $account_links .= '<li><a href="booking_history.php">Booking History</a></li>';
        }
    } else {
        $account_links = '<li><a href="login_page.php">Sign In / Register</a></li>
                          <li><a href="forgot_password.php">Forgot Password</a></li>';
    }
    $sierraflight_links = '<li><a href="homepage.php">Home</a></li><li><a href="about.php">About Us</a></li><li><a href="contact.php">Contact Us</a></li>';
    $role_specific_column = '';
    
    if ($user_role === 'Staff' || $user_role === 'Admin') {
        $panel_name = ($user_role === 'Staff') ? 'Staff Panel' : 'Admin Panel';
        $panel_links = '';
        if ($user_role === 'Staff') {
            $panel_links = '<li><a href="staff_sales_report.php">Sales Report</a></li>
                            <li><a href="staff_booking_status.php">View Booking Status</a></li>
                            <li><a href="staff_user_feedback.php">User Feedback</a></li>';
        } else {
            $panel_links = '<li><a href="admin_account_manager.php">Account Manager</a></li>
                        <li><a href="admin_staff_salary.php">Staff Salary</a></li>
                        <li><a href="admin_salary_report.php">Salary Report</a></li>';
        }
        $role_specific_column = "<div class='col-lg-3 col-md-6 mb-4'><h6>{$panel_name}</h6><ul class='list-unstyled'>{$panel_links}</ul></div>";
    } else {
        $sierraflight_links .= '<li><a href="book_a_flight.php">Book a Flight</a></li>';
        $role_specific_column = '<div class="col-lg-3 col-md-6 mb-4"><h6>Support</h6><ul class="list-unstyled"><li><a href="contact.php">Help Center</a></li></ul></div>';
    }
    return <<<HTML
    <footer class="site-footer">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>SierraFlight</h6><ul class="list-unstyled">{$sierraflight_links}</ul>
                </div>
                {$role_specific_column}
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Account</h6><ul class="list-unstyled">{$account_links}</ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Follow Us</h6>
                    <div class="social-icons">
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <a href="#" class="back-to-top" title="Back to Top"><i class="fas fa-arrow-up"></i></a>
HTML;
}

function get_html_bottom() {
    return <<<HTML
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const backToTopButton = document.querySelector('.back-to-top');
            if (backToTopButton) {
                window.addEventListener('scroll', () => {
                    if (window.scrollY > 300) { backToTopButton.classList.add('show'); } 
                    else { backToTopButton.classList.remove('show'); }
                });
                backToTopButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        });
    </script>
</body>
</html>
HTML;
}

// Check user role and display appropriate content
switch ($user_role) {
    case 'Admin':
    case 'Staff':
        $role_title = ($user_role === 'Admin') ? 'Admin' : 'Staff';
        echo get_html_head("{$role_title} Homepage - SierraFlight");
        $profile_image_html = '<img src="' . $profilePictureUrl . '" alt="Profile Picture" class="profile-picture-nav">';
        
        $nav_links = ($user_role === 'Admin') ? '
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_account_manager.php">Account Manager</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_staff_salary.php">Staff Salary</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_salary_report.php">Salary Report</a></li>
                <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>'
            : '
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link" href="staff_sales_report.php">Sales Report</a></li>
                <li class="nav-item"><a class="nav-link" href="staff_booking_status.php">View Booking Status</a></li>
                <li class="nav-item"><a class="nav-link" href="staff_user_feedback.php">User Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>';

        $welcome_image_html = '';
        if ($user_role === 'Admin') {
            $welcome_image_html = '<img src="image_website/website_image/sierraflight_admin_page.png" alt="Admin Dashboard View">';
        } else { // Staff
            $welcome_image_html = '<img src="image_website/website_image/sierraflight_staff_page.png" alt="Staff Control Panel View">';
        }

        echo <<<HTML
        <div class="top-gradient-bar">
            <div class="container">
                <a href="index.php" class="site-title"><img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo"><span>({$role_title})</span></a>
                <div class="user-info">
                    <span>Welcome, {$username}!</span><a href="profile_page.php">{$profile_image_html}</a><a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                </div>
            </div>
        </div>
        <nav class="navbar navbar-expand-lg navbar-dark"><div class="container"><button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav mr-auto">
            <li class="nav-item active"><a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a></li>{$nav_links}
        </ul></div></div></nav>
        <div class="page-content"><div class="container"><div class="row staff-welcome-banner">
            <div class="col-lg-6 text-content"><h1 class="display-4">Welcome, {$username}!</h1><p class="lead">{$role_title} Panel Homepage</p></div>
            <div class="col-lg-6 p-0 image-content">{$welcome_image_html}</div>
        </div></div></div>
HTML;
        echo get_html_footer($loggedIn, $user_role);
        echo get_html_bottom();
        break;

    case 'Customer':
    default:
        $title = $loggedIn ? 'Flight Booking' : 'SierraFlight Home';
        echo get_html_head($title);
        $user_actions_html = $loggedIn ?
            '<span>Welcome, ' . $username . '!</span><a href="profile_page.php"><img src="' . $profilePictureUrl . '" alt="Profile Picture" class="profile-picture-nav"></a><a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>'
            : '<a href="login_page.php" class="nav-link">Login/Sign Up</a>';
        echo <<<HTML
        <div class="top-gradient-bar">
            <div class="container">
                <a href="index.php" class="site-title"><img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo"></a>
                <div class="user-info">{$user_actions_html}</div>
            </div>
        </div>
        <nav class="navbar navbar-expand-lg navbar-dark"><div class="container"><button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav mr-auto">
            <li class="nav-item active"><a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a></li>
            <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
            <li class="nav-item"><a class="nav-link" href="book_a_flight.php">Book a Flight</a></li>
HTML;
        if ($loggedIn) {
            echo '<li class="nav-item"><a class="nav-link" href="booking_history.php">Book History</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>';
        }
        echo '</ul></div></div></nav>';
        
        // --- MODIFICATION: Replaced Jumbotron with Welcome Banner ---
        // You will need to add an image at this path: 'image_website/website_image/sierraflight_customer_page.png'
        $welcome_image_html = '<img src="image_website/website_image/sierraflight_customer_page.png" alt="Plane window view">';
        $welcome_title = $loggedIn ? "Welcome, {$username}!" : "Welcome to SierraFlight!";
        $welcome_subtitle = $loggedIn ? "Find and book your next flight with ease." : "Sign in or register to manage your bookings.";
        $button_html = $loggedIn ? 
            '<p><a class="btn btn-primary btn-lg" href="book_a_flight.php" role="button">Book a Flight Now</a></p>' : 
            '<p><a class="btn btn-primary btn-lg" href="login_page.php" role="button">Login / Register</a></p>';

        echo <<<HTML
        <main class="page-content"><div class="container"><div class="row staff-welcome-banner">
            <div class="col-lg-6 text-content">
                <h1 class="display-4">{$welcome_title}</h1>
                <p class="lead">{$welcome_subtitle}</p>
                {$button_html}
            </div>
            <div class="col-lg-6 p-0 image-content">{$welcome_image_html}</div>
        </div></div></main>
        HTML;
        // --- END MODIFICATION ---

        // Display the review carousel only if there are reviews
        if (!empty($five_star_reviews)) {
            echo '<div class="review-carousel-section">
                    <div class="container">
                        <h2>What Our Customers Say</h2>
                        <div id="reviewCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">
                            <ol class="carousel-indicators">';
            foreach ($five_star_reviews as $index => $review) {
                $active_class = ($index == 0) ? 'class="active"' : '';
                echo "<li data-target='#reviewCarousel' data-slide-to='{$index}' {$active_class}></li>";
            }
            echo '      </ol>
                        <div class="carousel-inner">';
            foreach ($five_star_reviews as $index => $review) {
                $active_class = ($index == 0) ? 'active' : '';
                echo "<div class='carousel-item {$active_class}'>
                        <div class='rating-stars'>
                            <i class='fas fa-star'></i><i class='fas fa-star'></i><i class='fas fa-star'></i><i class='fas fa-star'></i><i class='fas fa-star'></i>
                        </div>
                        <blockquote>
                            <p class='mb-0'>\"" . htmlspecialchars($review['comment']) . "\"</p>
                            <footer class='blockquote-footer'>- " . htmlspecialchars($review['book_username']) . "</footer>
                        </blockquote>
                      </div>";
            }
            echo '      </div>
                        <a class="carousel-control-prev" href="#reviewCarousel" role="button" data-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#reviewCarousel" role="button" data-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                </div>
            </div>';
        }

        echo get_html_footer($loggedIn, $user_role);
        echo get_html_bottom();
        break;
}
?>