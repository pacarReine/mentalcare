<?php
session_start();
include 'db.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Restrict access to admins only
if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Debug: Check if database connection exists
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle Tip Addition
if (isset($_POST['add_tip'])) {
    echo "<!-- DEBUG: Add tip form submitted -->";
    $category = $_POST['category'];
    $tip = $_POST['tip'];
    $keywords = $_POST['keywords'];
    $date = date('Y-m-d');

    // Debug: Check if all fields are filled
    if (empty($category) || empty($tip) || empty($keywords)) {
        echo "<script>alert('All fields are required!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO tips (category, tip, keywords, date_added) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $category, $tip, $keywords, $date);
        
        if ($stmt->execute()) {
            echo "<script>alert('Tip added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding tip: " . $stmt->error . "');</script>";
        }
        $stmt->close();
        
        // Remove header redirect for debugging
        // header("Location: admin_tips.php");
        // exit();
    }
}

// Handle Tip Update
if (isset($_POST['update_tip'])) {
    echo "<!-- DEBUG: Update tip form submitted -->";
    $id = $_POST['tip_id'];
    $category = $_POST['category'];
    $tip = $_POST['tip'];
    $keywords = $_POST['keywords'];

    // Debug: Check if all fields are filled
    if (empty($id) || empty($category) || empty($tip) || empty($keywords)) {
        echo "<script>alert('All fields are required for update!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE tips SET category=?, tip=?, keywords=? WHERE Tips_ID=?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("sssi", $category, $tip, $keywords, $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<script>alert('Tip updated successfully!');</script>";
            } else {
                echo "<script>alert('No tip found with that ID or no changes made.');</script>";
            }
        } else {
            echo "<script>alert('Error updating tip: " . $stmt->error . "');</script>";
        }
        $stmt->close();
        
        // Remove header redirect for debugging
        // header("Location: admin_tips.php");
        // exit();
    }
}

// Handle Tip Deletion
if (isset($_GET['delete'])) {
    echo "<!-- DEBUG: Delete request received -->";
    $id = $_GET['delete'];
    
    if (empty($id) || !is_numeric($id)) {
        echo "<script>alert('Invalid tip ID for deletion!');</script>";
    } else {
        $stmt = $conn->prepare("DELETE FROM tips WHERE Tips_ID = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<script>alert('Tip deleted successfully!');</script>";
            } else {
                echo "<script>alert('No tip found with that ID.');</script>";
            }
        } else {
            echo "<script>alert('Error deleting tip: " . $stmt->error . "');</script>";
        }
        $stmt->close();
        
        // Remove header redirect for debugging
        // header("Location: admin_tips.php");
        // exit();
    }
}

// Edit mode
$editMode = false;
$editTip = null;
if (isset($_GET['edit'])) {
    echo "<!-- DEBUG: Edit request received -->";
    $editId = $_GET['edit'];
    
    if (empty($editId) || !is_numeric($editId)) {
        echo "<script>alert('Invalid tip ID for editing!');</script>";
    } else {
        $stmt = $conn->prepare("SELECT * FROM tips WHERE Tips_ID = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $editTip = $result->fetch_assoc();
            $editMode = true;
            echo "<!-- DEBUG: Edit tip found with ID: " . $editId . " -->";
        } else {
            echo "<script>alert('No tip found with that ID.');</script>";
        }
        $stmt->close();
    }
}

// Search tips
$searchQuery = "";
$searchResults = null;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
    $searchTerm = "%$searchQuery%";
    $stmt = $conn->prepare("SELECT * FROM tips WHERE category LIKE ? OR tip LIKE ? OR keywords LIKE ? ORDER BY date_added DESC");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $searchResults = $stmt->get_result();
    $stmt->close();
} else {
    $searchResults = $conn->query("SELECT * FROM tips ORDER BY date_added DESC");
    if (!$searchResults) {
        die("Query failed: " . $conn->error);
    }
}

