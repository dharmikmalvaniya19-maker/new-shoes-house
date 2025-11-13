<?php
session_start();
include "admin/db.php";

// Calculate cart count for header
$cart_count = array_sum($_SESSION['cart'] ?? []);

// Fetch user data for profile picture (header)
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
        error_log("Failed to prepare statement for user profile picture: " . mysqli_error($conn));
    }
}

// Fetch all reviews for the page
$reviews_sql = "SELECT r.review_text, r.rating, r.created_at, u.fullname, u.profile_picture, p.brand_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                JOIN products p ON r.product_id = p.id 
                ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);
$all_reviews = [];
if ($reviews_result && mysqli_num_rows($reviews_result) > 0) {
    while ($row = mysqli_fetch_assoc($reviews_result)) {
        // Ensure profile picture path is correct
        $profile_picture = !empty($row['profile_picture']) && file_exists('assets/uploads/' . $row['profile_picture']) 
            ? 'assets/uploads/' . htmlspecialchars($row['profile_picture']) 
            : 'images/default-avatar.jpg';
        $row['profile_picture'] = $profile_picture;
        $all_reviews[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - Shoes House</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
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
                        <a href="#featured-products">Kids</a>
                    </div>
                </li>
                <li><a href="reviews.php" class="active">Reviews</a></li>
                <li><a href="#">About Us</a></li>
                <li><a href="Contact Us.php">Contact</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <a href="#" id="search-icon"><i class="fas fa-search"></i></a>
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
    <section class="testimonials-section">
        <div class="container">
            <h2>Customer Reviews</h2>
            <div class="testimonial-grid">
                <?php if (!empty($all_reviews)): ?>
                    <?php foreach ($all_reviews as $review): ?>
                    <div class="testimonial-card">
                        <p>"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                        <div class="customer-info">
                            <img src="<?php echo htmlspecialchars($review['profile_picture']); ?>" alt="Customer Avatar" class="customer-avatar">
                            <h4>- <?php echo htmlspecialchars($review['fullname']); ?> (on <?php echo htmlspecialchars($review['brand_name']); ?>)</h4>
                        </div>
                        <p>Rating: <?php echo $review['rating']; ?> ⭐</p>
                        <p>Date: <?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No reviews available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section about">
                <h3>Shoes House</h3>
                <p>Your ultimate destination for stepping out in style, comfort, and a whole lot of fun!</p>
            </div>
            <div class="footer-section links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="men_collection.php">Men's Fun</a></li>
                    <li><a href="women_collection.php">Women's Sparkle</a></li>
                    <li><a href="kids_collection.php">Kids' Adventures</a></li>
                    <li><a href="#">Crazy Deals!</a></li>
                    <li><a href="#">Return Policy</a></li>
                    <li><a href="profile.php">My Account</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Get in Touch!</h3>
                <p><i class="fas fa-map-marker-alt"></i> Fun Lane, Style City, ShoeLand 12345</p>
                <p><i class="fas fa-envelope"></i> hello@shoehouse.com</p>
                <p><i class="fas fa-phone"></i> +1 (HAPPY-FEET)</p>
            </div>
            <div class="footer-section social">
                <h3>Follow the Fun!</h3>
                <a href="#"><i class="fa-brands fa-linkedin"></i></a>
                <a href="#"><i class="fa-brands fa-facebook"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Shoes House. All rights reserved. Designed with Sole & Style!</p>
        </div>
    </div>
</footer>

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

    document.getElementById('search-icon').addEventListener('click', (event) => {
        event.preventDefault();
        document.getElementById('search-overlay').classList.add('active');
    });

    document.querySelector('.close-search-btn').addEventListener('click', () => {
        document.getElementById('search-overlay').classList.remove('active');
    });
</script>
</body>
</html>