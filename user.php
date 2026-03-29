<?php
session_start();
require_once '../db.php';

// 1. Session Check (Fakt Finance Role sathi)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Finance') {
    header("Location: ../loginsystem/index.php");
    exit();
}

$finance_name = $_SESSION['user_name'];
$message = "";

// 2. Action Logic (Approve/Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $action = $_GET['action'];
    
    // Status Logic
    if ($action == 'pay') {
        $new_status = 'Approved by Finance';
        $alert_msg = "Verification Successful! Sent to CFO for Final Payment.";
    } else {
        $new_status = 'Rejected by Finance';
        $alert_msg = "Claim Rejected by Finance department.";
    }

    $update_sql = "UPDATE expenses SET status = '$new_status' WHERE id = '$id'";
    if ($conn->query($update_sql)) {
        $message = "<div class='alert success'><i class='fas fa-check-circle'></i> $alert_msg</div>";
    }
}

// 3. Stats Calculation
$awaiting_pay = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE status = 'Approved by Manager'")->fetch_assoc()['total'] ?? 0;
$settled_pay = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE status IN ('Approved by Finance', 'Paid')")->fetch_assoc()['total'] ?? 0;

// POPUP COUNTS SATHI
$h_app_count = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE status IN ('Approved by Finance', 'Paid')")->fetch_assoc()['count'] ?? 0;
$h_rej_count = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'Rejected by Finance'")->fetch_assoc()['count'] ?? 0;

// 4. Fetch Claims Approved by Manager
$sql = "SELECT e.*, u.name as emp_name, u.email as emp_email 
        FROM expenses e 
        JOIN users u ON e.employee_id = u.id 
        WHERE e.status = 'Approved by Manager' 
        ORDER BY e.date DESC";
$claims = $conn->query($sql);

// 5. Payment History (For Modal)
$history_sql = "SELECT e.*, u.name as emp_name FROM expenses e 
                JOIN users u ON e.employee_id = u.id 
                WHERE e.status IN ('Approved by Finance', 'Paid', 'Rejected by Finance') 
                ORDER BY e.id DESC LIMIT 20";
