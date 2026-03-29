<?php
session_start();
require_once '../db.php'; 

$message = ""; 

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $selected_role = $_POST['role'];

    // Email ani Role match honari query
    $sql = "SELECT * FROM users WHERE email = '$email' AND role = '$selected_role'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // --- FIX ETHAA AAHE: password_verify kadhun direct compare kela ---
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Role nusar redirection (Check if folders exist)
            if ($user['role'] == 'Manager') {
                header("Location: ../dashboards/manager.php");
            } elseif ($user['role'] == 'Finance') {
                header("Location: ../dashboards/finance.php");
            } elseif ($user['role'] == 'Director' || $user['role'] == 'CFO') {
                header("Location: ../dashboards/director.php");
            } else {
                header("Location: ../dashboards/user.php");
            }
            exit();
        } else {
            $message = "<div class='alert error'><i class='fas fa-lock'></i> Incorrect password!</div>";
        }
    } else {
        $message = "<div class='alert error'><i class='fas fa-user-times'></i> User not found with this role!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Professional Login | Reimbursement</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4f46e5; --primary-hover: #4338ca; --text: #1e293b; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background: #0f172a;
            background-image: radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.33) 0, transparent 50%), 
                              radial-gradient(at 0% 0%, rgba(124, 58, 237, 0.33) 0, transparent 50%);
            display: flex; justify-content: center; align-items: center; min-height: 100vh; color: var(--text);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%; max-width: 420px;
        }

        h2 { font-size: 28px; font-weight: 700; text-align: center; margin-bottom: 8px; color: #111827; }
        p.subtitle { text-align: center; color: #64748b; margin-bottom: 30px; font-size: 14px; }

        .form-group { margin-bottom: 20px; position: relative; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #374151; }

        input, select {
            width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 15px; outline: none; background: #fff; transition: 0.2s;
        }
        input:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        
        .forgot-pass { display: block; text-align: right; margin-top: 8px; font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600; }

        .password-wrapper { position: relative; }
        .toggle-password {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #64748b;
        }

        .btn {
            width: 100%; padding: 14px; background: var(--primary); color: white;
            border: none; border-radius: 12px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: 0.3s; margin-top: 15px;
        }
        .btn:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .alert { padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; text-align: center; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .footer { text-align: center; margin-top: 25px; font-size: 14px; color: #64748b; }
        .footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Welcome Back</h2>
    <p class="subtitle">Select your role and log in</p>
    
    <?php echo $message; ?>

    <form method="POST">
        <div class="form-group">
            <label>Login As</label>
            <select name="role" required>
                <option value="Employee">Employee / User</option>
                <option value="Manager">Manager</option>
                <option value="Finance">Finance Department</option>
                <option value="Director">Director / CFO</option>
            </select>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="xxxxxxxxxxxxxxxxxx" required>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="xxxxxxxxxx" required>
                <i class="fa-solid fa-eye toggle-password" id="eyeIcon"></i>
            </div>
            <a href="forgot.php" class="forgot-pass">Forgot password?</a>
        </div>
        
        <button type="submit" name="login" class="btn">Access Dashboard</button>
    </form>
    
    <div class="footer">
        New here? <a href="signup.php">Create an account</a>
    </div>
</div>

<script>
    const passwordInput = document.querySelector('#password');
    const eyeIcon = document.querySelector('#eyeIcon');

    eyeIcon.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>