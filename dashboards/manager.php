<?php
session_start();
require_once '../db.php';

// 1. Session Check (Fakt Manager la entry)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: ../loginsystem/index.php");
    exit();
}

$manager_name = $_SESSION['user_name'];
$message = "";

// 2. Approval / Rejection Logic
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $action = $_GET['action'];
    
    // DB madhe specific status save hoil
    $new_status = ($action == 'approve') ? 'Approved by Manager' : 'Rejected by Manager';

    $update_sql = "UPDATE expenses SET status = '$new_status' WHERE id = '$id'";
    if ($conn->query($update_sql)) {
        $message = "<div class='alert success'>✨ Request #$id is now <b>$new_status</b>!</div>";
    } else {
        $message = "<div class='alert error'>❌ Error: " . $conn->error . "</div>";
    }
}

// 3. Stats Fetch
$p_count = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;
$t_count = $conn->query("SELECT COUNT(*) as count FROM expenses")->fetch_assoc()['count'] ?? 0;
$h_app_count = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'Approved by Manager'")->fetch_assoc()['count'] ?? 0;
$h_rej_count = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'Rejected by Manager'")->fetch_assoc()['count'] ?? 0;

// 4. Fetch Pending Requests
$requests = $conn->query("SELECT e.*, u.name as emp_name, u.email as emp_email FROM expenses e JOIN users u ON e.employee_id = u.id WHERE e.status = 'Pending' ORDER BY e.date DESC");

