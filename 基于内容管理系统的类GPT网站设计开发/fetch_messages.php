<?php
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "transapi";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch messages for the current session
if (isset($_SESSION['session_id'])) {
    $session_id = $_SESSION['session_id'];

    // Prepare SQL query to fetch messages
    $sql = "SELECT sender, message_text, created_at FROM messages WHERE session_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Array to hold messages
    $messages = [];

    // Fetch results into associative array
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'sender' => $row['sender'],
            'text' => $row['message_text'],
            'created_at' => $row['created_at']
        ];
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();

    // Return JSON response with messages
    header('Content-Type: application/json');
    echo json_encode(['messages' => $messages]);
} else {
    // Handle case where session ID is not set (should not happen in a proper setup)
    echo json_encode(['error' => 'Session ID not set.']);
}

?>
