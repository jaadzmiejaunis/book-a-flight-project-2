<?php
session_start(); // Start the session - MUST be the very first thing in the file

// --- Database Connection ---
include 'connection.php';

// Check for a logged-in user and their role
$loggedIn = isset($_SESSION['book_id']);
$user_role = 'Guest';
$username = 'Guest';
$profilePictureUrl = '/college_project/book-a-flight-project-2/image_website/default_profile.png';
$is_admin = false;

// If a user is logged in, get their details from the database
if ($loggedIn) {
    $user_id = $_SESSION['book_id'];

    // Fetch user details from the database using a prepared statement for security
    $sql = "SELECT book_username, book_user_roles, book_profile FROM BookUser WHERE book_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        $username = htmlspecialchars($user['book_username']);
        $user_role = $user['book_user_roles'];
        if (!empty($user['book_profile'])) {
            $profilePictureUrl = htmlspecialchars($user['book_profile']);
        }
        // Set the user role in the session for other pages to access
        $_SESSION['book_user_roles'] = $user_role;
        $_SESSION['username'] = $username;
    }
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    // Check if the user is an Admin
    if ($user_role === 'Admin') {
        $is_admin = true;
    } else {
        // If not an admin, redirect them away from this page
        header('Location: login_page.php');
        exit();
    }
} else {
    // If not logged in at all, redirect to login page
    header('Location: login_page.php');
    exit();
}

// --- End Database Connection and Authentication ---


// --- Fetch Flight Data with Sorting ---
$flights = []; // Initialize an empty array to store flight data
$error_message = ''; // For database query errors on this page

// Get sorting parameters from GET request
$allowed_sort_columns = ['book_id', 'book_origin_state', 'book_destination_state', 'book_departure', 'book_return', 'book_price', 'book_airlines', 'book_class']; // Columns allowed for sorting
$sort_by = $_GET['sort_by'] ?? 'book_departure'; // Default sort column
$order = strtoupper($_GET['order'] ?? 'ASC'); // Default sort order

// Validate sort_by column
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'book_departure'; // Use default if invalid column is provided
}

// Validate order
if ($order !== 'ASC' && $order !== 'DESC') {
    $order = 'ASC'; // Use default if invalid order is provided
}

$sql = "SELECT book_id, book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price FROM BookFlight ORDER BY $sort_by $order";

$result = mysqli_query($connection, $sql);

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $flights[] = $row;
        }
    }
    mysqli_free_result($result);
} else {
    $error_message = "Error fetching flights: " . mysqli_error($connection);
    error_log($error_message);
}