// 5. Fetch History
$history_res = $conn->query("SELECT e.*, u.name as emp_name FROM expenses e JOIN users u ON e.employee_id = u.id WHERE e.status != 'Pending' ORDER BY e.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Hub | 🛡️ ReimburseIt</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #6366f1; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --sidebar: #0f172a; --bg: #fdf2f8; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg); display: flex; color: #1e293b; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 280px; background: var(--sidebar); height: 100vh; position: fixed; padding: 30px 20px; color: white; }
        .logo { font-size: 24px; font-weight: 800; color: #ff69b4; margin-bottom: 50px; display: flex; align-items: center; gap: 10px; }
        .nav-links { list-style: none; }
        .nav-links a { color: #94a3b8; text-decoration: none; padding: 14px 18px; display: flex; align-items: center; gap: 12px; border-radius: 12px; transition: 0.3s; margin-bottom: 10px; cursor: pointer; }
        .nav-links a.active { background: #ff69b4; color: white; font-weight: 700; box-shadow: 0 4px 15px rgba(255,105,180,0.3); }
        .logout-btn { color: #f87171 !important; position: absolute; bottom: 30px; left: 20px; text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 10px; }

        /* Main Content */
        .main-content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header-date { text-align: right; background: white; padding: 12px 20px; border-radius: 18px; border: 2px solid #fce7f3; }
        .header-date .day { display: block; font-weight: 800; color: #ff69b4; font-size: 12px; text-transform: uppercase; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 2px solid #fce7f3; display: flex; align-items: center; gap: 20px; }
        .icon-box { width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .bg-pending { background: #fffbeb; color: #d97706; }
        .bg-total { background: #eff6ff; color: #1d4ed8; }

        /* Table Section */
        .table-card { background: white; border-radius: 30px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 13px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 20px 15px; border-bottom: 1px solid #f8fafc; font-size: 15px; }

        /* Buttons */
        .btn-action { padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 800; transition: 0.3s; display: inline-block; }
        .btn-approve { background: var(--success); color: white; box-shadow: 0 4px 10px rgba(16,185,129,0.2); }
        .btn-approve:hover { transform: translateY(-2px); background: #059669; }
        .btn-reject { background: var(--danger); color: white; box-shadow: 0 4px 10px rgba(239,68,68,0.2); }
        .btn-reject:hover { transform: translateY(-2px); background: #dc2626; }

        /* Status Badges */
        .status-badge { padding: 8px 15px; border-radius: 12px; font-size: 11px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; }
        .status-Approved-by-Manager { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .status-Rejected-by-Manager { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px); }
        .modal-content { background: white; margin: 4% auto; padding: 40px; border-radius: 35px; width: 90%; max-width: 900px; max-height: 85vh; overflow-y: auto; animation: pop 0.4s ease; }
        @keyframes pop { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .close-btn { position: absolute; right: 30px; top: 25px; font-size: 32px; cursor: pointer; color: #94a3b8; }

        .h-summary { display: flex; gap: 20px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 20px; }
        .h-box { padding: 10px 20px; border-radius: 15px; font-weight: 800; font-size: 14px; display: flex; align-items: center; gap: 8px; }

        .alert { padding: 15px 25px; border-radius: 18px; margin-bottom: 30px; font-weight: 700; }
        .success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">💖 Manager Hub</div>
    <ul class="nav-links">
        <li><a class="active"><i class="fas fa-tasks"></i> Review Claims</a></li>
        <li><a onclick="openHistory()"><i class="fas fa-history"></i> Decision History</a></li>
    </ul>
    <a href="../loginsystem/logout.php" class="logout-btn"><i class="fas fa-power-off"></i> Logout 👋</a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1 style="font-size: 32px; font-weight: 800;">Review Portal 👋</h1>
            <p style="color: #64748b;">Managing team requests for <b><?php echo $manager_name; ?></b></p>
        </div>
        <div class="header-date">
            <span class="day"><?php echo date('l'); ?></span>
            <span class="date"><?php echo date('d M Y'); ?></span>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon-box bg-pending">⏳</div>
            <div><p style="color: #64748b; font-size: 13px;">Awaiting Action</p><h3 style="color: #b45309;"><?php echo $p_count; ?> Requests</h3></div>
        </div>
        <div class="stat-card">
            <div class="icon-box bg-total">📑</div>
            <div><p style="color: #64748b; font-size: 13px;">Total Recieved</p><h3 style="color: #1d4ed8;"><?php echo $t_count; ?> Claims</h3></div>
        </div>
    </div>

    <div class="table-card">
        <div class="section-title" style="font-size: 20px; font-weight: 800; margin-bottom: 25px;">📥 Pending Reimbursements</div>
        <table>
            <thead>
                <tr>
                    <th>Employee Details</th>
                    <th>Expense Info</th>
                    <th>Amount</th>
                    <th>Proof</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($requests->num_rows > 0): ?>
                    <?php while($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: #0f172a;">👤 <?php echo $row['emp_name']; ?></div>
                            <small style="color: #64748b;"><?php echo $row['emp_email']; ?></small>
                        </td>
                        <td>
                            <div style="font-weight: 700;">
                                <?php 
                                    if($row['category'] == 'Food') echo "🍔 ";
                                    elseif($row['category'] == 'Travel') echo "✈️ ";
                                    elseif($row['category'] == 'Internet') echo "🌐 ";
                                    else echo "📦 ";
                                    echo $row['category']; 
                                ?>
                            </div>
                            <small style="color: #94a3b8;"><?php echo date('d M Y', strtotime($row['date'])); ?></small>
                        </td>
                        <td><b style="font-size: 16px;">₹<?php echo number_format($row['amount'], 2); ?></b></td>
                        <td><a href="../uploads/<?php echo $row['bill_img']; ?>" target="_blank" style="color:var(--primary); font-weight:800; text-decoration:none;">📂 View Bill</a></td>
                        <td>
                            <div class="actions">
                                <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn-action btn-approve" onclick="return confirm('Approve this claim? ✅')">Approve</a>
                                <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn-action btn-reject" onclick="return confirm('Reject this claim? ❌')">Reject</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">Sagle claims clear ahet! ☕ Relax kara.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="historyModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeHistory()">&times;</span>
        <h2 style="margin-bottom: 30px; font-weight: 800;"><i class="fas fa-history"></i> Decision History 📜</h2>
        
        <div class="h-summary">
            <div class="h-box" style="background: #ecfdf5; color: #059669;">✅ Approved: <?php echo $h_app_count; ?></div>
            <div class="h-box" style="background: #fff1f2; color: #e11d48;">❌ Rejected: <?php echo $h_rej_count; ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Category</th>
                    <th>Final Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($h = $history_res->fetch_assoc()): ?>
                <tr>
                    <td><strong>👤 <?php echo $h['emp_name']; ?></strong></td>
                    <td><b style="color: #0f172a;">₹<?php echo number_format($h['amount'], 2); ?></b></td>
                    <td><?php echo $h['category']; ?></td>
                    <td>
                        <?php $s_class = str_replace(' ', '-', $h['status']); ?>
                        <span class="status-badge status-<?php echo $s_class; ?>">
                            <?php 
                                if(strpos($h['status'], 'Approved') !== false) echo "🎉 Approved";
                                else echo "🚫 Rejected";
                            ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function openHistory() { document.getElementById('historyModal').style.display = 'block'; }
    function closeHistory() { document.getElementById('historyModal').style.display = 'none'; }
    window.onclick = function(e) { if(e.target == document.getElementById('historyModal')) closeHistory(); }
</script>

</body>
</html>