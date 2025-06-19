<?php
session_start();
include 'db.php';

if (!isset($_SESSION['User_Username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['User_Username'];
$userQuery = $conn->query("SELECT User_ID FROM users WHERE User_Username = '$username'");
$userRow = $userQuery->fetch_assoc();
$user_id = $userRow['User_ID'] ?? 0;

$message = "";
$edit_mode = false;
$edit_data = null;

// Handle edit mode
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM motivation_tips WHERE Motivation_ID = $edit_id AND User_ID = $user_id");
    
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
        $edit_mode = true;
    }
}

// Handle update
if (isset($_POST['update'])) {
    $motivation_id = intval($_POST['motivation_id']);
    $title = $_POST['title'];
    $content = $_POST['content'];

    $stmt = $conn->prepare("UPDATE motivation_tips SET Motivation_Title = ?, Motivation_Content = ? WHERE Motivation_ID = ? AND User_ID = ?");
    $stmt->bind_param("ssii", $title, $content, $motivation_id, $user_id);

    if ($stmt->execute()) {
        $message = "✅ Motivation tip updated successfully!";
        // Clear edit mode after successful update
        $edit_mode = false;
        $edit_data = null;
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle add motivation
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['update'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $stmt = $conn->prepare("INSERT INTO motivation_tips (User_ID, Motivation_Title, `Motivation_Content`) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $content);

    if ($stmt->execute()) {
        $message = "✅ Motivation tip added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM motivation_tips WHERE Motivation_ID = $delete_id AND User_ID = $user_id");
    $message = "✅ Motivation tip deleted!";
}

// Get all motivation tips
$result = $conn->query("SELECT * FROM motivation_tips WHERE User_ID = $user_id ORDER BY Motivation_ID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Motivational Tips - MentalCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            display: flex;
            height: 100vh;
            background: linear-gradient(to bottom, #fbc2eb, #a6c1ee);
            color: #333;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, #fbc2eb, #a6c1ee);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            color: #fff;
        }

        .sidebar h2 {
            color: #fff;
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 12px 0px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            font-weight: 500;
            background: transparent;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .sidebar a.active {
            background: #fff;
            color: #6a11cb;
            font-weight: 600;
        }
        
        .main-content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #6a11cb;
            font-weight: 600;
        }
        .card {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #6a11cb;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card h2, .card h3 {
            color: #6a11cb;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .card p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            font-family: 'Poppins', sans-serif;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #6a11cb;
            box-shadow: 0 0 0 2px rgba(106, 17, 203, 0.2);
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        label {
            color: #6a11cb;
            font-weight: 500;
            display: block;
            margin-bottom: 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(to right, #6a11cb, #8e54e9);
            color: white;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: linear-gradient(to right, #5a0cb2, #7a47d1);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(to right, #ff9a9e, #fad0c4);
            color: #333;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: linear-gradient(to right, #ff8a8e, #f9c0b4);
            box-shadow: 0 5px 15px rgba(255, 154, 158, 0.4);
        }
        .button-group {
            display: flex;
        }
        .success-message {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #28a745;
            border-radius: 4px;
        }
        .motivation-box {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 3px solid #6a11cb;
            transition: all 0.3s ease;
        }
        .motivation-box:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.1);
        }
        .motivation-box h4 {
            color: #6a11cb;
            margin-bottom: 10px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .motivation-box p {
            color: #555;
            line-height: 1.6;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .edit-link {
            color: #17a2b8;
            text-decoration: none;
            transition: all 0.2s;
        }
        .edit-link:hover {
            transform: scale(1.2);
        }
        .delete-link {
            color: #dc3545;
            text-decoration: none;
            transition: all 0.2s;
        }
        .delete-link:hover {
            transform: scale(1.2);
        }
        .motivation-list {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .no-tips {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>MentalCare</h2>
        <a href="user.php">🏠 Dashboard</a>
        <a href="mood.php">😊 Mood Tracker</a>
        <a href="journal.php">📓 Journal</a>
        <a href="goal.php">🎯 Goal Setting</a>
        <a href="motivation.php" class="active">💡 Motivational Tips</a>
        <a href="exercise.php">🏃 Exercise</a>
        <a href="chatbot.php">🧠 Chatbot</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><?= $edit_mode ? 'Edit Motivational Tip' : 'Motivational Tips' ?></h1>
        </div>
        
        <div class="card">
            <h2><?= $edit_mode ? 'Edit Motivational Tip' : 'Add New Motivational Tip' ?></h2>
            <?php if ($message) echo "<div class='success-message'>$message</div>"; ?>
            
            <?php if ($edit_mode): ?>
            <!-- Edit Form -->
            <form method="post">
                <input type="hidden" name="motivation_id" value="<?= $edit_data['Motivation_ID'] ?>">
                
                <label for="title">Title</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($edit_data['Motivation_Title']) ?>" required>

                <label for="content">Content</label>
                <textarea name="content" id="content" rows="4" required><?= htmlspecialchars($edit_data['Motivation_Content']) ?></textarea>

                <div class="button-group">
                    <button type="submit" name="update" class="btn">Update Tip</button>
                    <a href="motivation.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            <?php else: ?>
            <!-- Add New Form -->
            <form method="post">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" placeholder="Enter a catchy title for your tip" required>

                <label for="content">Content</label>
                <textarea name="content" id="content" rows="3" placeholder="Write an inspiring message to your future self..." required></textarea>

                <button type="submit" class="btn">Save Tip</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (!$edit_mode): ?>
        <div class="card">
            <h3>Your Motivational Collection</h3>
            
            <div class="motivation-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="motivation-box">
                            <h4>
                                <?= htmlspecialchars($row['Motivation_Title']) ?>
                                <div class="action-buttons">
                                    <a class="edit-link" href="?edit=<?= $row['Motivation_ID'] ?>">✏️</a>
                                    <a class="delete-link" href="?delete=<?= $row['Motivation_ID'] ?>" onclick="return confirm('Are you sure you want to delete this tip?')">🗑️</a>
                                </div>
                            </h4>
                            <p><?= nl2br(htmlspecialchars($row['Motivation_Content'])) ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-tips">
                        <p>You haven't added any motivational tips yet. Add your first one to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>