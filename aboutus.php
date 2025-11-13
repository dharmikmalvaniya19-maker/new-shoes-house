
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Shoes House</title>
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
                        <a href="kids_collection.php">Kids</a>
                    </div>
                </li>
                <!-- <li><a href="blog.php">Blog</a></li> -->
                <li><a href="about_us.php" class="active">About Us</a></li>
                <li><a href="Contact Us.php">Contact us</a></li>
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
<style>
    <style>
    /* Existing styles remain unchanged */
    :root {
        --primary-color: #3b1cff;
        --secondary-color: #00b7ff;
        --shadow-soft: rgba(0, 0, 0, 0.1);
    }

    /* New styles for Our Story section */
    .about-story .story-content {
        display: flex;
        align-items: center;
        gap: 30px;
        margin-top: 30px;
    }

    .about-story .story-image {
        flex: 0 0 40%; /* Image takes 40% of the container width */
        max-width: 400px;
        height: auto;
        border-radius: 15px;
        box-shadow: 0 10px 20px var(--shadow-soft);
    }

    .about-story .story-text {
        flex: 1; /* Text takes the remaining space */
        padding: 0 20px;
    }

    .about-story h2 {
        font-size: 2.5em;
        color: #1a1a3d;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .about-story p {
        color: #4a4a6a;
        font-size: 1.1em;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .about-story .story-content {
            flex-direction: column;
            text-align: center;
        }
        .about-story .story-image {
            max-width: 100%;
            margin-bottom: 20px;
        }
        .about-story .story-text {
            padding: 0;
        }
    }
</style>
    </style>

<main>
   

   <section class="about-story">
    <div class="container">
        <div class="story-content">
            <img src="images\other images\our story.png" alt="Our Story" class="story-image" style="width: 100%; max-width: 400px; height: auto; border-radius: 15px; box-shadow: 0 10px 20px var(--shadow-soft);">
            <div class="story-text">
                <h2>Our Story</h2>
                <p>Hey there! Shoes House kicked off back in 2010 in Surat, Gujarat, as this tiny shop run by a family who were totally obsessed with cool kicks. It was just a small spot with stacks of colorful sneakers, but we had big dreams! Now, we’ve turned into this dope online store where everyone can find shoes that scream *you*. We’re all about making your feet happy and your style pop off, whether you’re chilling or flexing.</p>
                <p>We’ve teamed up with big names like Nike, Adidas, and Puma to bring you the freshest designs. It’s not just about shoes—it’s about that feeling when you slip on a pair and know you’re ready to slay the day!</p>
            </div>
        </div>
    </div>
</section>

  <section class="about-mission" style="background-color: #f8f8f8; padding: 80px 0;">
        <div class="container">
            <h2>Our Mission</h2>
            <p>At Shoes House, our mission is simple: to make every step a joyful one. We aim to provide high-quality, affordable footwear that combines style, comfort, and durability. Whether you're hitting the gym, strolling the streets, or dressing up for a special occasion, we want to be your go-to source for shoes that make you smile.</p>
            <ul style="list-style: none; padding: 0; font-size: 1.1rem; line-height: 2;">
                <li><i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 10px;"></i> Deliver exceptional customer service with a fun twist.</li>
                <li><i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 10px;"></i> Offer a diverse range of eco-friendly and trendy options.</li>
                <li><i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 10px;"></i> Inspire confidence and creativity through footwear.</li>
            </ul>
        </div>
   

    <section class="about-why-choose">
        <div class="container">
            <h2>Why We are Your Go-To</h2>
            <div class="product-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                <div class="product-card" style="padding: 30px; text-align: center;">
                    <i class="fas fa-star" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 20px;"></i>
                    <h3>Top-Notch Quality</h3>
                    <p>We only pick the best stuff from brands you trust, so your shoes last through all your adventures.</p>
                </div>
                <div class="product-card" style="padding: 30px; text-align: center;">
                    <i class="fas fa-truck" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 20px;"></i>
                    <h3>Fast & Free Shipping</h3>
                    <p>Orders over ₹1000? We got you with quick, free delivery all across India. No stress!</p>
                </div>
                <div class="product-card" style="padding: 30px; text-align: center;">
                    <i class="fas fa-fire" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 20px;"></i>
                    <h3>Trendy Collections</h3>
                    <p>From bold sneakers to chic heels, our collections are always fresh and ready to level up your style.</p>
                </div>
                <div class="product-card" style="padding: 30px; text-align: center;">
                    <i class="fas fa-headset" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 20px;"></i>
                    <h3>24/7 Support</h3>
                    <p>Got a question? Hit us up anytime, and we’ll sort you out with a smile.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-team" style="background-color: #f8f8f8; padding: 80px 0;">
        <div class="container">
            <h2>Meet Our Crew</h2>
            <div class="testimonial-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                
                <div class="testimonial-card" style="text-align: center; padding: 30px;">
                    <img src="images\profile pics\admin2.jpg" alt="Team Member" class="customer-avatar" style="width: 120px; height: 120px; margin-bottom: 20px;">
                    <h4> Divy a muliya</h4>
                    <p>Design Founder & Boss</p>
                    <p>"Yo, let’s make every step lit!"</p>
                </div>
                <div class="testimonial-card" style="text-align: center; padding: 30px;">
                    <img src="images\profile pics\admin1.jpg" alt="Team Member" class="customer-avatar" style="width: 120px; height: 120px; margin-bottom: 20px;">
                    <h4>Dharmik t malvania</h4>
                    <p>Design king</p>
                    <p>"Making shoes that turn heads."</p>
                </div>
            </div>
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
</script>
</body>
</html>
