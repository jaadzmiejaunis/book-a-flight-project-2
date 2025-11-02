<?php
session_start(); // Start the session

// --- Database Connection ---
include 'connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}
// --- End Database Connection ---

// --- NEW: Staff Salary Calculation (Upgraded) ---
// Check if the user is a staff member and has an active session ID
if (isset($_SESSION['book_user_roles']) && $_SESSION['book_user_roles'] === 'Staff' && isset($_SESSION['staff_session_id'])) {
    
    $staff_user_id = $_SESSION['book_id'];
    $staff_session_id = $_SESSION['staff_session_id'];

    // 1. Get the staff's personal hourly rate from StaffDetails
    $sql_get_rate = "SELECT hourly_rate FROM StaffDetails WHERE user_id = ?";
    $stmt_rate = mysqli_prepare($connection, $sql_get_rate);
    mysqli_stmt_bind_param($stmt_rate, "i", $staff_user_id);
    mysqli_stmt_execute($stmt_rate);
    $result_rate = mysqli_stmt_get_result($stmt_rate);
    
    $hourly_rate = 10.00; // Default fallback rate
    if ($rate_row = mysqli_fetch_assoc($result_rate)) {
        $hourly_rate = $rate_row['hourly_rate'];
    }
    mysqli_stmt_close($stmt_rate);

    // 2. Update the StaffSessions table to mark logout_time and calculate duration
    $sql_end_session = "UPDATE StaffSessions 
                        SET logout_time = NOW(), 
                            duration_seconds = TIMESTAMPDIFF(SECOND, login_time, NOW())
                        WHERE session_id = ? AND user_id = ?";
    
    $stmt_end = mysqli_prepare($connection, $sql_end_session);
    mysqli_stmt_bind_param($stmt_end, "ii", $staff_session_id, $staff_user_id);
    mysqli_stmt_execute($stmt_end);
    
    if (mysqli_stmt_affected_rows($stmt_end) > 0) {
        // 3. Get the duration_seconds we just calculated
        $sql_get_duration = "SELECT duration_seconds FROM StaffSessions WHERE session_id = ?";
        $stmt_get = mysqli_prepare($connection, $sql_get_duration);
        mysqli_stmt_bind_param($stmt_get, "i", $staff_session_id);
        mysqli_stmt_execute($stmt_get);
        $result_duration = mysqli_stmt_get_result($stmt_get);
        
        if ($row = mysqli_fetch_assoc($result_duration)) {
            $duration_seconds = $row['duration_seconds'];
            
            // 4. Calculate salary earned this session using their personal rate
            $earned_salary = ($duration_seconds / 3600) * $hourly_rate;
            
            // 5. Update the StaffSessions table with the earned salary
            $sql_update_session_salary = "UPDATE StaffSessions SET earned_salary = ? WHERE session_id = ?";
            $stmt_session_salary = mysqli_prepare($connection, $sql_update_session_salary);
            mysqli_stmt_bind_param($stmt_session_salary, "di", $earned_salary, $staff_session_id);
            mysqli_stmt_execute($stmt_session_salary);
            mysqli_stmt_close($stmt_session_salary);

            // 6. Update the user's total salary in the StaffDetails table
            $sql_update_salary = "UPDATE StaffDetails 
                                  SET total_salary = total_salary + ? 
                                  WHERE user_id = ?";
            
            $stmt_salary = mysqli_prepare($connection, $sql_update_salary);
            mysqli_stmt_bind_param($stmt_salary, "di", $earned_salary, $staff_user_id);
            mysqli_stmt_execute($stmt_salary);
            mysqli_stmt_close($stmt_salary);
        }
        mysqli_stmt_close($stmt_get);
    }
    mysqli_stmt_close($stmt_end);
}
// --- END: Staff Salary Calculation ---

// --- Standard Logout Logic ---
// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Close the database connection
mysqli_close($connection);

// Redirect to the login page
header('Location: login_page.php');
exit();
?>