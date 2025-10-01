<?php
    include 'config.php';

    // Sanitize input
    $sender = htmlspecialchars($_POST['sender']);
    $message = htmlspecialchars($_POST['message']);

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Insert data
    $sql = "INSERT INTO chekecha_bongo (sender, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $sender, $message);

    if ($stmt->execute()) {
        header("refresh:0; url=chekecha-bongo.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
?>
