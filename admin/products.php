<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN category c ON p.category_id = c.id");

// Fetch distinct categories
$categories_query = $conn->query("SELECT * FROM category ORDER BY name ASC");
$categories = [];
while ($row = $categories_query->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch distinct tags
$tags_query = $conn->query("SELECT DISTINCT tag FROM products WHERE tag IS NOT NULL AND tag != '' ORDER BY tag ASC");
$tags = [];
while ($row = $tags_query->fetch_assoc()) {
    $tags[] = $row['tag'];
}

// Placeholder function for notifications
function getNotifications() {
    return [
        ['message' => 'New product added successfully', 'time' => '2025-07-13 16:00', 'type' => 'success'],
        ['message' => 'Product stock low for item #45', 'time' => '2025-07-13 15:00', 'type' => 'warning'],
        ['message' => 'Product update failed', 'time' => '2025-07-13 14:30', 'type' => 'error']
    ];
}

$notifications = getNotifications();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"> 
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shoe-prints"></i> Shoe House</h2>
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
                    <h1>Manage Products</h1>
                    <p>View and manage your shoe inventory.</p>
                </div>
                <div class="header-controls">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search products...">
                        <i class="fas fa-search"></i>
                    </div>

                    <div class="filter-dropdown">
                        <select id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-dropdown">
                        <select id="tagFilter">
                            <option value="">All Tags</option>
                            <?php foreach ($tags as $tag): ?>
                                <option value="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?></option>
                            <?php endforeach; ?>
                        </select>
                            </div>
            </header>
            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="add_product.php" class="action-btn"><i class="fas fa-plus"></i> Add Product</a>
                    <button type="submit" form="productForm" class="action-btn" id="deleteSelectedBtn" disabled><i class="fas fa-trash"></i> Delete Selected</button>
                </div>
            </section>
            <section class="product-table-section">
                <form id="productForm" action="bulk_delete_products.php" method="POST">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Brand Name</th>
                                <th>Price (₹)</th>
                                <th>Category</th>
                                <th>Tag</th>
                                  <th>Stocks</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="product_ids[]" value="<?php echo htmlspecialchars($row['id']); ?>"></td>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                                    <td><?php echo number_format($row['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo htmlspecialchars($row['tag'] ?? 'None'); ?></td>
                                    <td><?php echo htmlspecialchars($row['stocks']); ?></td>
                                    <td>
                                        <?php
                                        $imagePath = '../assets/uploads/' . htmlspecialchars($row['image']);
                                        if ($row['image'] && file_exists($imagePath)) {
                                            echo '<img src="' . $imagePath . '" alt="Product" style="width: 50px; border-radius: 4px; object-fit: cover;">';
                                        } else {
                                            echo '<span style="color: #dc3545;">Image not found</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="btn delete-btn" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </form>
            </section>

        </div>
    </div>
    <script>
       

        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Search and Filter functionality (client-side filtering)
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const tagFilter = document.getElementById('tagFilter');
        const productTableBody = document.getElementById('productTableBody');
        const rows = productTableBody.getElementsByTagName('tr');

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = categoryFilter.value.toLowerCase();
            const selectedTag = tagFilter.value.toLowerCase();

            for (let row of rows) {
                const brandName = row.cells[2].textContent.toLowerCase();
                const category = row.cells[4].textContent.toLowerCase();
                const tag = row.cells[5].textContent.toLowerCase(); // Ensure this matches your HTML structure for tag

                const matchesSearch = brandName.includes(searchTerm) || category.includes(searchTerm) || tag.includes(searchTerm);
                const matchesCategory = selectedCategory === '' || category === selectedCategory;
                const matchesTag = selectedTag === '' || tag === selectedTag;

                if (matchesSearch && matchesCategory && matchesTag) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        searchInput.addEventListener('input', applyFilters);
        categoryFilter.addEventListener('change', applyFilters);
        tagFilter.addEventListener('change', applyFilters);

        // Initial filter application in case of pre-filled inputs (though not typical here)
        applyFilters();


        // Bulk delete functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const productCheckboxes = document.querySelectorAll('input[name="product_ids[]"]');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

        // Enable/disable Delete Selected button based on checkbox selection
        function updateDeleteButton() {
            const checkedCount = document.querySelectorAll('input[name="product_ids[]"]:checked').length;
            deleteSelectedBtn.disabled = checkedCount === 0;
        }

        // Select all checkboxes
        selectAllCheckbox.addEventListener('change', () => {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButton();
        });

        // Update button state when individual checkboxes change
        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateDeleteButton();
                selectAllCheckbox.checked = document.querySelectorAll('input[name="product_ids[]"]').length === document.querySelectorAll('input[name="product_ids[]"]:checked').length;
            });
        });

        // Confirm before submitting bulk delete
        document.getElementById('productForm').addEventListener('submit', (e) => {
            const checkedCount = document.querySelectorAll('input[name="product_ids[]"]:checked').length;
            if (checkedCount === 0) {
                e.preventDefault();
                alert('Please select at least one product to delete.');
            } else if (!confirm(`Are you sure you want to delete ${checkedCount} product(s)?`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>