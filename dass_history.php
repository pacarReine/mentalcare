<?php
session_start();
include 'db.php';

if (!isset($_SESSION['User_Username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['User_Username'];
$stmt = $conn->prepare("SELECT User_ID FROM users WHERE User_Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$User_ID = $user['User_ID'];

// Fetch DASS test history
$sql = "SELECT * FROM dass_test_results WHERE User_ID = ? ORDER BY Test_Date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $User_ID);
$stmt->execute();
$result = $stmt->get_result();

// Functions to calculate DASS levels
function getDepressionLevel($score) {
    if ($score >= 0 && $score <= 9) return "Normal";
    elseif ($score <= 13) return "Mild";
    elseif ($score <= 20) return "Moderate";
    elseif ($score <= 27) return "Severe";
    else return "Extremely Severe";
}

function getAnxietyLevel($score) {
    if ($score >= 0 && $score <= 7) return "Normal";
    elseif ($score <= 9) return "Mild";
    elseif ($score <= 14) return "Moderate";
    elseif ($score <= 19) return "Severe";
    else return "Extremely Severe";
}

function getStressLevel($score) {
    if ($score >= 0 && $score <= 14) return "Normal";
    elseif ($score <= 18) return "Mild";
    elseif ($score <= 25) return "Moderate";
    elseif ($score <= 33) return "Severe";
    else return "Extremely Severe";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DASS History - MentalCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { 
            min-height: 100vh; 
            background: linear-gradient(to bottom, #fbc2eb, #a6c1ee); 
            color: #333;
            padding: 30px; 
        }
        
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header h1 {
            color: #6a11cb;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }

        .card {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #6a11cb;
            width: 100%;
            margin: auto;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin-bottom: 20px;
            background: linear-gradient(to right, #6a11cb, #8e54e9);
            color: white;
            font-weight: 500;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            background: linear-gradient(to right, #5a0cb2, #7a47d1);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 14px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: linear-gradient(to right, #6a11cb, #8e54e9);
            color: white;
            font-weight: 500;
        }
        
        tr:hover {
            background-color: rgba(106, 17, 203, 0.05);
        }
        
        .normal { color: #4CAF50; }
        .mild { color: #8BC34A; }
        .moderate { color: #FFC107; }
        .severe { color: #FF9800; }
        .extremely { color: #F44336; }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header">
        <h1>DASS Test History</h1>
    </div>

    <div class="card">
        <a href="user.php" class="btn">🏠 Back to Dashboard</a>
        
        <table>
            <tr>
                <th>Date</th>
                <th>Depression Score</th>
                <th>Depression Level</th>
                <th>Anxiety Score</th>
                <th>Anxiety Level</th>
                <th>Stress Score</th>
                <th>Stress Level</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): 
                $depLevel = $row['Depression_Level'] ?: getDepressionLevel($row['Depression_Score']);
                $anxLevel = $row['Anxiety_Level'] ?: getAnxietyLevel($row['Anxiety_Score']);
                $strLevel = $row['Stress_Level'] ?: getStressLevel($row['Stress_Score']);
                
                $depClass = strtolower(explode(' ', $depLevel)[0]);
                $anxClass = strtolower(explode(' ', $anxLevel)[0]);
                $strClass = strtolower(explode(' ', $strLevel)[0]);
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['Test_Date']) ?></td>
                    <td><?= $row['Depression_Score'] ?></td>
                    <td class="<?= $depClass ?>"><?= $depLevel ?></td>
                    <td><?= $row['Anxiety_Score'] ?></td>
                    <td class="<?= $anxClass ?>"><?= $anxLevel ?></td>
                    <td><?= $row['Stress_Score'] ?></td>
                    <td class="<?= $strClass ?>"><?= $strLevel ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if ($result->num_rows == 0): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">No test history available. Take a DASS test to see your results here.</td>
                </tr>
            <?php endif; ?>
        </table>
        
        <a href="exercise.php" class="btn" style="margin-top: 20px;">Take New DASS Test</a>
    </div>
</div>

</body>
</html>