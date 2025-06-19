<?php
session_start();
include 'db.php';

// Redirect if not admin
if (!isset($_SESSION['User_Username']) || $_SESSION['User_Role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchSql = $search ? "WHERE User_ID LIKE '%$search%'" : '';

// Fetch DASS records (with optional search)
$dassQuery = "SELECT * FROM dass_test_results $searchSql ORDER BY Test_Date DESC";
$dassResult = $conn->query($dassQuery);

// Fetch data for charts
$chartQuery = "SELECT Depression_Level, Anxiety_Level, Stress_Level, Test_Date FROM dass_test_results $searchSql ORDER BY Test_Date DESC LIMIT 50";
$chartResult = $conn->query($chartQuery);
$chartData = [];
while ($row = $chartResult->fetch_assoc()) {
    $chartData[] = $row;
}

// Calculate statistics
$statsQuery = "SELECT 
    AVG(CASE 
        WHEN Depression_Level = 'Normal' THEN 0 
        WHEN Depression_Level = 'Mild' THEN 1 
        WHEN Depression_Level = 'Moderate' THEN 2 
        WHEN Depression_Level = 'Severe' THEN 3 
        WHEN Depression_Level = 'Extremely Severe' THEN 4 
        ELSE 0 END) as avg_depression,
    AVG(CASE 
        WHEN Anxiety_Level = 'Normal' THEN 0 
        WHEN Anxiety_Level = 'Mild' THEN 1 
        WHEN Anxiety_Level = 'Moderate' THEN 2 
        WHEN Anxiety_Level = 'Severe' THEN 3 
        WHEN Anxiety_Level = 'Extremely Severe' THEN 4 
        ELSE 0 END) as avg_anxiety,
    AVG(CASE 
        WHEN Stress_Level = 'Normal' THEN 0 
        WHEN Stress_Level = 'Mild' THEN 1 
        WHEN Stress_Level = 'Moderate' THEN 2 
        WHEN Stress_Level = 'Severe' THEN 3 
        WHEN Stress_Level = 'Extremely Severe' THEN 4 
        ELSE 0 END) as avg_stress,
    COUNT(*) as total_tests
FROM dass_test_results $searchSql";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Reset result pointer for main display
$dassResult = $conn->query($dassQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DASS Analytics Dashboard - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
            width: 300px;
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

        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            height: 400px;
        }

        .chart-title {
            font-size: 20px;
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

        .record-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .record-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .record-id {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .record-date {
            color: #6b7280;
            font-size: 14px;
        }

        .levels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .level-badge {
            padding: 10px 15px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }

        .depression { background: #fef3c7; color: #92400e; }
        .anxiety { background: #dbeafe; color: #1e40af; }
        .stress { background: #fecaca; color: #dc2626; }

        .level-normal { background: #d1fae5; color: #065f46; }
        .level-mild { background: #fef3c7; color: #92400e; }
        .level-moderate { background: #fed7aa; color: #ea580c; }
        .level-severe { background: #fecaca; color: #dc2626; }
        .level-extremely-severe { background: #fde2e8; color: #be185d; }

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

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            font-size: 18px;
        }

        @media screen and (max-width: 1200px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
            .chart-container {
                height: 350px;
            }
        }

        @media screen and (max-width: 1024px) {
            .main {
                padding: 30px;
            }
            .chart-container {
                height: 320px;
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
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            .search-form input[type="text"] {
                width: 100%;
            }
            .chart-container {
                height: 280px;
                padding: 15px;
            }
            .chart-title {
                font-size: 18px;
                margin-bottom: 15px;
            }
            .levels-grid {
                grid-template-columns: 1fr;
            }
            .record-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                height: 250px;
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

<div class="sidebar">
    <div class="sidebar-header">
        <h2>🌟 Admin Panel</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="admin_dashboard.php"><span>📊</span> Dashboard</a>
        <a href="admin_users.php"><span>👥</span> User Management</a>
        <a href="admin_exercise.php"><span>📄</span> Question Management</a>
        <a href="admin_dass.php" class="active"><span>📚</span> DASS History</a>
        <a href="admin_chatbot.php"><span>🤖</span> Chatbot Management</a>
                <a href="tips_management.php"><span>💡</span> Tips Management</a>
    </nav>
    <button class="logout-btn" onclick="window.location.href='logout.php'">🚪 Logout</button>
</div>

<div class="main">
    <div class="page-header">
        <h1>DASS Analytics Dashboard</h1>
        <p>Comprehensive analysis of Depression, Anxiety, and Stress Scale results</p>
    </div>

    <div class="search-section">
        <form class="search-form" method="GET" action="admin_dass.php">
            <input type="text" name="search" placeholder="Search by User ID" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">🔍 Search</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?= number_format($stats['total_tests']) ?></div>
            <div class="stat-label">Total Tests</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">😔</div>
            <div class="stat-value"><?= number_format($stats['avg_depression'], 1) ?></div>
            <div class="stat-label">Avg Depression Level</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">😰</div>
            <div class="stat-value"><?= number_format($stats['avg_anxiety'], 1) ?></div>
            <div class="stat-label">Avg Anxiety Level</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">😫</div>
            <div class="stat-value"><?= number_format($stats['avg_stress'], 1) ?></div>
            <div class="stat-label">Avg Stress Level</div>
        </div>
    </div>

    <div class="charts-section">
        <div class="chart-container">
            <div class="chart-title">DASS Levels Distribution</div>
            <div class="chart-wrapper">
                <canvas id="levelsChart"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <div class="chart-title">Trends Over Time (Last 20 Tests)</div>
            <div class="chart-wrapper">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="data-table">
        <div class="table-header">
            <div class="table-title">Recent DASS Records</div>
        </div>

        <?php if ($dassResult->num_rows > 0): ?>
            <?php while ($dass = $dassResult->fetch_assoc()): ?>
                <div class="record-item">
                    <div class="record-header">
                        <div class="record-id">Test ID: <?= $dass['Test_ID'] ?> | User: <?= $dass['User_ID'] ?></div>
                        <div class="record-date"><?= date('M j, Y g:i A', strtotime($dass['Test_Date'])) ?></div>
                    </div>
                    
                    <div class="levels-grid">
                        <div class="level-badge depression level-<?= strtolower(str_replace(' ', '-', $dass['Depression_Level'])) ?>">
                            Depression: <?= htmlspecialchars($dass['Depression_Level']) ?>
                        </div>
                        <div class="level-badge anxiety level-<?= strtolower(str_replace(' ', '-', $dass['Anxiety_Level'])) ?>">
                            Anxiety: <?= htmlspecialchars($dass['Anxiety_Level']) ?>
                        </div>
                        <div class="level-badge stress level-<?= strtolower(str_replace(' ', '-', $dass['Stress_Level'])) ?>">
                            Stress: <?= htmlspecialchars($dass['Stress_Level']) ?>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="delete-btn" onclick="if(confirm('Are you sure you want to delete this DASS record?')) window.location.href='delete_dass.php?Test_ID=<?= $dass['Test_ID'] ?>'">🗑️ Delete</button>
                    </div>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <p>No DASS records found<?= $search ? " for user ID '$search'" : '' ?>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Prepare data for charts
const chartData = <?= json_encode($chartData) ?>;

// Function to make charts responsive
function createResponsiveChart(ctx, config) {
    return new Chart(ctx, {
        ...config,
        options: {
            ...config.options,
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    });
}

// Levels Distribution Chart
const levelsCtx = document.getElementById('levelsChart').getContext('2d');

// Count occurrences of each level across all categories
const levelCounts = {
    'Normal': 0, 
    'Mild': 0, 
    'Moderate': 0, 
    'Severe': 0, 
    'Extremely Severe': 0
};

// Count each level from all three categories
chartData.forEach(item => {
    if (levelCounts.hasOwnProperty(item.Depression_Level)) levelCounts[item.Depression_Level]++;
    if (levelCounts.hasOwnProperty(item.Anxiety_Level)) levelCounts[item.Anxiety_Level]++;
    if (levelCounts.hasOwnProperty(item.Stress_Level)) levelCounts[item.Stress_Level]++;
});

// Only include levels that have data
const filteredLabels = [];
const filteredData = [];
const filteredColors = [];
const colorMap = {
    'Normal': '#10b981',
    'Mild': '#f59e0b', 
    'Moderate': '#f97316',
    'Severe': '#ef4444',
    'Extremely Severe': '#ec4899'
};

Object.entries(levelCounts).forEach(([level, count]) => {
    if (count > 0) {
        filteredLabels.push(level);
        filteredData.push(count);
        filteredColors.push(colorMap[level]);
    }
});

const levelsChart = createResponsiveChart(levelsCtx, {
    type: 'doughnut',
    data: {
        labels: filteredLabels,
        datasets: [{
            data: filteredData,
            backgroundColor: filteredColors,
            borderWidth: 3,
            borderColor: '#ffffff',
            hoverBorderWidth: 5,
            hoverBorderColor: '#ffffff'
        }]
    },
    options: {
        cutout: '60%',
        plugins: {
            legend: {
                position: window.innerWidth < 768 ? 'bottom' : 'right',
                labels: {
                    padding: 15,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: {
                        size: window.innerWidth < 768 ? 11 : 13
                    },
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return {
                                    text: `${label} (${percentage}%)`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Trends Chart - Limit to last 20 entries for better readability
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
const limitedData = chartData.slice(0, 20).reverse(); // Get last 20 and reverse for chronological order

const dates = limitedData.map(item => {
    const date = new Date(item.Test_Date);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric'
    });
});

// Convert levels to numeric values
function levelToNumber(level) {
    const levels = {
        'Normal': 0, 
        'Mild': 1, 
        'Moderate': 2, 
        'Severe': 3, 
        'Extremely Severe': 4
    };
    return levels[level] || 0;
}

const depressionTrend = limitedData.map(item => levelToNumber(item.Depression_Level));
const anxietyTrend = limitedData.map(item => levelToNumber(item.Anxiety_Level));
const stressTrend = limitedData.map(item => levelToNumber(item.Stress_Level));

const trendsChart = createResponsiveChart(trendsCtx, {
    type: 'line',
    data: {
        labels: dates,
        datasets: [
            {
                label: 'Depression',
                data: depressionTrend,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4,
                fill: false,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            },
            {
                label: 'Anxiety',
                data: anxietyTrend,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: false,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            },
            {
                label: 'Stress',
                data: stressTrend,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                fill: false,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#ef4444',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }
        ]
    },
    options: {
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: {
                        size: window.innerWidth < 768 ? 11 : 13
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const levels = ['Normal', 'Mild', 'Moderate', 'Severe', 'Extremely Severe'];
                        const levelName = levels[context.parsed.y] || 'Unknown';
                        return `${context.dataset.label}: ${levelName}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 4,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        const levels = ['Normal', 'Mild', 'Moderate', 'Severe', 'Extremely Severe'];
                        return levels[value] || '';
                    },
                    font: {
                        size: window.innerWidth < 768 ? 10 : 12
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                ticks: {
                    maxTicksLimit: window.innerWidth < 768 ? 5 : 10,
                    font: {
                        size: window.innerWidth < 768 ? 10 : 12
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            }
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    // Update legend position based on screen size
    if (levelsChart) {
        levelsChart.options.plugins.legend.position = window.innerWidth < 768 ? 'bottom' : 'right';
        levelsChart.options.plugins.legend.labels.font.size = window.innerWidth < 768 ? 11 : 13;
        levelsChart.update();
    }
    
    if (trendsChart) {
        trendsChart.options.plugins.legend.labels.font.size = window.innerWidth < 768 ? 11 : 13;
        trendsChart.options.scales.x.ticks.maxTicksLimit = window.innerWidth < 768 ? 5 : 10;
        trendsChart.options.scales.x.ticks.font.size = window.innerWidth < 768 ? 10 : 12;
        trendsChart.options.scales.y.ticks.font.size = window.innerWidth < 768 ? 10 : 12;
        trendsChart.update();
    }
});

// Show loading message if no data
if (chartData.length === 0) {
    document.querySelector('.charts-section').innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: #6b7280; font-size: 18px;">
            <div style="font-size: 48px; margin-bottom: 20px;">📊</div>
            <p>No data available for charts</p>
            <p style="font-size: 14px; margin-top: 10px;">DASS test results will appear here once users complete assessments</p>
        </div>
    `;
}
</script>

</body>
</html>