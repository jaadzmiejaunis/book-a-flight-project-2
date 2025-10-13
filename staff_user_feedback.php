<?php
session_start(); // Start the session

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed on staff feedback page: " . mysqli_connect_error());
    die("An error occurred connecting to the database.");
}
// --- End Database Connection ---

// --- Staff Authentication and Data Retrieval ---
$loggedIn = isset($_SESSION['book_id']);
$username = 'Staff Member';
$profilePictureUrl = '/college_project/book-a-flight-project-2/image_website/default_profile.png';

if ($loggedIn) {
    $user_id = $_SESSION['book_id'];
    $sql = "SELECT book_username, book_user_roles, book_profile FROM BookUser WHERE book_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if ($user['book_user_roles'] !== 'Staff') {
            header('Location: login_page.php');
            exit();
        }
        $username = htmlspecialchars($user['book_username']);
        if (!empty($user['book_profile'])) {
            $profilePictureUrl = htmlspecialchars($user['book_profile']);
        }
    } else {
        header('Location: login_page.php');
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    header('Location: login_page.php');
    exit();
}

// --- Fetch Statistics (ALL reviews) ---
$total_reviews = 0;
$total_rating_sum = 0;
$rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

$stats_sql = "SELECT rating FROM BookReviews";
$stats_result = mysqli_query($connection, $stats_sql);
if ($stats_result) {
    while($row = mysqli_fetch_assoc($stats_result)) {
        $total_reviews++;
        $total_rating_sum += $row['rating'];
        if (isset($rating_counts[$row['rating']])) {
            $rating_counts[$row['rating']]++;
        }
    }
    mysqli_free_result($stats_result);
}
$average_rating = ($total_reviews > 0) ? $total_rating_sum / $total_reviews : 0;


// --- Fetch User Reviews with Filtering ---
$reviews = [];
$error_message = '';
$selected_rating = $_GET['rating'] ?? 'all';
$sql_reviews = "SELECT r.rating, r.comment, r.review_date, u.book_username FROM BookReviews r JOIN BookUser u ON r.user_id = u.book_id";

if ($selected_rating !== 'all' && is_numeric($selected_rating)) {
    $sql_reviews .= " WHERE r.rating = ?";
}
$sql_reviews .= " ORDER BY r.review_date DESC";

$stmt_reviews = mysqli_prepare($connection, $sql_reviews);
if ($selected_rating !== 'all' && is_numeric($selected_rating)) {
    mysqli_stmt_bind_param($stmt_reviews, "i", $selected_rating);
}

mysqli_stmt_execute($stmt_reviews);
$result_reviews = mysqli_stmt_get_result($stmt_reviews);

if ($result_reviews) {
    while ($row = mysqli_fetch_assoc($result_reviews)) {
        $reviews[] = $row;
    }
    mysqli_free_result($result_reviews);
} else {
    $error_message = "An error occurred fetching user reviews.";
}
mysqli_stmt_close($stmt_reviews);
mysqli_close($connection);

