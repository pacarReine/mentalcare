<?php
session_start();
include 'db.php';

// Redirect if not admin
if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Message handling
$status_message = '';
$status_type = '';

if (isset($_SESSION['status_message'])) {
    $status_message = $_SESSION['status_message'];
    $status_type = $_SESSION['status_type'];
    unset($_SESSION['status_message'], $_SESSION['status_type']);
}

// ADD question
if (isset($_POST['add'])) {
    $question_text = trim($_POST['question_text']);
    $question_type = trim($_POST['question_type']);

    if (!empty($question_text) && !empty($question_type)) {
        $stmt = $conn->prepare("INSERT INTO dass_questions (Question_Text, Question_Type) VALUES (?, ?)");
        $stmt->bind_param("ss", $question_text, $question_type);
        if ($stmt->execute()) {
            $status_message = "Question added successfully.";
            $status_type = "success";
        } else {
            $status_message = "Failed to add question.";
            $status_type = "error";
        }
    } else {
        $status_message = "Please fill in all fields to add a question.";
        $status_type = "error";
    }
}

// DELETE question
if (isset($_GET['delete'])) {
    $deleteID = $_GET['delete'];
    $del = $conn->prepare("DELETE FROM dass_questions WHERE Question_ID = ?");
    $del->bind_param("i", $deleteID);
    if ($del->execute()) {
        $_SESSION['status_message'] = "Question deleted successfully.";
        $_SESSION['status_type'] = "success";
    } else {
        $_SESSION['status_message'] = "Failed to delete question.";
        $_SESSION['status_type'] = "error";
    }
    header("Location: admin_exercise.php");
    exit();
}

// EDIT question
if (isset($_POST['update'])) {
    $updateID = $_POST['update_id'];
    $question_text = trim($_POST['question_text']);
    $question_type = trim($_POST['question_type']);

    if (!empty($question_text) && !empty($question_type)) {
        $upd = $conn->prepare("UPDATE dass_questions SET Question_Text = ?, Question_Type = ? WHERE Question_ID = ?");
        $upd->bind_param("ssi", $question_text, $question_type, $updateID);
        if ($upd->execute()) {
            $status_message = "Question updated successfully.";
            $status_type = "success";
        } else {
            $status_message = "Failed to update question.";
            $status_type = "error";
        }
    } else {
        $status_message = "Please fill in all fields to update.";
        $status_type = "error";
    }
}

// Search and filter
$searchTerm = $_GET['search'] ?? '';
$filterType = $_GET['filter_type'] ?? '';

if (!empty($searchTerm) || !empty($filterType)) {
    $query = "SELECT * FROM dass_questions WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($searchTerm)) {
        $query .= " AND Question_Text LIKE ?";
        $searchParam = "%$searchTerm%";
        $params[] = &$searchParam;
        $types .= "s";
    }

    if (!empty($filterType)) {
        $query .= " AND Question_Type = ?";
        $params[] = &$filterType;
        $types .= "s";
    }

    $query .= " ORDER BY Question_Type, Question_ID";
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $all = $stmt->get_result();
} else {
    $all = $conn->query("SELECT * FROM dass_questions ORDER BY Question_Type, Question_ID");
}

