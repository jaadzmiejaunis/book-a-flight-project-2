<?php
session_start(); // Start the session - MUST be the very first thing in the file

// --- Admin Authentication Check ---
if (!isset($_SESSION['book_id']) || $_SESSION['username'] !== 'Admin') {
    header('Location: login_page.php'); // Redirect to your login page
    exit();
}

// Admin user is logged in, no need to fetch profile data for header display
$loggedIn = true; // We know they are logged in if they passed the check


// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed on admin flight list page: " . mysqli_connect_error());
    die("An error occurred connecting to the database.");
}
// --- End Database Connection ---


// --- Fetch Flight Data with Sorting ---
$flights = []; // Initialize an empty array to store flight data
$error_message = ''; // For database query errors on this page

// Get sorting parameters from GET request
$allowed_sort_columns = ['book_id', 'book_origin_state', 'book_destination_state', 'book_departure', 'book_return', 'book_price', 'book_airlines', 'book_class']; // Columns allowed for sorting
$sort_by = $_GET['sort_by'] ?? 'book_departure'; // Default sort column
$order = strtoupper($_GET['order'] ?? 'ASC'); // Default sort order

// Validate sort_by column
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'book_departure'; // Revert to default if invalid column is provided
}

// Validate order
if ($order !== 'ASC' && $order !== 'DESC') {
    $order = 'ASC'; // Revert to default if invalid order is provided
}

// Define your SQL query to select all flight information, including book_id
// The sorting is still done by the numerical book_id in the database
$sql = "SELECT book_id, book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price FROM BookFlight ORDER BY " . mysqli_real_escape_string($connection, $sort_by) . " " . mysqli_real_escape_string($connection, $order);


// Execute the query
$result = mysqli_query($connection, $sql);

// Check if the query was successful
if ($result) {
    // Fetch rows from the result set
    while ($row = mysqli_fetch_assoc($result)) {
        $flights[] = $row; // Add each flight row to the $flights array
    }

    // Free the result set
    mysqli_free_result($result);
} else {
    // Query failed
    error_log("Database query error on admin flight list: " . mysqli_error($connection)); // Log the specific MySQL error
    $error_message = "An error occurred fetching flights. Please try again.";
}

// Close database connection
mysqli_close($connection);

// --- Handle Success or Error Messages from Processing Scripts ---
// Check if there are messages stored in the session (e.g., from admin_update_flight.php or admin_delete_flight.php)
$process_message = $_SESSION['update_message'] ?? ''; // Using update_message as the session key
$message_type = $_SESSION['message_type'] ?? ''; // 'success', 'danger', 'warning', 'info'

