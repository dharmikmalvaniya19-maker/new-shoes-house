<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$error = ""; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);

    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        // Check for duplicate category name
        $checkQuery = $conn->query("SELECT * FROM category WHERE name = '$name'");
        if ($checkQuery->num_rows > 0) {
            $error = "Category name already exists.";
        } else {
            $sql = "INSERT INTO category (name) VALUES ('$name')";
            if ($conn->query($sql)) {
                header("Location: categories.php?success=1");
                exit();
            } else {
                $error = "Failed to add category: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category</title>
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
                    <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shoe-prints"></i> Products</a></li>
                    <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
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
                    <h1>Add New Category</h1>
                    <p>Add new categories for your products.</p>
                </div>
                
            </header>

            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

            <form class="product-form" method="POST">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" placeholder="e.g., Men" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn add-btn">➕ Add Category</button>
                    <a href="categories.php" class="btn back-btn">⬅ Back to Categories</a>
                </div>
            </form>
        </div>
    </div>
    <script>
       

        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle) {
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