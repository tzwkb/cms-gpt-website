<?php
// Start a session
session_start();

// Connect to the database
require_once 'shared/conn.php';  // Ensure this file correctly sets up the connection and selects the database

// Set character set to UTF-8
if (!$conn->set_charset("utf8")) {
    echo "Error loading character set utf8: " . $conn->error;
    exit();
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Collect input data from the form
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL to check if username or email already exists
    if ($stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?")) {
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "<script>alert('用户名或邮箱已存在');</script>";
        } else {
            // No user found, proceed with registration
            $stmt->close();  // Close the previous statement

            // Prepare SQL to insert new user
            if ($insert_stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)")) {
                $insert_stmt->bind_param("sss", $username, $password, $email);
                $insert_stmt->execute();

                if ($insert_stmt->affected_rows > 0) {
                    echo "<script>alert('注册成功');</script>";
                    header('Location: index.php');
                    exit();
                } else {
                    echo "<script>alert('注册失败');</script>";
                }
                $insert_stmt->close();
            } else {
                echo "Prepare failed: " . $conn->error;
            }
        }
    } else {
        echo "Prepare failed: " . $conn->error;
    }
    $conn->close();
}
?>
