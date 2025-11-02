<?php
session_start();

// --- User Authentication and Data Retrieval ---
$loggedIn = isset($_SESSION['book_id']);
$username = 'Guest';
$user_role = 'Guest';

// --- FIX: Define $defaultProfilePicture ---
$defaultProfilePicture = '/college_project/book-a-flight-project-2/image_website/default_profile.png'; 
$profilePictureUrl = $defaultProfilePicture; // Set default value

if ($loggedIn && isset($_SESSION['book_id'])) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SierraFlight</title>
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
        .contact-container {
            max-width: 900px;
            margin: 30px auto;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            padding: 30px;
            color: #e0e0e0;
        }
        .contact-header {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            color: white;
            padding: 20px;
            margin: -30px -30px 30px -30px;
            text-align: center;
            font-size: 1.8rem;
            font-weight: bold;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .contact-info {
            list-style: none;
            padding-left: 0;
            font-size: 1.1rem;
            line-height: 2.2;
        }
        .contact-info li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .contact-info i {
            color: #ffb03a;
            font-size: 1.5rem;
            margin-right: 20px;
            width: 30px;
            text-align: center;
        }
        
        .contact-form .form-control {
            background-color: #3a3e52;
            color: #fff;
            border: 1px solid #5a5a8a;
        }
        .contact-form .form-control:focus {
            border-color: #ffb03a;
            box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
            outline: none;
            background-color: #3a3e52;
            color: #fff;
        }
        .contact-form .form-group label {
            color: #e0e0e0;
            margin-bottom: .5rem;
        }
        .contact-form .btn-primary {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: background-position 0.5s ease;
            background-size: 200% auto;
            width: 100%;
        }
        .contact-form .btn-primary:hover {
            background-position: right center;
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
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="contact.php">Contact Us <span class="sr-only">(current)</span></a>
                    </li>
                    
                    <?php if ($user_role === 'Admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_account_manager.php">Account Manager</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_staff_salary.php">Staff Salary</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_salary_report.php">Salary Report</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>
                    <?php elseif ($user_role === 'Staff'): ?>
                        <li class="nav-item"><a class="nav-link" href="staff_sales_report.php">Sales Report</a></li>
                        <li class="nav-item"><a class="nav-link" href="staff_booking_status.php">Booking Status</a></li>
                        <li class="nav-item"><a class="nav-link" href="staff_user_feedback.php">User Feedback</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>
                    <?php elseif ($user_role === 'Customer'): ?>
                        <li class="nav-item"><a class="nav-link" href="book_a_flight.php">Book a Flight</a></li>
                        <li class="nav-item"><a class="nav-link" href="booking_history.php">Book History</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>
                    <?php else: // Guest ?>
                        <li class="nav-item"><a class="nav-link" href="book_a_flight.php">Book a Flight</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="contact-container">
            <div class="contact-header">Get In Touch</div>
            
            <?php if (isset($_SESSION['contact_success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $_SESSION['contact_success']; ?>
                </div>
                <?php unset($_SESSION['contact_success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['contact_error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $_SESSION['contact_error']; ?>
                </div>
                <?php unset($_SESSION['contact_error']); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <h3>Contact Information</h3>
                    <p>Have questions? We're here to help. Reach out to us via phone, email, or by using the contact form.</p>
                    <ul class="contact-info">
                        <li><i class="fas fa-phone"></i> +60 12-345 6789</li>
                        <li><i class="fas fa-envelope"></i> support@sierraflight.com</li>
                        <li><i class="fas fa-map-marker-alt"></i> Jalan Sierra, 88000 Kota Kinabalu, Sabah, Malaysia</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h3>Send Us a Message</h3>
                    <form action="send_contact_form.php" method="POST" class="contact-form">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Your Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>