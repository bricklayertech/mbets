<?php
session_start();
require_once 'db_connect.php'; 

// SECURITY CHECK: Only Admins can access this page
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

$feedback = '';

// --- DELETION LOGIC ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    // Prevent admin from deleting themselves
    if ($delete_id == $_SESSION['user_id']) {
        $feedback = "<p class='error-msg'>🛑 ERROR: You cannot delete your own admin account!</p>";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $feedback = "<p class='success-msg'>✅ SUCCESS: User ID $delete_id deleted.</p>";
        } else {
            error_log("User Deletion Error: " . $conn->error);
            $feedback = "<p class='error-msg'>🛑 ERROR: Could not delete user. Details logged.</p>";
        }
        $stmt->close();
    }
}

// --- FETCH ALL USERS ---
$users_result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC");
// We intentionally leave the connection open here to fetch results in the HTML section
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBETS - Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; padding: 20px; }
        .container { max-width: 900px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); }
        h1 { color: #dc3545; text-align: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 10px; font-weight: 300; }
        h2 { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-top: 30px; color: #495057; }
        
        /* Navigation */
        .nav { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 15px; }
        .nav a { color: #007bff; text-decoration: none; padding: 8px 15px; margin: 0 5px; border-radius: 5px; transition: background-color 0.2s; }
        .nav a:hover { background-color: #f0f0f0; }
        .nav .add-link { color: #28a745; font-weight: 600; }
        .nav .logout-link { color: #6c757d; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background-color: #007bff; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }

        /* Roles & Action */
        .role-Admin { color: white; background-color: #dc3545; padding: 3px 8px; border-radius: 4px; font-size: 0.8em; }
        .role-Analyst { color: white; background-color: #ffc107; padding: 3px 8px; border-radius: 4px; font-size: 0.8em; }
        .role-Intern { color: white; background-color: #28a745; padding: 3px 8px; border-radius: 4px; font-size: 0.8em; }
        .action-link { color: #dc3545; text-decoration: none; font-weight: 600; }
        .action-link:hover { text-decoration: underline; }

        /* Feedback Messages */
        .success-msg, .error-msg { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .success-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Custom Modal for Confirmation */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 25px; border: 1px solid #888; width: 80%; max-width: 400px; border-radius: 10px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-content h3 { color: #dc3545; margin-bottom: 20px; }
        .modal-buttons button { margin: 5px; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
        #confirm-delete { background-color: #dc3545; color: white; }
        #confirm-cancel { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>MBETS - System Administration</h1>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="view_report.php">📈 View Reports</a>
            <a href="signup.php" class="add-link">➕ Add New User</a>
            <a href="logout.php" class="logout-link">🚪 Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </div>

        <?php echo $feedback; ?>

        <h2>System User List</h2>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($users_result) {
                    while($row = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><span class="role-<?php echo $row['role']; ?>"><?php echo $row['role']; ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="#" 
                               data-user-id="<?php echo $row['id']; ?>"
                               data-username="<?php echo htmlspecialchars($row['username']); ?>"
                               class="action-link delete-btn">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; 
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>

    <!-- Custom Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <h3>Confirm User Deletion</h3>
            <p>Are you absolutely sure you want to delete user: <strong id="delete-username"></strong>?</p>
            <div class="modal-buttons">
                <button id="confirm-delete">Yes, Delete User</button>
                <button id="confirm-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('delete-modal');
        const confirmBtn = document.getElementById('confirm-delete');
        const cancelBtn = document.getElementById('confirm-cancel');
        const usernameDisplay = document.getElementById('delete-username');
        let deleteUrl = '';

        // Open Modal Handler
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const userId = this.getAttribute('data-user-id');
                const username = this.getAttribute('data-username');
                
                usernameDisplay.textContent = username;
                // Construct the URL for PHP to handle deletion
                deleteUrl = `admin_panel.php?action=delete&id=${userId}`; 
                
                modal.style.display = 'block';
            });
        });

        // Confirmation/Proceed Handler
        confirmBtn.addEventListener('click', function() {
            if (deleteUrl) {
                // Redirects to the generated delete URL to process the request on the server
                window.location.href = deleteUrl;
            }
        });

        // Cancel/Close Modal Handlers
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };
    });
    </script>
</body>
</html>
