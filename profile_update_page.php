<?php
session_start(); // Start the session

// Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['book_id'])) { // Corrected session variable
    header('Location: login_page.php'); // Replace 'login_page.php' with your actual login page file
    exit();
}

// --- Database Connection ---
// In a production environment, store credentials in a configuration file outside the web root.
include 'connection.php';

// Check connection
if (!$connection) {
    // Log the error to the server logs
    error_log("Database connection failed in profile_update_page.php: " . mysqli_connect_error());
    $_SESSION['profile_error_message'] = "An error occurred connecting to the database. Please try again later.";
    // Redirect back to the profile page
    header('Location: profile_page.php');
    exit();
}
// --- End Database Connection ---

// Get the logged-in user's ID from the session
$user_id = $_SESSION['book_id']; // Corrected session variable

// Initialize errors array
$errors = [];

// Define the web-accessible base path for your project within htdocs
// Based on your screenshot: htdocs/college_project/book-a-flight-project/
$webAccessibleBasePath = '/college_project/book-a-flight-project/'; // **ADDED LINE**

// Define the upload directory (must be writable by the web server)
// This path is relative to where profile_update_page.php is located
$uploadDirectory = 'image_website/profile_pictures/';

// Define the default profile picture path (web-accessible) - Used for deleting old files
$defaultProfilePicture = $webAccessibleBasePath . 'image_website/default_profile.png'; // **UPDATED LINE**


