# ✈️ Book-A-Flight Project Setup Guide

GitHub link: https://github.com/jaadzmiejaunis/book-a-flight-project-2

This guide provides step-by-step instructions for setting up and running the **Book-A-Flight Project 2** locally using **XAMPP**.

---

## 🛑 Prerequisites

Before you begin, ensure you have the following software installed:

* **XAMPP** (Make sure it is running!)
* **VS Code**
* **Python**
* **Git**

---

## 1. Project Cloning and Setup

This section covers setting up the file structure and cloning the repository.

### 1.1 Create the Project Directory

1.  Navigate to your XAMPP installation directory: `xampp/htdocs`
2.  Create a new folder named `college_project` inside `htdocs`.

### 1.2 Clone the Repository

1.  Go to the GitHub repository page.
2.  Click the **`< > Code`** button and copy the HTTPS or SSH link for cloning.
    
    ![Screenshot of GitHub Code button for cloning](https://github.com/user-attachments/assets/015a520f-fd1f-4dd3-a0f7-7ca5fe1a6ccf)
    
3.  Open your Git terminal (or VS Code terminal) and run the following command, replacing `[REPOSITORY_LINK]` with the link you copied. The image below shows where to find the link:
    
    ![Screenshot showing where to copy the repository link](https://github.com/user-attachments/assets/ae654f78-8216-4a18-b851-25494a81fe66)
    
    ```bash
    cd xampp/htdocs/college_project
    git clone [REPOSITORY_LINK]
    ```
    This will create the project folder (likely named `book-a-flight-project-2`) inside your `college_project` directory.

---

## 2. Database Configuration (phpMyAdmin)

You need to configure your database connection and create the necessary tables.

### 2.1 Check Database Credentials

1.  Examine the **`config.php`** file within the cloned project folder to confirm the required database connection details (database name, username, and password).
    
    ![Screenshot of config.php file contents](https://github.com/user-attachments/assets/d181815d-f3b0-4c3e-8a2c-104bc9620667)
    

### 2.2 Configure User Privileges

1.  Open **phpMyAdmin** in your browser (usually `localhost/phpmyadmin`).
2.  Go to the **"User accounts"** tab.
3.  Create or modify a user account with the privileges that match the credentials found in your `config.php` file.
    
    ![Image showing user accounts in phpMyAdmin](https://github.com/user-attachments/assets/73fa5515-9632-4dc2-83dd-caecf3532ddd)
    

### 2.3 Create Databases and Tables

1.  Open the file **`setup.php`** in your browser by navigating to the following URL:
    ```
    localhost/college_project/book-a-flight-project-2/setup.php
    ```
2.  Running this script will automatically create all the required **databases and tables** for the project.
    
    ![Screenshot of setup.php output in a browser](https://github.com/user-attachments/assets/f79af0db-8dd7-43ba-960f-2c96ce3a55c6)
    

---

## 3. Post-Setup Configuration

### 3.1 Set Contact Form Email

1.  Open the file **`send_contact_form.php`** in your code editor.
2.  **ONLY CHANGE** the email address within the `addAddress` function to the email where you want to receive contact inquiries.
    
    ![Screenshot highlighting addAddress in send_contact_form.php](https://github.com/user-attachments/assets/012bb95e-f2f3-416e-929d-6f8d80aff5d0)
    

### 3.2 PayPal Payment Test Account

For testing the PayPal payment functionality on **`book-a-flight.php`**, use the following test account:

| Field | Details |
| :--- | :--- |
| **Email** | `gyrozzeppeli@sierraflight.com` |
| **Password** | `gyrozzeppeli2025` |

---

## 4. Setting Admin Privileges

To access administrative features, you must manually assign the 'Admin' role to your user account in the database.

1.  Go to **phpMyAdmin**.
2.  Select the **`booking_db`** database.
3.  Click on the **`bookuser`** table.
4.  Find your user record and click **"Edit"**.
5.  Change the value in the **`book_user_roles`** column from 'Customer' to **'Admin'**.
    
    ![Screenshot showing editing book_user_roles to 'Admin' in phpMyAdmin](https://github.com/user-attachments/assets/948e3ab2-2c40-47e5-b40d-de24a8d006e3)
    

> **Note:** Once you are logged in as an Admin, you will be able to manage and toggle the roles of other users (Customer, Staff, and Admin) directly through the application interface.
