<?php
session_start(); // Start the session

// --- Admin Authentication Check ---
if (!isset($_SESSION['book_id']) || $_SESSION['username'] !== 'Admin') {
    header('Location: login_page.php');
    exit();
}

// Retrieve admin user data from the session for display in the navbar
$loggedIn = true;
// $username is still retrieved, but we won't display it next to "Admin"

// Default profile picture path for Admin (no longer strictly needed for display, but keeping the variable)
$defaultProfilePicture = 'path/to/default-admin-profile-picture.png'; // <<<--- UPDATE THIS PATH

// $profilePictureUrl is also not needed for display anymore, but keeping the variable
$profilePictureUrl = isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;


// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed: " . mysqli_connect_error());
    // Set an error message to display instead of dying
    $db_error = "An error occurred connecting to the database. Please try again later.";
    // Ensure we don't try to unset session data related to booking if DB failed
    $booking_errors = [];
    $booking_form_data = [];
} else {
    // --- Code to check for and display errors/old data from session (from booking_process.php if validation failed) ---
    $booking_errors = $_SESSION['booking_errors'] ?? [];
    $booking_form_data = $_SESSION['booking_form_data'] ?? [];

    // Clear the session variables after retrieving them
    unset($_SESSION['booking_errors']);
    unset($_SESSION['booking_form_data']);

    // Close database connection
    mysqli_close($connection);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Add Flight - BookAFlight.com</title>
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

        .add-flight-container {
            max-width: 700px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .add-flight-header {
             background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
             color: white;
             padding: 20px;
             text-align: center;
             font-size: 1.8rem;
             font-weight: bold;
        }

        .add-flight-form {
            padding: 30px;
             color: #e0e0e0;
        }

        .add-flight-form .form-control,
        .add-flight-form .form-select {
             background-color: #3a3e52;
             color: #fff;
             border: 1px solid #5a5a8a;
        }
         .add-flight-form .form-control::placeholder {
             color: #ccc;
         }
          .add-flight-form .form-control:focus,
          .add-flight-form .form-select:focus {
              background-color: #3a3e52;
              border-color: #ffb03a;
              color: white;
              box-shadow: none;
              outline: none;
          }

        .add-flight-form .form-group label {
            color: #e0e0e0;
            margin-bottom: .5rem;
        }

        .add-flight-form .btn-primary {
             background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
             color: white;
             border: none;
             padding: 10px 20px;
             font-size: 1rem;
             border-radius: 5px;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
             transition: background-position 0.5s ease;
             background-size: 200% auto;
             display: block;
             width: 100%;
             margin-top: 20px;
         }
          .add-flight-form .btn-primary:hover {
               background-position: right center;
               color: white;
          }
           .add-flight-form .btn-primary:focus {
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
    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="homepage_staff.php" class="site-title">SierraFlight (Admin)</a> <div class="user-info">
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
                     <li class="nav-item"> <a class="nav-link" href="homepage_staff.php">Home</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="about_staff.php">About</a> </li>
                     <li class="nav-item active"> <a class="nav-link" href="edit_book_flight.php">Add Flight <span class="sr-only">(current)</span></a>
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
        <div class="add-flight-container">
             <div class="add-flight-header">
                 ADD NEW FLIGHT
             </div>
            <div class="add-flight-form">

                 <?php
                     // Display database connection error
                     if (isset($db_error)) {
                         echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($db_error) . "</div>";
                     }
                     // Display booking processing errors if any (from booking_process.php redirect)
                     if (!empty($booking_errors)) {
                         echo "<div class='alert alert-danger' role='alert'>";
                         foreach ($booking_errors as $error) {
                             echo htmlspecialchars($error) . "<br>";
                         }
                         echo "</div>";
                     }
                 ?>

                <form action="booking_process.php" method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="from_state">FROM (State)</label>
                            <input type="text" class="form-control" id="from_state" name="from_state" placeholder="Enter origin (State)" required value="<?php echo htmlspecialchars($booking_form_data['from_state'] ?? ''); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="from_country">FROM (Country)</label>
                            <input type="text" class="form-control" id="from_country" name="from_country" placeholder="Enter origin (Country)" required value="<?php echo htmlspecialchars($booking_form_data['from_country'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="to_state">TO (State)</label>
                            <input type="text" class="form-control" id="to_state" name="to_state" placeholder="Enter destination (State)" required value="<?php echo htmlspecialchars($booking_form_data['to_state'] ?? ''); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="to_country">TO (Country)</label>
                            <input type="text" class="form-control" id="to_country" name="to_country" placeholder="Enter destination (Country)" required value="<?php echo htmlspecialchars($booking_form_data['to_country'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="departure_date">DEPARTURE DATE</label>
                            <input type="date" class="form-control" id="departure_date" name="departure_date" required value="<?php echo htmlspecialchars($booking_form_data['departure_date'] ?? ''); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="return_date">RETURN DATE</label>
                            <input type="date" class="form-control" id="return_date" name="return_date" value="<?php echo htmlspecialchars($booking_form_data['return_date'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="class">CLASS</label>
                            <select id="class" name="class" class="form-control">
                                <option value="economy" <?php echo (($booking_form_data['class'] ?? '') === 'economy') ? 'selected' : ''; ?>>Economy</option>
                                <option value="premium_economy" <?php echo (($booking_form_data['class'] ?? '') === 'premium_economy') ? 'selected' : ''; ?>>Premium Economy</option>
                                <option value="business" <?php echo (($booking_form_data['class'] ?? '') === 'business') ? 'selected' : ''; ?>>Business</option>
                                <option value="first" <?php echo (($booking_form_data['class'] ?? '') === 'first') ? 'selected' : ''; ?>>First Class</option>
                            </select>
                        </div>
                         <div class="form-group col-md-6">
                             <label for="airlines">AIRLINES</label>
                             <select id="airlines" name="airlines" class="form-control">
                                 <option value="" <?php echo (($booking_form_data['airlines'] ?? '') === '') ? 'selected' : ''; ?>>Select Airline</option>
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
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>