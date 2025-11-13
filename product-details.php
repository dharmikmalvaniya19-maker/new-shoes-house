<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include "./admin/db.php";

// Check database connection
if (!isset($conn)) {
    die("Connection variable not set. Check db.php include or file path.");
} elseif ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch cart count for header
$cart_count = array_sum($_SESSION['cart'] ?? []);

// Fetch user data for profile picture
$user_profile_picture = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    $user_profile_picture = $user['profile_picture'];
    mysqli_stmt_close($stmt);
}

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = intval($_POST['product_id']);
    if ($product_id > 0) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]++;
        } else {
            $_SESSION['cart'][$product_id] = 1;
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        exit;
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Please log in to submit a review.']);
        exit;
    }
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $review_text = htmlspecialchars($_POST['review_text']);
    
    if ($product_id > 0 && $rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            error_log("Review Insert Prepare Error: " . $conn->error);
            die("Review Insert Prepare Error: " . $conn->error);
        }
        $stmt->bind_param("iiis", $product_id, $_SESSION['user_id'], $rating, $review_text);
        $stmt->execute();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid rating or product ID']);
        exit;
    }
}

// Fetch product details
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    die("Invalid product ID.");
}

$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN category c ON p.category_id = c.id WHERE p.id = ?");
if ($stmt === false) {
    error_log("Product Query Prepare Error: " . $conn->error);
    die("Product Query Prepare Error: " . $conn->error);
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// Fetch average rating and review count
$rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
if ($rating_stmt === false) {
    error_log("Rating Query Prepare Error: " . $conn->error);
    die("Rating Query Prepare Error: " . $conn->error);
}
$rating_stmt->bind_param("i", $product_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result()->fetch_assoc();
$avg_rating = round($rating_result['avg_rating'], 1);
$review_count = $rating_result['review_count'];

// Fetch reviews
$reviews_stmt = $conn->prepare("SELECT r.*, u.fullname FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
if ($reviews_stmt === false) {
    error_log("Reviews Query Prepare Error: " . $conn->error);
    die("Reviews Query Prepare Error: " . $conn->error);
}
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch related products
$related_stmt = $conn->prepare("SELECT p.* FROM products p JOIN category c ON p.category_id = c.id WHERE (p.category_id = ? OR p.tag = ?) AND p.id != ? LIMIT 4");
if ($related_stmt === false) {
    error_log("Related Products Query Prepare Error: " . $conn->error);
    die("Related Products Query Prepare Error: " . $conn->error);
}
$related_stmt->bind_param("isi", $product['category_id'], $product['tag'], $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
$related_products = $related_result->fetch_all(MYSQLI_ASSOC);

$imagePath = file_exists('assets/uploads/' . $product['image']) ? 'assets/uploads/' . htmlspecialchars($product['image']) : 'images/no-image.png';
$is_logged_in = isset($_SESSION['user_id']);
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoes House - <?php echo htmlspecialchars($product['brand_name']); ?> Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="collection_style.css">
    <style>
        /* --- Non-Navbar Styles (unchanged) --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #673ab7;
            --secondary-color: #ffc107;
            --accent-color: #f44336;
            --text-dark: #333;
            --text-light: #666;
            --background-light: #fefefe;
            --border-color: #eee;
            --shadow-soft: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.18);
            --shadow-vibrant: rgba(244, 67, 54, 0.3);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--text-light);
            background-color: var(--background-light);
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 15px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 800;
            margin-bottom: 20px;
            color: var(--text-dark);
            line-height: 1.2;
        }

        a {
            text-decoration: none;
            color: var(--primary-color);
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--accent-color);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(45deg, var(--primary-color), #9575cd);
            color: #fff;
            padding: 14px 28px;
            border-radius: 50px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            box-shadow: 0 4px 10px var(--shadow-soft);
        }

        .btn:hover {
            background: linear-gradient(45deg, var(--accent-color), #ff6f61);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px var(--shadow-vibrant);
        }

        .add-to-cart-btn {
            background: linear-gradient(45deg, #000000ff, #333);
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(45deg, #388e7dff, #4caf50);
        }

        .product-details-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #e3f2fd 0%, #fffde7 100%), url('https://source.unsplash.com/random/1920x1080/?texture') no-repeat center center/cover;
            background-attachment: fixed;
            position: relative;
        }

        .product-details-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            z-index: 1;
        }

        .product-details-container {
            position: relative;
            z-index: 2;
            display: flex;
            gap: 60px;
            flex-wrap: wrap;
            align-items: flex-start;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 12px 30px var(--shadow-medium);
            padding: 50px;
            overflow: hidden;
            border: 2px solid var(--primary-color);
        }

        .product-details-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .product-image {
            flex: 1;
            max-width: 550px;
            background: #f8f8f8;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px var(--shadow-soft);
            position: relative;
            transition: transform 0.4s ease;
        }

        .product-image:hover {
            transform: scale(1.05);
        }

        .product-image img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 0.6s ease, opacity 0.3s ease;
        }

        .product-image:hover img {
            transform: scale(1.15);
            opacity: 0.95;
        }

        .product-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 50%, rgba(0,0,0,0.1) 100%);
            pointer-events: none;
        }

        .product-info {
            flex: 1;
            max-width: 600px;
            padding: 30px;
            background: linear-gradient(145deg, #ffffff, #f9f9f9);
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow-soft);
        }

        .product-info h1 {
            font-size: 3.5rem;
            color: var(--text-dark);
            font-family: 'Pacifico', cursive;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }

        .product-info .price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            background: linear-gradient(45deg, #e44d26, #ff6f61);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .product-info .tag {
            display: inline-block;
            background: linear-gradient(45deg, var(--primary-color), #9575cd);
            color: #fff;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 1rem;
            margin-bottom: 25px;
            text-transform: uppercase;
            box-shadow: 0 3px 8px var(--shadow-soft);
            transition: transform 0.3s ease;
        }

        .product-info .tag:hover {
            transform: translateY(-2px);
        }

        .product-info p.description {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 30px;
            line-height: 1.9;
            padding: 20px;
            border-left: 4px solid var(--primary-color);
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
        }

        .product-details-list {
            margin-bottom: 30px;
            list-style: none;
            padding: 0;
        }

        .product-details-list li {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: color 0.3s ease;
        }

        .product-details-list li:hover {
            color: var(--primary-color);
        }

        .product-details-list li i {
            color: var(--accent-color);
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }

        .product-details-list li:hover i {
            transform: scale(1.2);
        }

        .product-actions {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .product-actions .btn {
            flex: 1;
            min-width: 160px;
            padding: 16px 30px;
            font-size: 1.1rem;
        }

        .star-rating {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
        }

        .star-rating .fas.fa-star {
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .star-rating .fas.fa-star-half-alt,
        .star-rating .far.fa-star {
            color: #ccc;
            font-size: 1.2rem;
        }

        .star-rating span {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-left: 10px;
        }

        .review-form {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 10px var(--shadow-soft);
        }

        .review-form h3 {
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        .review-form .star-input {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
        }

        .review-form .star-input input[type="radio"] {
            display: none;
        }

        .review-form .star-input label {
            font-size: 1.5rem;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .review-form .star-input label:hover,
        .review-form .star-input input:checked ~ label,
        .review-form .star-input label:hover ~ label {
            color: var(--secondary-color);
        }

        .reviews-section {
            margin-top: 40px;
        }

        .reviews-section h3 {
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        .review-item {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px var(--shadow-soft);
            margin-bottom: 20px;
        }

        .review-item .star-rating {
            margin-bottom: 10px;
        }

        .review-item p {
            font-size: 1rem;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        .review-item small {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .related-products-section {
            padding: 60px 0;
            background-color: var(--background-light);
        }

        .related-products-section h2 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 40px;
            color: var(--text-dark);
            font-family: 'Pacifico', cursive;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .related-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .related-product-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--shadow-soft);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px var(--shadow-medium);
        }

        .related-product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 3px solid var(--primary-color);
        }

        .related-product-info {
            padding: 20px;
        }

        .related-product-info h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .related-product-info .price {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .related-product-info .btn {
            width: 100%;
            text-align: center;
            padding: 12px;
            font-size: 0.9rem;
        }

        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 80px 0 40px;
            font-size: 0.95rem;
            box-shadow: inset 0 8px 20px rgba(0,0,0,0.4);
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 50px;
            margin-bottom: 40px;
        }

        .footer-section {
            flex: 1 1 220px;
            max-width: 300px;
            text-align: left;
            padding: 0 15px;
        }

        .footer-section h3 {
            color: var(--secondary-color);
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 700;
            padding-bottom: 10px;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .footer-section p {
            margin-bottom: 15px;
            line-height: 1.8;
            color: #bdc3c7;
        }

        .footer-section p i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 1.1em;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 12px;
        }

        .footer-section ul li a {
            color: #ecf0f1;
            transition: color 0.3s ease, transform 0.2s ease;
        }

        .footer-section ul li a:hover {
            color: var(--secondary-color);
            transform: translateX(5px);
        }

        .footer-section.social a {
            display: inline-block;
            color: #ecf0f1;
            font-size: 1.8rem;
            width: 48px;
            height: 48px;
            line-height: 48px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.08);
            margin: 0 8px;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
            text-align: center;
        }

        .footer-section.social a:hover {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            transform: translateY(-7px) scale(1.1);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 50px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #aeb6bf;
            font-size: 0.88rem;
        }

        @media (max-width: 992px) {
            .product-details-container {
                flex-direction: column;
                align-items: center;
                padding: 30px;
            }
            .product-image,
            .product-info {
                max-width: 100%;
            }
            .related-products-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }
            .product-info h1 {
                font-size: 2.5rem;
            }
            .product-info .price {
                font-size: 2rem;
            }
            .product-image img {
                height: 400px;
            }
            .product-actions .btn {
                min-width: 100%;
            }
            .related-products-section h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .product-info h1 {
                font-size: 2rem;
            }
            .product-info .price {
                font-size: 1.6rem;
            }
            .product-image img {
                height: 300px;
            }
            .product-details-container {
                padding: 20px;
            }
            .related-products-grid {
                grid-template-columns: 1fr;
            }
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
                        <a href="#" class="active">Collections <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-menu">
                            <a href="men_collection.php">Men</a>
                            <a href="women_collection.php">Women</a>
                            <a href="kids_collection.php">Kids</a>
                        </div>
                    </li>
                    <li><a href="blog.php">Blog</a></li>
                    <li><a href="about_us.php">About Us</a></li>
                    <li><a href="Contact Us.php">Contact</a></li>
                </ul>
            </nav>
            <div class="nav-icons">
                <a href="#" id="search-icon"><i class="fas fa-search"></i></a>
                <a href="admin/cart.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
                <?php if ($is_logged_in): ?>
                    <a href="profile.php" class="user-profile-link">
                        <?php if ($user_profile_picture && file_exists($user_profile_picture)): ?>
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

    <section class="product-details-section">
        <div class="container">
            <div class="product-details-container">
                <div class="product-image">
                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['brand_name']); ?>">
                </div>
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['brand_name']); ?></h1>
                    <div class="star-rating">
                        <?php
                        $full_stars = floor($avg_rating);
                        $half_star = ($avg_rating - $full_stars) >= 0.5 ? 1 : 0;
                        $empty_stars = 5 - $full_stars - $half_star;
                        for ($i = 0; $i < $full_stars; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        if ($half_star) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        }
                        for ($i = 0; $i < $empty_stars; $i++) {
                            echo '<i class="far fa-star"></i>';
                        }
                        ?>
                        <span><?php echo $avg_rating ? $avg_rating . ' (' . $review_count . ' reviews)' : 'No reviews yet'; ?></span>
                    </div>
                    <p class="price">₹<?php echo number_format($product['price'], 2); ?></p>
                    <span class="tag"><?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?></span>
                    <span class="tag"><?php echo htmlspecialchars($product['tag'] ?: 'Women'); ?></span>
                    <p class="description"><?php echo htmlspecialchars($product['description'] ?: 'Indulge in the perfect blend of elegance and comfort with this masterpiece from our Women\'s Collection. Crafted with premium materials, these shoes are designed to elevate your style for any occasion, offering unmatched sophistication and functionality.'); ?></p>
                    <ul class="product-details-list">
                        <li><i class="fas fa-check-circle"></i> Crafted with premium leather and breathable fabrics</li>
                        <li><i class="fas fa-shoe-prints"></i> Ergonomic design for all-day comfort</li>
                        <li><i class="fas fa-star"></i> Timeless style for casual and formal settings</li>
                        <li><i class="fas fa-truck"></i> Free shipping and hassle-free returns</li>
                    </ul>
                    <div class="product-actions">
                        <button class="btn add-to-cart-btn" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">Add to Cart <i class="fas fa-shopping-cart"></i></button>
                        <a href="/new shoes house/women_collection.php" class="btn">Back to Collection</a>
                    </div>
                    <!-- Review Form -->
                    <?php if ($is_logged_in): ?>
                        <div class="review-form">
                            <h3>Write a Review</h3>
                            <form id="review-form" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
                                <div class="star-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" id="rating-<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                        <label for="rating-<?php echo $i; ?>" class="fas fa-star"></label>
                                    <?php endfor; ?>
                                </div>
                                <div class="mb-3">
                                    <textarea name="review_text" class="form-control" rows="4" placeholder="Your review" required></textarea>
                                </div>
                                <button type="submit" class="btn">Submit Review</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="mt-3"><a href="/new shoes house/admin/user_login.php">Log in</a> to write a review.</p>
                    <?php endif; ?>
                    <!-- Reviews Section -->
                    <div class="reviews-section">
                        <h3>Customer Reviews</h3>
                        <?php if (count($reviews) > 0): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="star-rating">
                                        <?php
                                        for ($i = 0; $i < 5; $i++) {
                                            if ($i < $review['rating']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                        <span><?php echo htmlspecialchars($review['fullname']); ?></span>
                                    </div>
                                    <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                                    <small><?php echo $review['created_at']; ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No reviews yet. Be the first to review this product!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="related-products-section">
        <div class="container">
            <h2>Related Products</h2>
            <?php if (count($related_products) > 0): ?>
                <div class="related-products-grid">
                    <?php foreach ($related_products as $related_product): ?>
                        <?php
                        $related_imagePath = file_exists('assets/uploads/' . $related_product['image']) ? 'assets/uploads/' . htmlspecialchars($related_product['image']) : 'images/no-image.png';
                        ?>
                        <div class="related-product-card">
                            <img src="<?php echo $related_imagePath; ?>" alt="<?php echo htmlspecialchars($related_product['brand_name']); ?>" class="related-product-image">
                            <div class="related-product-info">
                                <h3><?php echo htmlspecialchars($related_product['brand_name']); ?></h3>
                                <p class="price">₹<?php echo number_format($related_product['price'], 2); ?></p>
                                <a href="product-details.php?id=<?php echo htmlspecialchars($related_product['id']); ?>" class="btn">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-light);">No related products found.</p>
            <?php endif; ?>
        </div>
    </section>

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
                    <li><a href="profile.php">My Account</a></li>
                    <li><a href="men_collection.php">Men's Fun</a></li>
                    <li><a href="women_collection.php">Women's Sparkle</a></li>
                    <li><a href="kids_collection.php">Kids' Adventures</a></li>
                    <li><a href="aboutus.php">Know Us</a></li>
                    <li><a href="Contact Us.php">Get Help</a></li>
                    
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Get in Touch!</h3>
                <p><i class="fas fa-map-marker-alt"></i>123 Market Street, Surat, Gujarat 395007, India</p>
                <p><i class="fas fa-envelope"></i> hello@shoehouse.com</p>
                <p><i class="fas fa-phone"></i>+91 98765 43210</p>
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

    <script src="js/jquery-3.6.0.js"></script>
    <script src="bootstrap-5.0.2-dist/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add to cart functionality
            $('.add-to-cart-btn').on('click', function() {
                var $button = $(this);
                var productId = $button.data('product-id');
                $.ajax({
                    url: 'product-details.php',
                    type: 'POST',
                    data: {
                        action: 'add_to_cart',
                        product_id: productId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('.cart-count').text(response.cart_count);
                            alert('Product added to cart!');
                        } else {
                            alert('Error adding product to cart: ' + response.error);
                        }
                    },
                    error: function() {
                        alert('Error communicating with the server.');
                    }
                });
            });

            // Review form submission
            $('#review-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var productId = $form.data('product-id');
                var formData = $form.serializeArray();
                formData.push({ name: 'action', value: 'submit_review' });
                formData.push({ name: 'product_id', value: productId });

                $.ajax({
                    url: 'product-details.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Review submitted successfully!');
                            location.reload();
                        } else {
                            alert('Error submitting review: ' + response.error);
                        }
                    },
                    error: function() {
                        alert('Error communicating with the server.');
                    }
                });
            });

            // Hamburger menu toggle
            $('.hamburger').on('click', function() {
                $('.navbar').toggleClass('active');
                $('.hamburger i').toggleClass('fa-bars fa-times');
            });

            // Dropdown menu toggle for mobile
            $('.has-dropdown').on('click', function(e) {
                if ($(window).width() <= 992) {
                    e.preventDefault();
                    $(this).toggleClass('active-mobile');
                    $(this).find('.dropdown-menu').slideToggle(200);
                }
            });

            // Scroll effect for header
            $(window).on('scroll', function() {
                if ($(this).scrollTop() > 50) {
                    $('.header').addClass('scrolled');
                } else {
                    $('.header').removeClass('scrolled');
                }
            });
        });
    </script>
</body>
</html>