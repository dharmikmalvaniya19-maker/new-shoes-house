<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch existing product
$result = $conn->query("SELECT * FROM products WHERE id=$id");
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Fetch categories for dropdown
$categories_query = $conn->query("SELECT * FROM category ORDER BY name ASC");
$categories = [];
while ($row = $categories_query->fetch_assoc()) {
    $categories[] = $row;
}

// Handle form submission
$error = "";
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $price = floatval($_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category_id = intval($_POST['category_id']); // Changed from 'category'
    $tag = mysqli_real_escape_string($conn, $_POST['tag']);
    $stocks = intval($_POST['stocks']); // Get stocks value and ensure it's an integer
    $image = !empty($_FILES['image']['name']) ? preg_replace("/[^A-Za-z0-9._-]/", "", basename($_FILES['image']['name'])) : $product['image'];
    $target = "../assets/uploads/" . $image;

    if (!empty($_FILES['image']['name'])) {
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $error = "Failed to upload image!";
        }
    }

    $sql = "UPDATE products SET brand_name='$brand_name', price='$price', description='$description', category_id='$category_id', tag='$tag', image='$image', stocks='$stocks' WHERE id=$id";    
    if ($conn->query($sql)) {
        header("Location: products.php");
        exit();
    } else {
        $error = "Failed to update product: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"> <!-- Common CSS link -->
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shoe-prints"></i> Shoes Admin</h2>
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" ><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="products.php" class="active"> <i class="fas fa-shoe-prints"></i> Products</a></li>
                    <li><a href="categories.php" ><i class="fas fa-tags"></i> Categories</a></li>
                    <li><a href="order.php"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="admin_reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
                    <li><a href="users.php"><i class="fas fa-globe"></i> Users</a></li>
                    <li><a href="admin_contact.php"><i class="fas fa-envelope"></i> Contact Messages</a></li>
                    <li><a href="admin_user.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                    <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Edit Product</h1>
                    <p>Modify product details for your inventory.</p>
                </div>
               
            </header>

            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

            <form class="product-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" value="<?= htmlspecialchars($product['brand_name']) ?>" placeholder="e.g., Nike Running Shoes" required>
                </div>
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" value="<?= $product['price'] ?>" placeholder="e.g., 1999.99" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tag (Optional)</label>
                    <select name="tag">
                        <option value="" <?= empty($product['tag']) ? 'selected' : '' ?>>None</option>
                        <option value="best-seller" <?= $product['tag'] == 'best-seller' ? 'selected' : '' ?>>Best Seller</option>
                        <option value="sports" <?= $product['tag'] == 'sports' ? 'selected' : '' ?>>Sports</option>
                        <option value="casual" <?= $product['tag'] == 'casual' ? 'selected' : '' ?>>Casual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stocks">Stocks:</label>
                    <input type="number" id="stocks" name="stocks" class="form-control" value="<?php echo htmlspecialchars($product['stocks'] ?? 0); ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="e.g., High-quality running shoes with cushioning" rows="4" required><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Current Image</label><br>
                    <img src="../assets/uploads/<?= htmlspecialchars($product['image']) ?>" class="product-img" alt="Product" style="width: 100px; height: auto;"><br><br>
                    <label>Change Image (Optional)</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn add-btn">💾 Save Changes</button>
                    <a href="products.php" class="btn back-btn">⬅ Back to Products</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        
        // Sidebar toggle for mobile (Adjusted for standalone behavior)
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle) { // Check if the element exists
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                const navUl = sidebar.querySelector('.sidebar-nav ul');
                if (navUl) {
                    navUl.style.display = navUl.style.display === 'block' ? 'none' : 'block';
                }
            });
        }
    </script>
</body>
</html>