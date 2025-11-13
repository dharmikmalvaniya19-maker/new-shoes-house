<?php
session_start();
include "admin/db.php";

// Calculate cart count for header
$cart_count = array_sum($_SESSION['cart'] ?? []);

// Fetch user data for profile picture
$user_profile_picture = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        $user_profile_picture = $user['profile_picture'] ?? null;
        mysqli_stmt_close($stmt);
    } else {
        error_log("Failed to prepare statement for user profile picture in about_us.php: " . mysqli_error($conn));
    }
}

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle form submission
$success_message = $error_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid CSRF token. Please try again.";
    } else {
        // Server-side validation
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || strlen($name) < 2) {
            $error_message = "Name must be at least 2 characters long.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } elseif (empty($message)) {
            $error_message = "Message cannot be empty.";
        } elseif (strlen($message) < 10) {
            $error_message = "Message must be at least 10 characters long.";
        } else {
            // Use prepared statement to insert data
            $sql = "INSERT INTO contact_message (name, email, message, submitted_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("MySQL Prepare Error (inserting message): " . $conn->error);
                $error_message = "An error occurred while sending your message. Please try again later.";
            } else {
                $stmt->bind_param("sss", $name, $email, $message);
                if ($stmt->execute()) {
                    $success_message = "Thank you for your message! We'll get back to you soon.";
                    // Regenerate CSRF token after successful submission
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    error_log("MySQL Execute Error: " . $stmt->error);
                    $error_message = "Failed to send your message. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Shoes House</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        
        .contact-section {
            background-color: #f8f8f8;
            padding: 80px 0;
        }
        .contact-section .container {
            max-width: 850px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .contact-section h2 {
            text-align: center;
            font-size: 2.5em;
            color: #1a1a3d;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .contact-section p {
            text-align: center;
            color: #4a4a6a;
            font-size: 1.1em;
            margin-bottom: 40px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 1.1em;
            color: #1a1a3d;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus, .form-group textarea:focus {
            background color: #3b1cff;
            border-color:;
            outline: none;
            box-shadow: 0 0 5px rgba(59, 28, 255, 0.3);
        }
        .form-group .error-text {
            color: #c62828;
            font-size: 0.9em;
            display: none;
            margin-top: 5px;
        }
        .form-group.error .error-text {
            display: block;
        }
        .form-group.error input, .form-group.error textarea {
            border-color: #c62828;
        }
        .character-counter {
            font-size: 0.9em;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }
        #submit-btn {
            background: #3b1cff;
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            border: none;
            width: 100%;
            transition: background 0.3s ease;
        }
        #submit-btn:hover {
            background: #2e16d2;
        }
        #submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #fff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .success-message, .error-message {
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.1em;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 6px solid #2e7d32;
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            border-left: 6px solid #c62828;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <div class="logo">
            <a href="index.php">Shoes House</a>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
        <nav class="navbar">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li class="has-dropdown">
                    <a href="#">Collections <i class="fas fa-chevron-down"></i></a>
                    <div class="dropdown-menu">
                        <a href="men_collection.php">Men</a>
                        <a href="women_collection.php">Women</a>
                        <a href="kids_collection.php">Kids</a>
                    </div>
                </li>
                <li><a href="aboutus.php">About Us</a></li>
                <li><a href="Contact Us.php" class="active">Contact us</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <a href="http://localhost/new%20shoes%20house/admin/cart.php">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?php echo $cart_count; ?></span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="user-profile-link">
                    <?php if (!empty($user_profile_picture) && file_exists($user_profile_picture)): ?>
                        <img src="<?php echo htmlspecialchars($user_profile_picture); ?>" alt="Profile Picture" class="user-profile-pic">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="admin/user_login.php"><i class="fas fa-user-circle"></i></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <section class="contact-section">
        <div class="container">
            <h2>Contact Us</h2>
            <p>Got a question, idea, or just want to say hello? Fill out the form below, and we'll get back to you faster than light speed!</p>
            
            <?php if ($success_message): ?>
              <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
              <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form id="contact-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
              <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Name</label>
                <input type="text" id="name" name="name" placeholder="Your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required aria-required="true" />
                <span class="error-text" id="name-error">Please enter a name (minimum 2 characters).</span>
              </div>

              <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required aria-required="true" />
                <span class="error-text" id="email-error">Please enter a valid email address.</span>
              </div>

              <div class="form-group">
                <label for="message"><i class="fas fa-comment"></i> Message</label>
                <textarea id="message" name="message" placeholder="Write your message here..." required aria-required="true"></textarea>
                <span class="error-text" id="message-error">Message must be at least 10 characters long.</span>
                <div class="character-counter" id="message-counter">0/10 characters</div>
              </div>

              <button type="submit" id="submit-btn">Send Message </span></button>
            </form>
        </div>
    </section>
</main>



   <!-- Include Footer -->
    <?php include 'footer.php'; ?>

<script>
const hamburger = document.querySelector('.hamburger');
const navbar = document.querySelector('.navbar');

hamburger.addEventListener('click', () => {
    navbar.classList.toggle('active');
});

window.addEventListener('scroll', () => {
    const backToTop = document.getElementById('back-to-top');
    if (window.scrollY > 300) {
        backToTop.style.display = 'block';
        backToTop.style.opacity = '1';
    } else {
        backToTop.style.opacity = '0';
        setTimeout(() => backToTop.style.display = 'none', 300);
    }
});

document.getElementById('back-to-top').addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Form validation script
const form = document.getElementById('contact-form');
const submitBtn = document.getElementById('submit-btn');
const nameInput = document.getElementById('name');
const emailInput = document.getElementById('email');
const messageInput = document.getElementById('message');
const messageCounter = document.getElementById('message-counter');

// Live character counter for message
function updateCharacterCounter() {
  const messageLength = messageInput.value.length;
  messageCounter.textContent = `${messageLength}/10 characters`;
  if (messageLength < 10) {
    messageCounter.style.color = '#c62828';
  } else {
    messageCounter.style.color = '#666';
  }
}

messageInput.addEventListener('input', updateCharacterCounter);

// Initialize character counter
updateCharacterCounter();

form.addEventListener('submit', (e) => {
  let isValid = true;

  // Reset error states
  document.querySelectorAll('.form-group').forEach(group => {
    group.classList.remove('error');
    const errorText = group.querySelector('.error-text');
    if (errorText) errorText.style.display = 'none';
  });

  // Name validation
  if (nameInput.value.trim().length < 2) {
    nameInput.parentElement.classList.add('error');
    const nameError = document.getElementById('name-error');
    if (nameError) nameError.style.display = 'block';
    isValid = false;
  }

  // Email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(emailInput.value.trim())) {
    emailInput.parentElement.classList.add('error');
    const emailError = document.getElementById('email-error');
    if (emailError) emailError.style.display = 'block';
    isValid = false;
  }

  // Message validation
  if (messageInput.value.trim().length === 0) {
    messageInput.parentElement.classList.add('error');
    const messageError = document.getElementById('message-error');
    if (messageError) {
      messageError.textContent = "Message cannot be empty.";
      messageError.style.display = 'block';
    }
    isValid = false;
  } else if (messageInput.value.trim().length < 10) {
    messageInput.parentElement.classList.add('error');
    const messageError = document.getElementById('message-error');
    if (messageError) {
      messageError.textContent = "Message must be at least 10 characters long.";
      messageError.style.display = 'block';
    }
    isValid = false;
  }

  if (!isValid) {
    e.preventDefault();
  } else {
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Sending... <span class="spinner"></span>';
    // Form will submit if valid
  }
});
</script>
</body>
</html>