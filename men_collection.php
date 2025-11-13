<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
error_reporting(E_ALL);
session_start();
include "./admin/db.php";

// Verify database connection
if (!isset($conn)) {
    error_log("Connection variable not set in db.php");
    die("Connection variable not set. Check db.php include or file path.");
} elseif ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Calculate cart count for header
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

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = intval($_POST['product_id']);
    error_log("Received add_to_cart request for product_id: $product_id");
    if ($product_id > 0) {
        // Verify product exists
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
        if ($stmt === false) {
            error_log("MySQL Prepare Error for product check: " . $conn->error);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database error']);
            exit;
        }
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]++;
            } else {
                $_SESSION['cart'][$product_id] = 1;
            }
            error_log("Cart updated: " . print_r($_SESSION['cart'], true));
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
            exit;
        } else {
            error_log("Product ID $product_id not found in database");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            exit;
        }
    } else {
        error_log("Invalid product ID received: $product_id");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        exit;
    }
}

// Fetch all products where category is 'Men'
$active_tag = isset($_GET['tag']) ? $_GET['tag'] : '';
$filter_tag = isset($_GET['tag']) ? $_GET['tag'] : '';

if (!empty($filter_tag)) {
    $stmt = $conn->prepare("SELECT p.* FROM products p JOIN category c ON p.category_id = c.id WHERE c.name = 'Men' AND p.tag = ?");
    if ($stmt === false) {
        die("MySQL Prepare Error: " . $conn->error);
    }
    $stmt->bind_param("s", $filter_tag);
} else {
    $stmt = $conn->prepare("SELECT p.* FROM products p JOIN category c ON p.category_id = c.id WHERE c.name = 'Men'");
    if ($stmt === false) {
        die("MySQL Prepare Error: " . $conn->error);
    }
}

$stmt->execute();
$result = $stmt->get_result();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoes House Men Collection</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/new%20shoes%20house/collection_style.css">
</head>
<body>
<header class="header">
    <div class="container">
        <div class="logo">
            <a href="/new%20shoes%20house/index.php">Shoes House</a>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
        <nav class="navbar">
            <ul>
                <li><a href="/new%20shoes%20house/index.php">Home</a></li>
                <li class="has-dropdown">
                    <a href="#" class="active">Collections <i class="fas fa-chevron-down"></i></a>
                    <div class="dropdown-menu">
                        <a href="/new%20shoes%20house/men_collection.php">Men</a>
                        <a href="/new%20shoes%20house/women_collection.php">Women</a>
                        <a href="/new%20shoes%20house/kids_collection.php">Kids</a>
                    </div>
                </li>
                <li><a href="/new%20shoes%20house/aboutus.php">About Us</a></li>
                <li><a href="/new%20shoes%20house/Contact%20Us.php">Contact us</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
           
            <a href="/new%20shoes%20house/admin/cart.php">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?php echo $cart_count; ?></span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/new%20shoes%20house/profile.php" class="user-profile-link">
                    <?php if ($user_profile_picture && file_exists($_SERVER['DOCUMENT_ROOT'] . '/new shoes house/' . $user_profile_picture)): ?>
                        <img src="/new%20shoes%20house/<?php echo htmlspecialchars($user_profile_picture); ?>" alt="Profile Picture" class="user-profile-pic">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="/new%20shoes%20house/admin/user_login.php"><i class="fas fa-user-circle"></i></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="page-title-section">
    <div class="container">
        <h1>Our Men Collection</h1>
    </div>
</div>

<section class="categories-section">
    <div class="container">
        <h2>Shop by Brand</h2>
        <div class="category-grid">
            <div class="category-card">
                <img src="/new%20shoes%20house/images/other images/sponsor-1.png" alt="Nike Logo" class="brand-logo">
            </div>
            <div class="category-card">
                <img src="/new%20shoes%20house/images/other images/sponsor-4.png" alt="Adidas Logo" class="brand-logo">
            </div>
            <div class="category-card">
                <img src="/new%20shoes%20house/images/other images/sponsor-3.png" alt="Puma Logo" class="brand-logo">
            </div>
            <div class="category-card">
                <img src="/new%20shoes%20house/images/other images/sponsor-2.png" alt="Asics Logo" class="brand-logo">
            </div>
        </div>
    </div>
</section>

<section id="collection" class="py-5">
    <div class="container">
       
        <div class="row g-0">
            <div class="filter-bar">
                <a href="/new%20shoes%20house/men_collection.php" class="filter-btn <?= $active_tag == '' ? 'active' : '' ?>">All</a>
                <a href="/new%20shoes%20house/men_collection.php?tag=best-seller" class="filter-btn <?= $active_tag == 'best-seller' ? 'active' : '' ?>">Best Seller</a>
                <a href="/new%20shoes%20house/men_collection.php?tag=sports" class="filter-btn <?= $active_tag == 'sports' ? 'active' : '' ?>">Sports</a>
                <a href="/new%20shoes%20house/men_collection.php?tag=casual" class="filter-btn <?= $active_tag == 'casual' ? 'active' : '' ?>">Casual</a>
            </div>
            <div class="collection-list mt-4">
                <?php
                $tagMap = [
                    'Featured' => 'featured',
                    'New Arrival' => 'new-arrival',
                    'Sports' => 'sports',
                    'Casual' => 'casual',
                    '' => 'all',
                    'none' => 'all',
                    null => 'all'
                ];

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $imagePath = file_exists($_SERVER['DOCUMENT_ROOT'] . '/new shoes house/assets/uploads/' . $row['image'])
                            ? '/new%20shoes%20house/assets/uploads/' . htmlspecialchars($row['image'])
                            : '/new%20shoes%20house/images/no-image.png';
                        $rawTag = $row['tag'] ?: '';
                        $tagClass = isset($tagMap[$rawTag]) ? $tagMap[$rawTag] : strtolower(str_replace(' ', '-', htmlspecialchars($rawTag))) ?: 'all';
                        ?>
                        <div class="product-card <?= $tagClass ?>" data-product-id="<?= htmlspecialchars($row['id']) ?>">
                            <div class="collection-img position-relative p-2 shadow-sm bg-white rounded">
                                <img src="<?= $imagePath ?>" class="w-100">
                            </div>
                            <div class="text-center product-details">
                                <h5><?= htmlspecialchars($row['brand_name']) ?></h5>
                                <p class="price">₹<?= number_format($row['price'], 2) ?></p>
                                <button class="btn add-to-cart-btn" data-product-id="<?= htmlspecialchars($row['id']) ?>">Add to Cart <i class="fas fa-shopping-cart"></i></button>
                                <a href="/new%20shoes%20house/product-details.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn view-details-btn">View Details <i class="fas fa-eye"></i></a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p class="text-center">No products found in Men\'s Collection.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Include Footer -->
<?php include 'footer.php'; ?>



<script>
  // Add to cart functionality
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.getAttribute('data-product-id');
            console.log('Adding product to cart, ID:', productId);
            const data = new FormData();
            data.append('action', 'add_to_cart');
            data.append('product_id', productId);
            fetch('/new%20shoes%20house/men_collection.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                console.log('Fetch response:', data);
                if (data.success) {
                    document.querySelector('.cart-count').textContent = data.cart_count;
                    alert('Product added to cart!');
                } else {
                    alert('Error adding product to cart: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Error communicating with the server: ' + error.message);
            });
        });
    });

</script>

</body>
</html>