// Clear the session messages after retrieving them so they don't persist on refresh
unset($_SESSION['update_message']);
unset($_SESSION['message_type']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Flight List - BookAFlight.com</title>
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

        .admin-container {
            margin: 30px auto;
            max-width: 1200px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            padding: 30px;
            color: #e0e0e0;
        }

        .admin-header {
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

        .filter-sort-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
             flex-wrap: wrap;
        }

         .filter-sort-section .form-inline label {
             margin-right: 10px;
             color: #e0e0e0;
         }
         .filter-sort-section .form-inline .form-control,
         .filter-sort-section .form-inline .btn {
             margin-right: 10px;
             margin-bottom: 10px;
         }
         .filter-sort-section .form-inline select.form-control,
         .filter-sort-section .form-inline input.form-control {
              background-color: #3a3e52;
              color: #fff;
              border: 1px solid #5a5a8a;
         }
          .filter-sort-section .form-inline select.form-control:focus,
          .filter-sort-section .form-inline input.form-control:focus {
              border-color: #ffb03a;
              box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
              outline: none;
          }

        .flight-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .flight-table th, .flight-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #5a5a8a;
            color: #ccc;
        }
        .flight-table th {
            background-color: #3a3e52;
            color: #fff;
            font-weight: bold;
        }
        .flight-table tbody tr:hover {
            background-color: #343a40;
        }

        .flight-table .btn {
            padding: 5px 10px;
            margin-right: 5px;
            font-size: 0.9rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
         .flight-table .btn:hover {
             box-shadow: 0 2px 4px rgba(0,0,0,0.2);
         }
         .flight-table .btn-success {
             background-color: #28a745;
             border-color: #28a745;
             color: white;
         }
         .flight-table .btn-success:hover {
             background-color: #218838;
             border-color: #1e7e34;
         }
         .flight-table .btn-danger {
             background-color: #dc3545;
             border-color: #dc3545;
             color: white;
         }
         .flight-table .btn-danger:hover {
             background-color: #c82333;
             border-color: #bd2130;
         }
          .filter-sort-section .btn-secondary {
              background-color: #6c757d;
              border-color: #6c757d;
              color: white;
              box-shadow: 0 1px 2px rgba(0,0,0,0.1);
          }
           .filter-sort-section .btn-secondary:hover {
               background-color: #5a6268;
               border-color: #545b62;
               box-shadow: 0 2px 4px rgba(0,0,0,0.2);
          }

        .no-flights {
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
        <div class="container"> <a href="homepage_staff.php" class="site-title">SierraFlight (Admin)</a> <div class="user-info">
                <?php if ($loggedIn): ?>
                     <span>Admin</span>
                     <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
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
        <div class="admin-container">
            <div class="admin-header">
                Flight List
            </div>

            <?php
            // Display success or error messages from update/delete operations
            if (isset($process_message) && !empty($process_message)) {
                // Determine Bootstrap alert class based on message type
                $alert_class = 'alert-success';
                if ($message_type === 'danger') {
                    $alert_class = 'alert-danger';
                } elseif ($message_type === 'warning') {
                     $alert_class = 'alert-warning';
                } elseif ($message_type === 'info') {
                     $alert_class = 'alert-info';
                }

                echo "<div class='alert " . htmlspecialchars($alert_class) . "' role='alert'>";
                echo htmlspecialchars($process_message);
                echo "</div>";
            }
            ?>


            <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="filter-sort-section">
                <form class="form-inline" action="admin_flight_list.php" method="get">
                     <label for="sort_by">Sort By:</label>
                     <select name="sort_by" id="sort_by" class="form-control">
                          <option value="book_id" <?php echo ($sort_by === 'book_id') ? 'selected' : ''; ?>>Flight ID</option>
                         <option value="book_departure" <?php echo ($sort_by === 'book_departure') ? 'selected' : ''; ?>>Departure Date</option>
                          <option value="book_return" <?php echo ($sort_by === 'book_return') ? 'selected' : ''; ?>>Return Date</option>
                         <option value="book_origin_state" <?php echo ($sort_by === 'book_origin_state') ? 'selected' : ''; ?>>Origin State</option>
                         <option value="book_destination_state" <?php echo ($sort_by === 'book_destination_state') ? 'selected' : ''; ?>>Destination State</option>
                         <option value="book_price" <?php echo ($sort_by === 'book_price') ? 'selected' : ''; ?>>Price</option>
                          <option value="book_airlines" <?php echo ($sort_by === 'book_airlines') ? 'selected' : ''; ?>>Airline</option>
                          <option value="book_class" <?php echo ($sort_by === 'book_class') ? 'selected' : ''; ?>>Class</option>
                     </select>

                     <select name="order" id="order" class="form-control">
                          <option value="ASC" <?php echo ($order === 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                          <option value="DESC" <?php echo ($order === 'DESC') ? 'selected' : ''; ?>>Descending</option>
                     </select>

                     <?php if (isset($_GET['from_location'])): ?>
                         <input type="hidden" name="from_location" value="<?php echo htmlspecialchars($_GET['from_location']); ?>">
                     <?php endif; ?>
                     <?php if (isset($_GET['to_location'])): ?>
                          <input type="hidden" name="to_location" value="<?php echo htmlspecialchars($_GET['to_location']); ?>">
                     <?php endif; ?>

                     <button type="submit" class="btn btn-secondary">Sort</button>
                 </form>
             </div>


            <?php if (!empty($flights)): ?>
                <table class="table flight-table">
                    <thead>
                        <tr>
                            <th>ID</th> <th>Origin</th>
                            <th>Destination</th>
                            <th>Departure</th>
                            <th>Return</th>
                            <th>Class</th>
                            <th>Airline</th>
                            <th>Price (RM)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flights as $flight): ?>
                            <tr>
                                 <td><?php echo 'FL' . sprintf('%03d', $flight['book_id']); ?></td> <td><?php echo htmlspecialchars($flight['book_origin_state'] . ', ' . $flight['book_origin_country']); ?></td>
                                <td><?php echo htmlspecialchars($flight['book_destination_state'] . ', ' . $flight['book_destination_country']); ?></td>
                                <td><?php echo htmlspecialchars($flight['book_departure']); ?></td>
                                <td><?php echo htmlspecialchars($flight['book_return']); ?></td>
                                <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $flight['book_class']))); ?></td>
                                <td><?php echo htmlspecialchars($flight['book_airlines']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($flight['book_price'], 2)); ?></td>
                                <td>
                                    <a href="admin_edit_flight.php?id=<?php echo htmlspecialchars($flight['book_id']); ?>" class="btn btn-success btn-sm">Update</a>

                                    <form action="admin_delete_flight.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this flight?');">
                                        <input type="hidden" name="flight_id" value="<?php echo htmlspecialchars($flight['book_id']); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-flights">No flights available in the database.</p>
            <?php endif; ?>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>