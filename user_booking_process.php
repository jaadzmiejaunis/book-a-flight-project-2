<?php
session_start();
require 'config.php';
include 'connection.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['book_id']) || !isset($_SESSION['username'])) {
        $response['message'] = 'User is not logged in.';
        echo json_encode($response);
        exit();
    }

    $userId = $_SESSION['book_id'];
    $username = $_SESSION['username'];
    $booking_status = 'Pending';
    
    $bookId = uniqid('BOOK-', true);

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        $response['message'] = 'No data received.';
        echo json_encode($response);
        exit();
    }
    
    // Log received data for debugging
    error_log("Received booking data: " . print_r($data, true));
    
    // Extract data with fallbacks
    $originState = $data['origin_state'] ?? 'Unknown';
    $originCountry = $data['origin_country'] ?? 'Unknown';
    $destinationState = $data['destination_state'] ?? 'Unknown';
    $destinationCountry = $data['destination_country'] ?? 'Unknown';
    $departureDate = $data['departure_date'] ?? null;
    $returnDate = $data['return_date'] ?? null;
    $travelClass = $data['travel_class'] ?? '';
    $airline = $data['airline'] ?? '';
    $noAdults = intval($data['no_of_adult'] ?? 0);
    $noChildren = intval($data['no_of_children'] ?? 0);
    $foodDrinks = $data['food_drinks'] ?? 'No';
    $bookTicketPrice = floatval($data['book_ticket_price'] ?? 0);
    $bookFoodDrinkPrice = floatval($data['book_food_drink_price'] ?? 0);
    $bookTotalPrice = floatval($data['book_total_price'] ?? 0);

    // Enhanced validation with detailed error reporting
    $missingFields = [];
    if (empty($originState) || $originState === 'Unknown') $missingFields[] = 'origin_state';
    if (empty($destinationState) || $destinationState === 'Unknown') $missingFields[] = 'destination_state';
    if (empty($departureDate)) $missingFields[] = 'departure_date';
    
    if (!empty($missingFields)) {
        error_log("Missing required fields: " . implode(', ', $missingFields));
        $response['message'] = 'Missing required flight information: ' . implode(', ', $missingFields);
        $response['debug'] = [
            'missing_fields' => $missingFields,
            'received_data' => [
                'originState' => $originState,
                'destinationState' => $destinationState,
                'departureDate' => $departureDate
            ]
        ];
        echo json_encode($response);
        exit();
    }

    // Begin database transaction to ensure all tables are updated or none are
    mysqli_begin_transaction($connection);
    
    try {
        // Prepare and execute statement for BookFlightStatus
        $stmt_status = $connection->prepare("INSERT INTO BookFlightStatus (book_id, user_id, book_username, book_class, book_airlines, book_price, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_status->bind_param("sisssds", $bookId, $userId, $username, $travelClass, $airline, $bookTotalPrice, $booking_status);
        $stmt_status->execute();
        $stmt_status->close();
        
        // Prepare and execute statement for BookFlightPlace
        $stmt_place = $connection->prepare("INSERT INTO BookFlightPlace (book_id, user_id, book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_place->bind_param("sissssss", $bookId, $userId, $originState, $originCountry, $destinationState, $destinationCountry, $departureDate, $returnDate);
        $stmt_place->execute();
        $stmt_place->close();
        
        // Prepare and execute statement for BookFlightPassenger
        $stmt_passenger = $connection->prepare("INSERT INTO BookFlightPassenger (book_id, user_id, book_no_adult, book_no_children, book_food_drink) VALUES (?, ?, ?, ?, ?)");
        $stmt_passenger->bind_param("siiis", $bookId, $userId, $noAdults, $noChildren, $foodDrinks);
        $stmt_passenger->execute();
        $stmt_passenger->close();
        
        // Prepare and execute statement for BookFlightPrice
        $stmt_price = $connection->prepare("INSERT INTO BookFlightPrice (book_id, user_id, book_ticket_price, book_food_drink_price, book_total_price) VALUES (?, ?, ?, ?, ?)");
        $stmt_price->bind_param("sidds", $bookId, $userId, $bookTicketPrice, $bookFoodDrinkPrice, $bookTotalPrice);
        $stmt_price->execute();
        $stmt_price->close();
        
        // Prepare and execute statement for BookHistory (consolidated view for user)
        $stmt_history = $connection->prepare("INSERT INTO BookHistory (user_id, book_username, book_origin_state, book_origin_country, book_destination_state, book_destination_country, book_departure, book_return, book_class, book_airlines, book_price, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_history->bind_param("isssssssssds", $userId, $username, $originState, $originCountry, $destinationState, $destinationCountry, $departureDate, $returnDate, $travelClass, $airline, $bookTotalPrice, $booking_status);
        $stmt_history->execute();
        $stmt_history->close();

        mysqli_commit($connection);
        $response['success'] = true;
        $response['message'] = 'Booking saved successfully!';
        $response['bookId'] = $bookId;

    } catch (Exception $e) {
        mysqli_rollback($connection);
        $response['message'] = "Database Transaction Failed: " . $e->getMessage();
        error_log($response['message']);
    } finally {
        if ($connection) {
            mysqli_close($connection);
        }
    }
    
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>