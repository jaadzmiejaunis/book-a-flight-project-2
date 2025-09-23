<?php
session_start(); // Start the session

// Initialize variables for messages and form values
$errors = [];
$success_message = "";
$username_value = "";
$email_value = "";

// --- Database Connection (Consider moving credentials out of this file for security) ---
// In a production environment, store credentials in a configuration file outside the web root.
include 'connection.php';

// Check connection
if (!$connection) {
    // In a real application, log the error and show a user-friendly message, not the technical error
    die("Database connection failed: " . mysqli_connect_error());
}
// --- End Database Connection ---
// --- Process Sign Up Form Submission ---
if (isset($_POST['signup'])) {
    // Sanitize and get form data
    // Use htmlspecialchars to prevent XSS when displaying values back in form
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password']; // Password will be hashed, so no direct sanitization needed here
    $confirm_password = $_POST['confirm_password'];
    $robot_check = $_POST['robot_check'] ?? ''; // Get the value from the hidden field

    // Retain submitted values in case of errors
    $username_value = $username;
    $email_value = $email;

    // --- Validation ---
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    // You might want to add length constraints or allowed characters for username

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (strpos($email, '@gmail.com') === false) {
        // This check is very restrictive; consider if you truly only want Gmail
        $errors[] = "Only Gmail addresses are allowed.";
    } else {
        // Check if email already exists in the database (important for sign-up)
        $check_email_sql = "SELECT book_id FROM BookUser WHERE book_email = ?";
        $stmt_check_email = mysqli_prepare($connection, $check_email_sql);
        mysqli_stmt_bind_param($stmt_check_email, "s", $email);
        mysqli_stmt_execute($stmt_check_email);
        mysqli_stmt_store_result($stmt_check_email);

        if (mysqli_stmt_num_rows($stmt_check_email) > 0) {
            $errors[] = "This email address is already registered.";
        }
        mysqli_stmt_close($stmt_check_email);
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    // Consider adding complexity requirements (uppercase, number, special char)

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Basic "Are you a robot" check (reliant on JavaScript)
    if ($robot_check !== 'human') {
        $errors[] = "Please confirm you are not a robot.";
    }
    // --- End Validation ---


    // --- Database Insertion (Only if there are no validation errors) ---
    if (empty($errors)) {
        // Hash the password securely
        // echo "DEBUG (Sign-up): Raw password BEFORE hashing: '" . $password . "'<br>"; // TEMPORARY DEBUG - REMOVE IN PRODUCTION
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Define the default user role and status
        $user_role = 'Customer';
        $user_status = 'Active';

        // SQL to insert user data including the default role and status
        // Added 'book_user_roles' and 'book_user_status' to the INSERT statement
        $insert_sql = "INSERT INTO BookUser (book_username, book_password, book_email, book_user_roles, book_user_status) VALUES (?, ?, ?, ?, ?)";

        // Prepare the statement
        $stmt_insert = mysqli_prepare($connection, $insert_sql);

        // Check if statement preparation was successful
        if ($stmt_insert) {
            // Bind parameters (s = string)
            // Added two 's' for the new parameters 'book_user_roles' and 'book_user_status'
            mysqli_stmt_bind_param($stmt_insert, "sssss", $username, $hashed_password, $email, $user_role, $user_status);

            // Execute the statement
            if (mysqli_stmt_execute($stmt_insert)) {
                // Insertion successful
                // In a real application, you'd typically send a confirmation email here
                $success_message = "Sign up successful! Please check your Gmail inbox for a confirmation link.";

                // Redirect to the sign-up success page
                header('Location: signup_success.php');
                exit(); // Important: stop further script execution after redirect

            } else {
                // Insertion failed
                // Log the error (e.g., to a file) and show a generic error message to the user
                error_log("Database insert error: " . mysqli_error($connection)); // Log the error
                $errors[] = "An error occurred during registration. Please try again later.";
            }

            // Close the statement
            mysqli_stmt_close($stmt_insert);

        } else {
            // Prepared statement failed
            error_log("Database prepare error: " . mysqli_error($connection)); // Log the error
            $errors[] = "An internal error occurred. Please try again later.";
        }
    }
    // --- End Database Insertion ---
}
// --- End Process Sign Up Form Submission ---

// --- Close Database Connection (After all processing is done) ---
mysqli_close($connection);
// --- End Close Database Connection ---

// Variables for header (Guest state as user is not logged in yet)
$loggedIn = false;
$username = 'Guest';
$defaultProfilePicture = 'path/to/default-profile-picture.png'; // <<<--- UPDATE THIS PATH
$profilePictureUrl = $defaultProfilePicture;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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

        .top-gradient-bar {
            background-image: linear-gradient(to right, #3b2e8b, #ffb03a);
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
        }
         .top-gradient-bar .site-title:hover {
              text-decoration: underline;
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
             display: flex;
             align-items: center;
             justify-content: center;
        }

        .signup-container {
            max-width: 400px;
            padding: 30px;
            background-color: #282b3c;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
             color: #e0e0e0;
        }

        .signup-container h2 {
            color: white;
        }

        .signup-container .form-control {
             background-color: #3a3e52;
             border-color: #3a3e52;
             color: #e0e0e0;
         }
          .signup-container .form-control::placeholder {
             color: #a0a0a0;
          }
          .signup-container .form-control:focus {
              background-color: #3a3e52;
              border-color: #ffb03a;
              color: white;
              box-shadow: none;
          }

         .signup-container .form-group label {
             color: #e0e0e0;
         }

        .signup-container .btn-primary {
             background-image: linear-gradient(to right, #ffb03a, #dd5b12, #3b2e8b);
             border: none;
             color: white;
             padding: 10px 20px;
             font-size: 1rem;
             border-radius: 5px;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
             transition: background-position 0.5s ease;
             background-size: 200% auto;
        }
        .signup-container .btn-primary:hover {
             background-position: right center;
             color: white;
        }
         .signup-container .btn-primary:focus {
             box-shadow: 0 0 0 0.2rem rgba(255, 176, 58, 0.5);
         }

        .error-message {
            color: #dc3545;
            margin-top: 10px;
            text-align: center;
        }
        .success-message {
            color: #28a745;
            margin-top: 10px;
            text-align: center;
        }

         .captcha-container label {
             font-weight: normal;
             color: #e0e0e0;
         }
         .captcha-container .form-check-input {
         }

        .signup-container p {
             color: #e0e0e0;
         }
         .signup-container p a {
             color: #ffb03a;
         }
          .signup-container p a:hover {
               text-decoration: underline;
          }

        
        .d-flex {
            display: flex;
            align-items: center;
        }

        .d-flex .form-control {
            flex-grow: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .d-flex .btn {
            text-align: center;
            height: 37px;
            margin-left: 5px;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            font-size: 0.8rem;
            line-height: 1;
            padding: 10px 12px;
        }
    </style>
</head>
<body>

    <div class="top-gradient-bar">
        <div class="container"> <a href="index.php" class="site-title">BookAFlight.com</a>
            <div class="user-info">
                <?php if ($loggedIn): ?>
                     <a href="profile_page.php">
                         Profile
                         <?php if ($profilePictureUrl === $defaultProfilePicture): ?>
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
                        <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_a_flight.php">Book a Flight</a>
                    </li>
                     <?php if ($loggedIn): ?>
                     <li class="nav-item">
                         <a class="nav-link" href="profile_page.php">Profile</a>
                     </li>
                      <li class="nav-item">
                         <a class="nav-link" href="booking_history.php">Check Book</a>
                     </li>
                     <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container page-content">
        <div class="signup-container">
            <h2 class="text-center mb-4">Sign Up</h2>
            <?php
                // Display errors if any
                if (!empty($errors)) {
                    echo "<div class='error-message'>";
                    foreach ($errors as $error) {
                        echo "<p>" . htmlspecialchars($error) . "</p>"; // Use htmlspecialchars when outputting errors
                    }
                    echo "</div>";
                }
                // Display success message if set
                if (!empty($success_message)) {
                    echo "<p class='success-message text-center'>" . htmlspecialchars($success_message) . "</p>";
                }
            ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="JohnDoe" required value="<?php echo $username_value; ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <div class="d-flex align-items-center">
                        <input type="email" class="form-control" id="email" name="email" placeholder="johndoe@gmail.com" required value="<?php echo $email_value; ?>">
                        <button class="btn btn-primary" type="button" name="getcodebutton" id="getcodebutton">Send</button>
                    </div>
                    <br>
                    <input type="text" class="form-control" id="codeinput" name="codeinput>" placeholder="Enter verification code" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                     <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group captcha-container">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="robot_checkbox">
                        <label class="form-check-label" for="robot_checkbox">I am not a robot</label>
                        <input type="hidden" id="robot_check_hidden" name="robot_check" value="">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" name="signup">Sign Up</button>
                <p class="mt-3 text-center">Already have an account? <a href="login_page.php">Log In</a></p>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
        // JS event listeners.
        document.addEventListener('DOMContentLoaded', function() { // if document is loaded..
            const getCodeButton = document.getElementById('getcodebutton'); // get the getcodebutton element.
            const emailInput = document.getElementById('email'); // get the email input element.
            const signupForm = document.querySelector('form'); // returns the first element (form) found within this document, which in this case, is the sign up submit form.
            const codeInput = document.getElementById('codeinput'); // get the code input element.

            let receivedCode = null;
            let timer;
            const initialTime = 30;

            getCodeButton.addEventListener('click', function(event){
                event.preventDefault(); // prevent this code from running when page loads.
                const email = emailInput.value;
                if(email.trim() === ''){ // if email is empty, stop.
                    alert('Please enter your email address first.')
                    return;
                }

                getCodeButton.disabled = true; // disable button
                let timeLeft = initialTime;
                getCodeButton.textContent = `Send (${timeLeft}s)`; // change getcodebutton button text.

                // getcodebutton text change & re-enabling.
                timer = setInterval(function() {
                    timeLeft--;
                    if(timeLeft>0) {
                        getCodeButton.textContent = `Send (${timeLeft}s)`;
                    } else {
                        clearInterval(timer);
                        getCodeButton.disabled = false;
                        getCodeCodeButton.textContent = 'Send';
                    }
                }, 1000);

                // fetch request to send_code.php.
                fetch('send_code.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `email=${encodeURIComponent(email)}` // attach email as the body to be sent to the php.
                })
                .then(response => response.json()) // get the json response and store it in response variable.
                .then(data => { // data now holds the json object from before, we can now use the received data.
                    if(data.success){ // refer to the success message from send_code.php.
                        receivedCode = data.code; // set receivedCode as the code message from send_code.php
                        alert(data.message + ' (Code for debugging: ' + receivedCode + ')'); // debugging purposes only, ignore.
                    } else {
                        // error handling.
                        alert('Error: ' + data.message);
                        clearInterval(timer);
                        getCodeButton.disabled = false;
                        getCodeButton.textContent = 'Send';
                    }
                })
                .catch(error => {
                    // catch any errors
                    console.error('Network Error:', error);
                    alert('An error occurred. Please try again.');
                    clearInterval(timer);
                    getCodeButton.disabled = false;
                    getCodeButton.textContent = 'Send';
                })
            })

            // Simple JavaScript to set the hidden field value when the checkbox is checked
            document.getElementById('robot_checkbox').addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('robot_check_hidden').value = 'human';
                } else {
                    document.getElementById('robot_check_hidden').value = ''; // Reset if unchecked
                }
            });

            // hijacks the form before calling the php script.
            signupForm.addEventListener('submit', function(event){
                const enteredCode = codeInput.value;

                // checks if code is empty or invalid.
                if(receivedCode === null || enteredCode != String(receivedCode)){
                    event.preventDefault();
                    codeInput.value = '';
                    alert('The verification code is incorrect. Please try again.');
                    return;
                }
            });
        })
    </script>
</body>
</html>