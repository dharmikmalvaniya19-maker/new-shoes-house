<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include "./db.php"; // Adjust path if necessary

// Debug: Check if db.php loaded $conn
if (!isset($conn)) {
    die("Connection variable not set. Check db.php include or file path.");
} elseif ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if cart is empty or user not logged in
if (empty($_SESSION['cart'])) {
    $_SESSION['checkout_message'] = "Your cart is empty. Please add products before checking out.";
    header('Location: cart.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php'; // Store current page to redirect after login
    header('Location: http://localhost/new%20shoes%20house/admin/user_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = '';
$user_email = '';
$shipping_address = [];

// Fetch user details for pre-filling form
$stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
if ($stmt === false) {
    die("MySQL Prepare Error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $username = htmlspecialchars($row['fullname']);
    $user_email = htmlspecialchars($row['email']);
}
$stmt->close();

// Fetch latest shipping address from orders
$sql = "SELECT shipping_name, shipping_address, shipping_city, shipping_zip, shipping_country FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 1";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $shipping_address = $result->fetch_assoc() ?: [];
    $stmt->close();
}

$cart_items_details = [];
$total_cart_amount = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    $stmt = $conn->prepare("SELECT id, brand_name, price, image FROM products WHERE id IN ($placeholders)");
    if ($stmt === false) {
        die("MySQL Prepare Error: " . $conn->error);
    }
    $types = str_repeat('i', count($product_ids));
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $product_id = $row['id'];
        $quantity = $_SESSION['cart'][$product_id];
        $item_total = $row['price'] * $quantity;
        $total_cart_amount += $item_total;

        // Correct image path handling
        $imagePath = file_exists('../assets/uploads/' . $row['image']) ? '../assets/uploads/' . htmlspecialchars($row['image']) : '../images/no-image.png';

        $cart_items_details[] = [
            'id' => $row['id'],
            'brand_name' => htmlspecialchars($row['brand_name']),
            'price' => $row['price'],
            'image' => $imagePath,
            'quantity' => $quantity,
            'item_total' => $item_total
        ];
    }
    $stmt->close();
}

// Handle form submission for placing order
$order_placed = false;
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_name = htmlspecialchars(trim($_POST['shipping_name']));
    $shipping_address = htmlspecialchars(trim($_POST['shipping_address']));
    $shipping_city = htmlspecialchars(trim($_POST['shipping_city']));
    $shipping_zip = htmlspecialchars(trim($_POST['shipping_zip']));
    $shipping_country = htmlspecialchars(trim($_POST['shipping_country']));
    $payment_method = 'cod'; // Hardcode to COD only

    if (empty($shipping_name) || empty($shipping_address) || empty($shipping_city) || empty($shipping_zip) || empty($shipping_country)) {
        $error_message = "Please fill in all shipping details.";
    } elseif (empty($cart_items_details)) {
        $error_message = "Your cart is empty. Please add products before checking out.";
    } else {
        $conn->begin_transaction();
        try {
            // Insert into orders table without payment_details
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_name, shipping_address, shipping_city, shipping_zip, shipping_country, payment_method, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            if ($stmt === false) {
                throw new Exception("MySQL Prepare Error: " . $conn->error);
            }
            $stmt->bind_param("idssssss", $user_id, $total_cart_amount, $shipping_name, $shipping_address, $shipping_city, $shipping_zip, $shipping_country, $payment_method);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            // Insert into order_items table
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name_at_purchase, image_at_purchase, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_item === false) {
                throw new Exception("MySQL Prepare Error for order_items: " . $conn->error);
            }

            foreach ($cart_items_details as $item) {
                $product_name_at_purchase = $item['brand_name'];
                $image_at_purchase = basename($item['image']) ?: 'no-image.png';
                $quantity = $item['quantity'];
                $price_at_purchase = $item['price'];

                $stmt_item->bind_param("iissid", $order_id, $item['id'], $product_name_at_purchase, $image_at_purchase, $quantity, $price_at_purchase);
                $stmt_item->execute();
            }
            $stmt_item->close();

            $conn->commit();
            $_SESSION['cart'] = []; // Clear the cart

            // Store order details in session for confirmation page
            $_SESSION['last_order_details'] = [
                'order_id' => $order_id,
                'total_amount' => $total_cart_amount,
                'shipping_name' => $shipping_name,
                'shipping_address' => $shipping_address . ', ' . $shipping_city . ', ' . $shipping_zip . ', ' . $shipping_country,
                'payment_method' => $payment_method,
                'items' => $cart_items_details
            ];

            // Redirect to a dedicated confirmation page
            header('Location: order_confirmation.php');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error placing order: " . $e->getMessage();
            error_log("Order Placement Error: " . $e->getMessage()); // Log detailed error
        }
    }
}

