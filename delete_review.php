
<?php
session_start();
include "admin/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: admin/user_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$review_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($review_id > 0) {
    // Verify the review belongs to the user
    $sql = "SELECT user_id FROM reviews WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $review_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $review = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($review && $review['user_id'] == $user_id) {
            // Delete the review
            $sql = "DELETE FROM reviews WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $review_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success_message'] = "Review deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to delete review.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $_SESSION['error_message'] = "Database query error: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_message'] = "Review not found or you don't have permission to delete it.";
        }
    } else {
        $_SESSION['error_message'] = "Database query error: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error_message'] = "Invalid review ID.";
}

header("Location: profile.php");
exit;