mysqli_close($connection);
// --- End Fetch Flight Data ---
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
            flex-direction: column;
            align-items: center;
        }

        .admin-flight-list-container {
            width: 100%;
            max-width: 1140px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            padding: 20px;
            margin-top: 20px;
        }

        .admin-flight-list-container h2 {
            color: #ffb03a;
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        .add-flight-btn {
            background-image: linear-gradient(to right, #3b2e8b, #ffb03a);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            transition: background-position 0.5s ease;
            background-size: 200% auto;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .add-flight-btn:hover {
            background-position: right center;
            color: white;
            text-decoration: none;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .table {
            background-color: #3a3e52;
            color: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .table th,
        .table td {
            border-top: 1px solid #5a5a8a;
            vertical-align: middle;
            padding: 12px;
        }

        .table thead th {
            background-color: #4a4f66;
            border-bottom: 2px solid #5a5a8a;
            color: #ffb03a;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .table tbody tr:hover {
            background-color: #4a4f66;
        }

        .table tbody tr:nth-child(even) {
            background-color: #3f4357;
        }

        .table tbody tr:nth-child(even):hover {
            background-color: #4a4f66;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .no-flights {
            text-align: center;
            font-size: 1.2rem;
            color: #bbb;
            margin-top: 40px;
        }
        .alert {
            margin-top: 20px;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
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
            <a href="index.php" class="site-title">SierraFlight (Admin)</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <a href="profile_page.php">
                         <span>Admin: <?php echo htmlspecialchars($username); ?></span>
                         <?php if ($profilePictureUrl === '/college_project/book-a-flight-project-2/image_website/default_profile.png'): ?>
                             <i class="fas fa-user-circle fa-lg profile-icon-nav ml-2"></i>
                         <?php else: ?>
                             <img src="<?php echo htmlspecialchars($profilePictureUrl); ?>" alt="Profile Picture" class="profile-picture-nav ml-2">
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
                     <li class="nav-item">
                         <a class="nav-link" href="edit_book_flight.php">Add Flight</a>
                     </li>
                     <li class="nav-item active">
                         <a class="nav-link" href="admin_flight_list.php">Flight List <span class="sr-only">(current)</span></a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="admin_booking_list.php">Booking List</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="profile_page.php">Profile</a>
                     </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="admin-flight-list-container">
            <h2 class="text-center">Flight List</h2>
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <a href="edit_book_flight.php" class="btn add-flight-btn">
                     <i class="fas fa-plus"></i> Add New Flight
                 </a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($flights)): ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover text-center">
                        <thead>
                            <tr>
                                <th scope="col"><a href="?sort_by=book_id&order=<?php echo ($sort_by === 'book_id' && $order === 'ASC') ? 'DESC' : 'ASC'; ?>">ID <i class="fas fa-sort<?php echo ($sort_by === 'book_id') ? ($order === 'ASC' ? '-up' : '-down') : ''; ?>"></i></a></th>
                                <th scope="col"><a href="?sort_by=book_origin_state&order=<?php echo ($sort_by === 'book_origin_state' && $order === 'ASC') ? 'DESC' : 'ASC'; ?>">Origin <i class="fas fa-sort<?php echo ($sort_by === 'book_origin_state') ? ($order === 'ASC' ? '-up' : '-down') : ''; ?>"></i></a></th>
                                <th scope="col"><a href="?sort_by=book_destination_state&order=<?php echo ($sort_by === 'book_destination_state' && $order === 'ASC') ? 'DESC' : 'ASC'; ?>">Destination <i class="fas fa-sort<?php echo ($sort_by === 'book_destination_state') ? ($order === 'ASC' ? '-up' : '-down') : ''; ?>"></i></a></th>
                                <th scope="col"><a href="?sort_by=book_departure&order=<?php echo ($sort_by === 'book_departure' && $order === 'ASC') ? 'DESC' : 'ASC'; ?>">Departure <i class="fas fa-sort<?php echo ($sort_by === 'book_departure') ? ($order === 'ASC' ? '-up' : '-down') : ''; ?>"></i></a></th>
                                <th scope="col"><a href="?sort_by=book_return&order=<?php echo ($sort_by === 'book_return' && $order === 'ASC') ? 'DESC' : 'ASC'; ?>">Return <i class="fas fa-sort<?php echo ($sort_by === 'book_return') ? ($order === 'ASC' ? '-up' : '-down') : ''; ?>"></i></a></th>
                                <th scope="col"><a href="?sort_by=book_class&order=<?php echo ($sort_by === 'book_class' && $order === 'ASC') ? 'DESC' : 'ASC'; ?>">Class <i class="fas fa-sort<?php echo ($sort_by === 'book_class') ? ($order === 'ASC' ? '-up' : '-down') : ''; ?>"></i></a></th>
                                <th scope="col"><a href="?sort_by=book_airlines&order=<?php echo ($sort_by === 'book_airlines' && $order === 'ASC') ? 'DESC' : 'ASC'; ?>">Airlines <i class="fas fa-sort<?php echo ($sort_by === 'book_airlines') ? ($order === 'ASC' ? '-up' : '-down') : ''; ?>"></i></a></th>
                                <th scope="col"><a href="?sort_by=book_price&order=<?php echo ($sort_by === 'book_price' && $order === 'ASC') ? 'DESC' : 'ASC'; ?>">Price (RM) <i class="fas fa-sort<?php echo ($sort_by === 'book_price') ? ($order === 'ASC' ? '-up' : '-down') : ''; ?>"></i></a></th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($flights as $flight): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($flight['book_id']); ?></td>
                                    <td><?php echo htmlspecialchars($flight['book_origin_state'] . ', ' . $flight['book_origin_country']); ?></td>
                                    <td><?php echo htmlspecialchars($flight['book_destination_state'] . ', ' . $flight['book_destination_country']); ?></td>
                                    <td><?php echo htmlspecialchars($flight['book_departure']); ?></td>
                                    <td><?php echo htmlspecialchars($flight['book_return']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($flight['book_class'])); ?></td>
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
                </div>
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