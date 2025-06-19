<?php
session_start();
include 'db.php';

if (!isset($_SESSION['User_Username'])) {
    echo "Please login first.";
    exit();
}

$userUsername = $_SESSION['User_Username'];

// Get User_ID
$userQuery = $conn->prepare("SELECT User_ID FROM users WHERE User_Username = ?");
$userQuery->bind_param("s", $userUsername);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userRow = $userResult->fetch_assoc();
$userID = $userRow['User_ID'] ?? null;

if (!$userID) {
    echo "User not found.";
    exit();
}

// === TEXT PREPROCESSING ===
function normalizeContractions($text) {
    $contractions = [
        "i'm" => "i am", "you're" => "you are", "he's" => "he is", "she's" => "she is", "it's" => "it is",
        "we're" => "we are", "they're" => "they are", "can't" => "cannot", "won't" => "will not", "don't" => "do not",
        "didn't" => "did not", "isn't" => "is not", "aren't" => "are not", "wasn't" => "was not", "weren't" => "were not",
        "i've" => "i have", "you've" => "you have", "we've" => "we have", "they've" => "they have",
        "i'll" => "i will", "you'll" => "you will", "we'll" => "we will", "they'll" => "they will",
        "i'd" => "i would", "you'd" => "you would", "he'd" => "he would", "she'd" => "she would", "they'd" => "they would",
        "im" => "i am", "dont" => "do not", "idk" => "i do not know", "ive" => "i have"
    ];
    foreach ($contractions as $short => $long) {
        $text = str_ireplace($short, $long, $text);
    }
    return $text;
}

function normalizeSynonyms($text) {
    $synonyms = [
        "get help" => "seek support",
        "need help" => "seek support",
        "talk to" => "speak with",
        "look for help" => "seek support",
        "someone to talk to" => "speak with someone"
    ];
    foreach ($synonyms as $variant => $standard) {
        $text = str_ireplace($variant, $standard, $text);
    }
    return $text;
}

// REMOVED: mapEmojiToKeyword function - no longer needed

function preprocessText($text) {
    $text = strtolower(trim($text));
    $text = normalizeContractions($text);
    $text = normalizeSynonyms($text);
    // REMOVED: $text = mapEmojiToKeyword($text); - emoji functionality removed
    $text = preg_replace("/[^a-zA-Z0-9\s]/u", "", $text); // remove punctuation
    $text = preg_replace("/\s+/", " ", $text);
    return explode(" ", $text);
}

// === MATCHING FUNCTIONS ===
function calculateMatchScore($userWords, $patternWords) {
    $score = 0;
    foreach ($patternWords as $pWord) {
        foreach ($userWords as $uWord) {
            if (
                $pWord === $uWord ||
                levenshtein($pWord, $uWord) <= 1 ||
                strpos($uWord, $pWord) !== false ||
                strpos($pWord, $uWord) !== false ||
                soundex($pWord) === soundex($uWord)
            ) {
                $score++;
                break;
            }
        }
    }
    return $score;
}

// === TIPS DATABASE FUNCTIONS ===
function getTipsByKeywords($conn, $keywords) {
    $tips = [];
    $keywordArray = is_array($keywords) ? $keywords : explode(" ", $keywords);
    
    foreach ($keywordArray as $keyword) {
        $keyword = trim($keyword);
        if (strlen($keyword) > 2) { // Only search for meaningful keywords
            $stmt = $conn->prepare("SELECT * FROM tips WHERE keywords LIKE ? OR tip LIKE ? ORDER BY RAND() LIMIT 2");
            $searchTerm = "%" . $keyword . "%";
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $tips[] = $row;
            }
        }
    }
    
    // Remove duplicates based on Tips_ID
    $uniqueTips = [];
    $seenIds = [];
    foreach ($tips as $tip) {
        if (!in_array($tip['Tips_ID'], $seenIds)) {
            $uniqueTips[] = $tip;
            $seenIds[] = $tip['Tips_ID'];
        }
    }
    
    return array_slice($uniqueTips, 0, 3); // Limit to 3 tips
}

