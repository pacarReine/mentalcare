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
$edit_data = null;

// Handle editing
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_result = $conn->query("SELECT * FROM moodtracker WHERE Mood_ID = $edit_id AND User_ID = $user_id");
    if ($edit_result->num_rows > 0) {
        $edit_data = $edit_result->fetch_assoc();
    } else {
        $message = "❌ Entry not found or access denied.";
    }
}

// Handle update
if (isset($_POST['update'])) {
    $mood_id = intval($_POST['mood_id']);
    $mood = $_POST['mood'];
    $mood_level = $_POST['mood_level'];
    $mood_details = $_POST['mood_details'];
    
    // Handle regular and custom factors
    $mood_factors_array = [];
    if (isset($_POST['mood_factors'])) {
        $mood_factors_array = $_POST['mood_factors'];
    }
    if (!empty($_POST['custom_factor'])) {
        $mood_factors_array[] = $_POST['custom_factor'];
    }
    $mood_factors = implode(', ', $mood_factors_array);
    
    // Handle custom feeling
    if ($mood === "custom" && !empty($_POST['custom_feeling'])) {
        $mood = $_POST['custom_feeling'];
    }

    $stmt = $conn->prepare("UPDATE moodtracker SET Mood_Feelings = ?, Mood_Level = ?, Mood_Details = ?, Mood_Factors = ? WHERE Mood_ID = ? AND User_ID = ?");
    $stmt->bind_param("sissii", $mood, $mood_level, $mood_details, $mood_factors, $mood_id, $user_id);

    if ($stmt->execute()) {
        $message = "✅ Mood updated successfully!";
        $edit_data = null; // Reset edit_data to close the edit form
    } else {
        $message = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM moodtracker WHERE Mood_ID = $delete_id AND User_ID = $user_id");
    $message = "✅ Mood entry deleted!";
}

// Handle form submission for new entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update'])) {
    $mood = $_POST['mood'];
    $mood_level = $_POST['mood_level'];
    $mood_details = $_POST['mood_details'];
    
    // Handle regular and custom factors
    $mood_factors_array = [];
    if (isset($_POST['mood_factors'])) {
        $mood_factors_array = $_POST['mood_factors'];
    }
    if (!empty($_POST['custom_factor'])) {
        $mood_factors_array[] = $_POST['custom_factor'];
    }
    $mood_factors = implode(', ', $mood_factors_array);
    
    // Handle custom feeling
    if ($mood === "custom" && !empty($_POST['custom_feeling'])) {
        $mood = $_POST['custom_feeling'];
    }

    $stmt = $conn->prepare("INSERT INTO moodtracker (User_ID, Mood_Feelings, Mood_Level, Mood_Details, Mood_Factors) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $user_id, $mood, $mood_level, $mood_details, $mood_factors);

    if ($stmt->execute()) {
        $message = "✅ Mood saved successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

// Get user's mood history
$history = $conn->query("SELECT * FROM moodtracker WHERE User_ID = $user_id ORDER BY Created_at DESC");

// Get distinct custom feelings used previously by this user
$custom_feelings_result = $conn->query("SELECT DISTINCT Mood_Feelings FROM moodtracker WHERE User_ID = $user_id AND Mood_Feelings NOT IN ('Happy', 'Sad', 'Angry', 'Excited', 'Anxious', 'Calm')");
$custom_feelings = [];
if ($custom_feelings_result->num_rows > 0) {
    while ($row = $custom_feelings_result->fetch_assoc()) {
        $custom_feelings[] = $row['Mood_Feelings'];
    }
}

// Get distinct custom factors used previously by this user
$all_factors = ["Sleep", "Food", "Work", "Relationships", "Health", "Weather"];
$custom_factors_result = $conn->query("SELECT Mood_Factors FROM moodtracker WHERE User_ID = $user_id");
$custom_factors = [];
if ($custom_factors_result->num_rows > 0) {
    while ($row = $custom_factors_result->fetch_assoc()) {
        $factors = explode(', ', $row['Mood_Factors']);
        foreach ($factors as $factor) {
            if (!empty($factor) && !in_array($factor, $all_factors) && !in_array($factor, $custom_factors)) {
                $custom_factors[] = $factor;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood Tracker - MentalCare</title>
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
        select, textarea, input[type="text"], input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }
        select:focus, textarea:focus, input:focus {
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
        .action-btns {
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
            font-size: 1.2em;
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
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            margin: 0;
            cursor: pointer;
            color: #555;
            background: #f9f9f9;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 0.9rem;
        }
        .checkbox-group label:hover {
            background: #f0f0f0;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 5px;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"]:checked + span {
            font-weight: 500;
            color: #6a11cb;
        }
        .success-message {
            background-color: rgba(40, 167, 69, 0.05);
            border: 1px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #28a745;
            border-radius: 4px;
        }
        .error-message {
            background-color: rgba(220, 53, 69, 0.05);
            border: 1px solid #dc3545;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #dc3545;
            border-radius: 4px;
        }
        .custom-input {
            margin-top: 15px;
        }
        .custom-input-row {
            display: flex;
            gap: 10px;
        }
        .custom-input-row input {
            flex: 1;
        }
        .hidden {
            display: none;
        }
        .mood-level-container {
            margin: 15px 0;
        }
        .mood-level-radio-group {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .mood-level-option {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .mood-level-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        .mood-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #f1f1f1;
            cursor: pointer;
            transition: all 0.3s;
            color: #777;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .mood-level-option input[type="radio"]:checked + .mood-circle {
            background: linear-gradient(to right, #6a11cb, #8e54e9);
            color: white;
            transform: scale(1.2);
            box-shadow: 0 3px 8px rgba(106, 17, 203, 0.3);
        }
        .mood-level-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            color: #777;
            font-size: 0.9rem;
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #6a11cb;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Responsive styles */
        @media screen and (max-width: 992px) {
            .main-content {
                padding: 15px;
            }
            .checkbox-group {
                gap: 8px;
            }
            .checkbox-group label {
                padding: 4px 8px;
                font-size: 0.85rem;
            }
            .action-btns {
                flex-direction: column;
                gap: 5px;
            }
            .btn {
                padding: 8px 16px;
            }
        }
        
        @media screen and (max-width: 700px) {
            body {
                flex-direction: column;
            }
            .menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1000;
                background: white;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                height: 100%;
                overflow-y: auto;
                z-index: 100;
            }
            .sidebar.active {
                left: 0;
            }
            .main-content {
                margin-top: 60px;
                width: 100%;
            }
            .header {
                justify-content: center;
                padding-left: 40px;
            }
            .card {
                padding: 15px;
            }
            .card h2, .card h3 {
                font-size: 1.3rem;
            }
            .btn {
                padding: 8px 16px;
            }
            th, td {
                padding: 8px 10px;
                font-size: 0.9rem;
            }
        }
        
        @media screen and (max-width: 576px) {
            .header h1 {
                font-size: 1.5rem;
            }
            .checkbox-group label {
                padding: 3px 6px;
                font-size: 0.8rem;
            }
            .card {
                padding: 12px;
                margin-bottom: 15px;
            }
            .table-details {
                max-width: 100px;
            }
            .mood-level-labels {
                font-size: 0.8rem;
            }
            label {
                font-size: 0.9rem;
            }
            .mood-circle {
                width: 24px;
                height: 24px;
                font-size: 0.75rem;
            }
        }
        
        /* Overlay for when sidebar is active on mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
        }
        .overlay.active {
            display: block;
        }
        
        /* Edit form styles */
        .edit-form {
            background-color: #f8f9fa;
            border: 2px solid #6a11cb;
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
    <button id="menuToggle" class="menu-toggle">☰</button>
    <div id="overlay" class="overlay"></div>
    
    <div id="sidebar" class="sidebar">
        <h2>MentalCare</h2>
        <a href="user.php">🏠 Dashboard</a>
        <a href="mood.php" class="active">😊 Mood Tracker</a>
        <a href="journal.php">📓 Journal</a>
        <a href="goal.php">🎯 Goal Setting</a>
        <a href="motivation.php">💡 Motivational Tips</a>
        <a href="exercise.php">🏃 Exercise</a>
        <a href="chatbot.php">🧠 Chatbot</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Mood Trackers</h1>
        </div>
        
        <?php if ($edit_data): ?>
        <div class="card edit-form">
            <h3>Edit Mood Entry</h3>
            <form method="post">
                <input type="hidden" name="mood_id" value="<?= $edit_data['Mood_ID'] ?>">
                
                <label for="mood_edit">Mood Feeling:</label>
                <select name="mood" id="mood_edit" onchange="checkCustomFeelingEdit()" required>
                    <option value="">-- Select Mood --</option>
                    <option value="Happy" <?= $edit_data['Mood_Feelings'] == 'Happy' ? 'selected' : '' ?>>Happy</option>
                    <option value="Sad" <?= $edit_data['Mood_Feelings'] == 'Sad' ? 'selected' : '' ?>>Sad</option>
                    <option value="Angry" <?= $edit_data['Mood_Feelings'] == 'Angry' ? 'selected' : '' ?>>Angry</option>
                    <option value="Excited" <?= $edit_data['Mood_Feelings'] == 'Excited' ? 'selected' : '' ?>>Excited</option>
                    <option value="Anxious" <?= $edit_data['Mood_Feelings'] == 'Anxious' ? 'selected' : '' ?>>Anxious</option>
                    <option value="Calm" <?= $edit_data['Mood_Feelings'] == 'Calm' ? 'selected' : '' ?>>Calm</option>
                    <option value="custom" <?= !in_array($edit_data['Mood_Feelings'], ['Happy', 'Sad', 'Angry', 'Excited', 'Anxious', 'Calm']) ? 'selected' : '' ?>>Custom Feeling</option>
                </select>

                <div id="custom_feeling_div_edit" class="custom-input <?= in_array($edit_data['Mood_Feelings'], ['Happy', 'Sad', 'Angry', 'Excited', 'Anxious', 'Calm']) ? 'hidden' : '' ?>">
                    <label for="custom_feeling_edit">Your Custom Feeling:</label>
                    <input type="text" name="custom_feeling" id="custom_feeling_edit" value="<?= !in_array($edit_data['Mood_Feelings'], ['Happy', 'Sad', 'Angry', 'Excited', 'Anxious', 'Calm']) ? htmlspecialchars($edit_data['Mood_Feelings']) : '' ?>" placeholder="Enter your custom feeling...">
                </div>

                <label for="mood_level_edit">Mood Level (1-10):</label>
                <div class="mood-level-container">
                    <div class="mood-level-radio-group">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <div class="mood-level-option">
                            <input type="radio" name="mood_level" id="level_edit<?= $i ?>" value="<?= $i ?>" <?= $edit_data['Mood_Level'] == $i ? 'checked' : '' ?>>
                            <label for="level_edit<?= $i ?>" class="mood-circle"><?= $i ?></label>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mood-level-labels">
                        <span>Very Low</span>
                        <span>Very High</span>
                    </div>
                </div>

                <label for="mood_details_edit">Mood Details:</label>
                <textarea name="mood_details" id="mood_details_edit" rows="3" maxlength="255" placeholder="Describe how you're feeling in more detail..."><?= htmlspecialchars($edit_data['Mood_Details']) ?></textarea>

                <label>Mood Factors:</label>
                <?php
                $selected_factors = explode(', ', $edit_data['Mood_Factors']);
                ?>
                <div class="checkbox-group">
                    <label><input type='checkbox' name='mood_factors[]' value='Sleep' <?= in_array('Sleep', $selected_factors) ? 'checked' : '' ?>><span>Sleep</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Food' <?= in_array('Food', $selected_factors) ? 'checked' : '' ?>><span>Food</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Work' <?= in_array('Work', $selected_factors) ? 'checked' : '' ?>><span>Work</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Relationships' <?= in_array('Relationships', $selected_factors) ? 'checked' : '' ?>><span>Relationships</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Health' <?= in_array('Health', $selected_factors) ? 'checked' : '' ?>><span>Health</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Weather' <?= in_array('Weather', $selected_factors) ? 'checked' : '' ?>><span>Weather</span></label>
                </div>

                <div class="custom-input">
                    <label for="custom_factor_edit">Add New Factor:</label>
                    <input type="text" name="custom_factor" id="custom_factor_edit" placeholder="Enter a new factor that affected your mood...">
                </div>

                <div class="action-btns">
                    <button class="btn" type="submit" name="update">Update Mood</button>
                    <a href="mood.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Log Your Mood</h2>
            <?php if ($message) echo "<div class='success-message'>$message</div>"; ?>
            <form method="post">
                <label for="mood">Mood Feeling:</label>
                <select name="mood" id="mood" onchange="checkCustomFeeling()" required>
                    <option value="">-- Select Mood --</option>
                    <option value="Happy">Happy</option>
                    <option value="Sad">Sad</option>
                    <option value="Angry">Angry</option>
                    <option value="Excited">Excited</option>
                    <option value="Anxious">Anxious</option>
                    <option value="Calm">Calm</option>
                    <option value="custom">Add Custom Feeling...</option>
                </select>

                <div id="custom_feeling_div" class="custom-input hidden">
                    <label for="custom_feeling">Your Custom Feeling:</label>
                    <input type="text" name="custom_feeling" id="custom_feeling" placeholder="Enter your custom feeling...">
                </div>

                <label for="mood_level">Mood Level (1-10):</label>
                <div class="mood-level-container">
                    <div class="mood-level-radio-group">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <div class="mood-level-option">
                            <input type="radio" name="mood_level" id="level<?= $i ?>" value="<?= $i ?>" <?= $i == 5 ? 'checked' : '' ?>>
                            <label for="level<?= $i ?>" class="mood-circle"><?= $i ?></label>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mood-level-labels">
                        <span>Very Low</span>
                        <span>Very High</span>
                    </div>
                </div>

                <label for="mood_details">Mood Details:</label>
                <textarea name="mood_details" id="mood_details" rows="3" maxlength="255" placeholder="Describe how you're feeling in more detail..."></textarea>

                <label>Mood Factors:</label>
                <div class="checkbox-group">
                    <label><input type='checkbox' name='mood_factors[]' value='Sleep'><span>Sleep</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Food'><span>Food</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Work'><span>Work</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Relationships'><span>Relationships</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Health'><span>Health</span></label>
                    <label><input type='checkbox' name='mood_factors[]' value='Weather'><span>Weather</span></label>
                </div>

                <div class="custom-input">
                    <label for="custom_factor">Add New Factor:</label>
                    <div class="custom-input-row">
                        <input type="text" name="custom_factor" id="custom_factor" placeholder="Enter a new factor that affected your mood...">
                    </div>
                </div>

                <button class="btn" type="submit">Save Mood</button>
            </form>
        </div>

        <div class="card">
            <h3>Your Mood History</h3>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Feeling</th>
                        <th>Level</th>
                        <th>Details</th>
                        <th>Factors</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($history->num_rows > 0): ?>
                        <?php while ($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('M d, Y H:i', strtotime($row['Created_at'])) ?></td>
                            <td><?= htmlspecialchars($row['Mood_Feelings']) ?></td>
                            <td><?= htmlspecialchars($row['Mood_Level']) ?></td>
                            <td class="table-details"><?= htmlspecialchars($row['Mood_Details']) ?></td>
                            <td><?= htmlspecialchars($row['Mood_Factors']) ?></td>
                            <td>
                                <a href="?edit=<?= $row['Mood_ID'] ?>" class="action-btn edit-btn">✏️</a>
                                <a href="?delete=<?= $row['Mood_ID'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this entry?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No mood entries yet. Start tracking your mood today!</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Show/hide custom feeling input based on selection
        function checkCustomFeeling() {
            const moodSelect = document.getElementById('mood');
            const customFeelingDiv = document.getElementById('custom_feeling_div');
            if (moodSelect.value === 'custom') {
                customFeelingDiv.classList.remove('hidden');
            } else {
                customFeelingDiv.classList.add('hidden');
            }
        }

        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        });

        // Close sidebar when clicking overlay
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('overlay').classList.remove('active');
        });
    </script>
</body>
</html>