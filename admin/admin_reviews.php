<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: http://localhost/new%20shoes%20house/admin/index.php");
    exit();
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include "db.php";

// Check if connection is established
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection not initialized"));
    die("Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection not initialized"));
}

// Log connection status
error_log("Database connected: " . ($conn->ping() ? "Yes" : "No"));

// Handle review deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_review') {
    $review_id = intval($_POST['review_id']);
    if ($review_id > 0) {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        if ($stmt === false) {
            error_log("Delete Review Prepare Error: " . $conn->error);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to prepare delete query']);
            exit;
        }
        $stmt->bind_param("i", $review_id);
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            error_log("Delete Review Execute Error: " . $stmt->error);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to delete review']);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid review ID']);
        exit;
    }
}

// Fetch reviews with search/filter
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = '';
if (!empty($search_query)) {
    $where_clause = "WHERE p.brand_name LIKE '%$search_query%' OR u.fullname LIKE '%$search_query%'";
}

$reviews_query = "SELECT r.id, r.product_id, r.user_id, r.rating, r.review_text, r.created_at, 
                 COALESCE(p.brand_name, 'Unknown Product') as product_name, 
                 COALESCE(u.fullname, 'Unknown User') as fullname 
                 FROM reviews r 
                 LEFT JOIN products p ON r.product_id = p.id 
                 LEFT JOIN users u ON r.user_id = u.id 
                 $where_clause 
                 ORDER BY r.created_at DESC";
$reviews_result = $conn->query($reviews_query);
if ($reviews_result === false) {
    error_log("Query failed in reviews_result: " . $conn->error . " | Query: $reviews_query");
    $reviews_result = new stdClass();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Shoes House</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif; 
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            transition: background 0.3s, color 0.3s;
        }

        body.dark-mode {
            background: #1e293b;
            color: #f1f5f9;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            padding: 24px;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 16px 0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #475569;
            transition: color 0.2s;
        }

        .sidebar-toggle:hover {
            color: #2563eb;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav ul li {
            margin-bottom: 12px;
        }

        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #475569;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-nav ul li a i {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .sidebar-nav ul li a:hover,
        .sidebar-nav ul li a.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .main-content {
            margin-left: 260px;
            padding: 32px;
            width: calc(100% - 260px);
            background: #f8fafc;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 24px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        body.dark-mode header {
             background: #374151;
             border-color: #475569;
        }

        .header-content h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .header-content p {
            color: #64748b;
            font-size: 1rem;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-bar {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-bar input {
            padding: 10px 12px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 240px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            color: #64748b;
            font-size: 1.1rem;
        }

        .theme-toggle {
            background: #e2e8f0;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .theme-toggle:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }

        .theme-toggle i {
            font-size: 1.25rem;
            color: #475569;
        }

        .content-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        body.dark-mode .content-card {
             background: #374151;
        }

        .content-card h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        body.dark-mode .content-card h1 {
            color: #f1f5f9;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            text-align: left;
        }

        .modern-table thead {
            background-color: #f9fafb;
        }

        .modern-table th {
            padding: 16px 20px;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .modern-table tbody tr:last-child {
            border-bottom: none;
        }

        .modern-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .modern-table td {
            padding: 16px 20px;
            font-size: 0.9rem;
            color: #334155;
            vertical-align: middle;
        }

        .action-buttons-group {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }

        .btn-action {
            padding: 6px 12px;
            border: 1px solid transparent;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-delete {
            background-color: #fee2e2;
            color: #ef4444;
        }

        .btn-delete:hover {
            background-color: #fecaca;
        }

        body.dark-mode .table-container {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .modern-table {
            background-color: #374151;
        }

        body.dark-mode .modern-table thead {
            background-color: #4b5563;
        }

        body.dark-mode .modern-table th {
            color: #d1d5db;
            border-bottom: 2px solid #4b5563;
        }

        body.dark-mode .modern-table tbody tr {
            border-bottom: 1px solid #4b5563;
        }

        body.dark-mode .modern-table tbody tr:hover {
            background-color: #4b5563;
        }

        body.dark-mode .modern-table td {
            color: #f1f5f9;
        }

        body.dark-mode .btn-delete {
            background-color: #991b1b;
            color: #fecaca;
        }

        body.dark-mode .btn-delete:hover {
            background-color: #b91c1c;
        }

        .no-reviews-message {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        body.dark-mode .no-reviews-message {
            color: #d1d5db;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .search-bar input {
                width: 180px;
            }

            .modern-table th, .modern-table td {
                padding: 12px 14px;
            }
        }

        @media (max-width: 600px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: fixed;
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 16px;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .header-controls {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }

            .search-bar input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shoe-prints"></i> Shoes House</h2>
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" ><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shoe-prints"></i> Products</a></li>
                    <li><a href="categories.php" ><i class="fas fa-tags"></i> Categories</a></li>
                    <li><a href="order.php"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="admin_reviews.php" class="active"><i class="fas fa-star"></i> Reviews</a></li>
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
                    <h1>View Reviews</h1>
                    <p>View and manage customer reviews for products.</p>
                </div>
                <div class="header-controls">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search reviews...">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </header>
            <section class="content-card">
                <h1>All Reviews</h1>
                <?php if ($reviews_result instanceof stdClass): ?>
                    <p class="text-red-600">Query Error: Check logs at C:\wamp64\logs\php_error.log for details.</p>
                    <p>Failed Query: <?php echo htmlspecialchars($reviews_query); ?></p>
                <?php endif; ?>
                <div class="table-container">
                    <?php if ($reviews_result instanceof stdClass || $reviews_result->num_rows === 0): ?>
                        <div class="no-reviews-message">
                            <?php echo $reviews_result instanceof stdClass ? 'No reviews available due to query error. Check logs.' : 'No reviews found.'; ?>
                        </div>
                    <?php else: ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>User</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($review = $reviews_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($review['id']); ?></td>
                                        <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($review['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($review['rating']); ?> <i class="fas fa-star text-yellow-400"></i></td>
                                        <td><?php echo htmlspecialchars($review['review_text']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons-group">
                                                <button class="btn-action btn-delete delete-review" data-id="<?php echo $review['id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const tableBody = document.querySelector('.modern-table tbody');
            if (tableBody) {
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    if (rowText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });

        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Highlight active nav link
        const navLinks = document.querySelectorAll('.sidebar-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });

        // Delete review functionality
        document.querySelectorAll('.delete-review').forEach(button => {
            button.addEventListener('click', () => {
                if (confirm('Are you sure you want to delete this review?')) {
                    const reviewId = button.getAttribute('data-id');
                    fetch('admin_reviews.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete_review&review_id=${reviewId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            button.closest('tr').remove();
                            alert('Review deleted successfully');
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to delete review');
                    });
                }
            });
        });
    </script>
</body>
</html>