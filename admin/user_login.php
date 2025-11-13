<?php
session_start();
include("db.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullname, $hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['fullname'] = $fullname;
                header("Location: http://localhost/new%20shoes%20house/index.php");
                exit;
            } else {
                $message = "❌ Invalid password. Please try again.";
            }
        } else {
            $message = "❌ No account found with that email.";
        }
    } else {
        $message = "❌ All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="loginpage.css">
    </head>
<body>
    <div class="container">
        <div class="glass-card">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Sign in to continue your journey</p>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email"  placeholder="Enter your email">
                    <i class="fas fa-envelope"></i>
                    <div class="error-msg" id="email-error"></div>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password">
                    <!-- <i class="fas fa-eye toggle-password" data-toggle="password"></i> -->
                    <div class="error-msg" id="password-error"></div>
                </div>
                  <button type="submit" class="btn" id="login-btn">Login</button>
            </form>
            
            <p class="signup-text">Not a member? <a href="user_register.php" id="switch-to-signup">Signup now</a></p>
            
            <?php if (!empty($message)): ?>
                <p class="error-msg"><?php echo $message; ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.querySelector('.toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye', isPassword);
            this.classList.toggle('fa-eye-slash', !isPassword);
        });

        document.getElementById('login-btn').addEventListener('click', function (e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            document.getElementById('email-error').textContent = '';
            document.getElementById('password-error').textContent = '';

            let isValid = true;

            if (!email) {
                document.getElementById('email-error').textContent = 'Email is required';
                isValid = false;
            } else if (!email.includes('@')) {
                document.getElementById('email-error').textContent = 'Invalid email format';
                isValid = false;
            }
            if (!password) {
                document.getElementById('password-error').textContent = 'Password is required';
                isValid = false;
            }

            if (isValid) {
                document.querySelector('form').submit();
            }
        });
    </script>
</head>
</body>
</html>