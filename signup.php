<?php
session_start();
require_once 'db_connect.php'; 

$feedback = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (PHP logic for signup remains the same: hashing and insertion) ...
    $new_username = $_POST['username'];
    $new_password = $_POST['password'];
    $role = $_POST['role'];
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $new_username);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $feedback = "<p style='color:#dc3545;'>User already exists. Choose a different username.</p>";
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $new_username, $hashed_password, $role);
        
        if ($insert_stmt->execute()) {
            $feedback = "<p style='color:#28a745;'>✅ SUCCESS: User **$new_username** added! <a href='login.php'>Go to Login</a></p>";
        } else {
            $feedback = "<p style='color:#dc3545;'>🛑 ERROR: " . $conn->error . "</p>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBETS - Add User</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { width: 100%; max-width: 450px; padding: 40px; background: #fff; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
        h2 { color: #28a745; margin-bottom: 30px; font-weight: 300; border-bottom: 2px solid #28a745; padding-bottom: 10px; text-align: center; }
        /* Reuse form-group, label, input/select styles from login.php or dashboard.php */
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #495057; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 16px; }
        button[type="submit"] { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; width: 100%; font-size: 1.1em; font-weight: 600; transition: background-color 0.2s; }
        button[type="submit"]:hover { background-color: #1e7e34; }
    </style>
</head>
<body>
    <div class="container">
        <h2>MBETS - Create New User</h2>
        <?php echo $feedback; ?>
        <form action="signup.php" method="POST">
            <div class="form-group"><label for="username">Username:</label><input type="text" name="username" required></div>
            <div class="form-group"><label for="password">Password:</label><input type="password" name="password" required></div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select name="role" required>
                    <option value="Intern">Intern</option>
                    <option value="Analyst">Analyst</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <button type="submit">Create User</button>
        </form>
        <p style="margin-top: 20px; text-align: center;"><a href="admin_panel.php">Manage Users</a> | <a href="login.php">Go to Login</a></p>
    </div>
</body>
</html>