<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: dashboard.php");
    exit;
}

// 1. Sanitize and validate inputs
$type = filter_input(INPUT_POST, 'entry_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$date_entered = filter_input(INPUT_POST, 'date_entered', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$user_id = filter_input(INPUT_POST, 'logged_by_user_id', FILTER_VALIDATE_INT);

// Basic validation check
if (!$type || !$amount || !$description || !$date_entered || !$user_id) {
    $_SESSION['entry_feedback'] = "<div class='feedback error'>❌ ERROR: Invalid or missing form data. Please ensure all fields are correct.</div>";
    header("Location: dashboard.php");
    exit;
}

// 2. Prepare and execute the SQL statement
// IMPORTANT: SQL updated to include logged_by_user_id
$stmt = $conn->prepare("INSERT INTO warrants (type, amount, description, date_entered, logged_by_user_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sdssi", $type, $amount, $description, $date_entered, $user_id); 

if ($stmt->execute()) {
    $feedback_message = "<div class='feedback success'>✅ SUCCESS: New **$type** record logged successfully for **GHS " . number_format($amount, 2) . "** on $date_entered.</div>";
} else {
    // Log detailed error and show generic error to user
    error_log("Entry Insertion Error: " . $stmt->error);
    $feedback_message = "<div class='feedback error'>❌ ERROR: A database error occurred. Details logged.</div>";
}

$stmt->close();
$conn->close();

// 3. Set feedback and redirect back to dashboard
$_SESSION['entry_feedback'] = $feedback_message;
header("Location: dashboard.php");
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry Result</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { max-width: 500px; padding: 40px; background: #fff; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); text-align: center; }
        h2 { color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 10px; margin-bottom: 20px; }
        .error h2 { color: #dc3545; border-color: #dc3545; }
        a { display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; transition: background-color 0.2s; }
        a:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="container <?php echo strpos($feedback_message, 'Error') !== false ? 'error' : ''; ?>">
        <?php echo $feedback_message; ?>
        <a href="dashboard.php">Log Another Entry / Go to Dashboard</a>
    </div>
</body>
</html>