<?php
session_start(); // Start the session

// --- Database Connection ---
// In a production environment, store credentials in a configuration file outside the web root.
include 'connection.php';

// Check connection
if (!$connection) {
    // Log the error to the server logs
    error_log("Database connection failed: " . mysqli_connect_error()); // This logs the specific error! CHECK YOUR SERVER LOGS!
    // Display a generic error message to the user
    die("An error occurred connecting to the database to fetch flights. Please try again later.");
}
// --- End Database Connection ---


// Retrieve user data from the session for display in the navbar (assuming consistent session handling)
$loggedIn = isset($_SESSION['book_id']); // Check logged-in status
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; // Get username from session

// Default profile picture path (replace with your actual path)
$defaultProfilePicture = 'path/to/default-profile-picture.png'; // <<<--- UPDATE THIS PATH

// Check if a custom profile picture URL is set and exists, otherwise use default
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

// Optional: Check if the profile picture file exists to avoid broken images
// if (!file_exists($profilePictureUrl) && $profilePictureUrl !== $defaultProfilePicture) {
//     $profilePictureUrl = $defaultProfilePicture;
// }


// --- Fetch Flight Data with Search/Filtering ---
$flights = []; // Initialize an empty array to store flight data
$error_message = ''; // Initialize error message

// Get search terms from GET request
$search_from = trim($_GET['from_location'] ?? ''); // 'from_location' is the name of the input field
$search_to = trim($_GET['to_location'] ?? '');  // 'to_location' is the name of the input field

// Build the SQL query and parameters dynamically based on search terms
$sql = "SELECT book_id, book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price FROM BookFlight"; // Start with the base query
$where_clauses = []; // Array to hold WHERE conditions
$param_types = ""; // String to hold parameter types for prepared statement
$param_values = []; // Array to hold parameter values for prepared statement

// Add WHERE clauses if search terms are provided
if (!empty($search_from)) {
    // Search in origin state OR country for the 'From' term
    $where_clauses[] = "(book_origin_state LIKE ? OR book_origin_country LIKE ?)";
    $param_types .= "ss";
    $param_values[] = '%' . $search_from . '%'; // Add wildcards for partial matching
    $param_values[] = '%' . $search_from . '%';
}

if (!empty($search_to)) {
    // Search in destination state OR country for the 'To' term
    $where_clauses[] = "(book_destination_state LIKE ? OR book_destination_country LIKE ?)";
    $param_types .= "ss";
    $param_values[] = '%' . $search_to . '%'; // Add wildcards for partial matching
    $param_values[] = '%' . $search_to . '%';
}

// Combine WHERE clauses if any exist
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses); // Use AND to combine multiple conditions
}

// Add ORDER BY clause
$sql .= " ORDER BY book_departure ASC"; // Example: order by departure date

// Prepare the statement
$stmt = mysqli_prepare($connection, $sql);

if ($stmt) {
    // Bind parameters if there are any search terms
    if (!empty($param_values)) {
        // mysqli_stmt_bind_param requires parameters to be passed by reference.
        // Use call_user_func_array to bind the variable number of parameters.
        mysqli_stmt_bind_param($stmt, $param_types, ...$param_values);
        // Note: Using '...' (splat operator) requires PHP 5.6 or later.
        // For older PHP, use call_user_func_array: call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $param_types], $param_values));
    }

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        // Get the result
        $result = mysqli_stmt_get_result($stmt);

        // Fetch rows from the result set
        while ($row = mysqli_fetch_assoc($result)) {
            $flights[] = $row; // Add each flight row to the $flights array
        }

        // Free the result set
        mysqli_free_result($result);
    } else {
        // Query execution failed
        error_log("Database query execution error: " . mysqli_error($connection));
        $error_message = "An error occurred fetching flights. Please try again.";
    }

    // Close the prepared statement
    mysqli_stmt_close($stmt);
} else {
    // Prepared statement failed
    error_log("Database prepare error: " . mysqli_error($connection) . " SQL: " . $sql);
    $error_message = "An internal error occurred preparing to fetch flights.";
}


// Close database connection
mysqli_close($connection);

