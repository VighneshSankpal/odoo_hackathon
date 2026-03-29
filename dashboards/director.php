<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Director' && $_SESSION['role'] !== 'CFO')) {
    header("Location: ../loginsystem/index.php");
    exit();
}

$cfo_name = $_SESSION['user_name'];
$message = "";

if (isset($_GET['action']) && $_GET['action'] == 'pay' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $update_sql = "UPDATE expenses SET status = 'Paid' WHERE id = '$id'";
    if ($conn->query($update_sql)) {
        $message = "<div class='alert success'><i class='fas fa-check-double'></i> Payment Authorized for Request #$id!</div>";
    }
}

// Analytics Queries
$total_spent = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE status = 'Paid'")->fetch_assoc()['total'] ?? 0;
$pending_all = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE status IN ('Pending', 'Approved by Manager', 'Approved by Finance')")->fetch_assoc()['count'] ?? 0;

$direct_pending = $conn->query("SELECT e.*, u.name as emp_name FROM expenses e JOIN users u ON e.employee_id = u.id WHERE e.status = 'Pending' ORDER BY e.id DESC");
$finance_verified = $conn->query("SELECT e.*, u.name as emp_name FROM expenses e JOIN users u ON e.employee_id = u.id WHERE e.status = 'Approved by Finance' ORDER BY e.id DESC");
$all_records = $conn->query("SELECT e.*, u.name as emp_name FROM expenses e JOIN users u ON e.employee_id = u.id ORDER BY e.id DESC LIMIT 30");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Director Master Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #6366f1; --success: #10b981; --warning: #f59e0b; 
            --danger: #ef4444; --sidebar: #0f172a; --bg: #f8fafc;
            --info: #0ea5e9;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg); display: flex; color: #1e293b; }

        .sidebar { width: 280px; background: var(--sidebar); height: 100vh; position: fixed; padding: 40px 25px; color: white; }
        .logo { font-size: 24px; font-weight: 800; color: #818cf8; margin-bottom: 50px; display: flex; align-items: center; gap: 10px; }
        .nav-links { list-style: none; }
        .nav-links a { color: #94a3b8; text-decoration: none; padding: 15px 20px; display: flex; align-items: center; gap: 12px; border-radius: 12px; transition: 0.3s; margin-bottom: 10px; font-weight: 600; }
        .nav-links a.active { background: rgba(99, 102, 241, 0.2); color: #818cf8; }

        .main-content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 35px; }
        
        /* Premium Date Card */
        .header-date { text-align: right; background: white; padding: 12px 20px; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header-date .day { display: block; font-weight: 800; color: var(--primary); font-size: 13px; text-transform: uppercase; }
        .header-date .date { color: #64748b; font-size: 14px; font-weight: 600; }

        /* VIBRANT STAT CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 24px; border: 1px solid #e2e8f0; position: relative; overflow: hidden; }
        .stat-card::before { content: ""; position: absolute; left: 0; top: 0; height: 100%; width: 6px; }
        .stat-card.spent::before { background: var(--success); }
        .stat-card.pending::before { background: var(--warning); }
        .stat-card h3 { font-size: 32px; font-weight: 800; color: #0f172a; margin-top: 5px; }

        /* TABLE STYLING */
        .section-title { font-size: 19px; font-weight: 800; margin: 35px 0 20px; display: flex; align-items: center; gap: 12px; color: #1e293b; }
        .table-card { background: white; border-radius: 24px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 10px 20px rgba(0,0,0,0.03); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; border-radius: 10px; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

        /* BUTTONS */
        .btn-super { background: var(--primary); color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.3s; display: inline-block; }
        .btn-super:hover { background: #4338ca; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(99,102,241,0.3); }
        
        .btn-paid { background: var(--success); color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.3s; display: inline-block; }
        .btn-paid:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16,185,129,0.3); }

        .bill-btn { color: var(--info); text-decoration: none; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }

        /* DYNAMIC STATUS BADGES (The Colors You Wanted!) */
        .status-badge { padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; }
        
        .status-Pending { background: #fffbeb; color: #b45309; border-color: #fde68a; } /* Amber/Yellow */
        .status-Approved-by-Manager { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; } /* Blue */
        .status-Approved-by-Finance { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; } /* Green */
        .status-Paid { background: #ecfdf5; color: #059669; border-color: #6ee7b7; font-weight: 900; } /* Strong Green */
        .status-Rejected-by-Manager, .status-Rejected-by-Finance { background: #fef2f2; color: #b91c1c; border-color: #fecaca; } /* Red */

        .alert { padding: 20px; border-radius: 18px; margin-bottom: 30px; font-weight: 700; background: #dcfce7; color: #15803d; border-left: 6px solid var(--success); }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><i class="fas fa-crown"></i> DIRECTOR</div>
    <ul class="nav-links">
        <li><a class="active"><i class="fas fa-layer-group"></i> Master Dashboard</a></li>
        <li><a href="../loginsystem/logout.php" style="color: #f87171;"><i class="fas fa-power-off"></i> Logout Session</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1>Executive Command Center</h1>
            <p style="color: #64748b;">Authority: <strong>Director <?php echo $cfo_name; ?></strong></p>
        </div>
        <div class="header-date">
            <span class="day"><?php echo date('l'); ?></span>
            <span class="date"><?php echo date('d M Y'); ?></span>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="stats-grid">
        <div class="stat-card spent">
            <small style="color:var(--success)">Total Capital Disbursed</small>
            <h3>₹<?php echo number_format($total_spent, 2); ?></h3>
        </div>
        <div class="stat-card pending">
            <small style="color:var(--warning)">Total Active Requests</small>
            <h3><?php echo $pending_all; ?> <span style="font-size: 16px; font-weight: 400;">Files</span></h3>
        </div>
    </div>

    <div class="section-title"><i class="fas fa-bolt" style="color: var(--primary);"></i> Immediate Payout (New Requests)</div>
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Amount</th>
                    <th>Evidence</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $direct_pending->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo $row['emp_name']; ?></strong></td>
                    <td style="font-weight: 800;">₹<?php echo number_format($row['amount'], 2); ?></td>
                    <td><a href="../uploads/<?php echo $row['bill_img']; ?>" target="_blank" class="bill-btn"><i class="fas fa-file-invoice"></i> View Proof</a></td>
                    <td><a href="?action=pay&id=<?php echo $row['id']; ?>" class="btn-super">Bypass & Pay</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section-title"><i class="fas fa-check-double" style="color: var(--success);"></i> Finance Verified (Ready)</div>
    <div class="table-card">
        <table>
            <thead>
                <tr><th>Payee</th><th>Final Amount</th><th>Bill</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while($row = $finance_verified->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo $row['emp_name']; ?></strong></td>
                    <td style="font-weight: 800;">₹<?php echo number_format($row['amount'], 2); ?></td>
                    <td><a href="../uploads/<?php echo $row['bill_img']; ?>" target="_blank" class="bill-btn">Evidence</a></td>
                    <td><a href="?action=pay&id=<?php echo $row['id']; ?>" class="btn-paid">Final Authorization</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section-title"><i class="fas fa-history" style="color: var(--info);"></i> Global Transaction Logs</div>
    <div class="table-card">
        <table>
            <thead>
                <tr><th>User</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while($record = $all_records->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $record['emp_name']; ?></td>
                    <td>₹<?php echo number_format($record['amount'], 2); ?></td>
                    <td>
                        <?php $s_class = str_replace(' ', '-', $record['status']); ?>
                        <span class="status-badge status-<?php echo $s_class; ?>"><?php echo $record['status']; ?></span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>