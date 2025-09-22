<?php
session_start(); // Start the session

// Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['book_id'])) {
    header('Location: login_page.php');
    exit();
}

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    error_log("Database connection failed in profile_update_page.php: " . mysqli_connect_error());
    $_SESSION['profile_error_message'] = "An error occurred connecting to the database. Please try again later.";
    header('Location: profile_page.php');
    exit();
}
// --- End Database Connection ---

// Get the logged-in user's ID from the session
$user_id = $_SESSION['book_id'];

// Initialize errors and success messages arrays
$errors = [];
$success_message = '';

// --- Process Profile Update Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    // --- Validation ---
    if (empty($username)) {
        $errors[] = "Username cannot be empty.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }

    // Initialize variables for the database update
    $new_profile_path = null;

    // --- Handle Profile Picture Upload ---
    // Check if a file was uploaded and there were no errors
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_type = $_FILES['profile_picture']['type'];

        // Get file extension
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file type and size
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
        if ($file_size > 5000000) { // 5MB maximum file size
            $errors[] = "File size exceeds the maximum limit of 5MB.";
        }

        // If no errors, process the upload
        if (empty($errors)) {
            // Define the web-accessible upload directory
            $upload_dir = 'image_website/profile_pictures/';
            
            // Generate a unique file name to prevent overwrites and security issues
            $new_file_name = uniqid('profile_', true) . '.' . $file_extension;
            $destination_path = $upload_dir . $new_file_name;

            // Attempt to move the uploaded file
            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                $new_profile_path = $destination_path;
                $success_message .= "Profile picture updated successfully. ";

                // Update the session variable with the new path
                $_SESSION['profile_picture_url'] = $new_profile_path;

            } else {
                $errors[] = "Failed to upload the profile picture.";
                error_log("Failed to move uploaded file to: " . $destination_path);
            }
        }
    }

    // If there are no validation errors, proceed with the database update
    if (empty($errors)) {
        // Build the SQL query dynamically
        $sql = "UPDATE BookUser SET book_username = ?, book_email = ?";
        if ($new_profile_path !== null) {
            $sql .= ", book_profile = ?";
        }
        $sql .= " WHERE book_id = ?";

        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt) {
            // Bind parameters based on whether a new profile picture was uploaded
            if ($new_profile_path !== null) {
                mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $new_profile_path, $user_id);
            } else {
                mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $user_id);
            }

            if (mysqli_stmt_execute($stmt)) {
                $success_message .= "Profile details updated successfully.";
                // Update the username in the session so it's consistent across pages
                $_SESSION['username'] = $username;
            } else {
                error_log("Database update failed for user ID " . $user_id . ": " . mysqli_error($connection));
                $errors[] = "An error occurred updating your profile. Please try again.";
            }

            mysqli_stmt_close($stmt);
        } else {
            error_log("Database prepare error in profile_update_page.php: " . mysqli_error($connection));
            $errors[] = "An internal error occurred during profile update preparation.";
        }
    }

    // Store messages in the session before redirecting
    if (!empty($errors)) {
        $_SESSION['profile_error_message'] = implode("<br>", $errors);
    }
    if (!empty($success_message)) {
        $_SESSION['profile_success_message'] = $success_message;
    }

    // Redirect back to the profile page
    header('Location: profile_page.php');
    exit();
}

// If accessed directly without POST, redirect to profile page
header('Location: profile_page.php');
exit();
?>