// --- Process Profile Update Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Debugging Start ---
    error_log("--- Profile Update Debug Start ---");
    error_log("User ID: " . $user_id);
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));
    // --- Debugging End ---


    // Retrieve and sanitize form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Sanitize inputs
    $username = htmlspecialchars($username);
    $email = htmlspecialchars($email);

    // Ensure the upload directory exists (relative to the script)
    if (!is_dir($uploadDirectory)) {
        // Attempt to create the directory
        if (!mkdir($uploadDirectory, 0755, true)) {
            $errors[] = "Error creating upload directory. Please contact the administrator.";
            error_log("Failed to create upload directory: " . $uploadDirectory);
        } else {
             error_log("Upload directory created: " . $uploadDirectory);
        }
    }

    $newProfilePicturePath = null; // Initialize profile picture path to null

    // --- Handle Profile Picture Upload ---
    // Only process if no directory creation errors occurred and a file was actually uploaded
    if (empty($errors) && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];

        // Basic file validation (add more robust validation for production)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            error_log("Invalid file type uploaded: " . $file['type']);
        }

        if ($file['size'] > $maxFileSize) {
            $errors[] = "File size exceeds the maximum limit of 5MB.";
            error_log("File size exceeded limit: " . $file['size']);
        }

        // If no file-specific errors, proceed with upload
        if (empty($errors)) {
            // Generate a unique filename to prevent overwriting and conflicts
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uniqueFileName = uniqid('profile_', true) . '.' . $fileExtension;
            $targetFilePath = $uploadDirectory . $uniqueFileName; // Filesystem path relative to script

             error_log("Attempting to move file to (relative path): " . $targetFilePath);

            // Move the uploaded file to the target directory
            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                // File uploaded successfully
                error_log("File moved successfully!");
                // Store the web-accessible path in the database and session
                // Construct the full web-accessible path using the base path
                $newProfilePicturePath = $webAccessibleBasePath . $uploadDirectory . $uniqueFileName; // **MODIFIED LINE**
                error_log("New profile picture path (DB/Web): " . $newProfilePicturePath);


                // Optional: Delete the old profile picture file if it exists and is not the default
                $sql_get_old_profile = "SELECT book_profile FROM BookUser WHERE book_id = ?";
                $stmt_get_old = mysqli_prepare($connection, $sql_get_old_profile);
                if ($stmt_get_old) {
                     mysqli_stmt_bind_param($stmt_get_old, "i", $user_id);
                     mysqli_stmt_execute($stmt_get_old);
                     $result_old = mysqli_stmt_get_result($stmt_get_old);
                     if ($row_old = mysqli_fetch_assoc($result_old)) {
                         $oldProfilePath = $row_old['book_profile'];
                         error_log("Old profile path from DB: " . $oldProfilePath);

                         // Check if the old path exists, is not empty, is not the default, and the file exists on the server
                         // We need to reconstruct the filesystem path for deletion based on the stored web path
                         $filesystemOldPathForDeletion = $_SERVER['DOCUMENT_ROOT'] . $oldProfilePath; // Assuming $oldProfilePath is relative to web root
                         // If your project is in a subdirectory like this, you might need a more complex conversion:
                         // $filesystemOldPathForDeletion = __DIR__ . '/' . str_replace($webAccessibleBasePath, '', $oldProfilePath); // More complex if $oldProfilePath wasn't relative to web root


                         error_log("Filesystem old path for deletion check: " . $filesystemOldPathForDeletion);
                         // Add a safety check to ensure we are only deleting within the intended upload directory
                         $fullUploadDirectory = realpath($uploadDirectory); // Get absolute path of upload directory
                         $fullOldFilePath = realpath($filesystemOldPathForDeletion); // Get absolute path of old file

                         if ($fullUploadDirectory && $fullOldFilePath && strpos($fullOldFilePath, $fullUploadDirectory) === 0) {
                             if (!empty($oldProfilePath) && $oldProfilePath !== $defaultProfilePicture && file_exists($filesystemOldPathForDeletion)) {
                                   error_log("Deleting old profile file: " . $filesystemOldPathForDeletion);
                                   unlink($filesystemOldPathForDeletion); // Delete the old file
                              } else {
                                error_log("Old profile file not found, is default, or invalid path, not deleting.");
                              }
                         } else {
                             error_log("Old profile path is outside the designated upload directory, not deleting.");
                         }

                     }
                     mysqli_stmt_close($stmt_get_old);
                }

            } else {
                // Error moving file
                error_log("Error moving uploaded file for user ID " . $user_id . ". PHP Error code: " . $_FILES['profile_picture']['error']);
                $errors[] = "Error uploading profile picture. Please try again.";
            }
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors (e.g., file size exceeds server limits)
        $upload_error_code = $_FILES['profile_picture']['error'];
        $error_message_text = "Unknown file upload error.";
        switch ($upload_error_code) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message_text = "Uploaded file exceeds maximum size allowed by PHP.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message_text = "Uploaded file exceeds maximum size specified in form.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message_text = "File upload was only partially completed.";
                break;
             case UPLOAD_ERR_NO_TMP_DIR:
                 $error_message_text = "Missing a temporary folder for uploads.";
                 break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message_text = "Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message_text = "A PHP extension stopped the file upload.";
                break;
        }
        $errors[] = "Profile picture upload failed: " . $error_message_text;
        error_log("File upload error for user ID " . $user_id . ": " . $error_message_text . " (Code: " . $upload_error_code . ")");

    } else {
         error_log("No new profile picture file was uploaded.");
    }


    // --- Database Update (Only if there are no validation or upload errors) ---
    if (empty($errors)) {
        // Build the SQL query dynamically based on whether a new profile picture was uploaded
        if ($newProfilePicturePath !== null) {
            $sql_update = "UPDATE BookUser SET book_username = ?, book_email = ?, book_profile = ? WHERE book_id = ?";
            $stmt_update = mysqli_prepare($connection, $sql_update);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "sssi", $username, $email, $newProfilePicturePath, $user_id); // s=string, i=integer
                 error_log("Update query (with picture): " . $sql_update);
                 error_log("Bound params: username=" . $username . ", email=" . $email . ", book_profile=" . $newProfilePicturePath . ", book_id=" . $user_id);
            }
        } else {
            // No new profile picture uploaded, only update username and email
            $sql_update = "UPDATE BookUser SET book_username = ?, book_email = ? WHERE book_id = ?";
            $stmt_update = mysqli_prepare($connection, $sql_update);
             if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "ssi", $username, $email, $user_id); // s=string, i=integer
                 error_log("Update query (no picture): " . $sql_update);
                 error_log("Bound params: username=" . $username . ", email=" . $email . ", book_id=" . $user_id);
             }
        }


        if ($stmt_update) {
            // Execute the update statement
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['profile_success_message'] = "Profile updated successfully!";
                error_log("Database update successful.");

                // Update session variables after successful update
                $_SESSION['username'] = $username;
                // Update the session profile picture URL
                if ($newProfilePicturePath !== null) {
                     $_SESSION['profile_picture_url'] = $newProfilePicturePath;
                     error_log("Session profile_picture_url updated: " . $_SESSION['profile_picture_url']);
                } else {
                     // If no new picture uploaded, ensure session reflects current DB value
                     // This query is already done in profile_page.php, but doing it here
                     // ensures session is fresh *after* update in case only name/email changed
                      $sql_get_current_profile = "SELECT book_profile FROM BookUser WHERE book_id = ?";
                      $stmt_get_current = mysqli_prepare($connection, $sql_get_current_profile);
                      if ($stmt_get_current) {
                           mysqli_stmt_bind_param($stmt_get_current, "i", $user_id);
                           mysqli_stmt_execute($stmt_get_current);
                           $result_current = mysqli_stmt_get_result($stmt_get_current);
                           if ($row_current = mysqli_fetch_assoc($result_current)) {
                                // Use the default picture if the value is empty in the DB after the update
                                $_SESSION['profile_picture_url'] = htmlspecialchars($row_current['book_profile'] ?? $defaultProfilePicture);
                                 error_log("Session profile_picture_url updated from DB (no new upload): " . $_SESSION['profile_picture_url']);
                           }
                           mysqli_stmt_close($stmt_get_current);
                      }

                }


            } else {
                // Update failed
                error_log("Database update failed for user ID " . $user_id . ": " . mysqli_error($connection));
                $errors[] = "An error occurred updating your profile. Please try again.";
            }

            // Close the prepared statement
            mysqli_stmt_close($stmt_update);
        } else {
            // Prepared statement failed
             error_log("Database prepare error in profile_update_page.php: " . mysqli_error($connection));
             $errors[] = "An internal error occurred during profile update preparation.";
        }
    }

    // If there are errors, store them in the session
    if (!empty($errors)) {
        $_SESSION['profile_error_message'] = implode("<br>", $errors); // Combine errors into a single message
        error_log("Errors generated: " . print_r($errors, true));
    }

    // --- Debugging End ---
    error_log("--- Profile Update Debug End ---");


    // Redirect back to the profile page regardless of success or failure
    header('Location: profile_page.php');
    exit();

} else {
    // If accessed directly without POST, redirect to profile page
    header('Location: profile_page.php');
    exit();
}
// --- End Process Profile Update Form Submission ---

// Close database connection
if ($connection) {
    mysqli_close($connection);
}
?>