// Helper function to generate star icons
function generate_stars($rating) {
    $stars_html = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars_html .= '<i class="fas fa-star ' . ($i <= $rating ? 'filled' : '') . '"></i>';
    }
    return $stars_html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Feedback - SierraFlight</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #1e1e2d;
            color: #e0e0e0;
            font-family: sans-serif;
            margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh;
        }
        .top-gradient-bar {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            padding: 10px 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); color: white;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
        }
        .top-gradient-bar .container {
            display: flex; justify-content: space-between; align-items: center;
            width: 100%; max-width: 1140px; margin: 0 auto; flex-wrap: wrap;
        }
        .top-gradient-bar .site-title {
            font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none;
            margin-right: auto; white-space: nowrap; display: flex; align-items: center;
        }
        .top-gradient-bar .site-title:hover { text-decoration: underline; }
        .top-gradient-bar .site-title .sierraflight-logo { width: 150px; height: auto; margin-right: 10px; vertical-align: middle; }
        .top-gradient-bar .user-info { display: flex; align-items: center; color: white; flex-shrink: 0; margin-left: auto; white-space: nowrap; }
        .top-gradient-bar .user-info a { color: white; text-decoration: none; display: flex; align-items: center; }
        .top-gradient-bar .profile-picture-nav, .top-gradient-bar .profile-icon-nav { width: 36px; height: 36px; border-radius: 50%; margin-left: 8px; vertical-align: middle; object-fit: cover; border: 1px solid white; }
        .top-gradient-bar .btn-danger { background-color: #dc3545; border-color: #dc3545; padding: .3rem .6rem; font-size: .95rem; line-height: 1.5; border-radius: .2rem; margin-left: 10px; }
        .navbar { background-color: #212529; padding: 0 20px; }
        
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

        .page-content { padding: 20px; flex-grow: 1; }
        .feedback-container { margin: 30px auto; max-width: 900px; background-color: #282b3c; border-radius: 8px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5); padding: 30px; }
        .admin-header {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60); color: white;
            padding: 20px; margin: -30px -30px 30px -30px; text-align: center; font-size: 1.8rem;
            font-weight: bold; border-top-left-radius: 8px; border-top-right-radius: 8px;
        }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #3a3e52, #2d3042); padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #ffb03a; }
        .stat-value { font-size: 2rem; font-weight: bold; color: #ffb03a; margin-bottom: 5px; }
        .stat-label { font-size: 0.9rem; color: #ccc; }
        .rating-dist { text-align: left; font-size: 0.9rem; }
        .rating-dist div { display: flex; align-items: center; margin-bottom: 5px; }
        .rating-dist .stars { width: 80px; color: #ffc107; }
        .rating-dist .bar-container { flex-grow: 1; background-color: #5a5a8a; border-radius: 5px; height: 10px; }
        .rating-dist .bar { background-color: #ffb03a; height: 100%; border-radius: 5px; }
        .rating-dist .count { width: 40px; text-align: right; }
        .filter-buttons { display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; }
        .filter-btn { background-color: #3a3e52; color: #e0e0e0; border: 1px solid #5a5a8a; padding: 8px 16px; border-radius: 5px; text-decoration: none; transition: all 0.2s; }
        .filter-btn:hover { background-color: #5a5a8a; color: white; text-decoration: none; }
        .filter-btn.active { background-color: #ffb03a; color: #1e1e2d; font-weight: bold; border-color: #ffb03a; }
        .feedback-card { background-color: #3a3e52; border-radius: 8px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #6c757d; }
        .feedback-card .user-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #5a5a8a; padding-bottom: 10px; }
        .feedback-card .username { font-weight: bold; color: #e0e0e0; font-size: 1.1rem; }
        .feedback-card .review-date { font-size: 0.85rem; color: #a0a0a0; }
        .feedback-card .rating-stars { margin-bottom: 15px; font-size: 1.2rem; }
        .feedback-card .rating-stars .fa-star { color: #6c757d; }
        .feedback-card .rating-stars .fa-star.filled { color: #ffc107; }
        .feedback-card .comment { line-height: 1.6; color: #ccc; }
        .no-feedback { text-align: center; color: #ccc; margin-top: 20px; padding: 20px; background-color: #3a3e52; border-radius: 8px; }
        .print-button { margin-bottom: 20px; }
        .print-header { display: none; }
        @media print {
            body { background-color: #fff !important; color: #000 !important; }
            .top-gradient-bar, .navbar, .filter-buttons, .print-button { display: none !important; }
            .page-content, .feedback-container { padding: 0 !important; margin: 0 !important; box-shadow: none !important; }
            .feedback-container { width: 100% !important; max-width: 100% !important; }
            .admin-header { background: none !important; color: #000 !important; text-align: left; padding-left: 0; }
            .stat-card { border: 1px solid #ccc; background: #f8f9fa !important; color: #000 !important; }
            .feedback-card { border: 1px solid #ccc; page-break-inside: avoid; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        }
    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container">
            <a href="homepage.php" class="site-title">
                <img src="image_website/website_image/sierraflight_logo.png" class="sierraflight-logo" alt="SierraFlight Logo">
                <span>(Staff)</span>
            </a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                    <a href="profile_page.php">
                        <span>Welcome, <?php echo $username; ?>!</span>
                        <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav">
                    </a>
                    <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item"><a class="nav-link" href="homepage.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="staff_sales_report.php">Sales Report</a></li>
                    <li class="nav-item"><a class="nav-link" href="staff_booking_status.php">View Booking Status</a></li>
                    <li class="nav-item active"><a class="nav-link" href="staff_user_feedback.php">User Feedback <span class="sr-only">(current)</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="profile_page.php">Profile</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="feedback-container">
            <div class="print-header">
                <h1>SierraFlight - User Feedback Report</h1>
                <p>Generated on: <?php echo date('F j, Y'); ?></p>
            </div>
            
            <div class="admin-header">User Feedback & Ratings</div>
            
            <button onclick="window.print()" class="btn btn-primary print-button"><i class="fas fa-print"></i> Print Report</button>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_reviews; ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($average_rating, 2); ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Rating Distribution</div>
                    <div class="rating-dist mt-2">
                        <?php foreach ($rating_counts as $rating => $count): 
                            $percentage = ($total_reviews > 0) ? ($count / $total_reviews) * 100 : 0;
                        ?>
                        <div>
                            <span class="stars"><?php echo $rating; ?> <i class="fas fa-star"></i></span>
                            <div class="bar-container mx-2">
                                <div class="bar" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <span class="count"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="filter-buttons">
                <a href="staff_user_feedback.php" class="filter-btn <?php echo ($selected_rating == 'all') ? 'active' : ''; ?>">All Reviews</a>
                <a href="staff_user_feedback.php?rating=5" class="filter-btn <?php echo ($selected_rating == '5') ? 'active' : ''; ?>">5 Stars</a>
                <a href="staff_user_feedback.php?rating=4" class="filter-btn <?php echo ($selected_rating == '4') ? 'active' : ''; ?>">4 Stars</a>
                <a href="staff_user_feedback.php?rating=3" class="filter-btn <?php echo ($selected_rating == '3') ? 'active' : ''; ?>">3 Stars</a>
                <a href="staff_user_feedback.php?rating=2" class="filter-btn <?php echo ($selected_rating == '2') ? 'active' : ''; ?>">2 Stars</a>
                <a href="staff_user_feedback.php?rating=1" class="filter-btn <?php echo ($selected_rating == '1') ? 'active' : ''; ?>">1 Star</a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php elseif (empty($reviews)): ?>
                <p class="no-feedback">No user reviews found for the selected rating.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="feedback-card">
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($review['book_username']); ?></span>
                            <span class="review-date"><?php echo date('F j, Y, g:i a', strtotime($review['review_date'])); ?></span>
                        </div>
                        <div class="rating-stars">
                            <?php echo generate_stars($review['rating']); ?>
                        </div>
                        <p class="comment">
                            <?php echo !empty($review['comment']) ? nl2br(htmlspecialchars($review['comment'])) : '<em>No comment provided.</em>'; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>