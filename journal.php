<?php
session_start();
include 'db.php';

if (!isset($_SESSION['User_Username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['User_Username'];
$userResult = $conn->query("SELECT User_ID FROM users WHERE User_Username = '$username'");
$userRow = $userResult->fetch_assoc();
$user_id = $userRow['User_ID'] ?? 0;

$message = "";
$edit_mode = false;
$edit_data = null;

// Handle edit mode
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM journal WHERE Journal_ID = $edit_id AND User_ID = $user_id");
    
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
        $edit_mode = true;
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM journal WHERE Journal_ID = $delete_id AND User_ID = $user_id");
    $message = "✅ Journal entry deleted!";
}

// Handle update
if (isset($_POST['update'])) {
    $journal_id = intval($_POST['journal_id']);
    $reflect = $_POST['reflect'];
    $learn = $_POST['learn'];
    $grateful = $_POST['grateful'];
    $date = $_POST['date'] ?? date('Y-m-d');

    $stmt = $conn->prepare("UPDATE journal SET Journal_Reflect = ?, Journal_Learn = ?, Journal_Grateful = ?, Journal_Date = ? WHERE Journal_ID = ? AND User_ID = ?");
    $stmt->bind_param("ssssis", $reflect, $learn, $grateful, $date, $journal_id, $user_id);

    if ($stmt->execute()) {
        $message = "✅ Journal updated successfully!";
        // Clear edit mode after successful update
        $edit_mode = false;
        $edit_data = null;
    } else {
        $message = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

// Handle submission of new entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update'])) {
    $reflect = $_POST['reflect'];
    $learn = $_POST['learn'];
    $grateful = $_POST['grateful'];
    $date = $_POST['date'] ?? date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO journal (User_ID, Journal_Reflect, Journal_Learn, Journal_Grateful, Journal_Date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $reflect, $learn, $grateful, $date);

    if ($stmt->execute()) {
        $message = "✅ Journal saved successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

// Get journal history
$journals = $conn->query("SELECT * FROM journal WHERE User_ID = $user_id ORDER BY Journal_Date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journal - MentalCare</title>
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
            padding: 12px 4px;
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
        label {
            color: #6a11cb;
            font-weight: 500;
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        textarea, input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }
        textarea:focus, input:focus {
            outline: none;
            border-color: #6a11cb;
            box-shadow: 0 0 0 2px rgba(106, 17, 203, 0.2);
        }
        .btn {
            display: inline-block;
            margin-top: 15px;
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
        }
        .btn-secondary:hover {
            background: linear-gradient(to right, #ff8a8e, #f9c0b4);
            box-shadow: 0 5px 15px rgba(255, 154, 158, 0.4);
        }
        .button-group {
            display: flex;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: rgba(106, 17, 203, 0.1);
            color: #6a11cb;
            font-weight: 600;
        }
        td {
            color: #444;
        }
        tr:hover {
            background-color: rgba(106, 17, 203, 0.05);
        }
        .action-btn {
            color: #6a11cb;
            cursor: pointer;
            margin-right: 10px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .action-btn:hover {
            transform: scale(1.2);
        }
        .delete-btn {
            color: #dc3545;
            cursor: pointer;
            transition: all 0.2s;
        }
        .delete-btn:hover {
            transform: scale(1.2);
        }
        .success-message {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #28a745;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>MentalCare</h2>
    <a href="user.php">🏠 Dashboard</a>
    <a href="mood.php">😊 Mood Tracker</a>
    <a href="journal.php" class="active">📓 Journal</a>
    <a href="goal.php">🎯 Goal Setting</a>
    <a href="motivation.php">💡 Motivational Tips</a>
    <a href="exercise.php">🏃 Exercise</a>
    <a href="chatbot.php">🧠 Chatbot</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main-content">
    <div class="header">
        <h1><?= $edit_mode ? 'Edit Journal Entry' : 'My Journal' ?></h1>
    </div>
    
    <div class="card">
        <h2><?= $edit_mode ? 'Edit Journal Entry' : 'Write a Journal Entry' ?></h2>
        <?php if ($message) echo "<div class='success-message'>$message</div>"; ?>
        
        <?php if ($edit_mode): ?>
        <!-- Edit Form -->
        <form method="post">
            <input type="hidden" name="journal_id" value="<?= $edit_data['Journal_ID'] ?>">
            
            <label for="reflect">What did you reflect on today?</label>
            <textarea name="reflect" id="reflect" rows="3" required><?= htmlspecialchars($edit_data['Journal_Reflect']) ?></textarea>
            
            <label for="learn">What did you learn?</label>
            <textarea name="learn" id="learn" rows="3" required><?= htmlspecialchars($edit_data['Journal_Learn']) ?></textarea>
            
            <label for="grateful">What are you grateful for?</label>
            <textarea name="grateful" id="grateful" rows="3" required><?= htmlspecialchars($edit_data['Journal_Grateful']) ?></textarea>
            
            <label for="date">Date:</label>
            <input type="date" name="date" id="date" value="<?= $edit_data['Journal_Date'] ?>">
            
            <div class="button-group">
                <button type="submit" name="update" class="btn">Update Journal Entry</button>
                <a href="journal.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <?php else: ?>
        <!-- New Entry Form -->
        <form method="post">
            <label for="reflect">What did you reflect on today?</label>
            <textarea name="reflect" id="reflect" rows="3" required placeholder="Share your thoughts and reflections..."></textarea>
            
            <label for="learn">What did you learn?</label>
            <textarea name="learn" id="learn" rows="3" required placeholder="What new insights or knowledge did you gain?"></textarea>
            
            <label for="grateful">What are you grateful for?</label>
            <textarea name="grateful" id="grateful" rows="3" required placeholder="Express gratitude for the positive things in your life..."></textarea>
            
            <label for="date">Date:</label>
            <input type="date" name="date" id="date" value="<?= date('Y-m-d') ?>">
            
            <button type="submit" class="btn">Save Journal Entry</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (!$edit_mode): ?>
    <div class="card">
        <h3>Journal History</h3>
        <table>
            <tr>
                <th>Date</th>
                <th>Reflection</th>
                <th>Lesson</th>
                <th>Gratitude</th>
                <th>Actions</th>
            </tr>
            <?php if ($journals->num_rows > 0): ?>
                <?php while ($row = $journals->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($row['Journal_Date'])) ?></td>
                        <td><?= htmlspecialchars($row['Journal_Reflect']) ?></td>
                        <td><?= htmlspecialchars($row['Journal_Learn']) ?></td>
                        <td><?= htmlspecialchars($row['Journal_Grateful']) ?></td>
                        <td>
                            <a href="?edit=<?= $row['Journal_ID'] ?>" class="action-btn">✏️</a>
                            <a href="?delete=<?= $row['Journal_ID'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this journal entry?')">🗑️</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No journal entries yet. Start journaling today to track your growth!</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
</div>

</body>
</html>