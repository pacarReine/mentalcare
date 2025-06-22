<?php
session_start();
include("db.php");

// Check if admin is logged in
if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get current admin's user ID for comparison
$current_admin_id = $_SESSION['User_ID'] ?? null; // Assuming User_ID is stored in session

// Search logic
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
if (!empty($search)) {
    $sql = "SELECT * FROM users 
            WHERE User_ID LIKE '%$search%' 
               OR User_Username LIKE '%$search%' 
               OR User_Email LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM users";
}
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
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
            width: 350px;
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

        .search-form button {
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

        .search-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .message {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 15px;
            font-weight: 500;
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .success {
            background: rgba(16, 185, 129, 0.15);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .error {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .data-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .table-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 18px 20px;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
            background: white;
            transition: all 0.2s ease;
        }

        tr:hover td {
            background: #f8fafc;
            transform: scale(1.01);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-details h4 {
            margin: 0 0 5px 0;
            font-weight: 600;
            color: #1f2937;
        }

        .user-details p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-admin {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }

        .role-user {
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
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
        }

        .delete-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(238, 90, 36, 0.4);
        }

        .delete-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .delete-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .self-indicator {
            padding: 4px 8px;
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 8px;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            font-size: 18px;
        }

        .no-data-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media screen and (max-width: 1024px) {
            .main {
                padding: 30px;
            }
            .search-form input[type="text"] {
                width: 280px;
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
            }
            .action-buttons {
                flex-direction: column;
            }
            .page-header h1 {
                font-size: 24px;
            }
            .table-title {
                font-size: 20px;
            }
        }

        @media screen and (max-width: 480px) {
            .main {
                padding: 15px;
            }
            .page-header {
                padding: 20px;
            }
            .data-table {
                padding: 20px;
            }
            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
        <a href="admin_users.php" class="active"><span>👥</span> User Management</a>
        <a href="admin_exercise.php"><span>📄</span> Question Management</a>
        <a href="admin_dass.php"><span>📚</span> DASS History</a>
        <a href="admin_chatbot.php"><span>🤖</span> Chatbot Management</a>
        <a href="tips_management.php"><span>💡</span> Tips Management</a>
    </nav>
    <button class="logout-btn" onclick="window.location.href='logout.php'">🚪 Logout</button>
</div>

<div class="main">
    <div class="page-header">
        <h1>User Management</h1>
        <p>Manage and monitor all system users</p>
    </div>

    <?php if (isset($_SESSION['message'])) { ?>
        <div class="message <?= $_SESSION['message_type'] ?>">
            <?= $_SESSION['message'] ?>
        </div>
        <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    } ?>

    <div class="search-section">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by ID, username, or email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">🔍 Search</button>
        </form>
    </div>

    <div class="data-table">
        <div class="table-header">
            <div class="table-title">All Users</div>
        </div>
        
        <?php if (mysqli_num_rows($result) > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $index = 1;
                    while ($row = mysqli_fetch_assoc($result)) { 
                        $is_current_user = ($row['User_ID'] == $current_admin_id);
                    ?>
                    <tr>
                        <td><?= $index++ ?></td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($row['User_Username'], 0, 1)) ?>
                                </div>
                                <div class="user-details">
                                    <h4>
                                        <?= htmlspecialchars($row['User_Username']) ?>
                                        <?php if ($is_current_user) { ?>
                                            <span class="self-indicator">You</span>
                                        <?php } ?>
                                    </h4>
                                    <p>ID: <?= $row['User_ID'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['User_Email']) ?></td>
                        <td>
                            <span class="role-badge role-<?= strtolower($row['User_Role']) ?>">
                                <?= ucfirst(htmlspecialchars($row['User_Role'])) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($row['Created_At'])) ?></td>
                        <td class="action-buttons">
                            <?php if ($is_current_user) { ?>
                                <button class="delete-btn" disabled title="You cannot delete your own account">
                                    🔒 Cannot Delete Self
                                </button>
                            <?php } else { ?>
                                <button class="delete-btn" onclick="if(confirm('Are you sure you want to delete this user? This will delete all associated data.')) window.location.href='delete_user.php?id=<?= $row['User_ID'] ?>'">
                                    🗑️ Delete
                                </button>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="no-data">
                <div class="no-data-icon">👥</div>
                <p>No users found matching your search criteria.</p>
            </div>
        <?php } ?>
    </div>
</div>

</body>
</html>