// Determine the current page for active class (optional, removed as per user request)
// $currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Flight</title>
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

        .page-content {
             padding: 20px;
             flex-grow: 1;
        }

        .search-container {
            margin: 30px auto;
            max-width: 800px;
            background-color: #282b3c;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .search-container label {
             color: #e0e0e0;
        }

         .search-form .form-control {
             background-color: #3a3e52;
             border-color: #3a3e52;
             color: #e0e0e0;
         }
         .search-form .form-control::placeholder {
             color: #a0a0a0;
         }
          .search-form .form-control:focus {
              background-color: #3a3e52;
              border-color: #ffb03a;
              color: white;
              box-shadow: none;
          }

         .search-form .btn-primary {
             background-color: #ffb03a;
             border-color: #ffb03a;
             color: white;
             background-image: none;
             background-size: auto;
             transition: none;
             border-radius: .25rem;
             font-size: 1rem;
             padding: .375rem .75rem;
             box-shadow: none;
         }
          .search-form .btn-primary:hover {
              background-color: #dd5b12;
              border-color: #dd5b12;
              background-position: initial;
          }
           .search-form .btn-primary:focus {
                box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
           }

        .flight-card {
            background-color: #2c2c54;
            color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 15px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: transform 0.2s ease-in-out;
             border: 1px solid #3a3e52;
        }

        .flight-card:hover {
            transform: translateY(-5px);
             border-color: #6a82fb;
        }

        .flight-card h5 {
            color: #6a82fb;
            margin-bottom: 5px;
        }

        .flight-card p {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

         .flight-card .btn-primary {
             background-color: #007bff;
             border-color: #007bff;
             color: white;
             background-image: none;
             background-size: auto;
             transition: none;
             border-radius: .25rem;
             font-size: 0.9rem;
             padding: .25rem .5rem;
             box-shadow: none;
         }
          .flight-card .btn-primary:hover {
              background-color: #0056b3;
              border-color: #0056b3;
          }

        .flight-list-container {
            margin-top: 20px;
            padding: 0 20px;
        }

         .container > p {
             color: #e0e0e0;
         }

    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container"> <a href="index.php" class="site-title">SierraFlight</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <a href="profile_page.php">
                         Profile
                         <?php if ($profilePictureUrl === $defaultProfilePicture): ?>
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
                        <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
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

    <div class="container page-content">
        <h2 class="text-center mb-4" style="color: white;">Search Flights</h2>

        <div class="search-container">
            <form action="book_a_flight.php" method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label for="from_location">From:</label>
                        <input type="text" class="form-control" id="from_location" name="from_location" placeholder="Origin (State or Country)" value="<?php echo htmlspecialchars($search_from); ?>">
                    </div>
                    <div class="form-group col-md-5">
                         <label for="to_location">To:</label>
                         <input type="text" class="form-control" id="to_location" name="to_location" placeholder="Destination (State or Country)" value="<?php echo htmlspecialchars($search_to); ?>">
                    </div>
                     <div class="col-md-2 d-flex align-items-end">
                         <button class="btn btn-primary btn-block" type="submit">
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                 <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                             </svg>
                             Search
                         </button>
                     </div>
                </div>
            </form>
            </div>

        <div class="flight-list-container">
            <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php elseif (!empty($flights)): ?>
                <div class="row">
                    <?php foreach ($flights as $flight): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="flight-card" data-flight-id="<?php echo htmlspecialchars($flight['book_id']); ?>">
                                <h5><?php echo htmlspecialchars($flight['book_origin_state'] . ', ' . $flight['book_origin_country']); ?> to <?php echo htmlspecialchars($flight['book_destination_state'] . ', ' . $flight['book_destination_country']); ?></h5>
                                <p>Departure: <?php echo htmlspecialchars($flight['book_departure']); ?></p>
                                <p>Return: <?php echo htmlspecialchars($flight['book_return']); ?></p>
                                <p>Class: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $flight['book_class']))); ?></p>
                                <p>Airline: <?php echo htmlspecialchars($flight['book_airlines']); ?></p>
                                <p>Price: RM <?php echo htmlspecialchars(number_format($flight['book_price'], 2)); ?></p>
                                <a href="book_a_flight_detail.php?flight_id=<?php echo htmlspecialchars($flight['book_id']); ?>" class="btn btn-primary btn-sm mt-2">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center">No flights found matching your criteria.</p>
            <?php endif; ?>
        </div>

    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>