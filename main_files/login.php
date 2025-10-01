<?php
session_start();

$message = "";

// If user already has cookie, log them in automatically
if (!isset($_SESSION["user_id"]) && isset($_COOKIE["user_id"])) {
    $_SESSION["user_id"] = $_COOKIE["user_id"];
    $_SESSION["email"] = $_COOKIE["email"];
    header("Location: home.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $remember = isset($_POST["remember"]);

    $conn = new mysqli("localhost", "root", "", "sungura_enterprises");

    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
    } else {
        $sql = "SELECT * FROM signup WHERE email=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["password"])) {
                $_SESSION["user_id"] = $row["id"];
                $_SESSION["email"] = $row["email"];

                // ✅ Set cookie if "Remember Me" is checked (7 days)
                if ($remember) {
                    setcookie("user_id", $row["id"], time() + (7 * 24 * 60 * 60), "/");
                    setcookie("email", $row["email"], time() + (7 * 24 * 60 * 60), "/");
                }

                header("Location: home.html");
                exit;
            } else {
                echo "</ul>❌ Invalid Email or Password. Please, <h3><a href='../welcome-page.html'>Go Back to Login</a></h3>";
                $message = "❌ Invalid password.";
            }
        } else {
            echo "</ul>❌ No user found with that email. Please, <h3><a href='../welcome-page.html'>Go Back to Sign Up</a></h3>";
            $message = "❌ No user found with that email.";
        }
    }
}
?>