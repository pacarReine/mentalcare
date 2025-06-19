<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['User_Username'];
    $password = $_POST['User_Password'];

    if (isset($_POST['remember'])) {
        setcookie("remember_username", $username, time() + (86400 * 30), "/");
    } else {
        setcookie("remember_username", "", time() - 3600, "/");
    }

    $sql = "SELECT * FROM users WHERE User_Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['User_Password'])) {
                $_SESSION['User_Username'] = $user['User_Username'];
                $_SESSION['User_Role'] = $user['User_Role'];

                if ($user['User_Role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: user.php");
                }
                exit();
            } else {
                $_SESSION['error'] = "Invalid password.";
            }
        } else {
            $_SESSION['error'] = "Username not found.";
        }
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
    }

    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Mental Health App</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom, #fbc2eb, #a6c1ee);
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
        }
        h1 {
            margin-bottom: 10px;
            text-align: center;
            color: #6a11cb;
        }
        .form-group {
            margin-bottom: 15px;
            position: relative;
        }
        label {
            font-weight: 600;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 2px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
        }
        .toggle-eye {
            position: absolute;
            top: 43px;
            right: 12px;
            cursor: pointer;
            font-size: 1.2rem;
            color: #999;
        }
        .submit-button {
            width: 100%;
            padding: 12px;
            background: #6a11cb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 10px;
        }
        .submit-button:hover {
            background: #4b00b6;
        }
        .remember-me {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }
        .error {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        .register-link a {
            color: #6a11cb;
            font-weight: bold;
            text-decoration: none;
        }
        @media screen and (max-width: 480px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Login</h1>
    <p style="text-align:center;">Enter your credentials below</p>
    <?php
    if (isset($_SESSION['error'])) {
        echo "<div class='error'>" . $_SESSION['error'] . "</div>";
        unset($_SESSION['error']);
    }
    ?>
    <form method="post" action="">
        <div class="form-group">
            <label for="User_Username">Username</label>
            <input type="text" id="User_Username" name="User_Username"
                   value="<?php echo isset($_COOKIE['remember_username']) ? htmlspecialchars($_COOKIE['remember_username']) : ''; ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="User_Password">Password</label>
            <input type="password" id="User_Password" name="User_Password" required>
            <i class="toggle-eye fa fa-eye-slash" onclick="togglePassword('User_Password', this)"></i>
        </div>

        <div class="remember-me">
            <input type="checkbox" name="remember" id="remember"
                <?php if (isset($_COOKIE['remember_username'])) echo 'checked'; ?>>
            <label for="remember">Remember me</label>
        </div>

        <button type="submit" class="submit-button">Login</button>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </form>
</div>

<script>
    function togglePassword(fieldId, icon) {
        const input = document.getElementById(fieldId);
        const isVisible = input.type === "text";

        input.type = isVisible ? "password" : "text";
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    }
</script>
</body>
</html>
