<?php
session_start();
require_once 'db_connect.php'; 

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dashboard.php");
    exit;
}

$feedback = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role);
    $stmt->fetch();
    
    if ($stmt->num_rows === 1 && password_verify($pass, $hashed_password)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $user;
        $_SESSION['role'] = $role;
        
        header("Location: dashboard.php");
        exit;
    } else {
        $feedback = "<p style='color:#dc3545; font-weight:bold;'>Invalid credentials. Try again.</p>";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBETS - Secure Login</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-container { width: 100%; max-width: 380px; padding: 40px; background: #fff; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); text-align: center; }
        h2 { color: #007bff; margin-bottom: 30px; font-weight: 300; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #495057; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 16px; transition: border-color 0.2s; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); outline: none; }
        button { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px; width: 100%; font-size: 1.1em; font-weight: 600; transition: background-color 0.2s; }
        button:hover { background-color: #0056b3; }
        .signup-link { margin-top: 25px; font-size: 0.9em; }
        .signup-link a { color: #28a745; text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>MBETS Secure Login</h2>
        <?php echo $feedback; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Log In</button>
        </form>
        <p class="signup-link">
            Need an account? <a href="signup.php">Request Access</a>
        </p>
    </div>
</body>
</html>