function getTipsByCategory($conn, $category) {
    $stmt = $conn->prepare("SELECT * FROM tips WHERE category = ? ORDER BY RAND() LIMIT 3");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tips = [];
    while ($row = $result->fetch_assoc()) {
        $tips[] = $row;
    }
    return $tips;
}

function getRandomTip($conn) {
    $stmt = $conn->prepare("SELECT * FROM tips ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function formatTips($tips) {
    if (empty($tips)) {
        return "";
    }
    
    $formatted = "<strong>=== Helpful Tips ===</strong><br>";
    foreach ($tips as $index => $tip) {
        $formatted .= "<strong>Tip " . ($index + 1) . ":</strong> " . htmlspecialchars($tip['tip']) . "<br><br>";
    }
    return $formatted;
}

// === TIP REQUEST DETECTION FUNCTION ===
function isTipRequest($words) {
    $tipKeywords = [
        "tip", "tips", "advice", "suggest", "suggestion", "suggestions", 
        "recommend", "recommendation", "recommendations", "guide", "guides",
        "how to", "what should i do", "strategies", "strategy", "technique", "techniques",
        "methods", "ways to", "help me", "show me", "give me tips", "share tips"
    ];
    
    $normalizedInput = implode(" ", $words);
    
    foreach ($tipKeywords as $keyword) {
        if (strpos($normalizedInput, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// === HELP DETECTION FUNCTION ===
function isHelpRequest($words) {
    $helpKeywords = [
        "help", "advice", "support", "guidance", "assistance", "recommend", "suggestion", 
        "what should i do", "need help", "seek support", "speak with someone", "counselor",
        "therapist", "professional", "crisis", "emergency", "suicide", "harm", "hurt",
        "tips", "how to", "what can i do", "strategies", "coping"
    ];
    
    $normalizedInput = implode(" ", $words);
    
    foreach ($helpKeywords as $keyword) {
        if (strpos($normalizedInput, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// === MENTAL HEALTH ANALYSIS FUNCTIONS ===
function analyzeAnxiety($words) {
    $anxietyKeywords = [
        "worried", "worry", "anxious", "anxiety", "panic", "fear", "scared", "nervous", 
        "restless", "tense", "overwhelmed", "stress", "stressed", "racing thoughts",
        "cant breathe", "heart racing", "sweating", "trembling", "catastrophic", 
        "what if", "worst case", "disaster", "terrified", "phobia", "avoidance"
    ];
    
    $score = 0;
    $normalizedInput = implode(" ", $words);
    
    foreach ($anxietyKeywords as $keyword) {
        if (strpos($normalizedInput, $keyword) !== false) {
            $score++;
        }
    }
    
    return $score;
}

function analyzeDepression($words) {
    $depressionKeywords = [
        "sad", "depressed", "depression", "hopeless", "worthless", "empty", "numb",
        "lonely", "isolated", "tired", "exhausted", "no energy", "cant sleep", 
        "sleeping too much", "no appetite", "overeating", "guilt", "shame", 
        "no motivation", "pointless", "meaningless", "suicidal", "end it all",
        "give up", "cant go on", "hate myself", "useless", "burden"
    ];
    
    $score = 0;
    $normalizedInput = implode(" ", $words);
    
    foreach ($depressionKeywords as $keyword) {
        if (strpos($normalizedInput, $keyword) !== false) {
            $score++;
        }
    }
    
    return $score;
}

function analyzeStress($words) {
    $stressKeywords = [
        "stress", "stressed", "pressure", "overwhelmed", "burned out", "exhausted",
        "too much", "cant handle", "breaking point", "overloaded", "deadline",
        "demands", "responsibilities", "juggling", "multitasking", "chaos",
        "frantic", "rushed", "no time", "behind schedule", "workload", "burden"
    ];
    
    $score = 0;
    $normalizedInput = implode(" ", $words);
    
    foreach ($stressKeywords as $keyword) {
        if (strpos($normalizedInput, $keyword) !== false) {
            $score++;
        }
    }
    
    return $score;
}

function generateAnalysis($anxietyScore, $depressionScore, $stressScore) {
    $analysis = "<br><br><strong>=== Mental Health Analysis ===</strong><br>";
    
    // Determine severity levels
    $anxietyLevel = $anxietyScore >= 3 ? "High" : ($anxietyScore >= 2 ? "Moderate" : ($anxietyScore >= 1 ? "Mild" : "Low"));
    $depressionLevel = $depressionScore >= 3 ? "High" : ($depressionScore >= 2 ? "Moderate" : ($depressionScore >= 1 ? "Mild" : "Low"));
    $stressLevel = $stressScore >= 3 ? "High" : ($stressScore >= 2 ? "Moderate" : ($stressScore >= 1 ? "Mild" : "Low"));
    
    $analysis .= "<strong>Anxiety Level:</strong> $anxietyLevel ($anxietyScore indicators)<br>";
    $analysis .= "<strong>Depression Level:</strong> $depressionLevel ($depressionScore indicators)<br>";
    $analysis .= "<strong>Stress Level:</strong> $stressLevel ($stressScore indicators)<br><br>";
    
    // Generate recommendations based on highest concern
    $maxScore = max($anxietyScore, $depressionScore, $stressScore);
    
    if ($maxScore >= 3) {
        $analysis .= "<strong>Recommendation:</strong> Your responses indicate significant mental health concerns. I strongly recommend speaking with a mental health professional, counselor, or therapist for proper support and guidance.<br><br>";
    } elseif ($maxScore >= 2) {
        $analysis .= "<strong>Recommendation:</strong> You may be experiencing moderate mental health challenges. Consider reaching out to a counselor, trusted friend, or family member for support.<br><br>";
    } elseif ($maxScore >= 1) {
        $analysis .= "<strong>Recommendation:</strong> You're showing some signs of mental health challenges. Practice self-care and don't hesitate to reach out for support if you need it.<br><br>";
    } else {
        $analysis .= "<strong>Recommendation:</strong> Your mental health indicators appear to be in a good range. Continue maintaining healthy habits and emotional well-being.<br><br>";
    }
    
    // Specific advice based on primary concern
    if ($anxietyScore >= $depressionScore && $anxietyScore >= $stressScore && $anxietyScore > 0) {
        $analysis .= "<strong>Anxiety Support:</strong> Try deep breathing exercises, mindfulness, or grounding techniques. Limit caffeine and practice regular relaxation.<br>";
    } elseif ($depressionScore >= $anxietyScore && $depressionScore >= $stressScore && $depressionScore > 0) {
        $analysis .= "<strong>Depression Support:</strong> Try to maintain a routine, get some sunlight, engage in activities you usually enjoy, and stay connected with others.<br>";
    } elseif ($stressScore > 0) {
        $analysis .= "<strong>Stress Management:</strong> Practice time management, break tasks into smaller steps, take regular breaks, and don't hesitate to ask for help.<br>";
    }
    
    return $analysis;
}

function shouldProvideAnalysis($anxietyScore, $depressionScore, $stressScore) {
    return ($anxietyScore + $depressionScore + $stressScore) >= 1;
}

// === MAIN CHATBOT LOGIC ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = $_POST['message'] ?? '';
    $userWords = preprocessText($userInput);
    $normalizedInput = implode(" ", $userWords);

    // Check if this is specifically a tip request
    $isTipRequest = isTipRequest($userWords);
    
    // Analyze mental health indicators
    $anxietyScore = analyzeAnxiety($userWords);
    $depressionScore = analyzeDepression($userWords);
    $stressScore = analyzeStress($userWords);
    
    $finalResponse = "";
    $relevantTips = [];
    
    // If this is a tip request, skip the main chatbot response
    if ($isTipRequest) {
        // Determine which category to get tips from based on analysis
        if ($anxietyScore >= $depressionScore && $anxietyScore >= $stressScore && $anxietyScore > 0) {
            $relevantTips = getTipsByCategory($conn, 'Anxiety');
        } elseif ($depressionScore >= $anxietyScore && $depressionScore >= $stressScore && $depressionScore > 0) {
            $relevantTips = getTipsByCategory($conn, 'Depression');
        } elseif ($stressScore > 0) {
            $relevantTips = getTipsByCategory($conn, 'Stress');
        } else {
            // Get tips based on keywords in user input
            $relevantTips = getTipsByKeywords($conn, $userWords);
        }
        
        // If no specific tips found, get general mental health tips
        if (empty($relevantTips)) {
            $relevantTips = getTipsByCategory($conn, 'Mental Health');
        }
        
        // If still no tips, get a random tip
        if (empty($relevantTips)) {
            $randomTip = getRandomTip($conn);
            if ($randomTip) {
                $relevantTips = [$randomTip];
            }
        }
        
        // For tip requests, only provide tips (no main response)
        $finalResponse = formatTips($relevantTips);
        
    } else {
        // Regular chatbot response logic
        // First, try to match with chatbot_rules
        $stmt = $conn->prepare("SELECT * FROM chatbot_rules");
        $stmt->execute();
        $result = $stmt->get_result();

        $bestScore = 0;
        $bestResponse = "I'm here for you. Can you tell me more about that?";

        while ($row = $result->fetch_assoc()) {
            $patterns = explode(",", strtolower($row['User_Input']));

            foreach ($patterns as $pattern) {
                $patternWords = preprocessText($pattern);
                $normalizedPattern = implode(" ", $patternWords);

                // Match by word similarity
                $matchScore = calculateMatchScore($userWords, $patternWords);
                if ($matchScore > $bestScore) {
                    $bestScore = $matchScore;
                    $responses = explode("|", $row['Bot_Response']);
                    $bestResponse = $responses[array_rand($responses)];
                }

                // Match by sentence similarity
                similar_text($normalizedInput, $normalizedPattern, $percent);
                if ($percent > 60 && $percent > $bestScore) {
                    $bestScore = $percent;
                    $responses = explode("|", $row['Bot_Response']);
                    $bestResponse = $responses[array_rand($responses)];
                }
            }
        }
        
        // Start with the main response
        $finalResponse = $bestResponse;
        
        // If mental health indicators are present, automatically provide relevant tips
        if (shouldProvideAnalysis($anxietyScore, $depressionScore, $stressScore)) {
            if ($anxietyScore >= $depressionScore && $anxietyScore >= $stressScore) {
                $relevantTips = getTipsByCategory($conn, 'Anxiety');
            } elseif ($depressionScore >= $stressScore) {
                $relevantTips = getTipsByCategory($conn, 'Depression');
            } else {
                $relevantTips = getTipsByCategory($conn, 'Stress');
            }
        }
        
        // Check if this is a help request and provide additional support message
        if (isHelpRequest($userWords)) {
            $supportMessages = [
                "Remember, seeking help is a sign of strength, not weakness. You don't have to go through this alone.",
                "If you're in crisis, please consider reaching out to a mental health professional or crisis hotline immediately.",
                "Take things one step at a time. Small steps forward are still progress.",
                "Your feelings are valid, and it's okay to ask for support when you need it.",
                "Consider talking to a trusted friend, family member, or mental health professional about what you're going through."
            ];
            
            $additionalSupport = $supportMessages[array_rand($supportMessages)];
            $finalResponse .= "<br><br>" . $additionalSupport;
        }
        
        // Add relevant tips to response
        if (!empty($relevantTips)) {
            $finalResponse .= formatTips($relevantTips);
        }
        
        // Add mental health analysis if indicators are present AND not shown before
        if (shouldProvideAnalysis($anxietyScore, $depressionScore, $stressScore) && !isset($_SESSION['analysis_shown'])) {
            $analysis = generateAnalysis($anxietyScore, $depressionScore, $stressScore);
            $finalResponse .= $analysis;
            $_SESSION['analysis_shown'] = true; // Mark analysis as shown
        }
    }
    
    echo $finalResponse;
}
?>