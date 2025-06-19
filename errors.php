<?php
// Start a session to capture errors
session_start();

// Initialize an array for errors
if (!isset($_SESSION['errors'])) {
    $_SESSION['errors'] = [];
}

// Capture and display errors if any
if (!empty($_SESSION['errors'])) {
    foreach ($_SESSION['errors'] as $error) {
        echo "<div class='error-message'>$error</div>";
    }

    // Clear the errors after displaying
    unset($_SESSION['errors']);
}
?>
