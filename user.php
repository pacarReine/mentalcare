<?php
session_start();
include('db.php');

if (!isset($_SESSION['User_Username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['User_Username'];
$userQuery = $conn->prepare("SELECT User_ID FROM users WHERE User_Username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();
$userId = $userData['User_ID'];

// Fetch latest mood
$moodResult = $conn->query("SELECT * FROM moodtracker WHERE User_ID = $userId ORDER BY Created_at DESC LIMIT 1");
$mood = $moodResult->fetch_assoc();

// Fetch mood history for graph
$moodHistoryResult = $conn->query("SELECT Mood_Level, Created_at FROM moodtracker WHERE User_ID = $userId ORDER BY Created_at DESC LIMIT 7");
$moodLevels = [];
$moodDates = [];
while ($row = $moodHistoryResult->fetch_assoc()) {
    $moodLevels[] = (int)$row['Mood_Level'];
    $moodDates[] = date('M d', strtotime($row['Created_at']));
}
$moodLevels = array_reverse($moodLevels);
$moodDates = array_reverse($moodDates);

// Fetch latest journal
$journalResult = $conn->query("SELECT * FROM journal WHERE User_ID = $userId ORDER BY Journal_Date DESC LIMIT 1");
$journal = $journalResult->fetch_assoc();

// Fetch latest goal with target date
$goalResult = $conn->query("SELECT * FROM goal_settings WHERE User_ID = $userId ORDER BY Goal_ID DESC LIMIT 1");
$goal = $goalResult->fetch_assoc();

// Calculate days remaining for goal (if target date exists)
$daysRemaining = null;
$targetStatus = '';
if ($goal && isset($goal['Target_Date']) && $goal['Target_Date']) {
    $targetDate = new DateTime($goal['Target_Date']);
    $currentDate = new DateTime();
    $interval = $currentDate->diff($targetDate);
    
    if ($targetDate > $currentDate) {
        $daysRemaining = $interval->days;
        $targetStatus = 'upcoming';
    } elseif ($targetDate < $currentDate) {
        $daysRemaining = $interval->days;
        $targetStatus = 'overdue';
    } else {
        $daysRemaining = 0;
        $targetStatus = 'today';
    }
}

// Fetch latest exercise score
$exerciseResult = $conn->query("SELECT * FROM dass_test_results WHERE User_ID = $userId ORDER BY Test_Date DESC LIMIT 1");
$exercise = $exerciseResult->fetch_assoc();

// Fetch random motivational tip
$motivationResult = $conn->query("SELECT * FROM motivation_tips ORDER BY RAND() LIMIT 1");
$motivation = $motivationResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - MentalCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
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
            margin-bottom: 10px;
        }
        .card p strong {
            color: #6a11cb;
            font-weight: 600;
        }
        .no-data {
            font-style: italic;
            color: #999;
            text-align: center;
            padding: 20px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-left: none;
            margin-bottom: 30px;
        }
        .welcome-card h1 {
            color: white;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .welcome-card p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }

        .chart-container {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border-left: 4px solid #6a11cb;
            margin-bottom: 30px;
        }
        .chart-container h3 {
            color: #6a11cb;
            margin-bottom: 15px;
            font-weight: 600;
        }
        canvas {
            max-width: 100%;
        }

        /* Target Date Styling */
        .target-date-info {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .target-upcoming {
            background-color: #e8f5e8;
            color: #2d5a2d;
            border-left: 3px solid #4caf50;
        }
        
        .target-today {
            background-color: #fff3cd;
            color: #856404;
            border-left: 3px solid #ffc107;
        }
        
        .target-overdue {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 3px solid #dc3545;
        }
        
        .days-counter {
            font-weight: 600;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
                height: auto;
            }
            .sidebar {
                width: 100%;
                flex-direction: row;
                overflow-x: auto;
                padding: 15px;
                gap: 10px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            .sidebar h2 {
                display: none;
            }
            .sidebar a {
                white-space: nowrap;
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            .main-content {
                padding: 20px 15px;
            }
            .grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>MentalCare</h2>
    <a href="user.php" class="active">🏠 Dashboard</a>
    <a href="mood.php">😊 Mood Tracker</a>
    <a href="journal.php">📓 Journal</a>
    <a href="goal.php">🎯 Goal Setting</a>
    <a href="motivation.php">💡 Motivational Tips</a>
    <a href="exercise.php">🏃 Exercise</a>
    <a href="chatbot.php">🧠 Chatbot</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main-content">
    <div class="welcome-card card">
        <h1>Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
        <p>Here's your mental health dashboard overview</p>
    </div>

    <div class="grid">
        <div class="card">
            <h3>💭 Recent Mood</h3>
            <?php if ($mood): ?>
                <p><strong>Feeling:</strong> <?= htmlspecialchars($mood['Mood_Feelings']) ?> (<?= htmlspecialchars($mood['Mood_Level']) ?>)</p>
                <p><strong>Details:</strong> <?= htmlspecialchars($mood['Mood_Details']) ?></p>
                <p><strong>Factors:</strong> <?= htmlspecialchars($mood['Mood_Factors']) ?></p>
                <p><em><?= date('M d, Y g:i A', strtotime($mood['Created_at'])) ?></em></p>
            <?php else: ?>
                <p class="no-data">No mood data yet. Start tracking your mood today!</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>📓 Recent Journal</h3>
            <?php if ($journal): ?>
                <p><strong>Reflection:</strong> <?= htmlspecialchars($journal['Journal_Reflect']) ?></p>
                <p><strong>Learned:</strong> <?= htmlspecialchars($journal['Journal_Learn']) ?></p>
                <p><strong>Grateful:</strong> <?= htmlspecialchars($journal['Journal_Grateful']) ?></p>
                <p><em><?= date('M d, Y', strtotime($journal['Journal_Date'])) ?></em></p>
            <?php else: ?>
                <p class="no-data">No journal entries yet. Start journaling to track your thoughts!</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>🎯 Recent Goal</h3>
            <?php if ($goal): ?>
                <p><strong>Title:</strong> <?= htmlspecialchars($goal['Goal_Title']) ?></p>
                <p><strong>Content:</strong> <?= htmlspecialchars($goal['Goal_Content']) ?></p>
                
                <?php if (isset($goal['Target_Date']) && $goal['Target_Date']): ?>
                    <p><strong>Target Date:</strong> <?= date('M d, Y', strtotime($goal['Target_Date'])) ?></p>
                    
                    <?php if ($targetStatus && $daysRemaining !== null): ?>
                        <div class="target-date-info target-<?= $targetStatus ?>">
                            <?php if ($targetStatus === 'upcoming'): ?>
                                <div class="days-counter">⏰ <?= $daysRemaining ?> days remaining</div>
                                <div>Keep pushing towards your goal!</div>
                            <?php elseif ($targetStatus === 'today'): ?>
                                <div class="days-counter">🎯 Goal deadline is TODAY!</div>
                                <div>Time to achieve your goal!</div>
                            <?php elseif ($targetStatus === 'overdue'): ?>
                                <div class="days-counter">⚠️ <?= $daysRemaining ?> days overdue</div>
                                <div>Consider revising your timeline or celebrating partial progress!</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p><em>No target date set for this goal</em></p>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-data">No goals added yet. Set your first goal to get started!</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>📊 Last DASS Score</h3>
            <?php if ($exercise): ?>
                <p><strong>Depression:</strong> <?= htmlspecialchars($exercise['Depression_Score']) ?></p>
                <p><strong>Anxiety:</strong> <?= htmlspecialchars($exercise['Anxiety_Score']) ?></p>
                <p><strong>Stress:</strong> <?= htmlspecialchars($exercise['Stress_Score']) ?></p>
                <p><em><?= date('M d, Y', strtotime($exercise['Test_Date'])) ?></em></p>
            <?php else: ?>
                <p class="no-data">No DASS test taken yet. Take your first assessment!</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>💡 Daily Motivation</h3>
            <?php if ($motivation): ?>
                <p><strong><?= htmlspecialchars($motivation['Motivation_Title']) ?></strong></p>
                <p><?= htmlspecialchars($motivation['Motivation_Content']) ?></p>
            <?php else: ?>
                <p class="no-data">No motivation tips available.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mood Chart -->
    <div class="chart-container">
        <h3>📈 Mood Trend (Last 7 Entries)</h3>
        <canvas id="moodChart"></canvas>
    </div>

    <!-- DASS Chart -->
    <?php if ($exercise): ?>
    <div class="chart-container">
        <h3>🧠 DASS Score Overview</h3>
        <canvas id="dassChart"></canvas>
    </div>
    <?php endif; ?>

    <div class="footer">
        &copy; <?php echo date("Y"); ?> MentalCare - Your Mental Health Companion
    </div>
</div>

<script>
    // Mood Chart
    const moodCtx = document.getElementById('moodChart').getContext('2d');
    const moodChart = new Chart(moodCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($moodDates) ?>,
            datasets: [{
                label: 'Mood Level',
                data: <?= json_encode($moodLevels) ?>,
                fill: true,
                borderColor: '#6a11cb',
                backgroundColor: 'rgba(106, 17, 203, 0.1)',
                tension: 0.3,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, max: 10 }
            }
        }
    });

    // DASS Chart
    <?php if ($exercise): ?>
    const dassCtx = document.getElementById('dassChart').getContext('2d');
    const dassChart = new Chart(dassCtx, {
        type: 'bar',
        data: {
            labels: ['Depression', 'Anxiety', 'Stress'],
            datasets: [{
                label: 'Score',
                data: [
                    <?= $exercise['Depression_Score'] ?>,
                    <?= $exercise['Anxiety_Score'] ?>,
                    <?= $exercise['Stress_Score'] ?>
                ],
                backgroundColor: ['#e74c3c', '#f39c12', '#3498db'],
                borderRadius: 10,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    <?php endif; ?>
</script>

</body>
</html>