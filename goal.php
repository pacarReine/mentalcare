<?php
session_start();
include("db.php");

if (!isset($_SESSION['User_Username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['User_Username'];
$query = $conn->prepare("SELECT User_ID FROM users WHERE User_Username = ?");
$query->bind_param("s", $username);
$query->execute();
$query->bind_result($userId);
$query->fetch();
$query->close();

$message = "";
$editGoal = null;

// Add Goal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_goal'])) {
    $goalTitle = $_POST['Goal_Title'];
    $goalContent = $_POST['Goal_Content'];

    $stmt = $conn->prepare("INSERT INTO goal_settings (User_ID, Goal_Title, Goal_Content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $goalTitle, $goalContent);
    
    if ($stmt->execute()) {
        $message = "✅ Goal added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Update Goal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_goal_content'])) {
    $goalId = $_POST['Goal_ID'];
    $goalTitle = $_POST['Goal_Title'];
    $goalContent = $_POST['Goal_Content'];

    $stmt = $conn->prepare("UPDATE goal_settings SET Goal_Title = ?, Goal_Content = ? WHERE Goal_ID = ? AND User_ID = ?");
    $stmt->bind_param("ssii", $goalTitle, $goalContent, $goalId, $userId);
    
    if ($stmt->execute()) {
        $message = "✅ Goal updated successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Delete Goal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_goal'])) {
    $goalId = $_POST['Goal_ID'];

    $stmt = $conn->prepare("DELETE FROM goal_settings WHERE Goal_ID = ? AND User_ID = ?");
    $stmt->bind_param("ii", $goalId, $userId);
    
    if ($stmt->execute()) {
        $message = "🗑️ Goal deleted successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Get goal for editing
if (isset($_GET['edit'])) {
    $editGoalId = $_GET['edit'];
    $editQuery = $conn->prepare("SELECT * FROM goal_settings WHERE Goal_ID = ? AND User_ID = ?");
    $editQuery->bind_param("ii", $editGoalId, $userId);
    $editQuery->execute();
    $editResult = $editQuery->get_result();
    if ($editResult->num_rows > 0) {
        $editGoal = $editResult->fetch_assoc();
    }
    $editQuery->close();
}

// Update Completed Status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_goal'])) {
    if (isset($_POST['completed'])) {
        foreach ($_POST['completed'] as $goalId) {
            $update = $conn->prepare("UPDATE goal_settings SET Completed = 1 WHERE Goal_ID = ? AND User_ID = ?");
            $update->bind_param("ii", $goalId, $userId);
            $update->execute();
            $update->close();
        }
        $message = "✅ Goals updated successfully!";
    }
}

// Reset All Goals
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_goals'])) {
    $reset = $conn->prepare("DELETE FROM goal_settings WHERE User_ID = ?");
    $reset->bind_param("i", $userId);

    if ($reset->execute()) {
        $message = "🗑️ All goals have been reset.";
    } else {
        $message = "❌ Failed to reset goals: " . $reset->error;
    }

    $reset->close();
}

// Get Goals
$goals = [];
$result = $conn->query("SELECT * FROM goal_settings WHERE User_ID = $userId ORDER BY Completed ASC, Goal_ID DESC");
while ($row = $result->fetch_assoc()) {
    $goals[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goal Setting - MentalCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; height: 100vh; background: linear-gradient(to bottom, #fbc2eb, #a6c1ee); color: #333; }
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
        .card:hover { transform: translateY(-5px); }
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
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .btn:hover {
            background: linear-gradient(to right, #5a0cb2, #7a47d1);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
        }
        .btn-danger {
            background: linear-gradient(to right, #dc3545, #e55b6b);
        }
        .btn-danger:hover {
            background: linear-gradient(to right, #c82333, #d73e5a);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .btn-warning {
            background: linear-gradient(to right, #ffc107, #ffcd39);
            color: #212529;
        }
        .btn-warning:hover {
            background: linear-gradient(to right, #e0a800, #ffca2c);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(to right, #6c757d, #868e96);
        }
        .btn-secondary:hover {
            background: linear-gradient(to right, #5a6268, #7a8288);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }
        .btn-small {
            padding: 5px 12px;
            font-size: 0.875rem;
        }
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            background-color: #f8f9fa;
        }
        .checkbox-label:hover {
            background-color: rgba(106, 17, 203, 0.05);
            border-left: 3px solid #6a11cb;
        }
        .checkbox-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            margin-top: 2px;
            accent-color: #6a11cb;
            cursor: pointer;
            flex-shrink: 0;
        }
        .checkbox-label .goal-content-wrapper {
            flex: 1;
            min-width: 0;
        }
        .goal-actions {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .done {
            text-decoration: line-through;
            color: #94a3b8;
        }
        .goal-list {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .goal-content {
            margin-top: 5px;
            color: #555;
            word-wrap: break-word;
        }
        .goal-title {
            font-weight: 600;
            color: #333;
            word-wrap: break-word;
        }
        .success-message {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #28a745;
            border-radius: 4px;
        }
        .no-goals {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }
        .edit-form {
            border: 2px solid #6a11cb;
            background-color: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .edit-form h3 {
            color: #6a11cb;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>MentalCare</h2>
    <a href="user.php">🏠 Dashboard</a>
    <a href="mood.php">😊 Mood Tracker</a>
    <a href="journal.php">📓 Journal</a>
    <a href="goal.php" class="active">🎯 Goal Setting</a>
    <a href="motivation.php">💡 Motivational Tips</a>
    <a href="exercise.php">🏃 Exercise</a>
    <a href="chatbot.php">🧠 Chatbot</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Goal Setting</h1>
    </div>
    
    <?php if ($editGoal): ?>
    <div class="card edit-form">
        <h3>✏️ Edit Goal</h3>
        <?php if ($message) echo "<div class='success-message'>$message</div>"; ?>
        <form method="post">
            <input type="hidden" name="Goal_ID" value="<?= $editGoal['Goal_ID'] ?>">
            <input type="text" name="Goal_Title" value="<?= htmlspecialchars($editGoal['Goal_Title']) ?>" placeholder="What do you want to achieve?" required>
            <textarea name="Goal_Content" placeholder="Describe your goal and why it's important to you..." required><?= htmlspecialchars($editGoal['Goal_Content']) ?></textarea>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="btn" type="submit" name="update_goal_content">Update Goal</button>
                <a href="goal.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="card">
        <h2>Add New Goal</h2>
        <?php if ($message) echo "<div class='success-message'>$message</div>"; ?>
        <form method="post">
            <input type="text" name="Goal_Title" placeholder="What do you want to achieve?" required>
            <textarea name="Goal_Content" placeholder="Describe your goal and why it's important to you..." required></textarea>
            <button class="btn" type="submit" name="add_goal">Add Goal</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3>My Goals</h3>
        <?php if (count($goals) > 0): ?>
            <!-- Completion Form -->
            <form method="post" id="completionForm">
                <div class="goal-list">
                    <?php foreach ($goals as $goal): ?>
                        <div class="checkbox-label">
                            <?php if (!$goal['Completed']): ?>
                                <input type="checkbox" name="completed[]" value="<?= $goal['Goal_ID'] ?>">
                            <?php else: ?>
                                <input type="checkbox" checked disabled>
                            <?php endif; ?>
                            <div class="goal-content-wrapper">
                                <span class="<?= $goal['Completed'] ? 'done' : '' ?>">
                                    <div class="goal-title"><?= htmlspecialchars($goal['Goal_Title']) ?></div>
                                    <div class="goal-content"><?= htmlspecialchars($goal['Goal_Content']) ?></div>
                                </span>
                                <?php if (!$goal['Completed']): ?>
                                <div class="goal-actions">
                                    <a href="goal.php?edit=<?= $goal['Goal_ID'] ?>" class="btn btn-warning btn-small">✏️ Edit</a>
                                    <button type="button" class="btn btn-danger btn-small" onclick="deleteGoal(<?= $goal['Goal_ID'] ?>, '<?= htmlspecialchars($goal['Goal_Title'], ENT_QUOTES) ?>')">🗑️ Delete</button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                    <button class="btn" type="submit" name="update_goal">Mark Selected as Completed</button>
                    <button class="btn btn-danger" type="button" onclick="resetAllGoals()">Reset All Goals</button>
                </div>
            </form>

            <!-- Hidden forms for individual actions -->
            <div id="hiddenForms" style="display: none;">
                <!-- Reset All Goals Form -->
                <form method="post" id="resetForm">
                    <input type="hidden" name="reset_goals" value="1">
                </form>
                
                <!-- Delete Individual Goal Form -->
                <form method="post" id="deleteForm">
                    <input type="hidden" name="delete_goal" value="1">
                    <input type="hidden" name="Goal_ID" id="deleteGoalId" value="">
                </form>
            </div>

        <?php else: ?>
            <div class="no-goals">
                <p>You haven't set any goals yet. Start by adding a goal above!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function resetAllGoals() {
            if (confirm('Are you sure you want to reset all your goals? This action cannot be undone.')) {
                document.getElementById('resetForm').submit();
            }
        }

        function deleteGoal(goalId, goalTitle) {
            if (confirm('Are you sure you want to delete "' + goalTitle + '"?')) {
                document.getElementById('deleteGoalId').value = goalId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</div>

</body>
</html>