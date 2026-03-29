<?php
require_once '../db.php'; 

$message = ""; 

if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); 
    $role = "Employee"; // Default role

    $checkEmail = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        $message = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Email already registered!</div>";
    } else {
        $insertQuery = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
        if ($conn->query($insertQuery) === TRUE) {
            $message = "<div class='alert success'><i class='fas fa-check-circle'></i> Account created! <a href='index.php'>Login here</a></div>";
        } else {
            $message = "<div class='alert error'>Error: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Professional Signup | Reimbursement</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome sathi link (Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4f46e5; --primary-hover: #4338ca; --text: #1e293b; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background: #0f172a;
            background-image: radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.33) 0, transparent 50%), 
                              radial-gradient(at 50% 0%, rgba(124, 58, 237, 0.33) 0, transparent 50%);
            display: flex; justify-content: center; align-items: center; min-height: 100vh; color: var(--text);
        }

        .signup-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%; max-width: 420px;
        }

        h2 { font-size: 28px; font-weight: 700; text-align: center; margin-bottom: 8px; color: #111827; }
        p.subtitle { text-align: center; color: #64748b; margin-bottom: 30px; font-size: 14px; }

        .form-group { margin-bottom: 20px; position: relative; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #374151; }

        input {
            width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 15px; outline: none; background: #fff; transition: 0.2s;
        }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }

        /* Eye Icon Style */
        .password-wrapper { position: relative; }
        .toggle-password {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #64748b; transition: 0.2s;
        }
        .toggle-password:hover { color: var(--primary); }

        .btn {
            width: 100%; padding: 14px; background: var(--primary); color: white;
            border: none; border-radius: 12px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: 0.3s; margin-top: 20px;
        }
        .btn:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .alert { padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; text-align: center; }
        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .footer { text-align: center; margin-top: 25px; font-size: 14px; color: #64748b; }
        .footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="signup-card">
    <h2>Create Account</h2>
    <p class="subtitle">Enter your details to get started</p>
    
    <?php echo $message; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="xxxxx xxxxxxx xxxxxx" required>
        </div>
        
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="xxxxxxxxxxxxxxxxxxxxx" required>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="xxxxxxxx" required>
                <i class="fa-solid fa-eye toggle-password" id="eyeIcon"></i>
            </div>
        </div>
        
        <button type="submit" name="register" class="btn">Create Account</button>
    </form>
    
    <div class="footer">
        Already have an account? <a href="index.php">Log in</a>
    </div>
</div>

<script>
    const passwordInput = document.querySelector('#password');
    const eyeIcon = document.querySelector('#eyeIcon');

    eyeIcon.addEventListener('click', function() {
        // Toggle input type
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle eye icon
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>