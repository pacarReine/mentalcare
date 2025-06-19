<?php
session_start();
include 'db.php';

if (!isset($_SESSION['User_Username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MentalCare Chatbot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; height: 100vh; background: linear-gradient(to bottom, #fbc2eb, #a6c1ee); color: #333; }

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
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #fff;
            font-weight: 600;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chat-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        .chatbox {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-height: 400px;
            max-height: 500px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 20px;
            max-width: 75%;
            word-wrap: break-word;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user {
            background: linear-gradient(135deg, #6a11cb, #8e54e9);
            color: white;
            align-self: flex-end;
            margin-left: auto;
            border-bottom-right-radius: 5px;
            box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
        }

        .bot {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            align-self: flex-start;
            margin-right: auto;
            border-bottom-left-radius: 5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .typing {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin: 0 2px;
            background-color: #6a11cb;
            border-radius: 50%;
            animation: blink 1.4s infinite both;
        }

        .typing:nth-child(2) { animation-delay: 0.2s; }
        .typing:nth-child(3) { animation-delay: 0.4s; }

        @keyframes blink {
            0%, 80%, 100% { opacity: 0.3; }
            40% { opacity: 1; }
        }

        .input-container {
            display: flex;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        input[type="text"] {
            flex-grow: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus {
            background: white;
            box-shadow: 0 0 20px rgba(106, 17, 203, 0.2);
        }

        input[type="text"]::placeholder {
            color: #888;
        }

        button {
            padding: 15px 25px;
            border: none;
            background: linear-gradient(135deg, #6a11cb, #8e54e9);
            color: white;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
        }

        button:hover {
            background: linear-gradient(135deg, #5a0cb2, #7a47d1);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 17, 203, 0.4);
        }

        .btn-reset {
            background: linear-gradient(135deg, #f44336, #e57373);
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        .btn-reset:hover {
            background: linear-gradient(135deg, #d32f2f, #c62828);
            box-shadow: 0 6px 20px rgba(244, 67, 54, 0.4);
        }

        /* Scrollbar styling */
        .chatbox::-webkit-scrollbar {
            width: 6px;
        }

        .chatbox::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .chatbox::-webkit-scrollbar-thumb {
            background: rgba(106, 17, 203, 0.5);
            border-radius: 10px;
        }

        .chatbox::-webkit-scrollbar-thumb:hover {
            background: rgba(106, 17, 203, 0.7);
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>MentalCare</h2>
    <a href="user.php">🏠 Dashboard</a>
    <a href="mood.php">😊 Mood Tracker</a>
    <a href="journal.php">📓 Journal</a>
    <a href="goal.php">🎯 Goal Setting</a>
    <a href="motivation.php">💡 Motivational Tips</a>
    <a href="exercise.php">🏃 Exercise</a>
    <a href="chatbot.php" class="active">🧠 Chatbot</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>MentalCare Chatbot</h1>
    </div>

    <div class="chat-container">
        <div class="chatbox" id="chatbox">
            <div class="message bot">Hello! I'm here to support you. How are you feeling today?</div>
        </div>

        <div class="input-container">
            <form id="chatForm" style="display: flex; gap: 10px; width: 100%;">
                <input type="text" id="userInput" placeholder="Type your message..." required>
                <button type="submit">Send</button>
                <button type="button" id="resetButton" class="btn-reset">Reset</button>
            </form>
        </div>
    </div>
</div>

<script>
    const chatbox = document.getElementById("chatbox");
    const chatForm = document.getElementById("chatForm");
    const userInput = document.getElementById("userInput");
    const resetButton = document.getElementById("resetButton");

    chatForm.addEventListener("submit", function(e) {
        e.preventDefault();
        const userText = userInput.value.trim();
        if (!userText) return;

        chatbox.innerHTML += `<div class="message user">${userText}</div>`;
        userInput.value = "";

        const typingDots = `<div class="message bot" id="typing">
            <span class="typing"></span>
            <span class="typing"></span>
            <span class="typing"></span>
        </div>`;
        chatbox.innerHTML += typingDots;
        chatbox.scrollTop = chatbox.scrollHeight;

        fetch("chatbot_response.php", {
            method: "POST",
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: "message=" + encodeURIComponent(userText)
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById("typing").remove();
            chatbox.innerHTML += `<div class="message bot">${data}</div>`;
            chatbox.scrollTop = chatbox.scrollHeight;
        });
    });

    resetButton.addEventListener("click", function() {
        chatbox.innerHTML = `<div class="message bot">Hello! I'm here to support you. How are you feeling today?</div>`;
    });
</script>

</body>
</html>