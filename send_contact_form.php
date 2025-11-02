<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['contact_error'] = 'Invalid request method.';
    header('Location: contact.php');
    exit;
}

// Sanitize and get form data
$name = htmlspecialchars(trim($_POST['name'] ?? ''));
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
$message = htmlspecialchars(trim($_POST['message'] ?? ''));

// Validate data
if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($subject) || empty($message)) {
    $_SESSION['contact_error'] = 'Please fill out all fields correctly.';
    header('Location: contact.php');
    exit;
}

// --- Send Email using your Brevo settings from send_forgot_password.php ---
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = '95a251001@smtp-brevo.com';
    $mail->Password   = '4O8mhY3Tq5pKaVjz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // --- Email Content ---
    
    // Set FROM (This MUST be your verified Brevo email)
    $mail->setFrom('akasukma0@gmail.com', 'SierraFlight Contact Form');
    
    // Set TO (This is you, the admin, receiving the message)
    $mail->addAddress('jaadzmiejaunis05@gmail.com'); 
    
    // Set Reply-To (This adds the user's email so you can hit "Reply")
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'New Contact Form Message: ' . $subject;
    
    // Create a nice HTML body for the email
    $mail->Body    = "<div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                        <h2>New Message from SierraFlight Contact Form</h2>
                        <p><strong>From:</strong> " . $name . "</p>
                        <p><strong>Email:</strong> " . $email . "</p>
                        <p><strong>Subject:</strong> " . $subject . "</p>
                        <hr>
                        <h3>Message:</h3>
                        <p style='background-color: #f4f4f4; padding: 15px; border-radius: 5px;'>" 
                            . nl2br($message) . 
                        "</p>
                    </div>";
    
    // Plain text version
    $mail->AltBody = "New Message from SierraFlight Contact Form\n\nFrom: " . $name . " (" . $email . ")\nSubject: " . $subject . "\n\nMessage:\n" . $message;

    $mail->send();
    
    // Success! Redirect back with a success message
    $_SESSION['contact_success'] = 'Thank you! Your message has been sent successfully.';
    header('Location: contact.php');
    exit;

} catch (Exception $e) {
    error_log("Contact Form Mailer Error: {$mail->ErrorInfo}");
    $_SESSION['contact_error'] = 'Your message could not be sent. Please try again later.';
    header('Location: contact.php');
    exit;
}
?>