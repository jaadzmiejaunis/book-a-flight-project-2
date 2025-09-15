<?php
// expects data sent is json format.
header('Content-Type: application/json');

// require phpmailer library. 
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// functions
function sendVerificationEmail($email, $verificationCode) {
    // phpmailer API settings
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;
        $mail->Username = '95a251001@smtp-brevo.com'; // using third-party brevo API
        $mail->Password = '4O8mhY3Tq5pKaVjz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('akasukma0@gmail.com', 'SierraFlight');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(false);
        $mail->Subject = "Email verification.";
        $mail->Body = "Your email verification code is: $verificationCode";
        $mail->send();

        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

function generateCode($length = 6){
    $characters = '0123456789';
    $code = '';
    for($i = 0; $i < $length; $i++){ // loops through length and appends random characters to the code variable.
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code; // return code variable.
}

// check if request is post request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email'] ?? ''); // get sent email input data OR null if no data is sent.

    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { // check if email is empty or not valid format.
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']); // send back data and exit.
        exit;
    }

    $verification_code = generateCode(); // generate random code.

    sendVerificationEmail($email, $verification_code); // send verification code to the email.

    // returns success data [].
    echo json_encode([
        'success' => true,
        'message' => 'Verification email sent.',
        'code' => $verification_code
    ]);
} else {
    // if request sent is NOT post request.
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

?>