// Debug: Check table structure
$tableCheck = $conn->query("DESCRIBE tips");
if ($tableCheck) {
    echo "<!-- DEBUG: Table structure exists -->";
    // Uncomment below to see table structure
    // echo "<!-- DEBUG: Table columns: ";
    // while ($col = $tableCheck->fetch_assoc()) {
    //     echo $col['Field'] . " ";
    // }
    // echo "-->";
} else {
    echo "<!-- DEBUG: Table 'tips' does not exist or query failed: " . $conn->error . " -->";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tips Management - Debug Version</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Your existing CSS styles here - keeping them the same */
        * {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 30px 25px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 40px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #4a5568;
            text-decoration: none;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .logout-btn {
            margin-top: 30px;
            padding: 15px;
            width: 100%;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border: none;
            color: white;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(238, 90, 36, 0.4);
        }

        .main {
            margin-left: 280px;
            padding: 40px;
            width: calc(100% - 280px);
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.1);
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            margin: 0;
            color: #6b7280;
            font-size: 16px;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .form-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .form-container h3 {
            margin: 0 0 25px 0;
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-container input,
        .form-container textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            font-size: 16px;
            transition: all 0.3s ease;
            color: #1f2937;
        }

        .form-container input:focus,
        .form-container textarea:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-container textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn-primary {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            padding: 15px 30px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .search-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-form input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            font-size: 16px;
            transition: all 0.3s ease;
            color: #1f2937;
        }

        .search-form input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .clear-search {
            padding: 12px 20px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .clear-search:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            margin-bottom: 20px;
        }

        .table-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .tips-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.5);
        }

        .tips-table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .tips-table th {
            padding: 18px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tips-table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            color: #1f2937;
            font-size: 14px;
            vertical-align: top;
        }

        .tips-table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .tips-table tr:last-child td {
            border-bottom: none;
        }

        .tip-text {
            max-width: 400px;
            word-wrap: break-word;
            line-height: 1.4;
        }

        .keywords-text {
            max-width: 250px;
            word-wrap: break-word;
            color: #6b7280;
            font-style: italic;
            line-height: 1.4;
        }

        .action-btn {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: inline-block;
            margin-right: 5px;
        }

        .action-btn:hover {
            background: rgba(74, 144, 226, 0.1);
            transform: translateY(-1px);
        }

        .delete-btn {
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .delete-btn:hover {
            background: rgba(239, 68, 68, 0.1);
            transform: translateY(-1px);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }

        /* Debug styles */
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
        }

        @media screen and (max-width: 1024px) {
            .main {
                padding: 30px;
            }
            .form-container,
            .table-container,
            .search-container {
                padding: 20px;
            }
        }

        @media screen and (max-width: 768px) {
            .main {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .sidebar {
                display: none;
            }
            .page-header h1 {
                font-size: 24px;
            }
            .tips-table {
                font-size: 12px;
            }
            .tips-table th,
            .tips-table td {
                padding: 12px 8px;
            }
            .search-form {
                flex-direction: column;
            }
            .search-form input {
                margin-bottom: 10px;
            }
        }

        @media screen and (max-width: 480px) {
            .main {
                padding: 15px;
            }
            .page-header,
            .form-container,
            .table-container,
            .search-container {
                padding: 20px;
            }
            .page-header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php">📊 Dashboard</a>
            <a href="admin_users.php">👥 User Management</a>
            <a href="admin_exercise.php">📄 Question Management</a>
            <a href="admin_dass.php">📚 DASS History</a>
            <a href="admin_chatbot.php">🤖 Chatbot Management</a>
            <a href="admin_tips.php" class="active">💡 Tips Management</a>
        </nav>
    </div>
    <button class="logout-btn" onclick="location.href='logout.php'">Logout</button>
</div>

<div class="main">
    <div class="page-header">
        <h1>Tips Management (Debug Version)</h1>
        <p>Manage motivational tips and wellness content for users</p>
    </div>

    <!-- Debug Information -->
    <div class="debug-info">
        <strong>Debug Info:</strong><br>
        POST Data: <?= !empty($_POST) ? json_encode($_POST) : 'None' ?><br>
        GET Data: <?= !empty($_GET) ? json_encode($_GET) : 'None' ?><br>
        Edit Mode: <?= $editMode ? 'Yes' : 'No' ?><br>
        Session User: <?= $_SESSION['User_Username'] ?? 'Not set' ?><br>
        Session Role: <?= $_SESSION['User_Role'] ?? 'Not set' ?>
    </div>

    <!-- Form for Add/Edit Tip -->
    <div class="form-container">
        <form method="POST">
            <h3><?= $editMode ? 'Edit Tip' : 'Add New Tip' ?></h3>
            <div class="form-group">
                <input type="text" name="category" placeholder="Category (e.g., Motivation, Wellness)" value="<?= $editMode ? htmlspecialchars($editTip['category']) : '' ?>" required>
            </div>
            <div class="form-group">
                <textarea name="tip" placeholder="Enter your motivational tip here..." required><?= $editMode ? htmlspecialchars($editTip['tip']) : '' ?></textarea>
            </div>
            <div class="form-group">
                <input type="text" name="keywords" placeholder="Keywords (comma-separated)" value="<?= $editMode ? htmlspecialchars($editTip['keywords']) : '' ?>" required>
            </div>
            <?php if ($editMode): ?>
                <input type="hidden" name="tip_id" value="<?= $editTip['Tips_ID'] ?>">
                <button type="submit" name="update_tip" class="btn-primary">Update Tip</button>
                <a href="admin_tips.php" class="btn-secondary">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_tip" class="btn-primary">Add Tip</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Search bar -->
    <div class="search-container">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search tips by category, content, or keywords..." value="<?= htmlspecialchars($searchQuery) ?>">
            <button type="submit" class="search-btn">Search</button>
            <?php if ($searchQuery): ?>
                <a href="admin_tips.php" class="clear-search">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tips Table -->
    <div class="table-container">
        <div class="table-header">
            <h3>All Tips <?= $searchQuery ? "(Search Results for \"" . htmlspecialchars($searchQuery) . "\")" : "" ?></h3>
        </div>
        <table class="tips-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Tip Content</th>
                    <th>Keywords</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($searchResults && $searchResults->num_rows > 0): ?>
                    <?php while ($row = $searchResults->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Tips_ID']) ?></td>
                            <td><strong><?= htmlspecialchars($row['category']) ?></strong></td>
                            <td class="tip-text"><?= nl2br(htmlspecialchars($row['tip'])) ?></td>
                            <td class="keywords-text"><?= htmlspecialchars($row['keywords']) ?></td>
                            <td><?= htmlspecialchars($row['date_added']) ?></td>
                            <td>
                                <a href="?edit=<?= $row['Tips_ID'] ?>" class="action-btn">Edit</a>
                                <a href="?delete=<?= $row['Tips_ID'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this tip?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-results">
                            <?= $searchQuery ? "No tips found matching your search." : "No tips found. Add your first tip above!" ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>