<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../loginsystem/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = "";

if (isset($_POST['submit_request'])) {
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $date = $_POST['date'];
    $bill_image = $_FILES['bill_image']['name'];
    $target_dir = "../uploads/";
    
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    $file_name = time() . "_" . basename($bill_image);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES['bill_image']['tmp_name'], $target_file)) {
        $sql = "INSERT INTO expenses (employee_id, amount, category, description, date, bill_img, status) 
                VALUES ('$user_id', '$amount', '$category', '$description', '$date', '$file_name', 'Pending')";
        if ($conn->query($sql)) {
            $message = "<script>alert('🚀 Request Sent to Outer Space (Manager)!'); window.location.href='user.php';</script>";
        }
    }
}

$total_paid = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE employee_id = '$user_id' AND status = 'Paid'")->fetch_assoc()['total'] ?? 0;
$pending_count = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE employee_id = '$user_id' AND status != 'Paid' AND status NOT LIKE 'Rejected%'")->fetch_assoc()['count'] ?? 0;
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE employee_id = '$user_id' AND status LIKE 'Rejected%'")->fetch_assoc()['count'] ?? 0;

$history_res = $conn->query("SELECT * FROM expenses WHERE employee_id = '$user_id' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Fun Portal | 💸 ReimburseIt</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #6366f1; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --sidebar: #0f172a; --bg: #fdf2f8; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { background-color: var(--bg); display: flex; color: #1e293b; min-height: 100vh; overflow-x: hidden; }

        /* Sidebar */
        .sidebar { width: 280px; background: var(--sidebar); height: 100vh; position: fixed; padding: 30px 20px; color: white; z-index: 100; }
        .logo { font-size: 24px; font-weight: 800; color: #ff69b4; margin-bottom: 50px; display: flex; align-items: center; gap: 10px; }
        .nav-links { list-style: none; }
        .nav-links a { color: #94a3b8; text-decoration: none; padding: 14px 18px; display: flex; align-items: center; gap: 12px; border-radius: 12px; transition: 0.3s; margin-bottom: 10px; cursor: pointer; }
        .nav-links a.active { background: #ff69b4; color: white; font-weight: 700; box-shadow: 0 4px 15px rgba(255,105,180,0.3); }

        .main-content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 2px solid #fce7f3; transition: 0.3s; }
        .stat-card h3 { font-size: 26px; font-weight: 800; margin-top: 10px; }

        .content-section { background: white; border-radius: 30px; padding: 30px; margin-bottom: 30px; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .section-title { font-size: 20px; font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 13px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 20px 15px; border-bottom: 1px solid #f8fafc; font-size: 15px; }

        .status-badge { padding: 8px 15px; border-radius: 12px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .status-Pending { background: #fef3c7; color: #92400e; }
        .status-Paid { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-Rejected-by-Manager, .status-Rejected-by-Finance { background: #fee2e2; color: #b91c1c; }

        .btn-main { background: linear-gradient(135deg, #6366f1, #a855f7); color: white; padding: 15px 30px; border-radius: 18px; border: none; font-weight: 800; cursor: pointer; font-size: 16px; box-shadow: 0 10px 20px rgba(99,102,241,0.2); transition: 0.3s; }
        .btn-cancel { background: #fef2f2; color: #ef4444; padding: 12px; border-radius: 15px; border: 2px solid #fee2e2; font-weight: 700; cursor: pointer; margin-top: 10px; width: 100%; transition: 0.2s; }
        .btn-cancel:hover { background: #fee2e2; }

        /* --- SCROLLABLE POPUP MODAL --- */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px); }
        .modal-content { 
            background: white; 
            margin: 2% auto; 
            padding: 35px; 
            border-radius: 35px; 
            width: 90%; 
            max-width: 500px; 
            max-height: 90vh; /* Popup chi height screen peksha jasti honar nahi */
            overflow-y: auto; /* Jar content jasti asel tar popup madhe scroll yeil */
            animation: pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            position: relative;
        }
        @keyframes pop { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; margin-bottom: 8px; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 15px; font-size: 14px; transition: 0.3s; }
        input:focus { border-color: #ff69b4; outline: none; }

        /* Custom Scrollbar for Popup */
        .modal-content::-webkit-scrollbar { width: 6px; }
        .modal-content::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">💖 ReimburseIt</div>
    <ul class="nav-links">
        <li><a class="active">🏠 My Dashboard</a></li>
        <li><a onclick="openModal()">➕ New Request</a></li>
    </ul>
    <a href="../loginsystem/logout.php" style="color:#f87171; text-decoration:none; font-weight:800; position:absolute; bottom:30px;"><i class="fas fa-power-off"></i> Bye Bye! 👋</a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1 style="font-size: 32px; font-weight: 800;">Hey <?php echo $user_name; ?>! 👋</h1>
            <p style="color: #64748b;">Let's get your money back! 💸✨</p>
        </div>
        <button class="btn-main" onclick="openModal()">🚀 Submit New Bill</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span style="font-size: 30px;">🤑</span>
            <small>Total Cash Back</small>
            <h3 style="color: var(--success);">₹<?php echo number_format($total_paid, 2); ?></h3>
        </div>
        <div class="stat-card">
            <span style="font-size: 30px;">⏳</span>
            <small>Pending Approval</small>
            <h3 style="color: var(--warning);"><?php echo $pending_count; ?> Requests</h3>
        </div>
        <div class="stat-card">
            <span style="font-size: 30px;">❌</span>
            <small>Rejected Claims</small>
            <h3 style="color: var(--danger);"><?php echo $rejected_count; ?> Ouch!</h3>
        </div>
    </div>

    <div class="content-section">
        <div class="section-title">📜 My Expense Journey</div>
        <table>
            <thead>
                <tr>
                    <th>Expense Type</th>
                    <th>Money Spent</th>
                    <th>Evidence</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $history_res->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="font-weight: 800; font-size: 16px;">
                            <?php 
                                if($row['category'] == 'Food') echo "🍔 ";
                                elseif($row['category'] == 'Travel') echo "✈️ ";
                                elseif($row['category'] == 'Internet') echo "🌐 ";
                                else echo "📦 ";
                                echo $row['category']; 
                            ?>
                        </div>
                        <small style="color: #94a3b8;"><?php echo date('D, d M Y', strtotime($row['date'])); ?></small>
                    </td>
                    <td><b style="font-size: 18px;">₹<?php echo number_format($row['amount'], 2); ?></b></td>
                    <td><a href="../uploads/<?php echo $row['bill_img']; ?>" target="_blank" style="color:var(--primary); font-weight:800; text-decoration:none;">👀 View Bill</a></td>
                    <td>
                        <?php $s_class = str_replace(' ', '-', $row['status']); ?>
                        <span class="status-badge status-<?php echo $s_class; ?>">
                            <?php 
                                if($row['status'] == 'Pending') echo "😴 " . $row['status'];
                                elseif($row['status'] == 'Paid') echo "🥳 " . $row['status'];
                                else echo "🔎 " . $row['status'];
                            ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="requestModal" class="modal">
    <div class="modal-content">
        <h2 style="font-weight: 800; margin-bottom: 20px; text-align: center;">💰 New Request 💰</h2>
        <p style="text-align: center; font-size: 13px; color: #64748b; margin-bottom: 25px;">Fill the details below to get your refund! ✨</p>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>How much? (₹) 💸</label>
                <input type="number" name="amount" required placeholder="Enter amount here...">
            </div>
            
            <div class="form-group">
                <label>What for? 📦</label>
                <select name="category">
                    <option value="Food">🍔 Food / Snacks</option>
                    <option value="Travel">✈️ Travel / Fuel</option>
                    <option value="Internet">🌐 Internet / Bill</option>
                    <option value="Medical">💊 Medical / Health</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>When? 🗓️</label>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label>Show us the Bill! 📸</label>
                <input type="file" name="bill_image" accept="image/*,.pdf" required>
                <small style="color: #94a3b8;">Only Photos or PDF allowed! ✅</small>
            </div>
            
            <div class="form-group">
                <label>Any notes? 📝</label>
                <textarea name="description" rows="3" placeholder="Tell us more about this expense..."></textarea>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="submit" name="submit_request" class="btn-main" style="width: 100%;">🌟 Send for Approval 🌟</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">❌ Cancel & Close</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById("requestModal").style.display = "block"; }
    function closeModal() { document.getElementById("requestModal").style.display = "none"; }
    
    // Background click kela tar pan close hoil
    window.onclick = function(e) { 
        if(e.target == document.getElementById("requestModal")) closeModal(); 
    }
</script>

</body>
</html>