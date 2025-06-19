<?php
session_start();
include 'db.php';

if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle add
if (isset($_POST['add'])) {
    $keyword = strtolower(trim($_POST['User_Input']));
    $response = trim($_POST['Bot_Response']);

    if (!empty($keyword) && !empty($response)) {
        $stmt = $conn->prepare("INSERT INTO chatbot_rules (User_Input, Bot_Response) VALUES (?, ?)");
        $stmt->bind_param("ss", $keyword, $response);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM chatbot_rules WHERE Rule_ID = $id");
}

// Handle edit
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $keyword = strtolower(trim($_POST['User_Input']));
    $response = trim($_POST['Bot_Response']);

    $stmt = $conn->prepare("UPDATE chatbot_rules SET User_Input = ?, Bot_Response = ? WHERE Rule_ID = ?");
    $stmt->bind_param("ssi", $keyword, $response, $id);
    $stmt->execute();
    $stmt->close();
}

// Get all rules
$rules = $conn->query("SELECT * FROM chatbot_rules ORDER BY Rule_ID ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chatbot Rules</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            margin: 0 0 20px 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }

        .form-row {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(229, 231, 235, 0.8);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: rgba(255, 255, 255, 1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
            font-family: 'Inter', sans-serif;
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        }

        .btn-danger:hover {
            box-shadow: 0 8px 25px rgba(238, 90, 36, 0.4);
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-container h2 {
            margin: 0 0 20px 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.9);
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .edit-form {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .edit-form input[type="text"] {
            width: 150px;
            padding: 8px 12px;
            font-size: 14px;
        }

        .edit-form textarea {
            width: 200px;
            min-height: 60px;
            padding: 8px 12px;
            font-size: 14px;
        }

        .rule-id {
            font-weight: 600;
            color: #667eea;
        }

        .keyword {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 500;
        }

        .response-text {
            color: #4b5563;
            line-height: 1.5;
        }

        @media screen and (max-width: 1024px) {
            .main {
                padding: 30px;
            }
            .form-row {
                flex-direction: column;
            }
            .form-group {
                min-width: 100%;
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
            .edit-form {
                flex-direction: column;
            }
            .edit-form input[type="text"],
            .edit-form textarea {
                width: 100%;
            }
            .actions {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media screen and (max-width: 480px) {
            .main {
                padding: 15px;
            }
            .page-header,
            .form-container,
            .table-container {
                padding: 20px;
            }
            .page-header h1 {
                font-size: 24px;
            }
            th, td {
                padding: 12px 15px;
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
        <a href="admin_dashboard.php"><span>📊</span> Dashboard</a>
        <a href="admin_users.php"><span>👥</span> User Management</a>
        <a href="admin_exercise.php"><span>📄</span> Question Management</a>
        <a href="admin_dass.php"><span>📚</span> DASS History</a>
        <a href="admin_chatbot.php" class="active"><span>🤖</span> Chatbot Management</a>
                <a href="tips_management.php"><span>💡</span> Tips Management</a>
    </nav>
    <button class="logout-btn" onclick="window.location.href='logout.php'">🚪 Logout</button>
</div>

<div class="main">
    <div class="page-header">
        <h1>Chatbot Management</h1>
        <p>Manage chatbot rules and responses for better user interactions</p>
    </div>

    <div class="form-container">
        <h2>Add New Chatbot Rule</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="User_Input">Keyword/Trigger</label>
                    <input type="text" id="User_Input" name="User_Input" placeholder="e.g., sad, happy, anxious" required>
                </div>
                <div class="form-group">
                    <label for="Bot_Response">Chatbot Response</label>
                    <textarea id="Bot_Response" name="Bot_Response" placeholder="Enter the response the chatbot should give..." required></textarea>
                </div>
            </div>
            <button type="submit" name="add" class="btn">➕ Add Rule</button>
        </form>
    </div>

    <div class="table-container">
        <h2>Existing Chatbot Rules</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Keyword</th>
                        <th>Response</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    while ($row = $rules->fetch_assoc()): ?>
                        <tr>
                            <td><span class="rule-id"><?= $counter++ ?></span></td>
                            <td><span class="keyword"><?= htmlspecialchars($row['User_Input']) ?></span></td>
                            <td><span class="response-text"><?= htmlspecialchars($row['Bot_Response']) ?></span></td>
                            <td class="actions">
                                <!-- Edit Form -->
                                <form method="POST" class="edit-form">
                                    <input type="hidden" name="id" value="<?= $row['Rule_ID'] ?>">
                                    <input type="text" name="User_Input" value="<?= htmlspecialchars($row['User_Input']) ?>" placeholder="Keyword" required>
                                    <textarea name="Bot_Response" placeholder="Response" required><?= htmlspecialchars($row['Bot_Response']) ?></textarea>
                                    <button type="submit" name="edit" class="btn btn-small">✏️ Update</button>
                                </form>

                                <!-- Delete Form -->
                                <form method="GET" onsubmit="return confirm('Are you sure you want to delete this rule?');">
                                    <input type="hidden" name="delete" value="<?= $row['Rule_ID'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">🗑️ Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>