// Display messages from session
if (isset($_SESSION['checkout_message'])) {
    $error_message = $_SESSION['checkout_message'];
    unset($_SESSION['checkout_message']);
}

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Shoes House</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <style>
        /* --- Global Styles & Resets --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #673ab7; /* Deep Purple */
            --secondary-color: #ffc107; /* Amber */
            --accent-color: #f44336; /* Red */
            --text-dark: #333;
            --text-light: #666;
            --background-light: #fefefe;
            --background-body: #f8f9fa; /* Light Gray */
            --border-color: #ddd;
            --shadow-soft: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.18);
            --gradient-checkout: linear-gradient(135deg, #ede7f6, #e0f2f7); /* Light purple-blue gradient */
            --payment-hover-bg: #e8f0fe; /* Light blue for payment option hover */
            --payment-active-bg: #d1c4e9; /* Light purple for active payment */
            --payment-icon-bg: #f5f5f5; /* Light gray for icon background */
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--text-light);
            background-color: var(--background-body);
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 30px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 800;
            margin-bottom: 20px;
            color: var(--text-dark);
            line-height: 1.2;
        }

        h1 { font-size: 3.8rem; }
        h2 { font-size: 3rem; }
        h3 { font-size: 2.2rem; }
        p { margin-bottom: 18px; }

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
            background-color: var(--primary-color);
            color: #fff;
            padding: 16px 32px;
            border-radius: 50px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            box-shadow: 0 6px 12px var(--shadow-soft);
        }

        .btn:hover {
            background-color: var(--accent-color);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 16px var(--shadow-medium);
        }

        .btn i {
            font-size: 1.1em;
        }

        .page-title-section {
            background-color: var(--primary-color);
            color: #fff;
            padding: 40px 0;
            text-align: center;
            box-shadow: 0 4px 15px var(--shadow-medium);
            margin-bottom: 50px;
        }

        .page-title-section h1 {
            font-size: 3.5rem;
            color: #fff;
            margin-bottom: 0;
            font-family: 'Pacifico', cursive;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
        }

        .header {
            background-color: #fff;
            box-shadow: 0 4px 15px var(--shadow-soft);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo a {
            font-family: 'Pacifico', cursive;
            font-size: 2.8rem;
            font-weight: normal;
            color: var(--primary-color);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .navbar ul {
            list-style: none;
            display: flex;
            margin: 0;
        }

        .navbar ul li {
            margin-left: 40px;
            position: relative;
        }

        .navbar ul li a {
            color: var(--text-dark);
            font-weight: 600;
            font-size: 1.1rem;
            position: relative;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .navbar ul li a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
            transition: width 0.3s ease-out;
        }

        .navbar ul li a:hover::after,
        .navbar ul li a.active::after {
            width: 100%;
        }

        .nav-icons {
            display: flex;
            align-items: center;
        }

        .nav-icons a {
            margin-left: 25px;
            color: var(--text-dark);
            font-size: 1.5rem;
            transition: color 0.3s ease, transform 0.2s ease;
            position: relative;
        }

        .nav-icons a:hover {
            color: var(--accent-color);
            transform: scale(1.15);
        }

        .cart-count {
            background-color: var(--accent-color);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 50%;
            padding: 3px 7px;
            position: absolute;
            top: -8px;
            right: -8px;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Dropdown Menu */
        .navbar .dropdown-menu {
            background-color: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 20px var(--shadow-medium);
            padding: 15px 0;
            min-width: 200px;
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s;
        }

        .navbar .has-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .navbar .dropdown-menu a {
            color: var(--text-dark);
            padding: 12px 20px;
            display: block;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .navbar .dropdown-menu a:hover {
            background-color: var(--background-light);
            color: var(--accent-color);
        }

        .navbar .dropdown-menu a.active {
            background-color: var(--primary-color);
            color: #fff;
        }

        .dropdown-toggle::after {
            display: none;
        }

        .dropdown-toggle i {
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }

        .dropdown:hover .dropdown-toggle i {
            transform: rotate(180deg);
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
            margin-left: 15px;
        }

        .user-dropdown .dropbtn {
            background-color: transparent;
            color: #333;
            padding: 0;
            font-size: 16px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .user-dropdown .dropbtn i {
            margin-right: 5px;
        }

        .user-dropdown .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            right: 0;
            border-radius: 5px;
            overflow: hidden;
            top: 100%;
        }

        .user-dropdown .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-size: 14px;
        }

        .user-dropdown .dropdown-content a:hover {
            background-color: #ddd;
        }

        .user-dropdown:hover .dropdown-content {
            display: block;
        }

        /* Checkout Page Specific Styles */
        .checkout-section {
            padding: 50px 0;
            background: var(--gradient-checkout);
        }

        .checkout-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
        }

        .checkout-container h2 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 35px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .checkout-container h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .checkout-form .form-group {
            margin-bottom: 25px;
        }

        .checkout-form label {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            display: block;
            font-size: 1.1rem;
        }

        .checkout-form .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1.05rem;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .checkout-form select.form-control {
            height: 50px;
        }

        .checkout-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(103, 58, 183, 0.2);
            outline: none;
            background-color: #fffafc;
        }

        .payment-options {
            margin-bottom: 30px;
            background-color: var(--background-light);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px var(--shadow-soft);
        }

        .payment-option {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid transparent;
        }

        .payment-option input[type="radio"] {
            margin-right: 15px;
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: var(--primary-color);
            transition: transform 0.2s ease;
        }

        .payment-option input[type="radio"]:checked {
            transform: scale(1.2);
        }

        .payment-option input[type="radio"]:checked + .payment-label {
            background-color: var(--payment-active-bg);
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px var(--shadow-medium);
        }

        .payment-label {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .payment-label:hover {
            background-color: var(--payment-hover-bg);
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px var(--shadow-soft);
        }

        .payment-icon {
            font-size: 1.8rem;
            color: var(--primary-color);
            background-color: var(--payment-icon-bg);
            padding: 10px;
            border-radius: 50%;
            transition: transform 0.3s ease, background-color 0.3s ease;
        }

        .payment-label:hover .payment-icon {
            transform: scale(1.1);
            background-color: #e0e7ff;
        }

        .payment-description {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 8px;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .payment-label:hover .payment-description {
            opacity: 1;
        }

        .payment-details {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .payment-details.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .payment-details:not(.active) {
            opacity: 0;
            transform: translateY(10px);
        }

        .checkout-order-summary {
            background-color: #f0f4f8;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
            margin-top: 0;
        }

        .checkout-order-summary h3 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 25px;
            text-align: center;
            padding-bottom: 10px;
            position: relative;
        }

        .checkout-order-summary h3::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .checkout-order-summary .summary-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px dashed #cfd8dc;
            font-size: 1.05rem;
            color: var(--text-dark);
        }

        .checkout-order-summary .summary-item:last-of-type {
            border-bottom: none;
        }

        .summary-item .product-info {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }

        .summary-item .product-info img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .summary-item .product-info div {
            flex-grow: 1;
        }

        .summary-item .product-info h6 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .summary-item .product-info small {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .summary-item span.item-price {
            font-weight: 600;
            color: var(--accent-color);
        }

        .checkout-order-summary .summary-total {
            font-weight: 700;
            font-size: 1.5rem;
            border-top: 2px solid var(--primary-color);
            padding-top: 20px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-dark);
        }

        .checkout-order-summary .summary-total span {
            color: var(--accent-color);
            font-size: 1.8rem;
        }

        .place-order-btn {
            background-color: #28a745;
            color: #fff;
            padding: 18px 35px;
            border-radius: 50px;
            font-size: 1.3rem;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 40px;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
            letter-spacing: 1px;
        }

        .place-order-btn:hover {
            background-color: #218838;
            transform: translateY(-3px);
            box-shadow: 0 9px 20px rgba(40, 167, 69, 0.5);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        .empty-cart-message {
            text-align: center;
            padding: 50px 0;
            font-size: 1.4rem;
            color: var(--text-light);
            background-color: #fdfdfd;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .empty-cart-message p {
            margin-bottom: 20px;
        }

        .empty-cart-message a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: underline;
            transition: color 0.3s ease;
        }

        .empty-cart-message a:hover {
            color: var(--accent-color);
        }

        /* --- Footer Styles - Refined --- */
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
            align-items: flex-start;
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
            text-shadow: 1px 1px 4px rgba(0,0,0,0.3);
            position: relative;
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
            display: inline-block;
        }

        .footer-section ul li a:hover {
            color: var(--secondary-color);
            text-decoration: none;
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
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .footer-section.social a:hover {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            transform: translateY(-7px) scale(1.1);
            box-shadow: 0 8px 16px rgba(0,0,0,0.4);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 50px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #aeb6bf;
            font-size: 0.88rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 991px) {
            .checkout-container .row > div {
                margin-bottom: 40px;
            }
            .checkout-order-summary {
                position: relative;
                top: auto;
            }
        }

        @media (max-width: 768px) {
            .footer-content {
                gap: 30px;
            }
            .footer-section {
                flex: 1 1 180px;
                padding: 0 10px;
            }
            .checkout-container {
                padding: 25px;
            }
            .checkout-container h2 {
                font-size: 2rem;
            }
            .checkout-order-summary h3 {
                font-size: 1.6rem;
            }
            .place-order-btn {
                padding: 15px 25px;
                font-size: 1.1rem;
            }
            .payment-option {
                padding: 10px;
            }
            .payment-icon {
                font-size: 1.5rem;
                padding: 8px;
            }
        }

        @media (max-width: 480px) {
            .footer-content {
                flex-direction: column;
                align-items: center;
                gap: 40px;
            }
            .footer-section {
                width: 90%;
                max-width: 300px;
                text-align: center;
                padding: 0;
            }
            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .page-title-section h1 {
                font-size: 2rem;
            }
            .checkout-form label {
                font-size: 1rem;
            }
            .checkout-form .form-control {
                padding: 10px 12px;
                font-size: 0.95rem;
            }
            .summary-item .product-info img {
                width: 50px;
                height: 50px;
            }
            .payment-option {
                flex-direction: column;
                align-items: flex-start;
                padding: 8px;
            }
            .payment-label {
                padding: 8px;
            }
            .payment-icon {
                font-size: 1.3rem;
                padding: 6px;
            }
        }
         /* Dropdown container */
        .has-dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: white;
            display: none;
            min-width: 150px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 999;
        }

        .has-dropdown:hover .dropdown-menu {
            display: block;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: black;
            text-decoration: none;
        }

        .dropdown-menu a:hover {
            background-color: #18d3e0ff;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <a href="index.php">Shoes House</a>
            </div>

            <nav class="navbar">
                <ul>
                    <li><a href="http://localhost/new%20shoes%20house/index.php">Home</a></li>
                    <li class="has-dropdown">
                        <a href="#">Collections <i style="font-size: 0.8em; margin-left: 5px;"></i></a>
                        <div class="dropdown-menu">
                            <a href="../men_collection.php">men</a>
                            <a href="women_collection.php">Women</a>
                            <a href="kids_collection.php">kids</a>
                        </div>
                    </li>
                    <li><a href="http://localhost/new%20shoes%20house/aboutus.php">About Us</a></li>
                    <li><a href="http://localhost/new%20shoes%20house/Contact%20Us.php">Contact Us</a></li>
                </ul>
            </nav>

            <div class="nav-icons" style="display: flex; align-items: center; gap: 15px;">
               

                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <a href="user_logout.php" class="auth-btn" style="padding: 6px 12px; background: #d9534f; color: #fff; border-radius: 4px; text-decoration: none;">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="user_login.php" class="auth-btn" style="padding: 6px 12px; background: #0275d8; color: #fff; border-radius: 4px; text-decoration: none;">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="page-title-section">
        <div class="container">
            <h1>Checkout</h1>
        </div>
    </div>

     <section class="checkout-section">
        <div class="container">
            <div class="checkout-container">
                <?php if (!empty($cart_items_details)): ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-7 col-md-12">
                            <div class="checkout-form-box">
                                <h2>Shipping & Payment Details</h2>
                                <form id="checkout-form" method="POST" action="checkout.php" class="checkout-form">
                                    <div class="form-group">
                                        <label for="shipping_name">Full Name</label>
                                        <input type="text" class="form-control" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($shipping_address['shipping_name'] ?? $username); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="shipping_address">Address</label>
                                        <input type="text" class="form-control" id="shipping_address" name="shipping_address" value="<?php echo htmlspecialchars($shipping_address['shipping_address'] ?? ''); ?>" placeholder="Street Address" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="shipping_city">City</label>
                                                <input type="text" class="form-control" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($shipping_address['shipping_city'] ?? ''); ?>" placeholder="City" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="shipping_zip">ZIP Code</label>
                                                <input type="text" class="form-control" id="shipping_zip" name="shipping_zip" value="<?php echo htmlspecialchars($shipping_address['shipping_zip'] ?? ''); ?>" placeholder="ZIP Code" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="shipping_country">Country</label>
                                        <input type="text" class="form-control" id="shipping_country" name="shipping_country" value="<?php echo htmlspecialchars($shipping_address['shipping_country'] ?? ''); ?>" placeholder="Country" required>
                                    </div>

                                    <h3>Payment Method</h3>
                                    <div class="payment-options">
                                        <div class="payment-option">
                                            <input type="radio" id="payment_cod" name="payment_method" value="cod" checked>
                                            <label for="payment_cod" class="payment-label">
                                                <i class="fas fa-money-bill-wave payment-icon"></i>
                                                <div>
                                                    Cash on Delivery
                                                    <div class="payment-description">Pay when you receive your order</div>
                                                </div>
                                            </label>
                                        </div>
                                      
                                        <button type="submit" name="place_order" class="place-order-btn">
                                        Place Order Now <i class="fas fa-arrow-right"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-12">
                            <div class="checkout-order-summary">
                                <h3>Your Order Summary</h3>
                                <div class="summary-items-list">
                                    <?php foreach ($cart_items_details as $item): ?>
                                        <div class="summary-item">
                                            <div class="product-info">
                                                <img src="<?= $item['image'] ?>" alt="<?= $item['brand_name'] ?>">
                                                <div>
                                                    <h6><?= $item['brand_name'] ?></h6>
                                                    <small>Quantity: <?= $item['quantity'] ?></small>
                                                </div>
                                            </div>
                                            <span class="item-price">₹<?= number_format($item['item_total'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="summary-total">
                                    <span>Order Total:</span>
                                    <span>₹<?= number_format($total_cart_amount, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-cart-message">
                        <p>It looks like your cart is empty!</p>
                        <a href="women_collection.php" class="btn">Continue Shopping <i class="fas fa-shopping-basket"></i></a>
                    </div>
                <?php endif; ?>
            </div>
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
                        <li><a href="men_collection.php">Men's Fun</a></li>
                        <li><a href="women_collection.php">Women's Sparkle</a></li>
                        <li><a href="kids_collection.php">Kids' Adventures</a></li>
                        <li><a href="#">Crazy Deals!</a></li>
                        <li><a href="#">Return Policy</a></li>
                        <li><a href="profile.php">My Account</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Get in Touch!</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Fun Lane, Style City, ShoeLand 12345</p>
                    <p><i class="fas fa-envelope"></i> hello@shoehouse.com</p>
                    <p><i class="fas fa-phone"></i> +1 (HAPPY-FEET)</p>
                </div>
                <div class="footer-section social">
                    <h3>Follow the Fun!</h3>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Shoes House. All rights reserved. Designed with Sole & Style!</p>
            </div>
        </div>
    </footer>

   
    <script>
        // Toggle payment details based on selected payment method
        document.querySelectorAll('input[name="payment_method"]').forEach((radio) => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-details').forEach((section) => {
                    section.classList.remove('active');
                });
            });
        });

        // Basic client-side validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method.');
                return;
            }
        });
    </script>
</body>
</html>

