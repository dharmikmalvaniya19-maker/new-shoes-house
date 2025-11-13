<?php
session_start();
include "admin/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: admin/user_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages after use
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    $sql = "UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssi", $fullname, $email, $phone, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Database query error: " . mysqli_error($conn);
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $upload_dir = "Uploads/profile_pictures/";
    $file_name = $user_id . "_" . time() . "_" . basename($_FILES['profile_picture']['name']);
    $target_file = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
        $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $target_file, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Profile picture updated successfully!";
            } else {
                $error_message = "Failed to update profile picture.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database query error: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Failed to upload profile picture.";
    }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_profile_picture'])) {
    $sql = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
            unlink($user['profile_picture']);
            $sql = "UPDATE users SET profile_picture = NULL WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Profile picture removed successfully!";
                } else {
                    $error_message = "Failed to remove profile picture.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error_message = "New password and confirm password do not match.";
    } else {
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if (password_verify($current_password, $user['password'])) {
                if (preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $success_message = "Password updated successfully!";
                        } else {
                            $error_message = "Failed to update password.";
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $error_message = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    }
}

// Handle address update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_address'])) {
    $shipping_name = trim($_POST['shipping_name']);
    $shipping_address = trim($_POST['shipping_address']);
    $shipping_city = trim($_POST['shipping_city']);
    $shipping_zip = trim($_POST['shipping_zip']);
    $shipping_country = trim($_POST['shipping_country']);
    
    $sql = "UPDATE orders SET shipping_name = ?, shipping_address = ?, shipping_city = ?, shipping_zip = ?, shipping_country = ? WHERE user_id = ? ORDER BY order_date DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssi", $shipping_name, $shipping_address, $shipping_city, $shipping_zip, $shipping_country, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Address updated successfully!";
        } else {
            $error_message = "Failed to update address.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Database query error: " . mysqli_error($conn);
    }
}

// Fetch user data
$sql = "SELECT fullname, email, phone, profile_picture, password FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Failed to fetch user data: " . mysqli_error($conn);
    $user = ['fullname' => '', 'email' => '', 'phone' => '', 'profile_picture' => null, 'password' => ''];
}

// Fetch latest shipping address
$shipping_address = [];
$sql = "SELECT shipping_name, shipping_address, shipping_city, shipping_zip, shipping_country FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $shipping_address = mysqli_fetch_assoc($result) ?: [];
    mysqli_stmt_close($stmt);
}

// Fetch order history
$orders = [];
$sql = "SELECT id, total_amount, order_status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Failed to fetch orders: " . mysqli_error($conn);
}

