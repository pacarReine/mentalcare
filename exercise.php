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

// DASS level functions
function getDepressionLevel($score) {
    if ($score <= 9) return "Normal";
    elseif ($score <= 13) return "Mild";
    elseif ($score <= 20) return "Moderate";
    elseif ($score <= 27) return "Severe";
    else return "Extremely Severe";
}
function getAnxietyLevel($score) {
    if ($score <= 7) return "Normal";
    elseif ($score <= 9) return "Mild";
    elseif ($score <= 14) return "Moderate";
    elseif ($score <= 19) return "Severe";
    else return "Extremely Severe";
}
function getStressLevel($score) {
    if ($score <= 14) return "Normal";
    elseif ($score <= 18) return "Mild";
    elseif ($score <= 25) return "Moderate";
    elseif ($score <= 33) return "Severe";
    else return "Extremely Severe";
}

// Fetch questions from database
$depQuestions = $conn->query("SELECT * FROM dass_questions WHERE Question_Type = 'depression' ORDER BY Question_ID");
$anxQuestions = $conn->query("SELECT * FROM dass_questions WHERE Question_Type = 'anxiety' ORDER BY Question_ID");
$strQuestions = $conn->query("SELECT * FROM dass_questions WHERE Question_Type = 'stress' ORDER BY Question_ID");

