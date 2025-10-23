<?php
// Enable error reporting for debugging
ini_set('display_errors', 0); // don't echo errors
ini_set('log_errors', 1);     // log them instead
error_reporting(E_ALL);

session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// This is the correct way to load PHPMailer
require 'vendor/autoload.php';

// Include the database connection file
include 'connection.php';

// Check if the database connection was successful
if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

try{
    $stmt = $connection->prepare("SELECT book_id FROM BookUser WHERE book_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        date_default_timezone_set('UTC');
        $expires = date("Y-m-d H:i:s", time() + 3600);
        
        $delete_stmt = $connection->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete_stmt->bind_param("s", $email);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        $insert_stmt = $connection->prepare("INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $email, $token, $expires);

        if ($insert_stmt->execute()) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp-relay.brevo.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = '95a251001@smtp-brevo.com';
                $mail->Password   = '4O8mhY3Tq5pKaVjz';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
            
                // The from address should match the one that is verified on your Brevo account.
                $mail->setFrom('akasukma0@gmail.com', 'SierraFlight Confirm Reset Password.');
                $mail->addAddress($email);
            
                // This is the corrected line for the reset link
                $reset_link = "http://localhost/college_project/book-a-flight-enhanced/book-a-flight-project-2/reset_password.php?token= " . $token . "&email=" . urlencode($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = 'Hello, a password reset was requested for your account. To reset your password, please click the following link: <a href="' . htmlspecialchars($reset_link) . '">Reset Password</a>';
                $mail->AltBody = 'Hello, a password reset was requested for your account. To reset your password, please copy and paste the following link into your browser: ' . htmlspecialchars($reset_link) ;
            
                $mail->send();
                echo json_encode(['success' => true, 'message' => 'A password reset link has been sent to your email.']);
            
            } catch (Exception $e) {
                error_log("Mailer Error: {$mail->ErrorInfo}");
                echo json_encode(['success' => false, 'message' => 'Unable to send reset email. Please try again later.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save reset token. Please try again.']);
        }
        $insert_stmt->close();
    } else {
        echo json_encode(['success' => true, 'message' => 'If this email address is in our system, a password reset link will be sent to it.']);
    }
} catch(Throwable $e) {
    error_log($e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Internal server error.']);
    exit;
}

$connection->close();
?>