<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Check for and display success message if redirected from process_entry.php
$feedback = '';
if (isset($_SESSION['entry_feedback'])) {
    $feedback = $_SESSION['entry_feedback'];
    unset($_SESSION['entry_feedback']); // Clear after displaying
}

$entry_count = 0;
// Fetch total entry count
$result = $conn->query("SELECT COUNT(*) as count FROM warrants");
if ($result) {
    $row = $result->fetch_assoc();
    $entry_count = $row['count'];
}
$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBETS - Dashboard & Data Entry</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #e9ecef; padding: 20px; }
        .container { max-width: 950px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); }
        h1 { color: #007bff; text-align: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 10px; font-weight: 300; }
        .nav { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 15px; }
        .nav a { color: #007bff; text-decoration: none; padding: 8px 15px; margin: 0 5px; border-radius: 5px; transition: background-color 0.2s; }
        .nav a:hover { background-color: #f0f0f0; }
        .nav .admin-link { color: #dc3545; }
        .nav .logout-link { color: #6c757d; }
        
        /* Form Styling */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #495057; }
        input[type="text"], input[type="number"], input[type="date"], select, textarea { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 16px; transition: border-color 0.2s; }
        input:focus, select:focus, textarea:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); outline: none; }
        textarea { resize: vertical; }
        button[type="submit"] { background-color: #28a745; color: white; padding: 15px 25px; border: none; border-radius: 8px; cursor: pointer; margin-top: 25px; width: 100%; font-size: 1.2em; font-weight: 600; transition: background-color 0.2s, transform 0.2s; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3); }
        button[type="submit"]:hover { background-color: #1e7e34; transform: translateY(-2px); }

        /* Stats & Feedback */
        .stats { text-align: center; margin-bottom: 25px; padding: 15px; background: #e0f7fa; border-radius: 8px; border: 1px solid #b2ebf2; }
        .stats p { margin: 0; color: #007bff; font-size: 1.1em; font-weight: 500; }
        .feedback { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    </style>
</head>
<body>
    <div class="container">
        <h1>MBETS Dashboard & Data Entry</h1>
        
        <div class="nav">
            <a href="dashboard.php" style="font-weight: bold;">📊 Dashboard</a>
            <a href="view_report.php">📈 View Reports</a>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="admin_panel.php" class="admin-link">🛠 Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-link">🚪 Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </div>

        <div class="stats">
            <p>Total Records Logged in System: **<?php echo $entry_count; ?>**</p>
        </div>
        
        <?php echo $feedback; ?>
        
        <!-- Client-side validation message area -->
        <div id="validation-message" class="feedback warning" style="display:none;"></div>

        <h2>New Transaction Schedule</h2>
        <form action="process_entry.php" method="POST" id="entry-form">
            
            <div class="form-group">
                <label for="entry_type">Entry Type:</label>
                <select id="entry_type" name="entry_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="Warrant">Warrant (Expenditure Release)</option>
                    <option value="Request">Request (Pre-Expenditure)</option>
                    <option value="IGF_Revenue">IGF (Internally Generated Revenue)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount (GHS):</label>
                <input type="number" id="amount" name="amount" step="0.01" required placeholder="e.g., 500.00">
            </div>

            <div class="form-group">
                <label for="description">Description / Purpose:</label>
                <textarea id="description" name="description" rows="4" required placeholder="Brief description of the transaction/purpose."></textarea>
            </div>
            
            <div class="form-group">
                <label for="date_entered">Date:</label>
                <input type="date" id="date_entered" name="date_entered" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <!-- Hidden field for logger ID (essential for linking data) -->
            <input type="hidden" name="logged_by_user_id" value="<?php echo $_SESSION['user_id']; ?>">

            <button type="submit">Log Entry to Database</button>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('entry-form');
        const validationMessage = document.getElementById('validation-message');
        
        if (form) {
            form.addEventListener('submit', function(event) {
                const entryType = document.getElementById('entry_type').value;
                const amountInput = document.getElementById('amount').value;
                const amount = parseFloat(amountInput);
                let message = '';

                // Reset message area
                validationMessage.style.display = 'none';
                validationMessage.innerHTML = '';
                
                // 1. Check if an entry type is selected
                if (entryType === "") {
                    message = "⚠️ Please select a valid Entry Type.";
                } 
                // 2. Check if the amount is a positive number
                else if (amountInput === "" || isNaN(amount) || amount <= 0) {
                    message = "⚠️ The Amount must be a positive number greater than zero.";
                }

                if (message) {
                    validationMessage.innerHTML = message;
                    validationMessage.style.display = 'block';
                    event.preventDefault(); // Stop form submission
                    window.scrollTo(0, 0); // Scroll to top to show the message
                }
            });
        }
    });
    </script>
</body>
</html>
