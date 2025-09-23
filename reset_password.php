<?php
// Start the session to be consistent with other pages
session_start();

// Include the database connection
include 'connection.php';

$message = "";
$error = "";

// These variables are needed for the header to maintain a consistent look.
$loggedIn = isset($_SESSION['book_id']);
$user_role = $loggedIn && isset($_SESSION['book_user_roles']) ? $_SESSION['book_user_roles'] : 'Guest';
$username = $loggedIn && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
$profilePictureUrl = $loggedIn && isset($_SESSION['profile_picture_url']) && !empty($_SESSION['profile_picture_url'])
    ? htmlspecialchars($_SESSION['profile_picture_url'])
    : '/college_project/book-a-flight-project-2/image_website/default_profile.png';
$siteTitle = 'SierraFlight';

// Check for token and email in the URL
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    $error = "Invalid password reset link.";
} else {
    $token = $_GET['token'];
    $email = $_GET['email'];

    // Check if the form was submitted to change the password
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate new password
        if (empty($new_password) || empty($confirm_password)) {
            $error = "Please enter and confirm your new password.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Find the token in the password_resets table
            $stmt = $connection->prepare("SELECT * FROM password_resets WHERE token = ? AND email = ? AND expires > NOW()");
            $stmt->bind_param("ss", $token, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Token is valid and not expired, proceed with password update
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the user's password in the BookUser table
                $stmt_update = $connection->prepare("UPDATE BookUser SET book_password = ? WHERE book_email = ?");
                $stmt_update->bind_param("ss", $hashed_password, $email);
                $stmt_update->execute();
                $stmt_update->close();

                // Delete the token so it can't be used again
                $stmt_delete = $connection->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt_delete->bind_param("s", $email);
                $stmt_delete->execute();
                $stmt_delete->close();

                $message = "Your password has been successfully reset! You can now log in with your new password.";
            } else {
                $error = "This password reset link is invalid or has expired.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
        .header-bar {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
        }
        .header-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1140px;
            margin: 0 auto;
            flex-wrap: wrap;
        }
        .header-bar .site-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            margin-right: auto;
            white-space: nowrap;
        }
        .header-bar .site-title:hover {
            text-decoration: underline;
        }
        .header-bar .user-info {
            display: flex;
            align-items: center;
            color: white;
            flex-shrink: 0;
            margin-left: auto;
            white-space: nowrap;
        }
        .header-bar .user-info a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .header-bar .user-info a:hover {
            text-decoration: underline;
        }
        .header-bar .profile-picture-nav,
        .header-bar .profile-icon-nav {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
            object-fit: cover;
            border: 1px solid white;
        }
        .header-bar .profile-icon-nav {
            border: none;
        }
        .header-bar .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            padding: .3rem .6rem;
            font-size: .95rem;
            line-height: 1.5;
            border-radius: .2rem;
            margin-left: 10px;
        }
        .header-bar .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .page-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            padding: 30px;
            background-color: #2a2a3e;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            color: #e0e0e0;
        }
        .login-container h2 {
            color: white;
        }
        .login-container .form-control {
            background-color: #3b3b55;
            border-color: #3b3b55;
            color: #e0e0e0;
        }
        .login-container .form-control::placeholder {
            color: #a0a0a0;
        }
        .login-container .form-control:focus {
            background-color: #3b3b55;
            border-color: #6a6a80;
            color: white;
            box-shadow: none;
        }
        .login-container .form-group label {
            color: #e0e0e0;
        }
        .login-container .btn-primary {
            background-image: linear-gradient(to right, #0D1164, #EA2264, #F78D60);
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: background-position 0.5s ease;
            background-size: 200% auto;
        }
        .login-container .btn-primary:hover {
            background-position: right center;
            color: white;
        }
        .error-message {
            color: #ff6347;
            text-align: center;
            margin-bottom: 15px;
        }
        .success-message {
            color: #7aff7a;
            text-align: center;
            margin-bottom: 15px;
        }
        .login-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #7289da;
        }
    </style>
</head>
<body>
    <div class="header-bar">
        <div class="container">
            <a href="index.php" class="site-title"><?php echo $siteTitle; ?></a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                    <a href="profile_page.php">
                        <span>Welcome, <?php echo $username; ?>!</span>
                        <img src="<?php echo $profilePictureUrl; ?>" alt="Profile Picture" class="profile-picture-nav">
                    </a>
                    <a class="btn btn-danger ml-2" href="log_out_page.php">Logout</a>
                <?php else: ?>
                    <a href="login_page.php" class="nav-link">Login/Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="container page-content">
        <div class="login-container">
            <h2>Set New Password</h2>
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <a href="forgot_password.php" class="login-link">Try again?</a>
            <?php elseif (!empty($message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
                <a href="login_page.php" class="login-link">Proceed to Login</a>
            <?php else: ?>
                <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>&email=<?php echo urlencode($email); ?>" method="post">
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Set New Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>