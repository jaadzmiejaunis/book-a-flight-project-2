<?php
session_start(); // Start the session

// Check if the user is logged in. If not, redirect to the login page.
// Assuming 'book_id' is used for logged-in user ID in session based on login_page.php
if (!isset($_SESSION['book_id'])) {
    header('Location: login_page.php'); // Redirect to your login page
    exit();
}

// Retrieve user data from the session for display in the navbar
$loggedIn = isset($_SESSION['book_id']);
$username = $loggedIn && isset($_SESSION['book_user_roles']) ? htmlspecialchars($_SESSION['book_user_roles']) : 'Guest';

// Default profile picture path (replace with your actual path if different from index.php)
$defaultProfilePicture = '/college_project/book-a-flight-project-2/image_website/default_profile.png'; // <<<--- UPDATE THIS PATH if needed

// Check if a custom profile picture URL is set and exists, otherwise use default
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed: " . mysqli_connect_error());
    // Instead of dying immediately, set an error message to display
    $error_message = "An error occurred connecting to the database.";
    $flight = null; // Ensure flight is null if DB connection fails
} else {
    // --- Fetch Specific Flight Data ---
    $flight_id = $_GET['flight_id'] ?? null; // Get flight_id from URL
    $flight = null; // Initialize flight variable

    if ($flight_id) {
        // Validate flight_id is a number
        if (!filter_var($flight_id, FILTER_VALIDATE_INT)) {
            // Invalid flight ID, set an error message instead of redirecting immediately
            $error_message = "Invalid flight ID provided.";
        } else {
            // Prepare SQL query to fetch specific flight details
            $sql = "SELECT book_id, book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price FROM BookFlight WHERE book_id = ?";
            $stmt = mysqli_prepare($connection, $sql);

            if ($stmt) {
                // Bind the flight ID parameter
                mysqli_stmt_bind_param($stmt, "i", $flight_id); // 'i' for integer

                // Execute the statement
                mysqli_stmt_execute($stmt);

                // Get the result
                $result = mysqli_stmt_get_result($stmt);

                // Fetch the flight details
                if ($row = mysqli_fetch_assoc($result)) {
                    $flight = $row; // Store flight details
                } else {
                     // Flight not found
                     $error_message = "Flight details not found.";
                }

                // Close statement and result set
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);
            } else {
                error_log("Database prepare error for fetching flight detail: " . mysqli_error($connection));
                // Handle error - set a message
                $error_message = "Could not retrieve flight details.";
            }
        }
    } else {
        // No flight_id provided in URL, set an error message
        $error_message = "No flight ID specified.";
    }

    // Close database connection after fetching data (or if there was an error)
    mysqli_close($connection);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Details</title>
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

        .navbar-nav .nav-item.active .nav-link {
            font-weight: bold;
            background-color: rgba(255, 255, 255, 0.15);
             text-decoration: none;
        }

        .main-content-container {
             flex-grow: 1; 
             padding: 20px; 
        }

        .detail-container {
            max-width: 800px;
            margin: 20px auto; 
            background-color: #2c2c54; 
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            padding: 30px;
        }

        .flight-overview {
            background-color: #3e3e70;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; 
        }

        .flight-overview .route-section {
            flex-grow: 1; 
            margin-right: 20px;
        }

        .flight-overview .route-section h5 {
            color: #ffb03a;
            margin-bottom: 5px;
             display: inline-block; 
             margin-right: 5px; 
        }
         .flight-overview .route-section .h5 {
             color: #e0e0e0; 
         }

        .flight-overview .price-section {
            background: linear-gradient(to right, #8e24aa, #d81b60);
            padding: 15px;
            border-radius: 5px;
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            text-align: center;
            min-width: 150px;
             flex-shrink: 0; 
             margin-left: auto; 
        }

         .flight-details {
             margin-bottom: 20px;
             padding-top: 15px; 
             border-top: 1px solid #5a5a8a; 
         }

        .flight-details p {
            margin-bottom: 10px; 
            font-size: 1.1rem;
        }
         .flight-details strong {
             color: #ffb03a; 
         }

        .payment-section {
            margin-top: 20px;
            padding-top: 15px; 
            border-top: 1px solid #5a5a8a; 
        }

        .payment-section label {
            color: #ccc;
            margin-bottom: 10px;
            display: block; 
            font-weight: bold;
        }

        .payment-section select {
             background-color: #3e3e70;
             color: #fff;
             border: 1px solid #5a5a8a;
             width: 100%;
             padding: .375rem .75rem;
             border-radius: .25rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23ffffff%22%20d%3D%22M287%2C114.5L155.7%2C27.9c-4.1-3.3-9.7-3.3-13.8%2C0L6%2C114.5C2%2C117.8-0.3%2C122.3%2C0%2C127c0.3%2C4.7%2C2.6%2C9.2%2C6.7%2C12.6l131.2%2C86.1c4.1%2C3.3%2C9.7%2C3.3%2C13.8%2C0l131.2-86.1c4.1-3.3%2C6.4-7.8%2C6.7-12.6C292.7%2C122.3%2C290.4%2C117.8%2C287%2C114.5z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right .75rem center;
            background-size: 8px 10px;
        }

        .payment-section select:focus {
            border-color: #ffb03a; 
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.25); 
        }

        .book-now-btn {
            background-image: linear-gradient(to right, #ffb03a, #dd5b12, #3b2e8b);
            border: none;
            color: white;
            font-size: 1.2rem;
            padding: 10px;
            margin-top: 30px;
            width: 100%;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
             transition: background-position 0.5s ease;
             background-size: 200% auto;
        }
         .book-now-btn:hover {
             background-position: right center;
             color: white;
             text-decoration: none;
         }
         .book-now-btn:focus {
             box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
         }

        .alert {
            margin-top: 20px;
        }


    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="index.php" class="site-title">SierraFlight</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <a href="profile_page.php">
                         Profile
                         <?php if ($profilePictureUrl === $defaultProfilePicture): ?>
                             <i class="fas fa-user-circle fa-lg profile-icon-nav"></i>
                         <?php else: ?>
                             <img src="<?php echo htmlspecialchars($profilePictureUrl); ?>" alt="Profile Picture" class="profile-picture-nav">
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item active"> <?php if ($loggedIn): ?>
                            <a class="nav-link" href="book_a_flight.php">Book a Flight <span class="sr-only">(current)</span></a>
                        <?php else: ?>
                            <a class="nav-link" href="login_page.php">Book a Flight <span class="sr-only">(current)</span></a>
                        <?php endif; ?>
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

    <div class="main-content-container container">
        <div class="detail-container">
            <?php if (isset($error_message)): // Display error message if any ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php elseif ($flight): // Display flight details if found and no error ?>
                <div class="flight-overview">
                    <div class="route-section">
                        <h5><?php echo htmlspecialchars($flight['book_origin_state'] . ', ' . $flight['book_origin_country']); ?></h5>
                        <span class="h5">&gt;</span> <h5><?php echo htmlspecialchars($flight['book_destination_state'] . ', ' . $flight['book_destination_country']); ?></h5>
                    </div>
                    <div class="price-section">
                        RM <?php echo htmlspecialchars(number_format($flight['book_price'], 2)); ?>
                    </div>
                </div>

                <div class="flight-details">
                    <p><strong>Departure:</strong> <?php echo htmlspecialchars($flight['book_departure']); ?></p>
                    <p><strong>Return:</strong> <?php echo htmlspecialchars($flight['book_return']); ?></p>
                    <p><strong>Class:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $flight['book_class']))); ?></p>
                    <p><strong>Airline:</strong> <?php echo htmlspecialchars($flight['book_airlines']); ?></p>
                </div>

                <div class="payment-section">
                    <form action="process_booking_history.php" method="post">
                        <input type="hidden" name="flight_id" value="<?php echo htmlspecialchars($flight['book_id']); ?>">

                        <div class="form-group">
                            <label for="payment_method">Select Payment Method:</label>
                            <select id="payment_method" name="payment_method" class="form-control" required>
                                <option value="">-- Select Method --</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="digital_wallet">Digital Wallet</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary book-now-btn">BOOK NOW</button>
                    </form>
                </div>

            <?php else: // Fallback if flight was not found and no specific error message ?>
                 <div class="alert alert-warning" role="alert">
                     Flight details not found. Please go back and select a flight.
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