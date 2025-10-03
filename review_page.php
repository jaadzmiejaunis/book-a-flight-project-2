<?php
session_start();
require 'config.php';
include 'connection.php';

$loggedIn = isset($_SESSION['book_id']);
if (!$loggedIn) {
    header('Location: login_page.php');
    exit();
}

$bookingId = $_GET['bookId'] ?? null;
if (!$bookingId) {
    // Redirect to home or booking history if no booking ID is provided
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'];
$defaultProfilePicture = 'path/to/default-profile-picture.png';
$profilePictureUrl = isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Your Flight</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #1a1a2e;
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
        .navbar-nav .nav-link {
            padding: 8px 15px;
            color: white !important;
            transition: background-color 0.3s ease, text-decoration 0.3s ease;
        }

        .page-content {
            padding: 20px;
            flex-grow: 1;
        }
        .review-box {
            padding: 30px;
            background-color: #282844;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.6);
            margin: 50px auto;
            max-width: 600px;
            border: 1px solid #3a3a5a;
        }
        .review-box h2 {
            color: #ffb03a;
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .review-box p {
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group label {
            color: #e0e0e0;
        }
        .form-control {
            background-color: #555577;
            border: 1px solid #6a6a8a;
            color: #ffffff;
            border-radius: 8px;
        }
        .form-control:focus {
            background-color: #6a6a8a;
            border-color: #ffb03a;
            box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.4);
        }
        .btn-primary {
            background-color: #ffb03a;
            border-color: #ffb03a;
            color: #1a1a2e;
            font-weight: bold;
            border-radius: 8px;
            width: 100%;
            padding: 10px;
        }
        .btn-primary:hover {
            background-color: #e09e2a;
            border-color: #e09e2a;
        }
        .star-rating {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            font-size: 2rem;
            color: #6a6a8a;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffb03a;
        }
    </style>
</head>
<body>
    <div class="top-gradient-bar">
        <div class="container">
            <a href="index.php" class="site-title">SierraFlight</a>
            <div class="user-info">
                <a href="profile_page.php">
                    Profile
                    <i class="fas fa-user-circle fa-lg profile-icon-nav"></i>
                </a>
                <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
            </div>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
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
                    <li class="nav-item">
                        <a class="nav-link" href="profile_page.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="booking_history.php">Check Book</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="review-box">
            <h2>Review Your Flight</h2>
            <p>Please share your experience and help us improve!</p>
            <form action="submit_review.php" method="post">
                <input type="hidden" name="bookId" value="<?php echo htmlspecialchars($bookingId); ?>">
                <div class="form-group">
                    <label for="rating">Star Rating</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5 stars">&#9733;</label>
                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars">&#9733;</label>
                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars">&#9733;</label>
                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars">&#9733;</label>
                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star">&#9733;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="comment">Comments</label>
                    <textarea class="form-control" id="comment" name="comment" rows="5" placeholder="Enter your comments here..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>