// Count total questions
$totalQuestions = $depQuestions->num_rows + $anxQuestions->num_rows + $strQuestions->num_rows;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $questions = $_POST['question'];
    
    // Get the count of each question type
    $depCount = $depQuestions->num_rows;
    $anxCount = $anxQuestions->num_rows;
    $strCount = $strQuestions->num_rows;
    
    // Calculate scores by summing the answers for each category
    $depression = 0;
    $anxiety = 0;
    $stress = 0;
    
    // Process depression questions (first group in the array)
    for ($i = 0; $i < $depCount; $i++) {
        $depression += intval($questions[$i]);
    }
    
    // Process anxiety questions (second group)
    for ($i = $depCount; $i < ($depCount + $anxCount); $i++) {
        $anxiety += intval($questions[$i]);
    }
    
    // Process stress questions (third group)
    for ($i = ($depCount + $anxCount); $i < $totalQuestions; $i++) {
        $stress += intval($questions[$i]);
    }

    // Normalize scores if question count differs from standard DASS-21
    // Standard DASS-21 has 7 questions per category
    $depNormFactor = 7 / max(1, $depCount); // Avoid division by zero
    $anxNormFactor = 7 / max(1, $anxCount);
    $strNormFactor = 7 / max(1, $strCount);
    
    // Apply normalization factors
    $depression = round($depression * $depNormFactor);
    $anxiety = round($anxiety * $anxNormFactor);
    $stress = round($stress * $strNormFactor);

    $depLevel = getDepressionLevel($depression);
    $anxLevel = getAnxietyLevel($anxiety);
    $strLevel = getStressLevel($stress);
    $date = date("Y-m-d");

    $stmt = $conn->prepare("INSERT INTO dass_test_results (User_ID, Depression_Score, Depression_Level, Anxiety_Score, Anxiety_Level, Stress_Score, Stress_Level, Test_Date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississss", $User_ID, $depression, $depLevel, $anxiety, $anxLevel, $stress, $strLevel, $date);
    $stmt->execute();

    header("Location: dass_history.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASS Test - MentalCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(to bottom, #fbc2eb, #a6c1ee);
            color: #333;
            overflow-x: hidden;
        }
        
        .page-wrapper {
            display: flex;
            min-height: 100vh;
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
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
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
            margin-left: 250px; /* Same width as sidebar */
            width: calc(100% - 250px);
            overflow-y: auto;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        h1 {
            color: #6a11cb;
            font-weight: 600;
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
        
        .instruction-box {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #6a11cb;
            position: relative;
        }
        
        .instruction-box h3 {
            color: #6a11cb;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .instruction-box ul {
            margin-left: 20px;
            margin-top: 15px;
        }
        
        .instruction-box li {
            margin-bottom: 8px;
            position: relative;
            padding-left: 5px;
        }
        
        .instruction-box .icon {
            position: absolute;
            top: -15px;
            right: 20px;
            background: #6a11cb;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(106, 17, 203, 0.3);
        }
        
        form {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #6a11cb;
        }
        
        .question-item {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .question-item:last-child {
            border-bottom: none;
        }
        
        .question-label {
            color: #6a11cb;
            font-weight: 500;
            display: block;
            margin-bottom: 15px;
            font-size: 1.1rem;
            line-height: 1.4;
        }
        
        .question-category {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 8px;
            color: white;
        }
        
        .category-depression {
            background-color: #e63946;
        }
        
        .category-anxiety {
            background-color: #457b9d;
        }
        
        .category-stress {
            background-color: #2a9d8f;
        }
        
        .option-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .option-button {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            color: #495057;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
        }
        
        .option-button:hover {
            background: #e9ecef;
            border-color: #6a11cb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 17, 203, 0.15);
        }
        
        .option-button.selected {
            background: linear-gradient(to right, #6a11cb, #8e54e9);
            border-color: #6a11cb;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 17, 203, 0.3);
        }
        
        .option-button input[type="radio"] {
            display: none;
        }
        
        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: flex-start;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(to right, #6a11cb, #8e54e9);
            color: white;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn:hover {
            background: linear-gradient(to right, #5a0cb2, #7a47d1);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .test-categories {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .category {
            flex: 1;
            min-width: 200px;
            background-color: rgba(106, 17, 203, 0.05);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #6a11cb;
        }
        
        .category h4 {
            color: #6a11cb;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .category p {
            font-size: 0.9rem;
            color: #666;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stat-box {
            flex: 1;
            min-width: 150px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .stat-box.depression { border-top: 3px solid #e63946; }
        .stat-box.anxiety { border-top: 3px solid #457b9d; }
        .stat-box.stress { border-top: 3px solid #2a9d8f; }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 1.4rem;
            font-weight: 600;
            color: #333;
        }
        
        .no-questions-warning {
            background-color: #ffe8e8;
            color: #e63946;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e63946;
            font-weight: 500;
        }
        
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
            height: 8px;
        }
        
        .progress-fill {
            background: linear-gradient(to right, #6a11cb, #8e54e9);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            text-align: center;
            margin-bottom: 10px;
            color: #6a11cb;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .page-wrapper {
                flex-direction: column;
            }
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .btn-container {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                text-align: center;
            }
            .test-categories {
                flex-direction: column;
            }
            .stats-container {
                flex-direction: column;
            }
            .option-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="sidebar">
        <h2>MentalCare</h2>
        <a href="user.php">🏠 Dashboard</a>
        <a href="mood.php">😊 Mood Tracker</a>
        <a href="journal.php">📔 Journal</a>
        <a href="goal.php">🎯 Goals</a>
        <a href="motivation.php">💡 Motivation Tips</a>
        <a href="exercise.php" class="active">📝 Exercise</a>
        <a href="chatbot.php">🤖 Chatbot</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="container">
            <h1>DASS-21 Assessment</h1>
            
            <div class="instruction-box">
                <div class="icon">ℹ️</div>
                <h3>Instructions</h3>
                <p>Please read each statement and select the option that indicates how much the statement applied to you over the past week. There are no right or wrong answers. Do not spend too much time on any statement.</p>
                
                <h3>Rating Scale</h3>
                <ul>
                    <li><strong>0</strong> - Did not apply to me at all</li>
                    <li><strong>1</strong> - Applied to me to some degree, or some of the time</li>
                    <li><strong>2</strong> - Applied to me to a considerable degree, or a good part of time</li>
                    <li><strong>3</strong> - Applied to me very much, or most of the time</li>
                </ul>
                
                <div class="test-categories">
                    <div class="category">
                        <h4>Depression</h4>
                        <p>Measures dysphoria, hopelessness, devaluation of life, self-deprecation, lack of interest/involvement, anhedonia, and inertia.</p>
                    </div>
                    <div class="category">
                        <h4>Anxiety</h4>
                        <p>Measures autonomic arousal, skeletal muscle effects, situational anxiety, and subjective experience of anxious affect.</p>
                    </div>
                    <div class="category">
                        <h4>Stress</h4>
                        <p>Measures difficulty relaxing, nervous arousal, being easily upset/agitated, irritable/over-reactive and impatient.</p>
                    </div>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-box depression">
                    <div class="stat-label">Depression Questions</div>
                    <div class="stat-value"><?php echo $depQuestions->num_rows; ?></div>
                </div>
                <div class="stat-box anxiety">
                    <div class="stat-label">Anxiety Questions</div>
                    <div class="stat-value"><?php echo $anxQuestions->num_rows; ?></div>
                </div>
                <div class="stat-box stress">
                    <div class="stat-label">Stress Questions</div>
                    <div class="stat-value"><?php echo $strQuestions->num_rows; ?></div>
                </div>
            </div>
            
            <?php if ($totalQuestions == 0): ?>
            <div class="no-questions-warning">
                There are currently no questions available for the DASS test. Please contact the administrator.
            </div>
            <?php endif; ?>
            
            <?php if ($totalQuestions > 0): ?>
            <form method="post" id="dassForm">
                <div class="progress-text">Progress: <span id="progressText">0 / <?php echo $totalQuestions; ?></span></div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <?php
                // Display depression questions first
                $questionNum = 1;
                $inputIndex = 0;
                while ($q = $depQuestions->fetch_assoc()) {
                    echo "<div class='question-item'>";
                    echo "<div class='question-label'><span class='question-category category-depression'>Depression</span> Q" . $questionNum . ". " . htmlspecialchars($q['Question_Text']) . "</div>";
                    echo "<div class='option-buttons'>";
                    for ($i = 0; $i <= 3; $i++) {
                        $optionText = "";
                        switch($i) {
                            case 0: $optionText = "0 - Did not apply to me at all"; break;
                            case 1: $optionText = "1 - Applied to me to some degree"; break;
                            case 2: $optionText = "2 - Applied to me to a considerable degree"; break;
                            case 3: $optionText = "3 - Applied to me very much"; break;
                        }
                        echo "<label class='option-button' for='q{$inputIndex}_{$i}'>";
                        echo "<input type='radio' name='question[{$inputIndex}]' value='{$i}' id='q{$inputIndex}_{$i}' required>";
                        echo $optionText;
                        echo "</label>";
                    }
                    echo "</div>";
                    echo "</div>";
                    $questionNum++;
                    $inputIndex++;
                }
                
                // Display anxiety questions next
                while ($q = $anxQuestions->fetch_assoc()) {
                    echo "<div class='question-item'>";
                    echo "<div class='question-label'><span class='question-category category-anxiety'>Anxiety</span> Q" . $questionNum . ". " . htmlspecialchars($q['Question_Text']) . "</div>";
                    echo "<div class='option-buttons'>";
                    for ($i = 0; $i <= 3; $i++) {
                        $optionText = "";
                        switch($i) {
                            case 0: $optionText = "0 - Did not apply to me at all"; break;
                            case 1: $optionText = "1 - Applied to me to some degree"; break;
                            case 2: $optionText = "2 - Applied to me to a considerable degree"; break;
                            case 3: $optionText = "3 - Applied to me very much"; break;
                        }
                        echo "<label class='option-button' for='q{$inputIndex}_{$i}'>";
                        echo "<input type='radio' name='question[{$inputIndex}]' value='{$i}' id='q{$inputIndex}_{$i}' required>";
                        echo $optionText;
                        echo "</label>";
                    }
                    echo "</div>";
                    echo "</div>";
                    $questionNum++;
                    $inputIndex++;
                }
                
                // Display stress questions last
                while ($q = $strQuestions->fetch_assoc()) {
                    echo "<div class='question-item'>";
                    echo "<div class='question-label'><span class='question-category category-stress'>Stress</span> Q" . $questionNum . ". " . htmlspecialchars($q['Question_Text']) . "</div>";
                    echo "<div class='option-buttons'>";
                    for ($i = 0; $i <= 3; $i++) {
                        $optionText = "";
                        switch($i) {
                            case 0: $optionText = "0 - Did not apply to me at all"; break;
                            case 1: $optionText = "1 - Applied to me to some degree"; break;
                            case 2: $optionText = "2 - Applied to me to a considerable degree"; break;
                            case 3: $optionText = "3 - Applied to me very much"; break;
                        }
                        echo "<label class='option-button' for='q{$inputIndex}_{$i}'>";
                        echo "<input type='radio' name='question[{$inputIndex}]' value='{$i}' id='q{$inputIndex}_{$i}' required>";
                        echo $optionText;
                        echo "</label>";
                    }
                    echo "</div>";
                    echo "</div>";
                    $questionNum++;
                    $inputIndex++;
                }
                ?>
                <div class="btn-container">
                    <button type="submit" class="btn" id="submitBtn" disabled>Submit Assessment</button>
                    <a href="dass_history.php" class="btn">View Test History</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const optionButtons = document.querySelectorAll('.option-button');
    const submitBtn = document.getElementById('submitBtn');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const totalQuestions = <?php echo $totalQuestions; ?>;
    
    // Handle option button clicks
    optionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            const questionName = radio.name;
            
            // Remove selected class from all buttons in this question group
            const sameGroupButtons = document.querySelectorAll(`input[name="${questionName}"]`);
            sameGroupButtons.forEach(r => {
                r.closest('.option-button').classList.remove('selected');
            });
            
            // Add selected class to clicked button
            this.classList.add('selected');
            radio.checked = true;
            
            updateProgress();
        });
    });
    
    function updateProgress() {
        const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
        const percentage = (answeredQuestions / totalQuestions) * 100;
        
        progressFill.style.width = percentage + '%';
        progressText.textContent = `${answeredQuestions} / ${totalQuestions}`;
        
        // Enable submit button when all questions are answered
        if (answeredQuestions === totalQuestions) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }
    
    // Initial progress update
    updateProgress();
});
</script>

</body>
</html>