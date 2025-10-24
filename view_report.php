<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// --- PHP Logic for fetching and calculating report data ---
$total_warrants = 0.00;
$total_requests = 0.00;
$total_igf = 0.00;
$transactions = []; 

// IMPORTANT: Join with users table to get the username of the logger
$sql = "SELECT w.type, w.amount, w.description, w.date_entered, u.username AS logger_username 
        FROM warrants w
        LEFT JOIN users u ON w.logged_by_user_id = u.id
        ORDER BY date_entered DESC, w.id DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row; 
        if ($row['type'] === 'Warrant') {
            $total_warrants += $row['amount'];
        } elseif ($row['type'] === 'Request') {
            $total_requests += $row['amount'];
        } elseif ($row['type'] === 'IGF_Revenue') {
            $total_igf += $row['amount'];
        }
    }
}
$net_transactions = $total_igf - $total_warrants;
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBETS - Financial Reports</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; padding: 20px; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); }
        h1 { color: #28a745; text-align: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 10px; font-weight: 300; }
        h2 { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-top: 30px; color: #495057; }

        /* Navigation */
        .nav { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 15px; }
        .nav a { color: #007bff; text-decoration: none; padding: 8px 15px; margin: 0 5px; border-radius: 5px; transition: background-color 0.2s; }
        .nav a:hover { background-color: #f0f0f0; }
        .nav .admin-link { color: #dc3545; }
        .nav .logout-link { color: #6c757d; }

        /* Summary Cards */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); text-align: center; }
        .card h3 { margin-top: 0; font-size: 1.1em; color: #6c757d; font-weight: 400; }
        .card p { margin: 5px 0 0; font-size: 1.8em; font-weight: 700; }
        .card.revenue p { color: #28a745; } /* Green for IGF */
        .card.warrants p { color: #dc3545; } /* Red for Warrants */
        .card.requests p { color: #ffc107; } /* Yellow for Requests */
        .card.net { background-color: #e6f7ff; border: 2px solid #007bff; }
        .card.net p { color: #007bff; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background-color: #007bff; color: white; font-weight: 600; }
        tr:hover { background-color: #f0f0f0; }
        
        /* Transaction Type Colors */
        .type-warrant { color: #dc3545; font-weight: 600; }
        .type-request { color: #ffc107; font-weight: 600; }
        .type-igf { color: #28a745; font-weight: 600; }

        /* Search Input */
        #reportSearch { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 20px; 
            border: 1px solid #ced4da; 
            border-radius: 5px; 
            box-sizing: border-box; 
            font-size: 16px; 
            transition: border-color 0.2s;
        }
        #reportSearch:focus { border-color: #007bff; outline: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Financial Performance Report</h1>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="view_report.php" style="font-weight: bold;">📈 View Reports</a>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="admin_panel.php" class="admin-link">🛠 Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-link">🚪 Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </div>

        <h2>Summary of Transactions (GHS)</h2>
        <div class="summary-grid">
            <div class="card revenue">
                <h3>Total IGF Revenue</h3>
                <p><?php echo number_format($total_igf, 2); ?></p>
            </div>
            <div class="card warrants">
                <h3>Total Warrants (Expenditure)</h3>
                <p><?php echo number_format($total_warrants, 2); ?></p>
            </div>
            <div class="card requests">
                <h3>Total Requests (Pre-Expenditure)</h3>
                <p><?php echo number_format($total_requests, 2); ?></p>
            </div>
            <div class="card net">
                <h3>Net Position (IGF - Warrants)</h3>
                <p><?php echo number_format($net_transactions, 2); ?></p>
            </div>
        </div>

        <h2>Detailed Transaction Log</h2>
        
        <?php if (empty($transactions)): ?>
            <p style="text-align: center; margin-top: 30px;">No transactions have been logged in the system yet.</p>
        <?php else: ?>
            
            <input type="text" id="reportSearch" onkeyup="filterTable()" placeholder="Search by Description or Logger..." title="Type to filter the table">
            
            <table id="transaction-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount (GHS)</th>
                        <th>Description</th>
                        <th>Logged By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($t['date_entered'])); ?></td>
                        <td class="<?php echo strtolower(str_replace('_Revenue', '', 'type-' . $t['type'])); ?>">
                            <?php echo htmlspecialchars(str_replace('_', ' ', $t['type'])); ?>
                        </td>
                        <td><?php echo number_format($t['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($t['description']); ?></td>
                        <!-- Use the joined logger_username -->
                        <td><?php echo htmlspecialchars($t['logger_username'] ?: 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

    <script>
    // JS for real-time filtering of the transaction table
    function filterTable() {
        var input, filter, table, tr, tdDescription, tdLogger, i, txtValueDesc, txtValueLogger;
        input = document.getElementById("reportSearch");
        filter = input.value.toUpperCase();
        table = document.getElementById("transaction-table");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows (starting at 1 to skip header)
        for (i = 1; i < tr.length; i++) {
            // Get cells for Description (index 3) and Logged By (index 4)
            tdDescription = tr[i].getElementsByTagName("td")[3]; 
            tdLogger = tr[i].getElementsByTagName("td")[4]; 

            if (tdDescription && tdLogger) {
                txtValueDesc = tdDescription.textContent || tdDescription.innerText;
                txtValueLogger = tdLogger.textContent || tdLogger.innerText;
                
                // Check if the filter matches either the Description or the Logger
                if (txtValueDesc.toUpperCase().indexOf(filter) > -1 || 
                    txtValueLogger.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }       
        }
    }
    </script>
</body>
</html>
