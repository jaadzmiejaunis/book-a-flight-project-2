<?php
session_start();
require 'config.php';
include 'connection.php';

if (!isset($_SESSION['book_id'])) {
    header('Location: login_page.php');
    exit();
}

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['book_id'];
    $bookId = $_POST['bookId'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $comment = $_POST['comment'] ?? '';

    // Validate the input
    if (empty($bookId) || empty($rating)) {
        $response['message'] = 'Missing required review information.';
        header('Location: review_page.php?bookId=' . urlencode($bookId) . '&error=' . urlencode($response['message']));
        exit();
    }

    try {
        $stmt = $connection->prepare("INSERT INTO BookReviews (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $userId, $bookId, $rating, $comment);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Review submitted successfully!';
            // Redirect to a success page or booking history
            header('Location: booking_history.php?success=1');
            exit();
        } else {
            $response['message'] = 'Failed to save review: ' . $stmt->error;
            error_log('Failed to save review: ' . $stmt->error);
            header('Location: review_page.php?bookId=' . urlencode($bookId) . '&error=' . urlencode($response['message']));
            exit();
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = "Database Transaction Failed: " . $e->getMessage();
        error_log($response['message']);
        header('Location: review_page.php?bookId=' . urlencode($bookId) . '&error=' . urlencode($response['message']));
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>