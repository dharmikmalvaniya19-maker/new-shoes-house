<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: http://localhost/new%20shoes%20house/admin/index.php");
    exit();
}

// Include database connection
include "db.php";

// Check if connection is established
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection not initialized"));
}

// Fetch real counts from the database
function getTotalProducts($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM products");
    return $result === false ? 0 : $result->fetch_assoc()['total'];
}

function getTotalUsers($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    return $result === false ? 0 : $result->fetch_assoc()['total'];
}

function getTotalAdmins($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM admin_user");
    return $result === false ? 0 : $result->fetch_assoc()['total'];
}

function getTotalOrders($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
    return $result === false ? 0 : $result->fetch_assoc()['total'];
}

function getTotalReviews($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM reviews");
    return $result === false ? 0 : $result->fetch_assoc()['total'];
}

function getTotalContacts($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM contact_form");
    return $result === false ? 0 : $result->fetch_assoc()['total'];
}

function getTotalRevenue($conn) {
    $result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status = 'Completed'");
    return $result === false ? 0 : $result->fetch_assoc()['total'] ?? 0;
}

function getPendingOrders($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'Pending'");
    return $result === false ? 0 : $result->fetch_assoc()['total'];
}

function getRecentOrders($conn) {
    $result = $conn->query("SELECT id, user_id, total_amount, order_status, order_date FROM orders ORDER BY order_date DESC LIMIT 5");
    if ($result === false) {
        return [];
    }
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
}

