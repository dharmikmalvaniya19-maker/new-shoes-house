<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT * FROM category ORDER BY name ASC");

// Placeholder function for notifications (reuse from products.php if needed)
function getNotifications() {
    return [
        ['message' => 'New category added successfully', 'time' => '2025-07-13 16:00', 'type' => 'success'],
        ['message' => 'Category update failed', 'time' => '2025-07-13 14:30', 'type' => 'error']
    ];
}

$notifications = getNotifications();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"> <!-- Common CSS link -->
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
                    <h1>Manage Categories</h1>
                    <p>View and manage your product categories.</p>
                </div>
                <div class="header-controls">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search categories...">
                        <i class="fas fa-search"></i>
                    </div>
                    
                </div>
            </header>
            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="add_category.php" class="action-btn"><i class="fas fa-plus"></i> Add Category</a>
                    <button type="submit" form="categoryForm" class="action-btn" id="deleteSelectedBtn" disabled><i class="fas fa-trash"></i> Delete Selected</button>
                </div>
            </section>
            <section class="category-table-section">
                <form id="categoryForm" action="bulk_delete_categories.php" method="POST">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoryTableBody">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="category_ids[]" value="<?php echo htmlspecialchars($row['id']); ?>"></td>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td>
                                        <a href="edit_category.php?id=<?php echo $row['id']; ?>" class="btn edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_category.php?id=<?php echo $row['id']; ?>" class="btn delete-btn" onclick="return confirm('Are you sure? This will set associated products to Uncategorized.')"><i class="fas fa-trash"></i> Delete</a>
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

        // Search functionality (client-side filtering)
        const searchInput = document.getElementById('searchInput');
        const categoryTableBody = document.getElementById('categoryTableBody');
        const rows = categoryTableBody.getElementsByTagName('tr');

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let row of rows) {
                const name = row.cells[2].textContent.toLowerCase();
                const matchesSearch = name.includes(searchTerm);
                if (matchesSearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        searchInput.addEventListener('input', applyFilters);
        applyFilters();

        // Bulk delete functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const categoryCheckboxes = document.querySelectorAll('input[name="category_ids[]"]');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

        function updateDeleteButton() {
            const checkedCount = document.querySelectorAll('input[name="category_ids[]"]:checked').length;
            deleteSelectedBtn.disabled = checkedCount === 0;
        }

        selectAllCheckbox.addEventListener('change', () => {
            categoryCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButton();
        });

        categoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateDeleteButton();
                selectAllCheckbox.checked = document.querySelectorAll('input[name="category_ids[]"]').length === document.querySelectorAll('input[name="category_ids[]"]:checked').length;
            });
        });

        document.getElementById('categoryForm').addEventListener('submit', (e) => {
            const checkedCount = document.querySelectorAll('input[name="category_ids[]"]:checked').length;
            if (checkedCount === 0) {
                e.preventDefault();
                alert('Please select at least one category to delete.');
            } else if (!confirm(`Are you sure you want to delete ${checkedCount} category(s)? This will set associated products to Uncategorized.`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>