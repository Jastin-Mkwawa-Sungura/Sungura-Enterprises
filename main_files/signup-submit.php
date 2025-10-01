<?php
include'config.php';

// 1. Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Collect and sanitize form data
    $name     = htmlspecialchars(trim($_POST['name']));
    $country  = htmlspecialchars(trim($_POST['country']));
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone    = htmlspecialchars(trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // 3. Validate input
    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    // 4. If there are errors, show them
    if (!empty($errors)) {
        echo "<h3>Form Errors:</h3><ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul><h3><a href='../welcome-page.html'>Go Back</a></h3>";
        exit;
    }

    // 5. (Optional) Hash password before saving to DB
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // --- If using a database (MySQL example) ---

    $conn = new mysqli("localhost", "root", "", "sungura_enterprises");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO signup (name, country, email, phone, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $country, $email, $phone, $hashedPassword);

    if ($stmt->execute()) {
        echo "<h3>Registration successful! <a href='../welcome-page.html'>Now go to Login</h3></a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    // 6. If no database, just show data
    /*echo "<h3>Form submitted successfully!</h3>";
    echo "Name: $name <br>";
    echo "Country: $country <br>";
    echo "Email: $email <br>";
    echo "Phone: $phone <br>";*/
} else {
    echo "Invalid request.";
}
?>
