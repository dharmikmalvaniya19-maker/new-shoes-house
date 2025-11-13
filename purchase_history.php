<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: admin/user_login.php");  // Fixed: Assuming your login is at admin/user_login.php (based on other files)
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=shoes_website", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

    if ($order_id <= 0) {
        // Fetch all orders for the user
        $stmt = $pdo->prepare("SELECT id, total_amount, order_date, order_status FROM orders WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $show_order_list = true;
    } else {
        // Validate order_id
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            die("Order not found or you do not have access to this order.");
        }

        // Query order_items with image
        $stmt = $pdo->prepare("
            SELECT product_id, product_name_at_purchase AS name, quantity, price_at_purchase AS price, image_at_purchase AS image
            FROM order_items
            WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $show_order_list = false;
    }
} catch (PDOException $e) {
    die("Database error: Unable to connect or query the database. Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_order_list ? 'Purchase History' : 'Order Details'; ?> - Shoes House</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> 
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0e7ff, #dbeafe);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* Typography */
        .title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e3a8a;
            text-align: center;
            margin-bottom: 2rem;
            background: linear-gradient(to right, #3b82f6, #1e3a8a);
            -webkit-background-clip: text;
            color: transparent;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        .table-header {
            background: linear-gradient(to right, #3b82f6, #1e3a8a);
            color: white;
        }

        .table-header th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
        }

        .table-row {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .table-row:hover {
            background-color: #f1f5f9;
            transform: translateY(-2px);
        }

        .table-row td {
            padding: 1rem 1.5rem;
            color: #374151;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-shipped {
            background-color: #bfdbfe;
            color: #1e40af;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(to right, #3b82f6, #1e3a8a);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #2563eb, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }

        /* Product Image */
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Spinner */
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Fade-in Animation */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
                margin: 1rem;
            }

            .title {
                font-size: 1.875rem;
            }

            .table-header th,
            .table-row td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }

            .product-img {
                width: 60px;
                height: 60px;
            }

            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner" id="spinner"></div>
        <div class="content fade-in" id="content" style="display: none;">
            <?php if ($show_order_list): ?>
                <h1 class="title">Purchase History</h1>
                <?php if (empty($orders)): ?>
                    <p class="text-gray-600 text-center">No orders found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr class="table-header">
                                    <th>Order ID</th>
                                    <th>Total Amount</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="table-row">
                                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars(date('d-m-Y, h:i A', strtotime($order['order_date']))); ?></td>
                                        <td>
                                            <span class="status-badge
                                                <?php echo $order['order_status'] === 'Pending' ? 'status-pending' :
                                                    ($order['order_status'] === 'Shipped' ? 'status-shipped' : 'status-completed'); ?>">
                                                <?php echo htmlspecialchars($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="purchase_history.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <h1 class="title">Order #<?php echo htmlspecialchars($order_id); ?> Details</h1>
                <?php if (empty($order_details)): ?>
                    <p class="text-gray-600 text-center">No items found for this order. It may be empty or invalid.</p>
                <?php else: ?>
                    <?php
                    $grand_total = 0;
                    foreach ($order_details as $detail) {
                        $grand_total += $detail['quantity'] * $detail['price'];
                    }
                    ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr class="table-header">
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_details as $detail): ?>
                                    <tr class="table-row">
                                        <td>
                                            <?php
                                            // Fixed: Use root-relative paths (no leading '../')
                                            $imagePath = ($detail['image'] && $detail['image'] !== '0' && file_exists('assets/uploads/' . $detail['image']))
                                                ? 'assets/uploads/' . htmlspecialchars($detail['image'])
                                                : 'images/no-image.png';
                                            ?>
                                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($detail['name']); ?>" class="product-img">
                                        </td>
                                        <td><?php echo htmlspecialchars($detail['name']); ?></td>
                                        <td><?php echo htmlspecialchars($detail['quantity']); ?></td>
                                        <td>₹<?php echo number_format($detail['price'], 2); ?></td>
                                        <td>₹<?php echo number_format($detail['quantity'] * $detail['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-row">
                                    <td colspan="4" class="text-right font-bold">Grand Total</td>
                                    <td class="font-bold">₹<?php echo number_format($grand_total, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-8 text-center">
                        <a href="index.php" class="btn btn-primary">Back to Homepage</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Hide spinner and show content after page load
        window.addEventListener('load', () => {
            document.getElementById('spinner').style.display = 'none';
            document.getElementById('content').style.display = 'block';
        });
    </script>
</body>
</html>