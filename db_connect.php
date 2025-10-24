<?php
// CRITICAL: UPDATE THESE VALUES FOR YOUR LIVE HOSTING ENVIRONMENT!
$servername = "localhost"; // Your database host (often 'localhost' or a specific address)
$username = "root"; // <-- CHANGE THIS
$password = ""; // <-- CHANGE THIS
$dbname = "mbets_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error internally but present a generic error to the user
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("❌ System Error: Database connection could not be established.");
}

// Note: The $conn variable is now available for use in any file that includes this one.
?>