$history_res = $conn->query($history_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance Dashboard | Settlement Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --finance: #0ea5e9; --success: #10b981; --danger: #ef4444; --sidebar: #0f172a; --bg: #f8fafc; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg); display: flex; color: #1e293b; }

        .sidebar { width: 280px; background: var(--sidebar); height: 100vh; position: fixed; padding: 30px 20px; color: white; display: flex; flex-direction: column; }
        .logo { font-size: 22px; font-weight: 800; color: var(--finance); margin-bottom: 50px; display: flex; align-items: center; gap: 12px; }
        .nav-links { list-style: none; flex-grow: 1; }
        .nav-links a { color: #94a3b8; text-decoration: none; padding: 14px 18px; display: flex; align-items: center; gap: 12px; border-radius: 12px; transition: 0.3s; margin-bottom: 10px; cursor: pointer; }
        .nav-links a:hover, .nav-links a.active { background: rgba(14, 165, 233, 0.1); color: var(--finance); font-weight: 700; }
        .logout-btn { color: #f87171 !important; padding: 14px 18px; text-decoration: none; border-top: 1px solid #334155; margin-top: auto; display: flex; align-items: center; gap: 10px; }

        .main-content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .header h1 { font-size: 28px; font-weight: 800; color: #0f172a; }

        /* Date & Day Style */
        .header-date { text-align: right; background: white; padding: 10px 20px; border-radius: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
        .header-date .day { display: block; font-weight: 800; color: var(--finance); font-size: 14px; text-transform: uppercase; }
        .header-date .date { color: #64748b; font-size: 13px; font-weight: 600; }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; }
        .stat-card p { color: #64748b; font-size: 14px; font-weight: 600; margin-bottom: 10px; }
        .stat-card h2 { font-size: 32px; font-weight: 800; color: #0f172a; }

        .table-card { background: white; border-radius: 24px; padding: 30px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #f8fafc; color: #64748b; font-size: 13px; font-weight: 700; text-transform: uppercase; }
        td { padding: 18px 15px; border-bottom: 1px solid #f8fafc; font-size: 14px; }

        .actions { display: flex; gap: 10px; }
        .btn-pay { background: var(--success); color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.3s; }
        .btn-pay:hover { background: #059669; transform: translateY(-2px); }
        .btn-reject { background: #fee2e2; color: #dc2626; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.3s; }
        .btn-reject:hover { background: #dc2626; color: white; transform: translateY(-2px); }

        .bill-link { color: var(--finance); text-decoration: none; font-weight: 700; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(8px); }
        .modal-content { background: white; margin: 5% auto; padding: 35px; border-radius: 30px; width: 90%; max-width: 900px; max-height: 80vh; overflow-y: auto; position: relative; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: modalSlide 0.4s ease-out; }
        @keyframes modalSlide { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close-btn { position: absolute; right: 25px; top: 20px; font-size: 30px; cursor: pointer; color: #94a3b8; transition: 0.2s; }
        
        .h-summary { display: flex; gap: 20px; margin-bottom: 25px; background: #f8fafc; padding: 20px; border-radius: 20px; border: 1px solid #f1f5f9; }
        .h-box { padding: 10px 20px; border-radius: 12px; font-weight: 800; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .h-approved { background: #ecfdf5; color: #059669; }
        .h-rejected { background: #fff1f2; color: #e11d48; }

        .status-badge { padding: 6px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-Approved-by-Finance, .status-Paid { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-Rejected-by-Finance { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .alert { padding: 15px 25px; border-radius: 15px; margin-bottom: 30px; font-weight: 600; }
        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><i class="fas fa-file-invoice-dollar"></i> Finance Hub</div>
    <ul class="nav-links">
        <li><a class="active"><i class="fas fa-wallet"></i> Pending Verification</a></li>
        <li><a onclick="openHistory()"><i class="fas fa-history"></i> Verification History</a></li>
    </ul>
    <a href="../loginsystem/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout Session</a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1>Finance Dashboard</h1>
            <p>Processing approved settlements for <strong><?php echo $finance_name; ?></strong></p>
        </div>
        <div class="header-date">
            <span class="day"><?php echo date('l'); ?></span>
            <span class="date"><?php echo date('d F Y'); ?></span>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Awaiting Finance Review</p>
            <h2 style="color: #f59e0b;">₹<?php echo number_format($awaiting_pay, 2); ?></h2>
        </div>
        <div class="stat-card">
            <p>Processed by Finance</p>
            <h2 style="color: var(--success);">₹<?php echo number_format($settled_pay, 2); ?></h2>
        </div>
    </div>

    <div class="table-card">
        <h3 style="margin-bottom: 20px;">Manager Approved Claims</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee Details</th>
                    <th>Date & Category</th>
                    <th>Amount</th>
                    <th>Bill Proof</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($claims->num_rows > 0): ?>
                    <?php while($row = $claims->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo $row['emp_name']; ?></strong><br>
                            <span style="font-size: 12px; color: #64748b;"><?php echo $row['emp_email']; ?></span>
                        </td>
                        <td>
                            <div style="font-weight: 600;"><?php echo $row['category']; ?></div>
                            <div style="font-size: 12px; color: #64748b;"><?php echo date('d M Y', strtotime($row['date'])); ?></div>
                        </td>
                        <td><span style="font-weight: 800; color: #0f172a;">₹<?php echo number_format($row['amount'], 2); ?></span></td>
                        <td><a href="../uploads/<?php echo $row['bill_img']; ?>" target="_blank" class="bill-link"><i class="fas fa-external-link-alt"></i> View Bill</a></td>
                        <td>
                            <div class="actions">
                                <a href="?action=pay&id=<?php echo $row['id']; ?>" class="btn-pay" onclick="return confirm('Approve this request for CFO payment?')">Approve</a>
                                <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this claim?')">Reject</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 50px; color: #94a3b8;">No new claims from Manager yet. 🚀</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="historyModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeHistory()">&times;</span>
        <h2 style="margin-bottom: 25px; font-weight: 800; letter-spacing: -1px;"><i class="fas fa-history" style="color: var(--finance);"></i> Verification History</h2>
        
        <div class="h-summary">
            <div class="h-box h-approved"><i class="fas fa-check-circle"></i> Forwarded/Paid: <?php echo $h_app_count; ?></div>
            <div class="h-box h-rejected"><i class="fas fa-times-circle"></i> Rejected: <?php echo $h_rej_count; ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Amount</th>
                    <th>Category</th>
                    <th>Verification Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if($history_res->num_rows > 0): ?>
                    <?php while($h = $history_res->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $h['emp_name']; ?></strong></td>
                        <td style="font-weight: 800; color: #0f172a;">₹<?php echo number_format($h['amount'], 2); ?></td>
                        <td><?php echo $h['category']; ?></td>
                        <td>
                            <?php 
                                $s_class = str_replace(' ', '-', $h['status']);
                                $s_label = ($h['status'] == 'Approved by Finance') ? 'Verified' : $h['status'];
                            ?>
                            <span class="status-badge status-<?php echo $s_class; ?>"><?php echo $s_label; ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px; color:#94a3b8;">No history available yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function openHistory() { document.getElementById('historyModal').style.display = 'block'; }
    function closeHistory() { document.getElementById('historyModal').style.display = 'none'; }
    window.onclick = function(event) {
        if (event.target == document.getElementById('historyModal')) closeHistory();
    }
</script>

</body>
</html>