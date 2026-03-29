<?php
require_once '../db.php';

$message = "";
$step = 1; // 1: Email verify, 2: New Password set

if (isset($_POST['check_email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $step = 2;
        $user_email = $email;
    } else {
        $message = "<div class='alert error'>Email not found!</div>";
    }
}

if (isset($_POST['reset_password'])) {
    $email = $_POST['email_hidden'];
    $new_pass = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

    $update = "UPDATE users SET password = '$new_pass' WHERE email = '$email'";
    if ($conn->query($update)) {
        $message = "<div class='alert success'>Password updated! <a href='index.php'>Login now</a></div>";
        $step = 1;
    } else {
        $message = "<div class='alert error'>Error updating password.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Reimbursement</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --text: #1e293b; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { background: white; padding: 40px; border-radius: 20px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        h2 { text-align: center; margin-bottom: 20px; color: #111827; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none; }
        .btn { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .alert { padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .error { background: #fee2e2; color: #991b1b; }
        .success { background: #dcfce7; color: #166534; }
        .back { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #64748b; font-size: 14px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Reset Password</h2>
    <?php echo $message; ?>

    <?php if ($step == 1): ?>
    <form method="POST">
        <div class="form-group">
            <label>Enter Registered Email</label>
            <input type="email" name="email" placeholder="xxxxxxxxxxxxxxxxxxxxx" required>
        </div>
        <button type="submit" name="check_email" class="btn">Verify Email</button>
    </form>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="email_hidden" value="<?php echo $user_email; ?>">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="xxxxxxxx" required>
        </div>
        <button type="submit" name="reset_password" class="btn">Update Password</button>
    </form>
    <?php endif; ?>

    <a href="index.php" class="back">Back to Login</a>
</div>

</body>
</html>