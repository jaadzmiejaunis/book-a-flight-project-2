<?php
session_start();

// --- Admin Authentication Check ---
$loggedIn = isset($_SESSION['book_id']);
$is_admin = isset($_SESSION['book_user_roles']) && $_SESSION['book_user_roles'] === 'Admin';

if (!$loggedIn || !$is_admin) {
    header('Location: login_page.php');
    exit();
}

// Get user data for the navbar
$username = htmlspecialchars($_SESSION['username'] ?? 'Admin');
$profilePictureUrl = htmlspecialchars($_SESSION['profile_picture_url'] ?? '/college_project/book-a-flight-project-2/image_website/default_profile.png');

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed: " . mysqli_connect_error());
    $db_error = "An error occurred connecting to the database. Please try again later.";
    $booking_errors = [];
    $booking_form_data = [];
} else {
    $booking_errors = isset($_SESSION['booking_errors']) ? $_SESSION['booking_errors'] : [];
    $booking_form_data = isset($_SESSION['booking_form_data']) ? $_SESSION['booking_form_data'] : [];
    unset($_SESSION['booking_errors']);
    unset($_SESSION['booking_form_data']);
}

// The dynamic dropdowns are no longer needed, but the variables are kept for form value population
$origins = [];
$destinations = [];

// Close the database connection if it was successful
if ($connection) {
    mysqli_close($connection);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Flight - BookAFlight.com</title>
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
            flex-direction: column;
            align-items: center;
        }
        .booking-form-container {
            width: 100%;
            max-width: 700px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            padding: 30px;
            margin-top: 20px;
        }
        .booking-form-container h2 {
            color: #ffb03a;
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .booking-form-container .form-control {
            background-color: #3a3e52;
            color: white;
            border: 1px solid #5a5a8a;
        }
        .booking-form-container .form-control:focus {
            background-color: #3a3e52;
            color: white;
            border-color: #ffb03a;
            box-shadow: none;
        }
        .booking-form-container .form-select {
            background-color: #3a3e52;
            color: white;
            border: 1px solid #5a5a8a;
        }
        .booking-form-container label {
            color: #e0e0e0;
        }
        .btn-primary {
            background-image: linear-gradient(to right, #ffb03a, #dd5b12, #3b2e8b);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 1.1rem;
            border-radius: 5px;
            transition: background-position 0.5s ease;
            background-size: 200% auto;
        }
        .btn-primary:hover {
            background-position: right center;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="index.php" class="site-title">SierraFlight (Admin)</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <span>Admin: <?php echo htmlspecialchars($username); ?></span>
                     <?php if ($profilePictureUrl === '/college_project/book-a-flight-project-2/image_website/default_profile.png'): ?>
                         <i class="fas fa-user-circle fa-lg profile-icon-nav ml-2"></i>
                     <?php else: ?>
                         <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav ml-2">
                     <?php endif; ?>
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
                    <li class="nav-item"> <a class="nav-link" href="homepage.php">Home</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="about.php">About</a> </li>
                    <li class="nav-item active"> <a class="nav-link" href="edit_book_flight.php">Add Flight <span class="sr-only">(current)</span></a> </li>
                    <li class="nav-item"> <a class="nav-link" href="admin_flight_list.php">Flight List</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="admin_booking_list.php">Booking List</a> </li>
                    <li class="nav-item"> <a class="nav-link" href="profile_page.php">Profile</a> </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="booking-form-container">
            <h2>Add New Flight</h2>
            <?php if (!empty($db_error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($db_error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($booking_errors)): ?>
                <div class="alert alert-danger" role="alert">
                    Please correct the following errors:
                    <ul>
                        <?php foreach ($booking_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="booking_process.php" method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="origin_state">ORIGIN STATE:</label>
                        <input type="text" class="form-control" id="origin_state" name="origin_state" required value="<?php echo htmlspecialchars($booking_form_data['origin_state'] ?? ''); ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="origin_country">ORIGIN COUNTRY:</label>
                        <input type="text" class="form-control" id="origin_country" name="origin_country" required value="<?php echo htmlspecialchars($booking_form_data['origin_country'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="destination_state">DESTINATION STATE:</label>
                        <input type="text" class="form-control" id="destination_state" name="destination_state" required value="<?php echo htmlspecialchars($booking_form_data['destination_state'] ?? ''); ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="destination_country">DESTINATION COUNTRY:</label>
                        <input type="text" class="form-control" id="destination_country" name="destination_country" required value="<?php echo htmlspecialchars($booking_form_data['destination_country'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="departure_date">DEPARTURE DATE:</label>
                        <input type="date" class="form-control" id="departure_date" name="departure_date" required value="<?php echo htmlspecialchars($booking_form_data['departure_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="return_date">RETURN DATE:</label>
                        <input type="date" class="form-control" id="return_date" name="return_date" value="<?php echo htmlspecialchars($booking_form_data['return_date'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                         <label for="class">CLASS:</label>
                         <select id="class" class="form-control" name="class" required>
                             <option value="" disabled selected>-- Select Class --</option>
                             <option value="economy" <?php echo (($booking_form_data['class'] ?? '') === 'economy') ? 'selected' : ''; ?>>Economy</option>
                             <option value="business" <?php echo (($booking_form_data['class'] ?? '') === 'business') ? 'selected' : ''; ?>>Business</option>
                             <option value="first" <?php echo (($booking_form_data['class'] ?? '') === 'first') ? 'selected' : ''; ?>>First</option>
                         </select>
                    </div>
                     <div class="form-group col-md-6">
                         <label for="airlines">AIRLINES:</label>
                         <select id="airlines" class="form-control" name="airlines" required>
                             <option value="" disabled selected>-- Select Airline --</option>
                             <option value="AirAsia" <?php echo (($booking_form_data['airlines'] ?? '') === 'AirAsia') ? 'selected' : ''; ?>>AirAsia</option>
                             <option value="MasWing" <?php echo (($booking_form_data['airlines'] ?? '') === 'MasWing') ? 'selected' : ''; ?>>MasWing</option>
                             <option value="Malaysia Airlines" <?php echo (($booking_form_data['airlines'] ?? '') === 'Malaysia Airlines') ? 'selected' : ''; ?>>Malaysia Airlines</option>
                         </select>
                     </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="pricing">PRICE (RM):</label>
                        <input type="number" class="form-control" id="pricing" name="pricing" step="0.01" required value="<?php echo htmlspecialchars($booking_form_data['pricing'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">ADD FLIGHT</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>