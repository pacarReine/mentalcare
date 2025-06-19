<?php
session_start();
include 'db.php';

$username = $email = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['User_Username'], $_POST['User_Email'], $_POST['User_Password_1'], $_POST['User_Password_2'])) {

        $username = trim($_POST['User_Username']);
        $email = trim($_POST['User_Email']);
        $password1 = $_POST['User_Password_1'];
        $password2 = $_POST['User_Password_2'];
        $role = "user";

        if ($password1 !== $password2) {
            $error = "⚠️ Passwords do not match.";
        } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\W).{8,}$/', $password1)) {
            $error = "⚠️ Password must be at least 8 characters, contain one uppercase letter and one symbol.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE User_Email = ? OR User_Username = ?");
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "⚠️ Username or email already exists.";
            } else {
                $hashed_password = password_hash($password1, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (User_Username, User_Email, User_Password, User_Role) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

                if ($stmt->execute()) {
                    $_SESSION['User_Username'] = $username;
                    $_SESSION['User_Role'] = $role;
                    header("Location: user.php");
                    exit();
                } else {
                    $error = "⚠️ Error: " . $stmt->error;
                }
            }
        }
    } else {
        $error = "⚠️ Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Mental Health App</title>
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
        input[type="email"],
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
        .strength-meter {
            height: 8px;
            width: 100%;
            background: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 5px;
        }
        .strength-bar {
            height: 100%;
            width: 0%;
            background-color: red;
            transition: width 0.3s ease-in-out;
        }
        .field-warning {
            color: #cc0000;
            font-size: 0.85rem;
            margin-top: 3px;
            display: block;
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
        .login-link {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        .login-link a {
            color: #6a11cb;
            font-weight: bold;
            text-decoration: none;
        }
        .error {
            background: #ffe6e6;
            color: #cc0000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            text-align: center;
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
    <h1>Register</h1>
    <p style="text-align:center;">Create your account below</p>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
    <form method="post" onsubmit="return validateForm()">
        <div class="form-group">
            <label for="User_Username">Username</label>
            <input type="text" name="User_Username" id="User_Username" value="<?= htmlspecialchars($username) ?>" required>
            <span class="field-warning" id="username-warning"></span>
        </div>
        <div class="form-group">
            <label for="User_Email">Email</label>
            <input type="email" name="User_Email" id="User_Email" value="<?= htmlspecialchars($email) ?>" required>
            <span class="field-warning" id="email-warning"></span>
        </div>
        <div class="form-group">
            <label for="User_Password_1">Password</label>
            <input type="password" name="User_Password_1" id="User_Password_1" required oninput="checkStrength(this.value)">
            <i class="toggle-eye fa fa-eye-slash" onclick="togglePassword('User_Password_1', this)"></i>
            <div class="strength-meter"><div class="strength-bar" id="strengthBar"></div></div>
            <small>Password must be 8+ characters with an uppercase and a symbol</small>
            <span class="field-warning" id="password1-warning"></span>
        </div>
        <div class="form-group">
            <label for="User_Password_2">Confirm Password</label>
            <input type="password" name="User_Password_2" id="User_Password_2" required>
            <i class="toggle-eye fa fa-eye-slash" onclick="togglePassword('User_Password_2', this)"></i>
            <span class="field-warning" id="password2-warning"></span>
        </div>
        <button type="submit" name="reg_user" class="submit-button">Register</button>
    </form>
    <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
</div>

<script>
    function togglePassword(fieldId, icon) {
        const input = document.getElementById(fieldId);
        const isVisible = input.type === "text";
        input.type = isVisible ? "password" : "text";
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    }

    function checkStrength(password) {
        const bar = document.getElementById('strengthBar');
        let strength = 0;
        if (password.length >= 8) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/\W/.test(password)) strength += 1;

        if (strength === 0) {
            bar.style.width = "0%";
            bar.style.backgroundColor = "transparent";
        } else if (strength === 1) {
            bar.style.width = "33%";
            bar.style.backgroundColor = "red";
        } else if (strength === 2) {
            bar.style.width = "66%";
            bar.style.backgroundColor = "orange";
        } else if (strength === 3) {
            bar.style.width = "100%";
            bar.style.backgroundColor = "green";
        }
    }

    function validateForm() {
        let valid = true;

        const username = document.getElementById("User_Username").value.trim();
        const email = document.getElementById("User_Email").value.trim();
        const password1 = document.getElementById("User_Password_1").value;
        const password2 = document.getElementById("User_Password_2").value;

        document.getElementById("username-warning").innerText = "";
        document.getElementById("email-warning").innerText = "";
        document.getElementById("password1-warning").innerText = "";
        document.getElementById("password2-warning").innerText = "";

        if (username === "") {
            document.getElementById("username-warning").innerText = "⚠️ Username is required.";
            valid = false;
        }

        if (email === "") {
            document.getElementById("email-warning").innerText = "⚠️ Email is required.";
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            document.getElementById("email-warning").innerText = "⚠️ Invalid email format (e.g., name@example.com).";
            valid = false;
        }

        if (password1 === "") {
            document.getElementById("password1-warning").innerText = "⚠️ Password is required.";
            valid = false;
        } else if (!/^(?=.*[A-Z])(?=.*\W).{8,}$/.test(password1)) {
            document.getElementById("password1-warning").innerText = "⚠️ Password must be 8+ characters, 1 uppercase & 1 symbol.";
            valid = false;
        }

        if (password2 === "") {
            document.getElementById("password2-warning").innerText = "⚠️ Please confirm your password.";
            valid = false;
        } else if (password1 !== password2) {
            document.getElementById("password2-warning").innerText = "⚠️ Passwords do not match.";
            valid = false;
        }

        return valid;
    }
</script>
</body>
</html>
