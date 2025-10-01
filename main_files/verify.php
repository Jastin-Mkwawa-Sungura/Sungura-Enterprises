<?php
session_start();
header("Content-Type: application/json");

$response = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    $response["message"] = "Invalid request method.";
    echo json_encode($response);
    exit;
}

$code = trim($_POST["code"] ?? "");

// Validate: must be exactly 6 digits
if (!preg_match('/^[0-9]{6}$/', $code)) {
    http_response_code(400);
    $response["message"] = "❌ Code must be exactly 6 digits.";
    echo json_encode($response);
    exit;
}

$conn = new mysqli("localhost", "root", "", "sungura_enterprises");
if ($conn->connect_error) {
    http_response_code(500);
    $response["message"] = "Database connection failed.";
    echo json_encode($response);
    exit;
}

$sql = "SELECT id FROM chekecha_bongo_codes WHERE code = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $_SESSION["user_id"] = (int)$row["id"];
    $response["success"] = true;
    $response["message"] = " Code verified successfully! \n\n";
} else {
    http_response_code(401);
    $response["message"] = " Invalid code. Try again.";
}

$stmt->close();
$conn->close();

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
