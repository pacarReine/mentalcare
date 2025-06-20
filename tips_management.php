<?php
session_start();
include 'db.php';

// Restrict access to admins only
if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

// Add Tip
if (isset($_POST['add_tip'])) {
    $category = trim($_POST['category']);
    $tip = trim($_POST['tip']);
    $keywords = trim($_POST['keywords']);
    $date = date('Y-m-d');

    if (empty($category) || empty($tip) || empty($keywords)) {
        $message = "All fields are required!";
    } else {
        $stmt = $conn->prepare("INSERT INTO tips (category, tip, keywords, date_added) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $category, $tip, $keywords, $date);
        $stmt->execute();
        $message = $stmt->affected_rows > 0 ? "Tip added successfully!" : "Error adding tip.";
        $stmt->close();
    }
}

// Update Tip
if (isset($_POST['update_tip'])) {
    $id = $_POST['tip_id'];
    $category = trim($_POST['category']);
    $tip = trim($_POST['tip']);
    $keywords = trim($_POST['keywords']);

    if (empty($id) || empty($category) || empty($tip) || empty($keywords)) {
        $message = "All fields are required for update!";
    } else {
        $stmt = $conn->prepare("UPDATE tips SET category=?, tip=?, keywords=? WHERE Tips_ID=?");
        $stmt->bind_param("sssi", $category, $tip, $keywords, $id);
        $stmt->execute();
        $message = $stmt->affected_rows > 0 ? "Tip updated successfully!" : "No changes made or tip not found.";
        $stmt->close();
    }
}

// Delete Tip
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if (!empty($id) && is_numeric($id)) {
        $stmt = $conn->prepare("DELETE FROM tips WHERE Tips_ID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = $stmt->affected_rows > 0 ? "Tip deleted successfully!" : "No tip found with that ID.";
        $stmt->close();
    }
}

// Edit Mode
$editMode = false;
$editTip = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    if (!empty($editId) && is_numeric($editId)) {
        $stmt = $conn->prepare("SELECT * FROM tips WHERE Tips_ID = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $editTip = $result->fetch_assoc();
            $editMode = true;
        }
        $stmt->close();
    }
}

// Search
$searchQuery = "";
$searchResults = null;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
    $searchTerm = "%$searchQuery%";
    $stmt = $conn->prepare("SELECT * FROM tips WHERE category LIKE ? OR tip LIKE ? OR keywords LIKE ? ORDER BY date_added ASC");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $searchResults = $stmt->get_result();
    $stmt->close();
} else {
    $searchResults = $conn->query("SELECT * FROM tips ORDER BY date_added ASC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tips Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            margin: 0;
        }

        .sidebar {
            width: 220px;
            background: #1e293b;
            color: #fff;
            padding: 20px;
            min-height: 100vh;
        }

        .sidebar-header h2 {
            margin: 0 0 20px;
            font-size: 22px;
        }

        .sidebar-nav a {
            display: block;
            padding: 10px;
            color: #cbd5e1;
            text-decoration: none;
            margin-bottom: 10px;
            border-radius: 6px;
        }

        .sidebar-nav a.active,
        .sidebar-nav a:hover {
            background: #3b82f6;
            color: #fff;
        }

        .logout-btn {
            margin-top: 20px;
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
        }

        .main {
            flex-grow: 1;
            padding: 30px;
            background: #f1f5f9;
        }

        .page-header h1 {
            margin: 0 0 10px;
        }

        .alert {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .form-container {
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .form-container input,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 16px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-secondary {
            background-color: #6b7280;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
            margin-left: 10px;
        }

        .search-form {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-form input {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }

        .search-btn,
        .clear-search {
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
        }

        .search-btn {
            background: #3b82f6;
            border: none;
        }

        .clear-search {
            background-color: #ef4444;
            border: none;
            cursor: pointer;
        }

        .tips-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        .tips-table th, .tips-table td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
        }

        .tips-table th {
            background: #f3f4f6;
        }

        .tips-table .action-btn,
        .tips-table .delete-btn {
            padding: 6px 10px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }

        .action-btn {
            background: #3b82f6;
        }

        .delete-btn {
            background: #ef4444;
        }

        .no-results {
            text-align: center;
            color: #6b7280;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="sidebar">
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
    <button class="logout-btn" onclick="location.href='logout.php'">Logout</button>
</div>

<div class="main">
    <div class="page-header">
        <h1>Tips Management</h1>
        <p>Manage motivational tips and wellness content for users.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST">
            <h3><?= $editMode ? 'Edit Tip' : 'Add New Tip' ?></h3>
            <input type="text" name="category" placeholder="Category (e.g., Motivation, Wellness)" value="<?= $editMode ? htmlspecialchars($editTip['category']) : '' ?>" required>
            <textarea name="tip" placeholder="Enter your motivational tip here..." required><?= $editMode ? htmlspecialchars($editTip['tip']) : '' ?></textarea>
            <input type="text" name="keywords" placeholder="Keywords (comma-separated)" value="<?= $editMode ? htmlspecialchars($editTip['keywords']) : '' ?>" required>
            <?php if ($editMode): ?>
                <input type="hidden" name="tip_id" value="<?= $editTip['Tips_ID'] ?>">
                <button type="submit" name="update_tip" class="btn-primary">Update Tip</button>
                <a href="admin_tips.php" class="btn-secondary">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_tip" class="btn-primary">Add Tip</button>
            <?php endif; ?>
        </form>
    </div>

    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search tips by category, content, or keywords..." value="<?= htmlspecialchars($searchQuery) ?>">
        <button type="submit" class="search-btn">Search</button>
        <?php if ($searchQuery): ?>
            <button type="button" class="clear-search" onclick="window.location.href='tips_management.php'">Clear</button>
        <?php endif; ?>
    </form>

    <table class="tips-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Category</th>
                <th>Tip Content</th>
                <th>Keywords</th>
                <th>Date Added</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($searchResults && $searchResults->num_rows > 0): ?>
            <?php $no = 1; while ($row = $searchResults->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['tip'])) ?></td>
                    <td><?= htmlspecialchars($row['keywords']) ?></td>
                    <td><?= htmlspecialchars($row['date_added']) ?></td>
                    <td>
                        <a href="?edit=<?= $row['Tips_ID'] ?>" class="action-btn">Edit</a>
                        <a href="?delete=<?= $row['Tips_ID'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this tip?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" class="no-results">No tips found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