function getTopProducts($conn) {
    $result = $conn->query("SELECT p.brand_name, COUNT(oi.product_id) as order_count 
                            FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            GROUP BY oi.product_id 
                            ORDER BY order_count DESC LIMIT 5");
    if ($result === false) {
        return [];
    }
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    return $products;
}

$totalProducts = getTotalProducts($conn);
$totalUsers = getTotalUsers($conn);
$totalAdmins = getTotalAdmins($conn);
$totalOrders = getTotalOrders($conn);
$totalReviews = getTotalReviews($conn);
$totalContacts = getTotalContacts($conn);
$totalRevenue = getTotalRevenue($conn);
$pendingOrders = getPendingOrders($conn);
$recentOrders = getRecentOrders($conn);
$topProducts = getTopProducts($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Shoes House</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shoe-prints"></i> Products</a></li>
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
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin']); ?> 👟</h1>
                    <p>Take control of your shoe empire with real-time insights and actions.</p>
                </div>
               
            </header>
            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="add_product.php" class="action-btn"><i class="fas fa-plus"></i> Add Product</a>
                    <a href="order.php" class="action-btn"><i class="fas fa-box-open"></i> View Orders</a>
                    <a href="admin_user.php" class="action-btn"><i class="fas fa-user-plus"></i> Add Admin</a>
                    <a href="admin_contact.php" class="action-btn"><i class="fas fa-envelope"></i> View Contacts</a>
                    
                </div>
            </section>
            <section class="dashboard-grid">
                <div class="card card-gradient products">
                    <i class="fas fa-shoe-prints card-icon"></i>
                    <h3>Products</h3>
                    <p>Total: <?php echo $totalProducts; ?></p>
                    <a href="products.php" class="card-link">View Products</a>
                </div>
                <div class="card card-gradient users">
                    <i class="fas fa-users card-icon"></i>
                    <h3>Users</h3>
                    <p>Total: <?php echo $totalUsers; ?></p>
                    <a href="users.php" class="card-link">View Users</a>
                </div>
               
                <div class="card card-gradient orders">
                    <i class="fas fa-box card-icon"></i>
                    <h3>Orders</h3>
                    <p>Total: <?php echo $totalOrders; ?></p>
                    <a href="order.php" class="card-link">View Orders</a>
                </div>
                <div class="card card-gradient reviews">
                    <i class="fas fa-star card-icon"></i>
                    <h3>Reviews</h3>
                    <p>Total: <?php echo $totalReviews; ?></p>
                    <a href="admin_reviews.php" class="card-link">View Reviews</a>
                </div>
                <div class="card card-gradient contacts">
                    <i class="fas fa-envelope card-icon"></i>
                    <h3>Contact Messages</h3>
                    <p>Total: <?php echo $totalContacts; ?></p>
                    <a href="admin_contact.php" class="card-link">View Messages</a>
                </div>
                <!-- <div class="card card-gradient revenue">
                    <i class="fas fa-dollar-sign card-icon"></i>
                    <h3>Total Revenue</h3>
                    <p>₹<?php echo number_format($totalRevenue, 2); ?></p>
                    <a href="order.php" class="card-link">View Details</a>
                </div> -->
                <div class="card card-gradient pending">
                    <i class="fas fa-clock card-icon"></i>
                    <h3>Pending Orders</h3>
                    <p>Total: <?php echo $pendingOrders; ?></p>
                    <a href="order.php" class="card-link">View Pending</a>
                </div>
            </section>
            <section class="recent-activity">
                <h2>Recent Orders</h2>
                <div class="table-container">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User ID</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr><td colspan="6" class="no-data">No recent orders.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo $order['user_id']; ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                                <?php echo $order['order_status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td><a href="order.php?id=<?php echo $order['id']; ?>" class="action-btn small">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="top-products">
                <h2>Top Selling Products</h2>
                <div class="table-container">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                                <tr><td colspan="2" class="no-data">No top products data.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
                                        <td><?php echo $product['order_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <style>
        :root {
            --primary-color: #2563eb;
            --accent-color: #1d4ed8;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --bg-dark: #1e293b;
            --card-bg: #ffffff;
            --shadow: rgba(0,0,0,0.05);
            --shadow-hover: rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            transition: background 0.3s, color 0.3s;
        }

        body.dark-mode {
            background: var(--bg-dark);
            color: #f1f5f9;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: var(--card-bg);
            border-right: 1px solid #e2e8f0;
            padding: 24px;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 8px var(--shadow);
            transition: transform 0.3s ease;
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
            color: var(--text-dark);
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
            color: var(--text-light);
            transition: color 0.2s;
        }

        .sidebar-toggle:hover {
            color: var(--primary-color);
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
            color: var(--text-light);
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
            background: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 2px 4px var(--shadow);
        }

        .main-content {
            margin-left: 260px;
            padding: 32px;
            width: calc(100% - 260px);
            background: var(--bg-light);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 24px;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        .header-content h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .header-content p {
            color: var(--text-light);
            font-size: 1rem;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 16px;
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
            color: var(--text-light);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .user-profile i {
            font-size: 1.75rem;
            color: var(--primary-color);
        }

        .quick-actions {
            margin-bottom: 32px;
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        .quick-actions h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 12px 24px;
            background: var(--primary-color);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .action-btn.small {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .action-btn i {
            margin-right: 8px;
        }

        .action-btn:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 12px var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px var(--shadow-hover);
        }

        .card-gradient.products {
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            color: #ffffff;
        }

        .card-gradient.users {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: #ffffff;
        }

        .card-gradient.admins {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: #ffffff;
        }

        .card-gradient.orders {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: #ffffff;
        }

        .card-gradient.reviews {
            background: linear-gradient(135deg, #ec4899, #f472b6);
            color: #ffffff;
        }

        .card-gradient.contacts {
            background: linear-gradient(135deg, #14b8a6, #2dd4bf);
            color: #ffffff;
        }

        .card-gradient.revenue {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: #ffffff;
        }

        .card-gradient.pending {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: #ffffff;
        }

        .card-icon {
            font-size: 2.25rem;
            margin-bottom: 16px;
        }

        .card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .card p {
            font-size: 1rem;
            margin-bottom: 16px;
        }

        .card-link {
            display: inline-block;
            padding: 10px 20px;
            background: #ffffff;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }

        .card-link:hover {
            background: #e2e8f0;
            color: var(--accent-color);
            box-shadow: 0 2px 8px var(--shadow);
        }

        .recent-activity,
        .top-products {
            margin-top: 40px;
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        .recent-activity h2,
        .top-products h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .activity-table th,
        .activity-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .activity-table th {
            background: #f9fafb;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .activity-table td {
            font-size: 0.9rem;
        }

        .activity-table .no-data {
            text-align: center;
            color: var(--text-light);
            padding: 20px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        body.dark-mode .sidebar,
        body.dark-mode .card,
        body.dark-mode header,
        body.dark-mode .quick-actions,
        body.dark-mode .recent-activity,
        body.dark-mode .top-products {
            background: #374151;
            border-color: #475569;
        }

        body.dark-mode .main-content {
            background: var(--bg-dark);
        }

        body.dark-mode .sidebar-header h2,
        body.dark-mode .user-profile,
        body.dark-mode .quick-actions h2,
        body.dark-mode .card h3,
        body.dark-mode .recent-activity h2,
        body.dark-mode .top-products h2 {
            color: #f1f5f9;
        }

        body.dark-mode .sidebar-nav ul li a,
        body.dark-mode .card p,
        body.dark-mode .activity-table td {
            color: #d1d5db;
        }

        body.dark-mode .card-link {
            background: #4b5563;
            color: #f1f5f9;
        }

        body.dark-mode .card-link:hover {
            background: #6b7280;
            color: #e0e7ff;
        }

        body.dark-mode .action-btn {
            background: var(--accent-color);
        }

        body.dark-mode .action-btn:hover {
            background: #1e40af;
        }

        body.dark-mode .activity-table th {
            background: #4b5563;
            color: #f1f5f9;
        }

        body.dark-mode .activity-table td {
            border-bottom: 1px solid #475569;
        }

        body.dark-mode .activity-table .no-data {
            color: #d1d5db;
        }

        body.dark-mode .status-pending {
            background: #7c2d12;
            color: #fdba74;
        }

        body.dark-mode .status-completed {
            background: #064e3b;
            color: #6ee7b7;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .action-buttons {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
                z-index: 1000;
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

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
     

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
    </script>
</body>
</html>