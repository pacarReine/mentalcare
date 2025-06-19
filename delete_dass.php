<?php
include 'db.php';

if (isset($_GET['Test_ID'])) {
    $testId = intval($_GET['Test_ID']);
    $deleteQuery = $conn->prepare("DELETE FROM dass_test_results WHERE Test_ID = ?");
    $deleteQuery->bind_param("i", $testId);
    $deleteQuery->execute();
    header("Location: admin_dass.php");
    exit();
} else {
    echo "Invalid request.";
}
?>
