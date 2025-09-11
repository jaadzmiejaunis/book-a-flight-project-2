<?php
session_start(); // Start the session

// --- Admin Authentication Check ---
// Assuming 'Admin' is the username for the administrator
if (!isset($_SESSION['book_id']) || $_SESSION['username'] !== 'Admin') {
    header('Location: login_page.php'); // Redirect to your login page
    exit();
}

// Retrieve admin user data from the session for display in the navbar
$loggedIn = true;
$username = htmlspecialchars($_SESSION['username']);

// Default profile picture path for Admin (replace with your actual path)
$defaultProfilePicture = 'path/to/default-admin-profile-picture.png'; // <<<--- UPDATE THIS PATH

$profilePictureUrl = isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;


// --- Get and Validate Flight ID from URL ---
$flight_id = $_GET['id'] ?? null; // Get flight ID from URL parameter

// Initialize variables for flight data and error messages
$flight_data = null;
$error_message = '';

// Check if flight_id is provided and is a valid integer
if (!$flight_id || !filter_var($flight_id, FILTER_VALIDATE_INT)) {
    $error_message = "Invalid flight ID provided.";
    // Optionally redirect back to the flight list page
    // header('Location: admin_flight_list.php');
    // exit();
} else {
    // --- Database Connection ---
    include 'connection.php';

    if (!$connection) {
        error_log("Database connection failed on admin edit flight page: " . mysqli_connect_error());
        $error_message = "An error occurred connecting to the database.";
    } else {
        // --- Fetch Flight Details ---
        $sql = "SELECT book_id, book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price FROM BookFlight WHERE book_id = ?";
        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $flight_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $flight_data = $row; // Flight data fetched successfully
            } else {
                $error_message = "Flight with ID " . htmlspecialchars($flight_id) . " not found.";
            }

            mysqli_free_result($result);
            mysqli_stmt_close($stmt);
        } else {
            error_log("Database prepare error for fetching flight on admin edit page: " . mysqli_error($connection));
            $error_message = "An internal error occurred fetching flight details.";
        }

        // Close database connection
        mysqli_close($connection);
    }
}

// --- Handle Success or Error Messages from Update Process ---
// Check if there are messages stored in the session (e.g., from admin_update_flight.php)
$update_message = $_SESSION['update_message'] ?? '';
$message_type = $_SESSION['message_type'] ?? ''; // 'success' or 'danger'

