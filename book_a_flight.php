<?php
session_start();

require 'config.php';
include 'connection.php';

// Check connection
if (!$connection) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("An error occurred connecting to the database to fetch flights. Please try again later.");
}

$loggedIn = isset($_SESSION['book_id']);
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
$defaultProfilePicture = '/college_project/book-a-flight-project-2/image_website/default_profile.png';
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) ? htmlspecialchars($_SESSION['profile_picture_url']) : $defaultProfilePicture;

$search_from = trim($_GET['from_location'] ?? '');
$search_to = trim($_GET['to_location'] ?? '');
$search_departure_date = $_GET['departure_date'] ?? '';
$search_return_date = $_GET['return_date'] ?? '';

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Flight</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_client_id ?>&currency=MYR"></script>

    <style>
        body {
            background-color: #1a1a2e; /* Darker background */
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

        /* --- Custom UI Styles for Reference Image --- */
        .search-section-box {
            padding: 30px; /* Increased padding */
            background-color: #282844; /* Darker blue-grey */
            border-radius: 15px; /* More rounded corners */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.6); /* More prominent shadow */
            margin: 50px auto; /* Increased margin */
            max-width: 900px; /* Wider box */
            border: 1px solid #3a3a5a; /* Subtle border */
        }

        .search-section-box h2 {
            color: #e0e0e0; /* Lighter text for title */
            font-size: 2.2rem;
            margin-bottom: 40px; /* More space below title */
            letter-spacing: 1px;
            font-weight: 600;
        }

        .flight-card {
            background-color: #38385e; /* Slightly lighter inner card */
            color: #fff;
            border-radius: 10px; /* Rounded corners for inner cards */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
            padding: 20px;
            margin-bottom: 20px;
            border: none; /* Remove default border */
            height: 100%; /* Ensure equal height */
            display: flex;
            flex-direction: column;
        }

        /* Specific styling for the left search form */
        .flight-card .search-form {
            display: flex;
            flex-direction: column;
            gap: 20px; /* Space between form rows */
        }

        .search-form .form-group {
            margin-bottom: 0; /* Remove default Bootstrap margin */
        }

        .search-form .form-row {
            display: flex;
            gap: 20px; /* Space between items in a row */
            margin-bottom: 0; /* Remove default form-row margin */
        }

        .search-form .form-group.col-md-6 {
            flex: 1; /* Make columns take equal space */
            max-width: none; /* Override Bootstrap max-width */
        }

        .search-form label {
            color: #e0e0e0;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .search-form .form-control,
        .search-form select.form-control {
            background-color: #555577; /* Input field background */
            border: 1px solid #6a6a8a; /* Input border */
            color: #ffffff; /* Input text color */
            border-radius: 8px; /* Rounded input fields */
            padding: 10px 15px;
            height: auto; /* Allow height to adjust */
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3);
            -webkit-appearance: none; /* Remove default select styling */
            -moz-appearance: none;
            appearance: none;
            background-image: none; /* Remove default dropdown arrow */
        }

        .search-form .form-control::placeholder {
            color: #bbbbbb; /* Placeholder color */
        }

        .search-form .form-control:focus,
        .search-form select.form-control:focus {
            background-color: #6a6a8a; /* Focused input background */
            border-color: #ffb03a; /* Focused border color */
            box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.4); /* Focused shadow */
            color: white;
        }

        /* Custom dropdown arrow for select */
        .search-form select.form-control {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ffffff"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 30px; /* Make space for the arrow */
        }


        /* Map placeholder styling */
        .map-placeholder {
            background-color: #555577;
            border-radius: 8px;
            height: 100px; /* Smaller height for map placeholders */
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cccccc;
            font-size: 1.2rem;
            margin-top: 10px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.3);
        }

        /* Styling for the Flight Information panel on the right */
        #travelDataResult {
            background-color: #38385e; /* Same as left card */
            padding: 25px; /* Slightly more padding */
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            display: flex; /* Use flexbox for layout */
            flex-direction: column;
            gap: 15px; /* Space between info items */
            min-height: 100%; /* Ensure it takes full height */
            flex-grow: 1; /* Allow content to grow */
        }

        #travelDataResult h5 {
            color: #ffb03a; /* Highlight color for title */
            font-size: 1.6rem;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        #travelDataResult p {
            background-color: #555577; /* Background for info lines */
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1rem;
            color: #ffffff;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3);
            min-height: 45px; /* Consistent height */
        }

        #travelDataResult p strong {
            color: #e0e0e0;
            font-weight: normal; /* Less bold */
        }

        #travelDataResult p span {
            color: #ffffff;
            font-weight: 500;
            text-align: right;
            flex-grow: 1; /* Allow span to take up space */
        }

        #resPrice {
            font-size: 1.5rem; /* Makes the font size larger */
            font-weight: 700; /* Makes the font bolder */
            color: #ffb03a; /* Changes the color to the highlight orange */
        }

        /* Specific styles for From/To fields in Flight Info */
        #travelDataResult .from-to-row {
            display: flex;
            gap: 15px;
        }
        #travelDataResult .from-to-row .field-box {
            flex: 1;
            background-color: #555577;
            border-radius: 8px;
            padding: 10px 15px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            min-height: 45px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.3);
        }
        #travelDataResult .from-to-row .field-box strong {
            font-size: 0.8rem;
            color: #e0e0e0;
            font-weight: normal;
            margin-bottom: 3px;
        }
        #travelDataResult .from-to-row .field-box span {
            font-size: 1rem;
            color: #ffffff;
            font-weight: 500;
            text-align: left; /* Align text to left */
            width: 100%; /* Take full width */
        }

        #paypal-button-container {
            margin-top: auto; /* Pushes button to the bottom */
            padding-top: 20px; /* Space above button */
        }

        /* PayPal button specific styles */
        .paypal-button-color-gold {
            background-color: #ffc439 !important; /* Gold color */
            border-radius: 8px !important; /* Rounded corners */
            height: 50px !important; /* Larger button */
            font-size: 1.1rem !important; /* Larger text */
            font-weight: 600 !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Button shadow */
        }
        .paypal-button-color-gold:hover {
            filter: brightness(0.9); /* Slight dim on hover */
        }
        .paypal-button-label-paypal {
            color: #333 !important; /* Darker text for PayPal label */
        }

        /* Food and Drinks checkbox style */
        .food-drinks-checkbox {
            background-color: #555577; /* Same as input fields */
            border: 1px solid #6a6a8a;
            color: #ffffff;
            border-radius: 8px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            min-height: 45px; /* Consistent height */
        }
        .food-drinks-checkbox input[type="checkbox"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffb03a; /* Checkbox border color */
            border-radius: 4px;
            background-color: #38385e; /* Checkbox background */
            cursor: pointer;
            position: relative;
            outline: none;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .food-drinks-checkbox input[type="checkbox"]:checked {
            background-color: #ffb03a; /* Checkbox checked background */
            border-color: #ffb03a;
        }
        .food-drinks-checkbox input[type="checkbox"]:checked::after {
            content: '✔'; /* Checkmark */
            color: #38385e; /* Checkmark color */
            font-size: 14px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Custom Modal / Message Box Styling */
        .message-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: none; /* Initially hidden */
            justify-content: center;
            align-items: center;
            z-index: 1050;
        }
        .message-modal-content {
            background-color: #38385e;
            color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        .message-modal-content h4 {
            color: #ffb03a;
            margin-bottom: 20px;
        }
        .message-modal-content p {
            font-size: 1rem; /* Adjust font size for better readability in modal */
        }
        .message-modal-content button {
            margin-top: 20px;
            background-color: #ffb03a;
            color: #1a1a2e;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .message-modal-content button:hover {
            background-color: #e09e2a;
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
                    <li class="nav-item active">
                        <a class="nav-link" href="contact.php">Contact Us <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="book_a_flight.php">Book a Flight <span class="sr-only">(current)</span></a>
                    </li>
                    <?php if ($loggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="booking_history.php">Book History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile_page.php">Profile</a> <!--fixed profile page position-->
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="search-section-box">
            <h2 class="text-center mb-4">Book a Flight</h2>

            <div class="row">
                <div class="col-md-6 d-flex">
                    <div class="flight-card">
                        <form action="book_a_flight.php" method="get" class="search-form">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="from_location">From</label>
                                    <input type="text" class="form-control" id="from_location" name="from_location"
                                        placeholder="" value="<?php echo htmlspecialchars($search_from); ?>">
                                    <div id="mapFrom" class="map-placeholder">map</div>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="to_location">To</label>
                                    <input type="text" class="form-control" id="to_location" name="to_location"
                                        placeholder="" value="<?php echo htmlspecialchars($search_to); ?>">
                                    <div id="mapTo" class="map-placeholder">map</div>
                                </div>

                                <input type="hidden" id="origin_state" name="origin_state">
                                <input type="hidden" id="origin_country" name="origin_country">
                                <input type="hidden" id="destination_state" name="destination_state">
                                <input type="hidden" id="destination_country" name="destination_country">
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="departure_date">Departure Date</label>
                                    <input type="date" class="form-control" id="departure_date" name="departure_date" value="<?php echo htmlspecialchars($search_departure_date); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="return_date">Return Date</label>
                                    <input type="date" class="form-control" id="return_date" name="return_date" value="<?php echo htmlspecialchars($search_return_date); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label for="airline">Airlines</label>
                                    <select class="form-control" id="airline" name="airline">
                                        <option value="Malaysia Airlines">Malaysia Airlines</option>
                                        <option value="AirAsia">AirAsia</option>
                                        <option value="Batik Air">Batik Air</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label for="travelClass">Travel Class</label>
                                    <select class="form-control" id="travelClass" name="travelClass">
                                        <option value="economy">Economy</option>
                                        <option value="business">Business</option>
                                        <option value="first">First</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="no_of_adult">Adult</label>
                                    <input type="text" class="form-control" id="no_of_adult" name="no_of_adult"
                                        placeholder="" value="0">
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="no_of_children">Children</label>
                                    <input type="text" class="form-control" id="no_of_children" name="no_of_children"
                                        placeholder="" value="0">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label class="food-drinks-checkbox">
                                        Food and Drinks
                                        <input type="checkbox" id="food_drinks" name="food_drinks">
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-6 d-flex">
                    <div id="travelDataResult" class="flight-card">
                        <h5>Flight Information</h5>
                        <div class="from-to-row">
                            <div class="field-box">
                                <strong>From Airport:</strong> <span id="resFrom"></span>
                            </div>
                            <div class="field-box">
                                <strong>To Airport:</strong> <span id="resTo"></span>
                            </div>
                        </div>
                        <p><strong>Distance (KM):</strong> <span id="resDistance"></span></p>
                        <p><strong>Estimation time (HOUR):</strong> <span id="resTime"></span></p>
                        <p><strong>Estimated Price (RM):</strong> <span id="resPrice"></span></p>
                        <div id="paypal-button-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="messageModal" class="message-modal">
        <div class="message-modal-content">
            <h4 id="messageTitle">Search Error</h4>
            <p id="messageText">We could not find a suitable **airport** for the location you entered. Please enter a valid **country** or **state** to proceed.</p>
            <button onclick="document.getElementById('messageModal').style.display='none'">OK</button>
        </div>
        <input type="hidden" id="originStateHidden" name="originStateHidden">
        <input type="hidden" id="originCountryHidden" name="originCountryHidden">
        <input type="hidden" id="destinationStateHidden" name="destinationStateHidden">
        <input type="hidden" id="destinationCountryHidden" name="destinationCountryHidden">
    </div>

    <script>
        // Initialize Leaflet maps if the elements exist
        let mapFrom, mapTo;
        let markerFrom, markerTo;

        // Global variables to store place data for display purposes
        let fromPlace = null;
        let toPlace = null;

        // Function to show custom message modal
        function showMessage(title, text) {
            document.getElementById('messageTitle').textContent = title;
            document.getElementById('messageText').innerHTML = text; // Use innerHTML to allow simple formatting like **
            document.getElementById('messageModal').style.display = 'flex';
        }

        // NEW FUNCTION TO SAVE BOOKING DATA
        // NEW FUNCTION TO SAVE BOOKING DATA
        function saveBookingData() {
            // --- 1. Get all the booking data (same as before) ---
            let bookingData = {
                origin_state: document.getElementById("origin_state").value,
                origin_country: document.getElementById("origin_country").value,
                destination_state: document.getElementById("destination_state").value,
                destination_country: document.getElementById("destination_country").value,
                departure_date: document.getElementById("departure_date").value,
                return_date: document.getElementById("return_date").value,
                travel_class: document.getElementById("travelClass").value,
                airline: document.getElementById("airline").value,
                no_of_adult: document.getElementById("no_of_adult").value,
                no_of_children: document.getElementById("no_of_children").value,
                food_drinks: document.getElementById("food_drinks").checked ? "Yes" : "No",
                book_ticket_price: document.getElementById("ticketPrice").value,
                book_food_drink_price: document.getElementById("foodDrinkPrice").value,
                book_total_price: document.getElementById("resPrice").textContent
            };

            // --- 2. Get the modal elements ---
            const modal = document.getElementById('messageModal');
            const modalTitle = document.getElementById('messageTitle');
            const modalText = document.getElementById('messageText');
            const modalButton = modal.querySelector('.message-modal-content button');

            // --- 3. Show a "Processing" message ---
            modalTitle.textContent = 'Payment Approved';
            modalText.innerHTML = 'Please wait, we are saving your booking...';
            modalButton.style.display = 'none'; // Hide the "OK" button
            modal.style.display = 'flex';


            // --- 4. Send the data to the server (same as before) ---
            fetch('user_booking_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(bookingData),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // --- 5. Show Success Message ---
                        modalTitle.textContent = 'Success!';
                        modalText.innerHTML = 'Your booking is confirmed. We are now preparing your review page...';
                        // (Button remains hidden)

                        setTimeout(function() {
                            // Redirect to the review page after 3 seconds
                            window.location.href = 'review_page.php?bookId=' + data.bookId;
                        }, 3000); // 3-second delay

                    } else {
                        // --- 6. Show Booking Failed Error ---
                        modalTitle.textContent = 'Booking Failed';
                        modalText.innerHTML = data.message || 'An unexpected error occurred. Please contact support.';
                        modalButton.style.display = 'block'; // Show the "OK" button again
                    }
                })
                .catch(error => {
                    // --- 7. Show Connection Error ---
                    console.error('Error:', error);
                    modalTitle.textContent = 'Connection Error';
                    modalText.innerHTML = 'Could not connect to the server to save your booking. Please check your internet connection and try again.';
                    modalButton.style.display = 'block'; // Show the "OK" button
                });
        }


        // Logic to refine the search query for a global airport search
        function refineAirportQuery(rawQuery) {
            const trimmedQuery = rawQuery.trim();
            if (!trimmedQuery) {
                return null;
            }

            const words = trimmedQuery.split(/\s+/).filter(Boolean);

            if (words.length <= 2) {
                return trimmedQuery;
            }

            return `${trimmedQuery} Airport`;
        }


        document.addEventListener("DOMContentLoaded", function() {
            if (document.getElementById('mapFrom') && document.getElementById('mapTo')) {
                mapFrom = L.map('mapFrom', {
                    attributionControl: false,
                    zoomControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                    touchZoom: false,
                }).setView([3.1390, 101.6869], 5);

                mapTo = L.map('mapTo', {
                    attributionControl: false,
                    zoomControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                    touchZoom: false,
                }).setView([3.1390, 101.6869], 5);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapFrom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapTo);

                markerFrom = L.marker([3.1390, 101.6869]).addTo(mapFrom);
                markerTo = L.marker([3.1390, 101.6869]).addTo(mapTo);

                function reverseGeocode(lat, lng, inputId) {
                    fetch(`nomimatim_proxy.php?lat=${lat}&lon=${lng}&accept-language=en`) //translate to english to non-english speaking country.
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.address) {
                                let address = data.address;
                                let state = address.state || "Unknown";
                                let country = address.country || "Unknown";

                                if (inputId === 'from_location') {
                                    document.getElementById("origin_state").value = state;
                                    document.getElementById("origin_country").value = country;
                                } else {
                                    document.getElementById("destination_state").value = state;
                                    document.getElementById("destination_country").value = country;
                                }

                                document.getElementById(inputId).value = data.display_name;
                            } else {
                                // Set to Unknown if data is not available
                                if (inputId === 'from_location') {
                                    document.getElementById("origin_state").value = 'Unknown';
                                    document.getElementById("origin_country").value = 'Unknown';
                                } else {
                                    document.getElementById("destination_state").value = 'Unknown';
                                    document.getElementById("destination_country").value = 'Unknown';
                                }
                                showMessage("Location Error", `Could not determine the state and country for the selected location.`);
                            }
                        })
                        .catch(err => {
                            console.error("Reverse geocode failed:", err);
                            showMessage("Connection Error", "Reverse geocoding service failed. Please check your network and try again.");
                        });
                }


                function searchLocation(rawQuery, mapInstance, markerInstance, inputId) {
                    const finalQuery = refineAirportQuery(rawQuery);

                    if (!finalQuery) {
                        showMessage("Missing Location", "Please enter a location (city, state, country, or airport code) to search for flights.");
                        return;
                    }

                    fetch(`search_proxy.php?q=${encodeURIComponent(finalQuery)}&accept-language=en`) //translate to english on non-english speaking country
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                let place = data[0];
                                let lat = parseFloat(place.lat);
                                let lon = parseFloat(place.lon);

                                mapInstance.setView([lat, lon], 10);
                                markerInstance.setLatLng([lat, lon]);

                                //update state & country hidden fields properly
                                reverseGeocode(lat, lon, inputId);

                                if (inputId === 'from_location') {
                                    fromPlace = place;
                                } else {
                                    toPlace = place;
                                }

                                document.getElementById(inputId).value = rawQuery;

                                updateTravelDataPanel();
                            } else {
                                showMessage("No Airport Found", `We could not find an airport for **${rawQuery}**. Please try a different location or check your spelling.`);
                                updateTravelDataPanel();
                            }
                        })
                        .catch(err => {
                            console.error("Search failed:", err);
                            showMessage("Connection Error", "The location service failed. Please check your connection.");
                        });
                }

                document.getElementById("from_location").addEventListener("change", function() {
                    searchLocation(this.value, mapFrom, markerFrom, "from_location");
                });

                document.getElementById("to_location").addEventListener("change", function() {
                    searchLocation(this.value, mapTo, markerTo, "to_location");
                });

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;
                        mapFrom.setView([lat, lng], 10);
                        markerFrom.setLatLng([lat, lng]);
                        reverseGeocode(lat, lng, 'from_location');
                    });
                }

                const initialFrom = document.getElementById("from_location").value;
                const initialTo = document.getElementById("to_location").value;

                if (initialFrom) searchLocation(initialFrom, mapFrom, markerFrom, "from_location");
                if (initialTo) searchLocation(initialTo, mapTo, markerTo, "to_location");

                if (!initialFrom && markerFrom) reverseGeocode(markerFrom.getLatLng().lat, markerFrom.getLatLng().lng, 'from_location');
                if (!initialTo && markerTo) reverseGeocode(markerTo.getLatLng().lat, markerTo.getLatLng().lng, 'to_location');
            } else {
                console.warn("Map elements not found. Map functionality will not be initialized.");
                updateTravelDataPanel();
            }

            function updateTravelDataPanel() {
                let fromCoords = markerFrom ? markerFrom.getLatLng() : {
                    lat: 3.1390,
                    lng: 101.6869
                };
                let toCoords = markerTo ? markerTo.getLatLng() : {
                    lat: 3.1390,
                    lng: 101.6869
                };

                let fromName = fromPlace ? (fromPlace.display_name || "N/A") : "N/A";
                let toName = toPlace ? (toPlace.display_name || "N/A") : "N/A";

                let distance = calculateDistance(fromCoords.lat, fromCoords.lng, toCoords.lat, toCoords.lng);
                let timeEstimation = distance / 885;
                let baseFare = 100;
                let perKmRate = 0.5;
                let selectedTravelClass = document.getElementById("travelClass").value;
                let classMult = {
                    economy: 1,
                    business: 2.2,
                    first: 4
                };
                let selectedAirline = document.getElementById("airline").value;
                let airlineMult = {
                    "Malaysia Airlines": 1,
                    "AirAsia": 0.5,
                    "Batik Air": 0.75
                };

                let numAdults = parseInt(document.getElementById("no_of_adult").value) || 0;
                let numChildren = parseInt(document.getElementById("no_of_children").value) || 0;
                let totalPeople = numAdults + numChildren;
                
                let singlePersonPrice = ((baseFare + (distance * perKmRate)) * classMult[selectedTravelClass]) * airlineMult[selectedAirline];
                let ticketPrice = (singlePersonPrice * numAdults) + (singlePersonPrice * numChildren * 0.75);

                if (totalPeople >= 5) {
                    ticketPrice *= 0.85;
                }

                let foodDrinkPrice = 0;
                const foodDrinksCheckbox = document.getElementById("food_drinks");
                if (foodDrinksCheckbox && foodDrinksCheckbox.checked) {
                    foodDrinkPrice = (totalPeople * 25);
                }

                let totalprice = ticketPrice + foodDrinkPrice;

                document.getElementById("resFrom").textContent = fromName;
                document.getElementById("resTo").textContent = toName;
                document.getElementById("resDistance").textContent = distance.toFixed(2);
                document.getElementById("resTime").textContent = timeEstimation.toFixed(2);
                document.getElementById("resPrice").textContent = (Math.ceil(totalprice * 100) / 100).toFixed(2);
                document.getElementById("ticketPrice").value = (Math.ceil(ticketPrice * 100) / 100).toFixed(2);
                document.getElementById("foodDrinkPrice").value = foodDrinkPrice.toFixed(2);
            }

            function calculateDistance(lat1, lon1, lat2, lon2) {
                function toRad(x) {
                    return x * Math.PI / 180;
                }
                let earthRadius = 6371;
                let distanceLat = toRad(lat2 - lat1);
                let distanceLon = toRad(lon2 - lon1);
                let a = Math.sin(distanceLat / 2) * Math.sin(distanceLat / 2) +
                    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                    Math.sin(distanceLon / 2) * Math.sin(distanceLon / 2);
                let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return earthRadius * c;
            }

            document.getElementById("travelClass").addEventListener("change", updateTravelDataPanel);
            document.getElementById("airline").addEventListener("change", updateTravelDataPanel);
            document.getElementById("no_of_adult").addEventListener("input", updateTravelDataPanel);
            document.getElementById("no_of_children").addEventListener("input", updateTravelDataPanel);
            document.getElementById("food_drinks").addEventListener("change", updateTravelDataPanel);
            document.getElementById("departure_date").addEventListener("change", updateTravelDataPanel);
            document.getElementById("return_date").addEventListener("change", updateTravelDataPanel);
            
            // Add two new hidden input fields for the prices
            const ticketPriceInput = document.createElement('input');
            ticketPriceInput.type = 'hidden';
            ticketPriceInput.id = 'ticketPrice';
            ticketPriceInput.name = 'ticketPrice';
            document.querySelector('.search-form').appendChild(ticketPriceInput);

            const foodDrinkPriceInput = document.createElement('input');
            foodDrinkPriceInput.type = 'hidden';
            foodDrinkPriceInput.id = 'foodDrinkPrice';
            foodDrinkPriceInput.name = 'foodDrinkPrice';
            document.querySelector('.search-form').appendChild(foodDrinkPriceInput);
            
            // Initial call to update price data when the page loads
            updateTravelDataPanel();


            paypal.Buttons({
                createOrder: function(data, actions) {
                    let price = parseFloat(document.getElementById("resPrice").textContent);
                    if (isNaN(price) || price <= 0) {
                        showMessage("Invalid Amount", "The flight price must be greater than RM 0.00. Please select your origin, destination, and number of passengers.");
                        return actions.reject();
                    }

                    return fetch("create_order.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                amount: price.toFixed(2)
                            })
                        })
                        .then(res => res.json())
                        .then(order => order.id);
                },
                onApprove: function(data, actions) {
                    return fetch("capture_order.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                orderID: data.orderID
                            })
                        })
                        .then(res => res.json())
                        .then(details => {
                            // After payment is approved, save the booking data to the database
                            saveBookingData();
                        });
                }
            }).render("#paypal-button-container");
        });
    </script>


    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>