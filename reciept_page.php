<?php
session_start();
require 'config.php';
include 'connection.php';

if (!isset($_SESSION['book_id'])) {
    header('Location: login_page.php');
    exit();
}

$bookingId = $_GET['bookId'] ?? null;
if (!$bookingId) {
    header('Location: booking_history.php');
    exit();
}

$user_id = $_SESSION['book_id'];

// This corrected SQL query fetches the price data from the BookFlightPrice table
$sql = "SELECT
            s.book_id,
            s.book_class,
            s.book_airlines,
            s.booking_status,
            p.book_ticket_price,
            p.book_food_drink_price,
            p.book_total_price,
            pl.book_origin_state,
            pl.book_origin_country,
            pl.book_destination_state,
            pl.book_destination_country,
            pl.book_departure,
            pl.book_return,
            pa.book_no_adult,
            pa.book_no_children,
            pa.book_food_drink
        FROM
            BookFlightStatus s
        JOIN
            BookFlightPrice p ON s.book_id = p.book_id
        JOIN
            BookFlightPlace pl ON s.book_id = pl.book_id
        JOIN
            BookFlightPassenger pa ON s.book_id = pa.book_id
        WHERE
            s.book_id = ? AND s.user_id = ?";

$stmt = $connection->prepare($sql);
$stmt->bind_param("si", $bookingId, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "<p style='color: white;'>Receipt not found.</p>";
    exit();
}

// Format the date for display
$departureDate = date('d-m-Y', strtotime($booking['book_departure']));
$returnDate = $booking['book_return'] ? date('d-m-Y', strtotime($booking['book_return'])) : 'N/A';

// Calculate total passengers
$totalPassengers = $booking['book_no_adult'] + $booking['book_no_children'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #1a1a2e;
            color: #e0e0e0;
            font-family: sans-serif;
        }
        .receipt-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #282844;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.6);
            border: 1px solid #3a3a5a;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #ffb03a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .receipt-header h2 {
            color: #ffb03a;
            font-weight: bold;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .receipt-details h5, .receipt-summary h5 {
            color: #e0e0e0;
            margin-top: 15px;
            font-weight: 600;
        }
        .receipt-details p {
            background-color: #555577;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .receipt-details .sub-info {
            font-size: 0.9em;
            color: #cccccc;
        }
        .receipt-summary {
            border-top: 2px solid #3a3a5a;
            padding-top: 20px;
        }
        .receipt-summary .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .total-price {
            font-size: 1.5em;
            font-weight: bold;
            color: #ffb03a;
            border-top: 1px dashed #6a6a8a;
            padding-top: 10px;
            margin-top: 15px;
        }
        .back-button-container {
            text-align: center;
            margin-top: 20px;
        }
        .btn-back {
            background-color: #ffb03a;
            color: #1a1a2e;
            font-weight: bold;
            border-radius: 8px;
            padding: 10px 20px;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #e09e2a;
            text-decoration: none;
        }
        
        @media print {
            body {
                background-color: #fff;
                color: #000;
            }
            .top-gradient-bar,
            .navbar,
            .back-button-container,
            .btn {
                display: none;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .receipt-header h2 {
                color: #000;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h2>Booking Receipt</h2>
            <p>Booking ID: <?php echo htmlspecialchars($booking['book_id']); ?></p>
        </div>

        <div class="receipt-details">
            <h5>Flight Details</h5>
            <p><strong>From:</strong> <?php echo htmlspecialchars($booking['book_origin_state'] . ', ' . $booking['book_origin_country']); ?></p>
            <p><strong>To:</strong> <?php echo htmlspecialchars($booking['book_destination_state'] . ', ' . $booking['book_destination_country']); ?></p>
            <p><strong>Departure Date:</strong> <?php echo htmlspecialchars($departureDate); ?></p>
            <p><strong>Return Date:</strong> <?php echo htmlspecialchars($returnDate); ?></p>
            <p><strong>Airline:</strong> <?php echo htmlspecialchars($booking['book_airlines']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($booking['book_class']); ?></p>
        </div>

        <div class="receipt-details">
            <h5>Passenger Details</h5>
            <p><strong>Adults:</strong> <?php echo htmlspecialchars($booking['book_no_adult']); ?></p>
            <p><strong>Children:</strong> <?php echo htmlspecialchars($booking['book_no_children']); ?></p>
            <p><strong>Total Passengers:</strong> <?php echo htmlspecialchars($totalPassengers); ?></p>
            <p><strong>Food & Drinks:</strong> <?php echo htmlspecialchars($booking['book_food_drink']); ?></p>
        </div>

        <div class="receipt-summary">
            <h5>Price Breakdown</h5>
            <div class="item">
                <span>Ticket Price:</span>
                <span>RM <?php echo htmlspecialchars(number_format($booking['book_ticket_price'], 2)); ?></span>
            </div>
            <div class="item">
                <span>Food & Drink Price:</span>
                <span>RM <?php echo htmlspecialchars(number_format($booking['book_food_drink_price'], 2)); ?></span>
            </div>
            <div class="item total-price">
                <span>Total Price:</span>
                <span>RM <?php echo htmlspecialchars(number_format($booking['book_total_price'], 2)); ?></span>
            </div>
        </div>
        
        <div class="back-button-container">
            <a href="booking_history.php" class="btn-back">Back to Booking History</a>
            <button onclick="window.print()" class="btn btn-primary ml-2">Print Receipt</button>
        </div>
    </div>
</body>
</html>