<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$error = ""; // Initialize error variable

// Fetch categories for dropdown
$categories_query = $conn->query("SELECT * FROM category ORDER BY name ASC");
$categories = [];
while ($row = $categories_query->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $brand_name = $_POST['brand_name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id']; // Changed from 'category'
    $tag = $_POST['tag'];
    $stocks = $_POST['stocks'];

    // Image upload
    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];
    $upload_path = "../assets/uploads/" . basename($image);

    if (move_uploaded_file($tmp, $upload_path)) {
        // Sanitize inputs to prevent SQL injection
        $brand_name = mysqli_real_escape_string($conn, $brand_name);
        $price = (float)$price; // Ensure price is a float
        $description = mysqli_real_escape_string($conn, $description);
        $category_id = (int)$category_id; // Ensure category_id is an integer
        $tag = mysqli_real_escape_string($conn, $tag);
        $stocks = (int)$stocks; // Ensure stocks is an integer
        $image = mysqli_real_escape_string($conn, $image);

        // Insert into database (updated to use category_id)
        $sql = "INSERT INTO products (brand_name, price, description, category_id, tag, image, stocks)
                VALUES ('$brand_name', '$price', '$description', '$category_id', '$tag', '$image', '$stocks')";

        if (mysqli_query($conn, $sql)) {
            // Success: go to products table
            header("Location: products.php?success=1");
            exit();
        } else {
            $error = "Database insert failed: " . mysqli_error($conn);
        }
    } else {
        $error = "Image upload failed!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
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
                    <li><a href="products.php" class="active"><i class="fas fa-shoe-prints"></i> Products</a></li>
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
                    <h1>Add New Product</h1>
                    <p>Add new shoe products to your inventory.</p>
                </div>
               
            </header>

            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

            <form class="product-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" placeholder="e.g., popular brand" required>
                </div>
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" placeholder="e.g., 1999.99"  required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tag (Optional)</label>
                    <select name="tag">
                        <option value="">None</option>
                        <option value="best-seller">Best Seller</option>
                        <option value="sports">Sports</option>
                        <option value="casual">Casual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stocks">Stocks:</label>
                    <input type="number" id="stocks" name="stocks" class="form-control" value="20" min="0" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="e.g., High-quality running shoes with cushioning" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn add-btn">➕ Add Product</button>
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