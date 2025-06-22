<?php
session_start();
include("db.php");

// Check if admin is logged in
if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Check if user ID is provided
if (isset($_GET['id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete associated records from all relevant tables
        // This assumes you have tables that relate to the users table
        
        // Delete from dass_scores if exists
        mysqli_query($conn, "DELETE FROM dass_test_results WHERE User_ID = '$user_id'");
        
        // Delete from moodtracker if exists
        mysqli_query($conn, "DELETE FROM moodtracker WHERE User_ID = '$user_id'");
        
        // Delete from motivationtips if exists
        mysqli_query($conn, "DELETE FROM motivation_tips WHERE User_ID = '$user_id'");
        
        // Delete from journal if exists
        mysqli_query($conn, "DELETE FROM journal WHERE User_ID = '$user_id'");
        
        // Delete from goal_settings if exists
        mysqli_query($conn, "DELETE FROM goal_settings WHERE User_ID = '$user_id'");
        
        // Finally delete the user
        $delete_user = mysqli_query($conn, "DELETE FROM users WHERE User_ID = '$user_id'");
        
        if (!$delete_user) {
            throw new Exception("Failed to delete user");
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        // Set success message
        $_SESSION['message'] = "User deleted successfully";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback the transaction if any query fails
        mysqli_rollback($conn);
        
        // Set error message
        $_SESSION['message'] = "Error deleting user: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
} else {
    // Set error message
    $_SESSION['message'] = "User ID not provided";
    $_SESSION['message_type'] = "error";
}

// Redirect back to admin users page
header("Location: admin_users.php");
exit();
?>