// Clear the session messages after retrieving them
unset($_SESSION['update_message']);
unset($_SESSION['message_type']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Edit Flight - BookAFlight.com</title>
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

        .page-content {
             padding: 20px;
             flex-grow: 1;
             display: flex;
             align-items: center;
             justify-content: center;
        }

        .edit-flight-container {
            max-width: 700px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .edit-flight-header {
            background-image: linear-gradient(to right, #3b2e8b, #ffb03a);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .edit-flight-form {
            padding: 30px;
             color: #e0e0e0;
        }

        .edit-flight-form .form-control,
        .edit-flight-form .form-select {
            background-color: #3a3e52;
            color: #fff;
            border: 1px solid #5a5a8a;
        }
         .edit-flight-form .form-control::placeholder {
              color: #ccc;
         }
          .edit-flight-form .form-control:focus,
          .edit-flight-form .form-select:focus {
              background-color: #3a3e52;
              border-color: #ffb03a;
              color: white;
              box-shadow: none;
              outline: none;
          }

        .edit-flight-form .form-group label {
            color: #e0e0e0;
            margin-bottom: .5rem;
        }

        .edit-flight-form .btn-success {
             background-image: linear-gradient(to right, #ffb03a, #dd5b12, #3b2e8b);
             color: white;
             border: none;
             padding: 10px 20px;
             font-size: 1rem;
             border-radius: 5px;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
             transition: background-position 0.5s ease;
             background-size: 200% auto;
        }
         .edit-flight-form .btn-success:hover {
               background-position: right center;
               color: white;
          }
           .edit-flight-form .btn-success:focus {
             box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
             outline: none;
         }

        .edit-flight-form .btn-secondary {
             background-color: #6c757d;
             color: white;
             border: none;
             padding: 10px 20px;
             font-size: 1rem;
             border-radius: 5px;
             text-align: center;
             display: inline-block;
             margin-top: 20px;
             margin-right: 10px;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
         .edit-flight-form .btn-secondary:hover {
             background-color: #5a6268;
             color: white;
             text-decoration: none;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
         }
          .edit-flight-form .btn-secondary:focus {
             box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.5);
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
        <div class="container"> <a href="index.php" class="site-title">BookAFlight.com</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <span>Admin: <?php echo htmlspecialchars($username); ?></span>
                     <?php if ($profilePictureUrl === $defaultProfilePicture): ?>
                         <i class="fas fa-user-circle fa-lg profile-icon-nav ml-2"></i> <?php else: ?>
                         <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav ml-2"> <?php endif; ?>
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
        <div class="edit-flight-container">
            <?php if (isset($update_message) && !empty($update_message)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>" role="alert">
                    <?php echo htmlspecialchars($update_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($flight_data): ?>
                <div class="edit-flight-header">
                    EDIT FLIGHT (ID: <?php echo htmlspecialchars($flight_data['book_id']); ?>)
                </div>
                <div class="edit-flight-form">
                    <form action="admin_update_flight.php" method="post">
                        <input type="hidden" name="flight_id" value="<?php echo htmlspecialchars($flight_data['book_id']); ?>">

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="origin_state">FROM (State)</label>
                                <input type="text" class="form-control" id="origin_state" name="origin_state" placeholder="Enter origin (State)" required value="<?php echo htmlspecialchars($flight_data['book_origin_state']); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="origin_country">FROM (Country)</label>
                                <input type="text" class="form-control" id="origin_country" name="origin_country" placeholder="Enter origin (Country)" required value="<?php echo htmlspecialchars($flight_data['book_origin_country']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="destination_state">TO (State)</label>
                                <input type="text" class="form-control" id="destination_state" name="destination_state" placeholder="Enter destination (State)" required value="<?php echo htmlspecialchars($flight_data['book_destination_state']); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="destination_country">TO (Country)</label>
                                <input type="text" class="form-control" id="destination_country" name="destination_country" placeholder="Enter destination (Country)" required value="<?php echo htmlspecialchars($flight_data['book_destination_country']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="departure_date">DEPARTURE DATE</label>
                                <input type="date" class="form-control" id="departure_date" name="departure_date" required value="<?php echo htmlspecialchars($flight_data['book_departure']); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="return_date">RETURN DATE</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" required value="<?php echo htmlspecialchars($flight_data['book_return']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="class">CLASS</label>
                                <select id="class" name="class" class="form-control">
                                    <option value="economy" <?php echo ($flight_data['book_class'] === 'economy') ? 'selected' : ''; ?>>Economy</option>
                                    <option value="premium_economy" <?php echo ($flight_data['book_class'] === 'premium_economy') ? 'selected' : ''; ?>>Premium Economy</option>
                                    <option value="business" <?php echo ($flight_data['book_class'] === 'business') ? 'selected' : ''; ?>>Business</option>
                                    <option value="first" <?php echo ($flight_data['book_class'] === 'first') ? 'selected' : ''; ?>>First Class</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="airlines">AIRLINES</label>
                                <select id="airlines" name="airlines" class="form-control">
                                    <option value="" <?php echo ($flight_data['book_airlines'] === '') ? 'selected' : ''; ?>>Select Airline</option>
                                    <option value="airline1" <?php echo ($flight_data['book_airlines'] === 'airline1') ? 'selected' : ''; ?>>Airline 1</option>
                                    <option value="airline2" <?php echo ($flight_data['book_airlines'] === 'airline2') ? 'selected' : ''; ?>>Airline 2</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="price">PRICE (RM):</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" required value="<?php echo htmlspecialchars($flight_data['book_price']); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success mt-4">Update Flight</button> <a href="admin_flight_list.php" class="btn btn-secondary mt-4">Cancel</a> </form>
                </div>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <br><a href="admin_flight_list.php" class="btn btn-primary mt-3">Back to Flight List</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>