// Fetch reviews with product names and images
$reviews = [];
$sql = "SELECT r.*, p.brand_name AS name, p.image AS image
        FROM reviews r 
        LEFT JOIN products p ON r.product_id = p.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Failed to fetch reviews: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Shoes House</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <style>
        /* Add styles for the review table image column */
        .review-table .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
                <li><a href="Contact Us.php">Contact US</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <a href="admin/cart.php">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?php echo array_sum($_SESSION['cart'] ?? []); ?></span>
            </a>
            <div class="profile-card" style="text-align:center;">
                <a href="profile.php" class="user-profile-link">
                    <?php if ($user['profile_picture'] && file_exists($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="user-profile-pic">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</header>



<main class="profile-section">
    <div class="container">
        <?php if ($success_message): ?>
            <div class="success-message" id="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Basic Info -->
        <div class="profile-card">
            <h3>Basic Info</h3>
            <div class="basic-info">
                <div class="profile-picture-group">
                    <?php if ($user['profile_picture'] && file_exists($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                        <form method="POST" class="profile-picture-form">
                            <button type="submit" name="remove_profile_picture" class="btn remove-btn">Remove Picture</button>
                        </form>
                    <?php else: ?>
                        <div class="profile-picture-placeholder"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data" class="profile-picture-form">
                        <input type="file" name="profile_picture" accept="image/*">
                        <button type="submit" class="btn submit-btn">Upload Picture</button>
                    </form>
                </div>
                <form method="POST" class="profile-info-form">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="update_profile" class="btn submit-btn">Update Profile</button>
                </form>
            </div>
        </div>

        <!-- Product Reviews -->
        <div class="profile-card">
            <h3>Product Reviews</h3>
            <?php if (empty($reviews)): ?>
                <p>No reviews found.</p>
            <?php else: ?>
                <table class="review-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>
                                    <?php
                                    $imagePath = ($review['image'] && $review['image'] !== '0' && file_exists('assets/uploads/' . $review['image']))
                                        ? 'assets/uploads/' . htmlspecialchars($review['image'])
                                        : 'images/no-image.png';
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($review['name'] ?? 'Product'); ?>" class="product-img">
                                </td>
                                <td><?php echo htmlspecialchars($review['name'] ?? 'Unknown Product'); ?></td>
                                <td>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </td>
                                <td><?php echo htmlspecialchars($review['review_text'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($review['created_at']))); ?></td>
                                <td>
                                    <a href="delete_review.php?id=<?php echo $review['id']; ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Order History -->
        <div class="profile-card">
            <h3>Order History</h3>
            <?php if (empty($orders)): ?>
                <p>No orders found.</p>
            <?php else: ?>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['order_date']))); ?></td>
                                <td>₹<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                                <td><span class="status-<?php echo htmlspecialchars($order['order_status']); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                <td><a href="purchase_history.php?order_id=<?php echo $order['id']; ?>" class="btn view-btn">View Details</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Account Settings -->
        <div class="profile-card">
            <h3>Account Settings</h3>
            <form method="POST" class="password-form">
                <h4>Change Password</h4>
                <div class="form-group password-group">
                    <label for="new_password">New Password</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="new_password" autocomplete="new-password" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="form-group password-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="confirm_password" autocomplete="new-password" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" name="update_password" class="btn submit-btn">Update Password</button>
            </form>
            <br>
            <br>
            <form method="POST" class="address-form">
                <h4 class="highlighted-heading">Update Shipping Address</h4>
                <div class="form-group">
                    <label for="shipping_name">Full Name</label>
                    <input type="text" name="shipping_name" value="<?php echo htmlspecialchars($shipping_address['shipping_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="shipping_address">Address</label>
                    <input type="text" name="shipping_address" value="<?php echo htmlspecialchars($shipping_address['shipping_address'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="shipping_city">City</label>
                    <input type="text" name="shipping_city" value="<?php echo htmlspecialchars($shipping_address['shipping_city'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="shipping_zip">Zip Code</label>
                    <input type="text" name="shipping_zip" value="<?php echo htmlspecialchars($shipping_address['shipping_zip'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="shipping_country">Country</label>
                    <input type="text" name="shipping_country" value="<?php echo htmlspecialchars($shipping_address['shipping_country'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="update_address" class="btn submit-btn">Update Address</button>
            </form>
        </div>
    </div>
</main>
<div class="profile-card" style="text-align:center; margin-top:30px;">
    <form action="user_logout.php" method="post">
        <button type="submit" class="btn delete-btn">Logout</button>
    </form>
</div>
<br>
<br>


   <!-- Include Footer -->
    <?php include 'footer.php'; ?>



<script>
const hamburger = document.querySelector('.hamburger');
const navbar = document.querySelector('.navbar');
hamburger.addEventListener('click', () => {
    navbar.classList.toggle('active');
});

document.getElementById('search-icon')?.addEventListener('click', (event) => {
    event.preventDefault();
    document.getElementById('search-overlay').classList.add('active');
});

document.querySelector('.close-search-btn')?.addEventListener('click', () => {
    document.getElementById('search-overlay').classList.remove('active');
});

// Auto-hide success message after 5 seconds
const successMessage = document.getElementById('success-message');
if (successMessage) {
    setTimeout(() => {
        successMessage.style.opacity = '0';
        setTimeout(() => successMessage.style.display = 'none', 500);
    }, 5000);
}

// Toggle password visibility
function togglePasswordVisibility(inputId, toggleElement) {
    const input = document.getElementById(inputId);
    const icon = toggleElement.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password validation on submit
document.querySelector('.password-form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    if (newPassword && !strongPasswordRegex.test(newPassword)) {
        e.preventDefault();
        alert('Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.');
    }
});
</script>
</body>
</html>