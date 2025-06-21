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
    $stmt = $conn->prepare("SELECT * FROM tips WHERE category LIKE ? OR tip LIKE ? OR keywords LIKE ? ORDER BY date_added DESC");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $searchResults = $stmt->get_result();
    $stmt->close();
} else {
    $searchResults = $conn->query("SELECT * FROM tips ORDER BY date_added DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tips Management - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
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

        .alert {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #10b981;
            color: #065f46;
            font-weight: 500;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .form-container h3 {
            margin: 0 0 25px 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }

        .form-container input,
        .form-container textarea {
            width: 100%;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            transition: all 0.3s ease;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
        }

        .form-container input:focus,
        .form-container textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-container textarea {
            resize: vertical;
            min-height: 120px;
        }

        .btn-primary {
            padding: 15px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            padding: 15px 25px;
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-left: 15px;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
        }

        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-form input[type="text"] {
            padding: 15px 20px;
            font-size: 16px;
            flex: 1;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            transition: all 0.3s ease;
        }

        .search-form input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            padding: 15px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .clear-search {
            padding: 15px 25px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .clear-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(238, 90, 36, 0.4);
        }

        .tips-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .tips-header {
            margin-bottom: 25px;
        }

        .tips-header h3 {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .tip-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .tip-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .tip-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .tip-category {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .tip-date {
            color: #6b7280;
            font-size: 14px;
        }

        .tip-content {
            color: #374151;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .tip-keywords {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .tip-keywords strong {
            color: #374151;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: none;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .delete-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: none;
        }

        .delete-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(238, 90, 36, 0.4);
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            font-size: 18px;
        }

        @media screen and (max-width: 1024px) {
            .main {
                padding: 30px;
            }
            .form-container,
            .search-section,
            .tips-container {
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
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            .search-form input[type="text"] {
                width: 100%;
                margin-bottom: 15px;
            }
            .tip-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .action-buttons {
                flex-direction: column;
            }
        }

        @media screen and (max-width: 480px) {
            .main {
                padding: 15px;
            }
            .page-header,
            .form-container,
            .search-section,
            .tips-container {
                padding: 15px;
            }
            .page-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>🌟 Admin Panel</h2>
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
        <p>Create and manage motivational tips and wellness content to inspire and support users on their mental health journey.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST">
            <h3><?= $editMode ? '✏️ Edit Tip' : 'Add New Tip' ?></h3>
            <input type="text" name="category" placeholder="Category (e.g., Motivation, Wellness, Self-Care, Mindfulness)" value="<?= $editMode ? htmlspecialchars($editTip['category']) : '' ?>" required>
            <textarea name="tip" placeholder="Enter your motivational tip here... Make it inspiring and actionable!" required><?= $editMode ? htmlspecialchars($editTip['tip']) : '' ?></textarea>
            <input type="text" name="keywords" placeholder="Keywords (comma-separated for better searchability)" value="<?= $editMode ? htmlspecialchars($editTip['keywords']) : '' ?>" required>
            <?php if ($editMode): ?>
                <input type="hidden" name="tip_id" value="<?= $editTip['Tips_ID'] ?>">
                <button type="submit" name="update_tip" class="btn-primary">Update Tip</button>
                <a href="admin_tips.php" class="btn-secondary">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_tip" class="btn-primary">Add Tip</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="search-section">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="🔍 Search tips by category, content, or keywords..." value="<?= htmlspecialchars($searchQuery) ?>">
            <button type="submit" class="search-btn">Search</button>
            <?php if ($searchQuery): ?>
                <button type="button" class="clear-search" onclick="window.location.href='tips_management.php'">Clear</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="tips-container">
        <div class="tips-header">
            <h3>Tips Collection</h3>
        </div>
        
        <?php if ($searchResults && $searchResults->num_rows > 0): ?>
            <?php while ($row = $searchResults->fetch_assoc()): ?>
                <div class="tip-item">
                    <div class="tip-header">
                        <div>
                            <div class="tip-category"><?= htmlspecialchars($row['category']) ?></div>
                        </div>
                        <div class="tip-date"><?= date('M j, Y', strtotime($row['date_added'])) ?></div>
                    </div>
                    
                    <div class="tip-content">
                        <?= nl2br(htmlspecialchars($row['tip'])) ?>
                    </div>
                    
                    <div class="tip-keywords">
                        <strong>Keywords:</strong> <?= htmlspecialchars($row['keywords']) ?>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="?edit=<?= $row['Tips_ID'] ?>" class="action-btn">✏️ Edit</a>
                        <a href="?delete=<?= $row['Tips_ID'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this tip? This action cannot be undone.')">🗑️ Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <?php if ($searchQuery): ?>
                    🔍 No tips found matching your search criteria.
                <?php else: ?>
                    📝 No tips available yet. Start by adding your first motivational tip!
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>