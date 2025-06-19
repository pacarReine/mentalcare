<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MentalCare - Your Mental Health Companion</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom, #fbc2eb, #a6c1ee);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.8rem;
            color: #6a11cb;
            font-weight: 700;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 15px;
        }

        .nav-button {
            padding: 8px 16px;
            background: #6a11cb;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .nav-button:hover {
            background: #4b00b6;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        .hero-container {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
            margin-bottom: 40px;
            text-align: center;
        }

        .hero-title {
            color: #6a11cb;
            margin-bottom: 10px;
        }

        .hero-description {
            color: #555;
            margin-bottom: 25px;
        }

        .cta-button {
            padding: 12px 25px;
            background: #6a11cb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: background 0.3s;
        }

        .cta-button:hover {
            background: #4b00b6;
        }

        .features-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            width: 100%;
            max-width: 1000px;
        }

        .feature-card {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .feature-title {
            color: #6a11cb;
            margin: 10px 0;
        }

        .feature-description {
            color: #555;
            font-size: 0.9rem;
        }

        .footer {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            text-align: center;
            margin-top: 40px;
            border-top: 1px solid #ddd;
        }

        .emergency-contact {
            margin-top: 10px;
            font-weight: 600;
            color: #6a11cb;
        }

        @media (max-width: 768px) {
            .features-section {
                grid-template-columns: 1fr;
            }

            .hero-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">MentalCare</a>
        <div class="nav-links">
            <a href="register.php" class="nav-button">Register</a>
            <a href="login.php" class="nav-button">Login</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="hero-container">
            <h1 class="hero-title">Your Companion for Mental Wellness</h1>
            <p class="hero-description">24/7 support, personalized guidance, and tools to help you maintain and improve your mental well-being. Join thousands of others who have taken the first step towards better mental health.</p>
            <a href="register.php" class="cta-button">Get Started Today</a>
        </div>

        <div class="features-section">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3 class="feature-title">AI-Powered Support</h3>
                <p class="feature-description">24/7 access to caring, understanding AI support whenever you need someone to talk to.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Mood Tracking</h3>
                <p class="feature-description">Track your emotional well-being and identify patterns to better understand yourself.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">Goal Setting</h3>
                <p class="feature-description">Set and track personal wellness goals with support and accountability.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <h3 class="feature-title">Guided Journaling</h3>
                <p class="feature-description">Express your thoughts and feelings with guided journaling prompts and reflections.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💪</div>
                <h3 class="feature-title">Mental Health Exercises</h3>
                <p class="feature-description">Practice breathing techniques, relaxation methods, and DASS tests to build resilience.</p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> MentalCare. Your mental health matters.</p>
        <p class="emergency-contact">Emergency: +603 2780 6803</p>
    </footer>
</body>
</html>
