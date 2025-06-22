<?php
session_start();
include("db.php");

// Check if admin is logged in
if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] != 'admin') {
    $_SESSION['message'] = "Access denied. Admin privileges required.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

// Get the user ID to delete
$user_id_to_delete = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id_to_delete <= 0) {
    $_SESSION['message'] = "Invalid user ID provided.";
    $_SESSION['message_type'] = "error";
    header("Location: admin_users.php");
    exit();
}

// Get current admin's information
$current_admin_id = $_SESSION['User_ID'] ?? null;
$current_admin_username = $_SESSION['User_Username'];

// If User_ID is not in session, get it from database
if (!$current_admin_id) {
    $current_user_query = "SELECT User_ID FROM users WHERE User_Username = '" . mysqli_real_escape_string($conn, $current_admin_username) . "' LIMIT 1";
    $current_user_result = mysqli_query($conn, $current_user_query);
    
    if ($current_user_result && mysqli_num_rows($current_user_result) > 0) {
        $current_user_data = mysqli_fetch_assoc($current_user_result);
        $current_admin_id = $current_user_data['User_ID'];
        // Store in session for future use
        $_SESSION['User_ID'] = $current_admin_id;
    } else {
        $_SESSION['message'] = "Unable to verify current admin identity.";
        $_SESSION['message_type'] = "error";
        header("Location: admin_users.php");
        exit();
    }
}

// CRITICAL: Check if admin is trying to delete themselves (Primary Protection)
if ($user_id_to_delete == $current_admin_id) {
    $_SESSION['message'] = "Security Error: You cannot delete your own account. This action is prohibited for security reasons.";
    $_SESSION['message_type'] = "error";
    header("Location: admin_users.php");
    exit();
}

// Get information about the user to be deleted
$user_check_query = "SELECT User_ID, User_Username, User_Email, User_Role FROM users WHERE User_ID = $user_id_to_delete LIMIT 1";
$user_check_result = mysqli_query($conn, $user_check_query);

if (!$user_check_result || mysqli_num_rows($user_check_result) == 0) {
    $_SESSION['message'] = "User not found. The user may have already been deleted.";
    $_SESSION['message_type'] = "error";
    header("Location: admin_users.php");
    exit();
}

$user_to_delete = mysqli_fetch_assoc($user_check_result);

// CRITICAL: Secondary protection using username comparison
if ($user_to_delete['User_Username'] == $current_admin_username) {
    $_SESSION['message'] = "Security Error: You cannot delete your own account. This action is prohibited for security reasons.";
    $_SESSION['message_type'] = "error";
    header("Location: admin_users.php");
    exit();
}

// If we reach here, it's safe to proceed with deletion
try {
    // Start transaction for data integrity
    mysqli_begin_transaction($conn);
    
    // Delete related data first to avoid foreign key constraint issues
    // Adjust these based on your actual database structure
    
    // Example: Delete user sessions
    $delete_sessions = "DELETE FROM user_sessions WHERE User_ID = $user_id_to_delete";
    mysqli_query($conn, $delete_sessions);
    
    // Example: Delete user activities/logs
    $delete_activities = "DELETE FROM user_activities WHERE User_ID = $user_id_to_delete";
    mysqli_query($conn, $delete_activities);
    
    // Example: Delete DASS history
    $delete_dass = "DELETE FROM dass_results WHERE User_ID = $user_id_to_delete";
    mysqli_query($conn, $delete_dass);
    
    // Example: Delete chatbot conversations
    $delete_chatbot = "DELETE FROM chatbot_conversations WHERE User_ID = $user_id_to_delete";
    mysqli_query($conn, $delete_chatbot);
    
    // Example: Delete user exercises/responses
    $delete_exercises = "DELETE FROM user_exercises WHERE User_ID = $user_id_to_delete";
    mysqli_query($conn, $delete_exercises);
    
    // Add more related table deletions as needed based on your database structure
    // $delete_other_table = "DELETE FROM other_table WHERE User_ID = $user_id_to_delete";
    // mysqli_query($conn, $delete_other_table);
    
    // Finally, delete the user record
    $delete_user_query = "DELETE FROM users WHERE User_ID = $user_id_to_delete";
    $delete_user_result = mysqli_query($conn, $delete_user_query);
    
    if ($delete_user_result && mysqli_affected_rows($conn) > 0) {
        // Commit all changes
        mysqli_commit($conn);
        
        // Success message with deleted user info
        $_SESSION['message'] = "User '" . htmlspecialchars($user_to_delete['User_Username']) . "' (ID: $user_id_to_delete) has been successfully deleted along with all associated data.";
        $_SESSION['message_type'] = "success";
        
        // Optional: Log this action for audit purposes
        $log_message = "Admin '" . $current_admin_username . "' deleted user '" . $user_to_delete['User_Username'] . "' (ID: $user_id_to_delete)";
        error_log($log_message); // This will log to your server's error log
        
    } else {
        // Rollback if user deletion failed
        mysqli_rollback($conn);
        $_SESSION['message'] = "Failed to delete user. The operation was cancelled.";
        $_SESSION['message_type'] = "error";
    }
    
} catch (Exception $e) {
    // Rollback transaction on any error
    mysqli_rollback($conn);
    $_SESSION['message'] = "An error occurred while deleting the user: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    
    // Log the error for debugging
    error_log("Delete user error: " . $e->getMessage());
}

// Always redirect back to user management page
header("Location: admin_users.php");
exit();
?>