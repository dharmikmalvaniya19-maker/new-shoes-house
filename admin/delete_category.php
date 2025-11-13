<?php
include "db.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Check if category has associated products
    $productQuery = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id=$id");
    $productCount = $productQuery->fetch_assoc()['count'];

    if ($productCount > 0) {
        // Set products to Uncategorized (NULL category_id)
        $conn->query("UPDATE products SET category_id = NULL WHERE category_id=$id");
    }

    // Delete the category
    $sql = "DELETE FROM category WHERE id=$id";
    if ($conn->query($sql)) {
        header("Location: categories.php?deleted=1");
        exit();
    } else {
        echo "Error deleting category: " . $conn->error;
    }
} else {
    echo "No ID provided.";
}
?>