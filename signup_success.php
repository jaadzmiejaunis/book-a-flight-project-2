<?php
    session_start(); // Start the session

    // These variables are primarily for the navbar consistency,
    // as the user won't be logged in immediately after signing up.
    $loggedIn = isset($_SESSION['book_id']); // Use 'book_id' for consistency
    $user_role = 'Guest'; // Set role for footer consistency
    $username = $loggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
    
    // --- MODIFICATION: Updated path to match homepage.php ---
    $defaultProfilePicture = '/college_project/book-a-flight-project-2/image_website/default_profile.png'; 
    $profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Successful! - SierraFlight</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- STYLES COPIED FROM homepage.php --- */
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
            /* --- MODIFICATION: Gradient changed to match homepage.php --- */
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
        .top-gradient-bar .profile-picture-nav,
        .top-gradient-bar .profile-icon-nav { /* Added from signup_success.php */
            width: 36px; height: 36px; border-radius: 50%; margin-left: 8px;
            vertical-align: middle; object-fit: cover; border: 1px solid white;
        }
         .top-gradient-bar .profile-icon-nav { /* Added from signup_success.php */
              border: none;
         }
        .top-gradient-bar .btn-danger {
            background-color: #dc3545; border-color: #dc3545; padding: .3rem .6rem;
            font-size: .95rem; line-height: 1.5; border-radius: .2rem; margin-left: 10px;
        }
        .navbar { background-color: #212529; padding: 0 20px; margin-bottom: 0; }
        
        .navbar > .container { /* Added from signup_success.php for toggler */
             display: flex;
             align-items: center;
             width: 100%;
             max-width: 1140px;
             margin: 0 auto;
             padding: 0;
        }
        
        /* --- MODIFICATION: Added toggler styles from signup_success.php --- */
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
            padding: 8px 15px; color: white !important;
            transition: background-color 0.3s ease, text-decoration 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: underline;
            color: white !important;
        }
        .page-content {
             padding: 20px;
             flex-grow: 1;
             display: flex;
             align-items: center;
             justify-content: center;
        }
        
        /* --- MODIFICATION: Added SierraFlight Logo style --- */
        .top-gradient-bar .site-title .sierraflight-logo {
            width: 150px; height: auto; margin-right: 10px; vertical-align: middle;
        }

        /* --- STYLES FOR SUCCESS CONTAINER (Kept from signup_success.php) --- */
        .signup-success-container {
            max-width: 500px;
            padding: 30px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            text-align: center;
            color: #e0e0e0;
        }

        .signup-success-container h2 {
            color: #28a745;
            margin-bottom: 20px;
        }

        .signup-success-container p {
            color: #ccc;
            margin-bottom: 20px;
        }

        /* --- MODIFICATION: Button style updated to match homepage.php --- */
        .signup-success-container .btn-login {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            border: none;
            color: white !important;
            padding: 12px 30px;
            font-size: 1.2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.5s ease;
            background-size: 200% auto;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .signup-success-container .btn-login:hover {
             background-position: right center;
             color: white;
             text-decoration: none;
        }

        /* --- FOOTER STYLES (Copied from homepage.php) --- */
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

    <div class="top-gradient-bar">
        <div class="container"> 
            <a href="homepage.php" class="site-title">
                <img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo">
            </a>
            <div class="user-info">
                <?php if ($loggedIn): // This block will not run on this page, but kept for consistency ?>
                     <a href="profile_page.php">
                         <span>Welcome, <?php echo $username; ?>!</span>
                         <?php if ($profilePictureUrl === $defaultProfilePicture || empty($profilePictureUrl)): ?>
                              <i class="fas fa-user-circle fa-lg profile-icon-nav" style="color: white; font-size: 36px; margin-left: 8px;"></i>
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
                        <a class="nav-link" href="homepage.php">Home</a> 
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                    </li>
                     <?php if ($loggedIn): // This block will not run ?>
                     <li class="nav-item">
                         <a class="nav-link" href="profile_page.php">Profile</a>
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
        <div class="signup-success-container">
            <h2><i class="fas fa-check-circle"></i> Sign Up Successful!</h2>
            <p>Thank you for registering. Please check your Gmail inbox for a confirmation link to activate your account.</p>
            <a href="login_page.php" class="btn btn-login">Proceed to Login</a>
        </div>
    </div>

    <footer class="site-footer">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>SierraFlight</h6>
                    <ul class="list-unstyled">
                        <li><a href="homepage.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="book_a_flight.php">Book a Flight</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="#">Help Center</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Account</h6>
                    <ul class="list-unstyled">
                        <li><a href="login_page.php">Sign In / Register</a></li>
                        <li><a href="forgot_password.php">Forgot Password</a></li>
                    </ul>
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
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

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