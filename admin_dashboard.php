<?php
session_start();
include 'db.php';

// Redirect if not admin
if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Stats - exclude admins from total user count
$userCount = $conn->query("SELECT COUNT(*) AS total FROM users WHERE User_Role != 'admin'")->fetch_assoc()['total'];
$adminCount = $conn->query("SELECT COUNT(*) AS total FROM users WHERE User_Role = 'admin'")->fetch_assoc()['total'];

// Total DASS tests taken
$testTakenCount = $conn->query("SELECT COUNT(*) AS total FROM dass_test_results")->fetch_assoc()['total'];

// Fetch DASS tests by date
$testByDate = [];
$result = $conn->query("SELECT Test_Date, COUNT(*) as total FROM dass_test_results GROUP BY Test_Date ORDER BY Test_Date ASC");
while ($row = $result->fetch_assoc()) {
    $testByDate[$row['Test_Date']] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            height: 500px;
        }

        .chart-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            text-align: center;
        }

        .chart-wrapper {
            position: relative;
            height: calc(100% - 60px);
            width: 100%;
        }

        .chart-wrapper canvas {
            max-height: 100% !important;
            max-width: 100% !important;
        }

        @media screen and (max-width: 1024px) {
            .main {
                padding: 30px;
            }
            .chart-container {
                height: 400px;
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .chart-container {
                height: 350px;
                padding: 15px;
            }
            .chart-title {
                font-size: 20px;
                margin-bottom: 15px;
            }
        }

        @media screen and (max-width: 480px) {
            .main {
                padding: 15px;
            }
            .page-header {
                padding: 20px;
            }
            .page-header h1 {
                font-size: 24px;
            }
            .chart-container {
                height: 300px;
                padding: 10px;
            }
            .stat-card {
                padding: 20px;
            }
            .stat-icon {
                font-size: 36px;
            }
            .stat-value {
                font-size: 24px;
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
        <a href="admin_dashboard.php" class="active"><span>📊</span> Dashboard</a>
        <a href="admin_users.php"><span>👥</span> User Management</a>
        <a href="admin_exercise.php"><span>📄</span> Question Management</a>
        <a href="admin_dass.php"><span>📚</span> DASS History</a>
        <a href="admin_chatbot.php"><span>🤖</span> Chatbot Management</a>
        <a href="tips_management.php"><span>💡</span> Tips Management</a>
    </nav>
    <button class="logout-btn" onclick="window.location.href='logout.php'">🚪 Logout</button>
</div>

<!-- Main content -->
<div class="main">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome to your comprehensive admin control panel</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $userCount ?></div>
            <div class="stat-label">Regular Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👨‍💼</div>
            <div class="stat-value"><?= $adminCount ?></div>
            <div class="stat-label">Admin Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?= $testTakenCount ?></div>
            <div class="stat-label">DASS Tests Taken</div>
        </div>
    </div>

    <div class="chart-container">
        <div class="chart-title">DASS Tests Taken by Date</div>
        <div class="chart-wrapper">
            <canvas id="dassChart"></canvas>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('dassChart').getContext('2d');
const dassChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($testByDate)) ?>,
        datasets: [{
            label: 'Tests Taken',
            data: <?= json_encode(array_values($testByDate)) ?>,
            backgroundColor: 'rgba(102, 126, 234, 0.2)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                position: 'top',
                labels: {
                    font: {
                        family: 'Inter',
                        size: 14,
                        weight: '500'
                    },
                    color: '#374151'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Tests',
                    font: {
                        family: 'Inter',
                        size: 14,
                        weight: '500'
                    },
                    color: '#6b7280'
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    borderColor: 'rgba(0, 0, 0, 0.1)'
                },
                ticks: {
                    font: {
                        family: 'Inter',
                        size: 12
                    },
                    color: '#6b7280'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Date',
                    font: {
                        family: 'Inter',
                        size: 14,
                        weight: '500'
                    },
                    color: '#6b7280'
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    borderColor: 'rgba(0, 0, 0, 0.1)'
                },
                ticks: {
                    font: {
                        family: 'Inter',
                        size: 12
                    },
                    color: '#6b7280'
                }
            }
        }
    }
});
</script>

</body>
</html>