// Edit mode
$editMode = false;
$editData = null;
if (isset($_GET['edit'])) {
    $editMode = true;
    $editID = $_GET['edit'];
    $res = $conn->prepare("SELECT * FROM dass_questions WHERE Question_ID = ?");
    $res->bind_param("i", $editID);
    $res->execute();
    $editData = $res->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASS Question Management</title>
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

        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 500;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .form-card, .search-card, .table-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        textarea, select, input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            font-size: 16px;
            transition: all 0.3s ease;
            resize: vertical;
        }

        textarea:focus, select:focus, input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }

        textarea {
            min-height: 120px;
            font-family: inherit;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-right: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        .btn-edit {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
            padding: 8px 16px;
            font-size: 14px;
        }

        .btn-edit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 2;
            min-width: 200px;
        }

        .search-select {
            flex: 1;
            min-width: 150px;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
        }

        td {
            padding: 20px 15px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }

        tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        .type-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .type-depression {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .type-anxiety {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .type-stress {
            background: rgba(139, 69, 19, 0.1);
            color: #92400e;
        }

        .question-text {
            max-width: 400px;
            line-height: 1.5;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }

        @media screen and (max-width: 1024px) {
            .main {
                padding: 30px;
            }
            .search-form {
                flex-direction: column;
            }
            .search-input, .search-select {
                flex: 1;
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
            .form-card, .search-card, .table-card {
                padding: 20px;
            }
            .page-header {
                padding: 20px;
            }
            .page-header h1 {
                font-size: 24px;
            }
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            .btn-edit, .btn-danger {
                width: 100%;
            }
        }

        @media screen and (max-width: 480px) {
            .main {
                padding: 15px;
            }
            .form-card, .search-card, .table-card {
                padding: 15px;
            }
            .btn {
                padding: 10px 20px;
                font-size: 14px;
            }
            table {
                font-size: 14px;
            }
            th, td {
                padding: 15px 10px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2>🌟 Admin Panel</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="admin_dashboard.php"><span>📊</span> Dashboard</a>
        <a href="admin_users.php"><span>👥</span> User Management</a>
        <a href="admin_exercise.php" class="active"><span>📄</span> Question Management</a>
        <a href="admin_dass.php"><span>📚</span> DASS History</a>
        <a href="admin_chatbot.php"><span>🤖</span> Chatbot Management</a>
                <a href="tips_management.php"><span>💡</span> Tips Management</a>
    </nav>
    <button class="logout-btn" onclick="window.location.href='logout.php'">🚪 Logout</button>
</div>

<!-- Main content -->
<div class="main">
    <div class="page-header">
        <h1><?= $editMode ? 'Edit DASS Question' : 'DASS Question Management' ?></h1>
        <p><?= $editMode ? 'Update the selected question below' : 'Manage and organize your DASS assessment questions' ?></p>
    </div>

    <?php if ($status_message): ?>
        <div class="alert alert-<?= $status_type ?>">
            <?= htmlspecialchars($status_message) ?>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="form-card">
        <h2 class="form-title"><?= $editMode ? '✏️ Edit Question' : '➕ Add New Question' ?></h2>
        <form method="POST">
            <?php if ($editMode): ?>
                <input type="hidden" name="update_id" value="<?= $editData['Question_ID'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="question_text">Question Text:</label>
                <textarea name="question_text" id="question_text" required placeholder="Enter the question text here..."><?= $editMode ? htmlspecialchars($editData['Question_Text']) : '' ?></textarea>
            </div>

            <div class="form-group">
                <label for="question_type">Question Type:</label>
                <select name="question_type" id="question_type" required>
                    <option value="">-- Select Question Type --</option>
                    <option value="depression" <?= ($editMode && $editData['Question_Type'] == 'depression') ? 'selected' : '' ?>>Depression</option>
                    <option value="anxiety" <?= ($editMode && $editData['Question_Type'] == 'anxiety') ? 'selected' : '' ?>>Anxiety</option>
                    <option value="stress" <?= ($editMode && $editData['Question_Type'] == 'stress') ? 'selected' : '' ?>>Stress</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" name="<?= $editMode ? 'update' : 'add' ?>" class="btn btn-primary">
                    <?= $editMode ? '✅ Update Question' : '➕ Add Question' ?>
                </button>

                <?php if ($editMode): ?>
                    <a href="admin_exercise.php" class="btn btn-secondary">❌ Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Search and Filter -->
    <div class="search-card">
        <h2 class="form-title">🔍 Search & Filter</h2>
        <form method="GET" class="search-form">
            <div class="search-input">
                <label for="search">Search Questions:</label>
                <input type="text" name="search" id="search" placeholder="Enter keywords to search..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <div class="search-select">
                <label for="filter_type">Filter by Type:</label>
                <select name="filter_type" id="filter_type">
                    <option value="">All Types</option>
                    <option value="depression" <?= $filterType == 'depression' ? 'selected' : '' ?>>Depression</option>
                    <option value="anxiety" <?= $filterType == 'anxiety' ? 'selected' : '' ?>>Anxiety</option>
                    <option value="stress" <?= $filterType == 'stress' ? 'selected' : '' ?>>Stress</option>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">🔍 Search</button>
                <a href="admin_exercise.php" class="btn btn-secondary">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- Questions Table -->
    <div class="table-card">
        <h2 class="form-title">📋 All DASS Questions</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Question Text</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($all->num_rows > 0): ?>
                        <?php 
                        $counter = 1;
                        while ($row = $all->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $counter ?></strong></td>
                                <td>
                                    <div class="question-text">
                                        <?= htmlspecialchars($row['Question_Text']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge type-<?= $row['Question_Type'] ?>">
                                        <?= ucfirst($row['Question_Type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?= $row['Question_ID'] ?>" class="btn btn-edit">✏️ Edit</a>
                                        <a href="?delete=<?= $row['Question_ID'] ?>" onclick="return confirm('Are you sure you want to delete this question?')" class="btn btn-danger">🗑️ Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                        $counter++;
                        endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data">
                                📭 No questions found matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>