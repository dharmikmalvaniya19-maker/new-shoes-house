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
        error_log("Failed to prepare statement for user profile picture in index.php: " . mysqli_error($conn));
    }
}

// Fetch last three reviews for testimonials section
$reviews_sql = "SELECT r.review_text, r.rating, r.created_at, u.fullname, u.profile_picture, p.brand_name, r.user_id 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                JOIN products p ON r.product_id = p.id 
                ORDER BY r.created_at DESC LIMIT 3";
$reviews_result = mysqli_query($conn, $reviews_sql);
$last_three_reviews = [];
if ($reviews_result && mysqli_num_rows($reviews_result) > 0) {
    while ($row = mysqli_fetch_assoc($reviews_result)) {
        // Ensure profile picture path is correct
        $profile_picture = !empty($row['profile_picture']) && file_exists('assets/uploads/' . $row['profile_picture']) 
            ? 'assets/uploads/' . htmlspecialchars($row['profile_picture']) 
            : 'images/default-avatar.jpg';
        // Log if profile picture is missing (only if user_id is available)
        if (isset($row['user_id'])) {
            if (empty($row['profile_picture'])) {
                error_log("Profile picture missing for user {$row['fullname']} (ID: {$row['user_id']}) in testimonials section");
            } elseif (!file_exists('assets/uploads/' . $row['profile_picture'])) {
                error_log("Profile picture file not found: assets/uploads/{$row['profile_picture']} for user {$row['fullname']} (ID: {$row['user_id']})");
            }
        }
        $row['profile_picture'] = $profile_picture;
        $last_three_reviews[] = $row;
    }
} else {
    error_log("No reviews found for testimonials section or query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoes House - Step into Fun!</title>
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
                <li><a href="index.php" class="active">Home</a></li>
                <li class="has-dropdown">
                    <a href="#">Collections <i class="fas fa-chevron-down"></i></a>
                    <div class="dropdown-menu">
                        <a href="men_collection.php">Men</a>
                        <a href="women_collection.php">Women</a>
                        <a href="kids_collection.php">Kids</a>
                    </div>
                </li>
                <!-- <li><a href="blog.php">Blog</a></li> -->
                <li><a href="aboutus.php">About Us</a></li>
                <li><a href="Contact Us.php">Contact US</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <!-- <a href="#" id="search-icon"><i class="fas fa-search"></i></a> -->
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

<!-- <div id="search-overlay" class="search-overlay">
    <div class="search-overlay-content">
        <span class="close-search-btn">&times;</span>
        <input type="text" id="search-input" placeholder="Search for shoes, styles, brands...">
        <button id="execute-search-btn"><i class="fas fa-search"></i></button>
    </div>
</div> -->

<main>
    <section class="hero-section">
        <div class="hero-slider">
            <div class="hero-slide active">
                <img src="images\other images\3banner.jpg" alt="Stylish Multi-Colored Sneakers" class="hero-slide-image">
                <div class="hero-text">
                    <p class="hero-slide-slogan">Step Out Boldly!</p>
                    <h2>Vibrant Kicks Collection</h2>
                    <p>Discover our latest range of eye-catching sneakers that blend style and comfort.</p>
                    <a href="men_collection.php" class="btn primary-btn-hero">Explore Sneakers <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="hero-slide">
                <img src="images\other images\4banner.jpg" alt="Elegant Colorful High Heels" class="hero-slide-image">
                <div class="hero-text">
                    <p class="hero-slide-slogan">Elevate Your Style!</p>
                    <h2>Chic Heels for Every Occasion</h2>
                    <p>From dazzling party stilettos to sophisticated everyday heels, find your perfect lift.</p>
                    <a href="women_collection.php" class="btn primary-btn-hero">Shop Heels <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="hero-slide">
                <img src="images\other images\2banner.jpg" alt="Comfortable Dynamic Running Shoes" class="hero-slide-image">
                <div class="hero-text">
                    <p class="hero-slide-slogan">Experience Ultimate Comfort!</p>
                    <h2>Performance Running Shoes</h2>
                    <p>Engineered for speed and designed for comfort. Take your runs to the next level.</p>
                    <a href="kids_collection.php" class="btn primary-btn-hero">Find Running Shoes <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="slider-controls">
                <button class="slide-btn prev"><i class="fas fa-chevron-left"></i></button>
                <button class="slide-btn next"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="slider-dots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
    </section>

   <section class="brands-section">
    <div class="container">
        <h2 class="section-title">Our Featured Brands</h2>
        <div class="brands-carousel">
            <div class="brand-card" >
                <img src="images\other images\sponsor-1.png" alt="Nike" class="brand-logo">
                <!-- <span class="brand-name">Nike</span> -->
            </div>
            <div class="brand-card">
                <img src="images\other images\sponsor-4.png" alt="Adidas" class="brand-logo">
                <!-- <span class="brand-name">Adidas</span> -->
            </div>
            <div class="brand-card">
                <img src="images\other images\sponsor-3.png" alt="Puma" class="brand-logo">
                <!-- <span class="brand-name">Puma</span> -->
            </div>
            <div class="brand-card">
                <img src="images\other images\sponsor-2.png" alt="Asics" class="brand-logo">
                <!-- <span class="brand-name">Asics</span> -->
            </div>
        </div>
        <div class="carousel-overlay"></div> <!-- Keep if you plan to add overlay effects later -->
    </div>
</section>

 <section id="featured-video">
        <div class="container">
            <h2>Discover Our Story</h2>
            <div>
                <video autoplay muted loop playsinline oncontextmenu="return false;">
                    <source src="images\other images\WhatsApp Video 2025-07-31 at 21.49.26_299f3899.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </section>

    <section class="deal-of-the-day-section">
        <div class="container">
            <div class="deal-content">
                <h2>🔥 Deal of the Day! 🔥</h2>
                <h3>The Radiant Runner 5000</h3>
                <p class="deal-description">Experience cloud-like comfort and dazzling style with our top-selling running shoes, now at an unbeatable price!</p>
                <div class="deal-price">
                    <span class="old-price">₹18000</span>
                    <span class="new-price">₹14999</span>
                    <span class="discount">-33% OFF!</span>
                </div>
                <a href="http://localhost/new%20shoes%20house/product-details.php?id=35" class="btn primary-btn-deal">Grab the Deal! <i class="fas fa-tags"></i></a>
                <div class="countdown-timer" id="countdown">
                    Time Left: <span id="hours">00</span>h <span id="minutes">00</span>m <span id="seconds">00</span>s
                </div>
            </div>
            <div class="deal-image">
                <img src="images\men's collection\03top.jpg" alt="Deal of the Day Shoe">
            </div>
        </div>
    </section>

    <section class="product section" id="hottest">
        <div class="container">
            <div class="section-header">
                <h2 class="title">🔥Check out the latest arrivals </h2>
            </div>
            <div class="product-center">
                <?php
                $sql = "SELECT * FROM products ORDER BY id DESC LIMIT 6";
                $result = mysqli_query($conn, $sql);
                while ($row = mysqli_fetch_assoc($result)):
                    $imagePath = file_exists('assets/uploads/' . $row['image']) ? 'assets/uploads/' . htmlspecialchars($row['image']) : 'images/no-image.png';
                ?>
                <div class="product-card">
                    <a href="product_details.php?id=<?= $row['id']; ?>" class="product-thumb">
                        <img src="<?= $imagePath; ?>" alt="<?= $row['brand_name']; ?>">
                    </a>
                    <div class="product-info">
                        <h3><?= $row['brand_name']; ?></h3>
                        <p>₹<?= $row['price']; ?></p>
                        <a href="product-details.php?id=<?= $row['id'] ?>" class="view-btn">View Details</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="container">
            <h2>Happy Feet, Happy Stories!</h2>
            <div class="testimonial-grid">
                <?php if (!empty($last_three_reviews)): ?>
                    <?php foreach ($last_three_reviews as $review): ?>
                    <div class="testimonial-card">
                        <p>"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                        <div class="customer-info">
                            <!-- <img src="<?php echo htmlspecialchars($review['profile_picture']); ?>" alt="Customer Avatar: <?php echo htmlspecialchars($review['fullname']); ?>" class="customer-avatar" onerror="this.src='images/default-avatar.jpg';"> -->
                            <h4>- <?php echo htmlspecialchars($review['fullname']); ?> (on <?php echo htmlspecialchars($review['brand_name']); ?>)</h4>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="testimonial-card">
                        <p>"Shoes House is my new obsession! The colors are so lively, and every pair feels like walking on clouds. Highly recommend!"</p>
                        <div class="customer-info">
                            <!-- <img src="images/modi.jpeg" alt="Customer Avatar" class="customer-avatar" onerror="this.src='images/default-avatar.jpg';"> -->
                            <h4>- Aisha Khan</h4>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <p>"Never thought shoe shopping could be this fun! Their collection is super trendy, and the website is a joy to navigate. 5 stars!"</p>
                        <div class="customer-info">
                            <!-- <img src="images/bill2.jpeg" alt="Customer Avatar" class="customer-avatar" onerror="this.src='images/default-avatar.jpg';"> -->
                            <h4>- Ben Carter</h4>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <p>"Finally a shoe store that understands style AND comfort. My new sneakers are a conversation starter everywhere I go!"</p>
                        <div class="customer-info">
                            <!-- <img src="images/elon.jpeg" alt="Customer Avatar" class="customer-avatar" onerror="this.src='images/default-avatar.jpg';"> -->
                            <h4>- Clara Devi</h4>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

      <!-- Include Footer -->
    <?php include 'footer.php'; ?>



<script>




let slideIndex = 0;
const slides = document.querySelectorAll('.hero-slide');
const dots = document.querySelectorAll('.dot');

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        dots[i].classList.remove('active');
    });
    slides[index].classList.add('active');
    dots[index].classList.add('active');
}

function nextSlide() {
    slideIndex = (slideIndex + 1) % slides.length;
    showSlide(slideIndex);
}

function prevSlide() {
    slideIndex = (slideIndex - 1 + slides.length) % slides.length;
    showSlide(slideIndex);
}

document.querySelector('.slide-btn.next').addEventListener('click', nextSlide);
document.querySelector('.slide-btn.prev').addEventListener('click', prevSlide);

dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        slideIndex = index;
        showSlide(slideIndex);
    });
});

setInterval(nextSlide, 5000);

function startCountdown() {
    const countdownDate = new Date();
    countdownDate.setHours(countdownDate.getHours() + 24);
    const x = setInterval(function() {
        const now = new Date().getTime();
        const distance = countdownDate.getTime() - now;
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        document.getElementById("hours").textContent = hours.toString().padStart(2, '0');
        document.getElementById("minutes").textContent = minutes.toString().padStart(2, '0');
        document.getElementById("seconds").textContent = seconds.toString().padStart(2, '0');
        if (distance < 0) {
            clearInterval(x);
            document.getElementById("countdown").textContent = "Deal Expired!";
        }
    }, 1000);
}
startCountdown();
</script>
</body>
</html>