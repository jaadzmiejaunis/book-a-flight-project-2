<?php
    require_once 'config.php';

    // Connection to MySQL server (No DB yet.)
    $connection = new mysqli($db_host, $db_user, $db_pass);

    if($connection->connect_error) {
        die("Connection failed: ". $connection->connect_error);
    }

    // Create DB
    $sql = "CREATE DATABASE IF NOT EXISTS ". $db_name;
    if($connection->query($sql) === TRUE){
        echo "Database ". $db_name. " created or already exists. <br>";
    } else {
        die("Error creating database: ". $connection->error);
    }

    $connection->select_db($db_name);

    // --- MODIFICATION: Removed salary columns ---
    $sql_create_bookuser = "CREATE TABLE IF NOT EXISTS BookUser (
        book_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        book_username VARCHAR(50) NOT NULL UNIQUE,
        book_password VARCHAR(255) NOT NULL,
        book_email VARCHAR(100) NOT NULL UNIQUE,
        book_profile VARCHAR(255) NULL,
        book_user_roles VARCHAR(50) NOT NULL,
        book_user_register_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        book_user_status VARCHAR(50) NOT NULL
    );";

    // IMPORTANT: Added UNIQUE to book_id to allow foreign key constraint
    $sql_create_bookflightstatus = "CREATE TABLE IF NOT EXISTS BookFlightStatus (
        status_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        book_id VARCHAR(50) NOT NULL UNIQUE,
        user_id INT(11) NOT NULL,
        book_username VARCHAR(50) NOT NULL,
        book_class VARCHAR(50) NOT NULL,
        book_airlines VARCHAR(100) NOT NULL,
        book_price DECIMAL(10, 2) NOT NULL,
        booking_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES BookUser(book_id) ON DELETE CASCADE
    );";

    $sql_create_bookflightplace = "CREATE TABLE IF NOT EXISTS BookFlightPlace (
        place_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        book_id VARCHAR(50) NOT NULL,
        user_id INT(11) NOT NULL,
        book_origin_state VARCHAR(100) NOT NULL,
        book_origin_country VARCHAR(100) NOT NULL,
        book_destination_state VARCHAR(100) NOT NULL,
        book_destination_country VARCHAR(100) NOT NULL,
        book_departure DATE NOT NULL,
        book_return DATE NULL,
        FOREIGN KEY (user_id) REFERENCES BookUser(book_id) ON DELETE CASCADE
    );";
    
    $sql_create_bookflightpassenger = "CREATE TABLE IF NOT EXISTS BookFlightPassenger (
        passenger_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        book_id VARCHAR(50) NOT NULL,
        user_id INT(11) NOT NULL,
        book_no_adult INT(11) NOT NULL,
        book_no_children INT(11) NOT NULL,
        book_food_drink VARCHAR(50) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES BookUser(book_id) ON DELETE CASCADE
    );";

    $sql_create_bookflightprice = "CREATE TABLE IF NOT EXISTS BookFlightPrice (
        price_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        book_id VARCHAR(50) NOT NULL,
        user_id INT(11) NOT NULL,
        book_ticket_price DECIMAL(10, 2) NOT NULL,
        book_food_drink_price DECIMAL(10, 2) NOT NULL,
        book_total_price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES BookUser(book_id) ON DELETE CASCADE
    );";
    
    $sql_create_bookhistory = "CREATE TABLE IF NOT EXISTS BookHistory (
        history_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        book_username VARCHAR(50) NOT NULL,
        book_origin_state VARCHAR(100) NOT NULL,
        book_origin_country VARCHAR(100) NOT NULL,
        book_destination_state VARCHAR(100) NOT NULL,
        book_destination_country VARCHAR(100) NOT NULL,
        book_departure DATE NOT NULL,
        book_return DATE NULL,
        book_class VARCHAR(50) NOT NULL,
        book_airlines VARCHAR(100) NOT NULL,
        book_price DECIMAL(10, 2) NOT NULL,
        booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        booking_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        FOREIGN KEY (user_id) REFERENCES BookUser(book_id) ON DELETE CASCADE
    );";

    $sql_create_bookreviews = "CREATE TABLE IF NOT EXISTS BookReviews (
        review_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        book_id VARCHAR(50) NOT NULL,
        rating INT(1) NOT NULL,
        comment TEXT,
        review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES BookUser(book_id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES BookFlightStatus(book_id) ON DELETE CASCADE
    );";

    $sql_create_password_resets = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires DATETIME NOT NULL,
        KEY (email),
        KEY (token)
    );";

    // --- MODIFICATION: Added earned_salary column ---
    $sql_create_staffsessions = "CREATE TABLE IF NOT EXISTS StaffSessions (
        session_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        login_time DATETIME NOT NULL,
        logout_time DATETIME NULL,
        duration_seconds INT(11) NULL,
        earned_salary DECIMAL(10, 2) NULL,
        FOREIGN KEY (user_id) REFERENCES BookUser(book_id) ON DELETE CASCADE
    );";

    // --- NEW TABLE: StaffDetails ---
    $sql_create_staffdetails = "CREATE TABLE IF NOT EXISTS StaffDetails (
        user_id INT(11) PRIMARY KEY,
        hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 10.00,
        total_salary DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (user_id) REFERENCES BookUser(book_id) ON DELETE CASCADE
    );";
    
    $queries = [
        'BookUser' => $sql_create_bookuser,
        'BookFlightStatus' => $sql_create_bookflightstatus,
        'BookFlightPlace' => $sql_create_bookflightplace,
        'BookFlightPassenger' => $sql_create_bookflightpassenger,
        'BookFlightPrice' => $sql_create_bookflightprice,
        'BookHistory' => $sql_create_bookhistory,
        'BookReviews' => $sql_create_bookreviews,
        'password_resets' => $sql_create_password_resets, 
        'StaffSessions' => $sql_create_staffsessions,
        'StaffDetails' => $sql_create_staffdetails // --- ADDED NEW TABLE ---
    ];
    
    foreach ($queries as $table_name => $sql_query) {
        if (mysqli_query($connection, $sql_query)) {
            echo "Create/Update {$table_name} table successfully <br>";
        } else {
            echo "Error creating/updating {$table_name} table: ". mysqli_error($connection). "<br>";
        }
    }
    
    
    mysqli_close($connection);
?>