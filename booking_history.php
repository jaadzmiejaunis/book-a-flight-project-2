<?php
session_start(); // Start the session

// Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['book_id'])) {
    header('Location: login_page.php'); // Redirect to your login page
    exit();
}

// Retrieve user data from the session for display in the navbar
$loggedIn = isset($_SESSION['book_id']);
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

// Default profile picture path
$defaultProfilePicture = '/college_project/book-a-flight-project-2/image_website/default_profile.png';

$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

// Get the logged-in user's ID from the session
$user_id = $_SESSION['book_id'];

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed on booking history page: " . mysqli_connect_error());
    die("An error occurred connecting to the database to fetch booking history.");
}
// --- End Database Connection ---

// --- Fetch Booking History for the Logged-in User ---
$booking_history = []; // Initialize an empty array to store booking history
$error_message = '';

// Corrected SQL to select booking history for the current user by joining tables
$sql = "SELECT
            s.book_id,
            s.book_class,
            s.book_airlines,
            s.booking_status,
            s.booking_date,
            pl.book_origin_state,
            pl.book_origin_country,
            pl.book_destination_state,
            pl.book_destination_country,
            pl.book_departure,
            pl.book_return,
            p.book_total_price
        FROM
            BookFlightStatus s
        JOIN
            BookFlightPlace pl ON s.book_id = pl.book_id AND s.user_id = pl.user_id
        JOIN
            BookFlightPrice p ON s.book_id = p.book_id AND s.user_id = p.user_id
        WHERE
            s.user_id = ?
        ORDER BY s.booking_date DESC";

// Prepare the statement
$stmt = mysqli_prepare($connection, $sql);

if ($stmt) {
    // Bind the user ID parameter
    mysqli_stmt_bind_param($stmt, "i", $user_id); // 'i' for integer user_id

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        // Get the result
        $result = mysqli_stmt_get_result($stmt);

        // Fetch rows from the result set
        while ($row = mysqli_fetch_assoc($result)) {
            $booking_history[] = $row; // Add each booking row to the array
        }

        // Free the result set
        mysqli_free_result($result);
    } else {
        // Query execution failed
        error_log("Database query execution error on booking history page: " . mysqli_error($connection));
        $error_message = "An error occurred fetching your booking history. Please try again.";
    }

    // Close the prepared statement
    mysqli_stmt_close($stmt);
} else {
    // Prepared statement failed
    error_log("Database prepare error on booking history page: " . mysqli_error($connection));
    $error_message = "An internal error occurred preparing to fetch booking history.";
}

// Close database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Booking History - SierraFlight</title>
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
        }

        .booking-history-container {
            margin: 30px auto;
            max-width: 800px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            padding: 30px;
             color: #e0e0e0;
        }

        .booking-history-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: white;
             font-size: 2rem;
        }

        .booking-card {
            background-color: #2c2c54;
            color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 15px;
            margin-bottom: 20px;
             border: 1px solid #3a3e52;
        }

        .booking-card h5 {
             color: #6a82fb;
             margin-bottom: 5px;
        }
        .booking-card p {
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #ccc;
        }

         .booking-status {
             font-weight: bold;
         }
          .status-Pending { color: #ffc107; }
          .status-Booked { color: #28a745; }
          .status-Cancelled { color: #dc3545; }

        .booking-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #ffb03a;
        }

        .no-bookings {
            text-align: center;
            color: #ccc;
            margin-top: 20px;
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

        .btn-info {
            background-color: #ffb03a;
            border-color: #ffb03a;
            color: #1a1a2e;
            font-weight: bold;
        }

        .btn-info:hover {
            background-color: #e09e2a;
            border-color: #e09e2a;
        }

    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="index.php" class="site-title">
                <img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo">
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
        <div class="container"> <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                    <li class="nav-item">
                         <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                    </li>
                     <?php if ($loggedIn): ?>
                     <li class="nav-item">
                         <a class="nav-link" href="profile_page.php">Profile</a>
                     </li>
                      <li class="nav-item active">
                         <a class="nav-link" href="booking_history.php">Book History <span class="sr-only">(current)</span></a>
                     </li>
                     <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="booking-history-container">
            <h2 class="text-center mb-4">My Booking History</h2>

            <?php if (isset($error_message) && !empty($error_message)): ?>
                 <div class="alert alert-danger" role="alert">
                     <?php echo htmlspecialchars($error_message); ?>
                 </div>
            <?php elseif (!empty($booking_history)): ?>
                <?php foreach ($booking_history as $booking): ?>
                    <div class="booking-card">
                        <h5>Booking ID: <?php echo htmlspecialchars($booking['book_id']); ?></h5>
                        <p><strong>Route:</strong> <?php echo htmlspecialchars($booking['book_origin_state'] . ', ' . $booking['book_origin_country']); ?> to <?php echo htmlspecialchars($booking['book_destination_state'] . ', ' . $booking['book_destination_country']); ?></p>
                        <p><strong>Dates:</strong> Departure: <?php echo htmlspecialchars($booking['book_departure']); ?> | Return: <?php echo htmlspecialchars($booking['book_return']); ?></p>
                        <p><strong>Details:</strong> Class: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['book_class']))); ?> | Airline: <?php echo htmlspecialchars($booking['book_airlines']); ?></p>
                         <div class="d-flex justify-content-between align-items-center mt-3">
                              <?php
                                  $status_class = 'status-' . str_replace(' ', '', $booking['booking_status']); // e.g., 'status-Pending', 'status-Booked', 'status-Cancelled'
                              ?>
                            <span class="booking-status <?php echo htmlspecialchars($status_class); ?>">
                                Status: <?php echo htmlspecialchars($booking['booking_status']); ?>
                            </span>
                            <div class="booking-price">
                                Price: RM <?php echo htmlspecialchars(number_format($booking['book_total_price'], 2)); ?>
                            </div>
                            <?php if ($booking['booking_status'] === 'Booked'): ?>
                                <a href="reciept_page.php?bookId=<?php echo htmlspecialchars($booking['book_id']); ?>" class="btn btn-info btn-sm ml-2">View Receipt</a>
                            <?php endif; ?>
                         </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-bookings">You have no booking history yet.</p>
            <?php endif; ?>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>