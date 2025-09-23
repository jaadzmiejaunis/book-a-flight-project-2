<?php
    require_once 'config.php';

    // Connection to MySQL server (No DB yet.)
    $connection = new mysqli($db_host, $db_user, $db_pass);

    if($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    // Create DB
    $sql = "CREATE DATABASE IF NOT EXISTS " . $db_name;
    if($connection->query($sql) === TRUE){
        echo "Database " . $$db_name . " created or already exists. <br>";
    } else {
        die("Error creating database: " . $connection->error);
    }

    $connection->select_db($db_name);

    $sql = "CREATE TABLE IF NOT EXISTS BookUser (
        book_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        book_username VARCHAR(50) NOT NULL UNIQUE,
        book_password VARCHAR(255) NOT NULL, 
        book_email VARCHAR(100) NOT NULL UNIQUE,
        book_profile VARCHAR(255) NULL,
        book_user_roles VARCHAR(50) NOT NULL, 
        book_user_register_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        book_user_status VARCHAR(50) NOT NULL
    );";

    $sql_create_bookflight = "CREATE TABLE IF NOT EXISTS BookFlight (
    book_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    book_origin_state VARCHAR(100) NOT NULL,
    book_origin_country VARCHAR(100) NOT NULL,
    book_destination_state VARCHAR(100) NOT NULL,
    book_destination_country VARCHAR(100) NOT NULL,
    book_departure DATE NOT NULL,
    book_return DATE NULL, 
    book_class VARCHAR(50) NOT NULL,
    book_airlines VARCHAR(100) NOT NULL,
    book_price DECIMAL(10, 2) NOT NULL,
    book_user_register_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

    if(mysqli_query($connection, $sql)){
        echo "Create BookUser table successfully";
    }
    else{
        echo "Error creating table: " . mysqli_error($connection);
    }

    if(mysqli_query($connection, $sql_create_bookflight)){
        echo "Create BookFlight table successfully";
    }
    else{
        echo "Error creating table: " . mysqli_error($connection);
    }

    if(mysqli_query($connection, $sql_create_bookhistory)){
        echo "Create BookUser table successfully";
    }
    else{
        echo "Error creating table: " . mysqli_error($connection);
    }
    
    $connection->close();
?>