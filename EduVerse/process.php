<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("Please submit the registration form.");
}

$name = trim($_POST["name"] ?? "");
$username = trim($_POST["username"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirm"] ?? "";
$city = trim($_POST["city"] ?? "");

$name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
$username = htmlspecialchars($username, ENT_QUOTES, "UTF-8");
$city = htmlspecialchars($city, ENT_QUOTES, "UTF-8");

if ($name == "" || $username == "" || $phone == "" || $email == "" ||
    $password == "" || $confirm == "" || $city == "") {
    exit("Error: Please fill in all fields.");
}

if (!preg_match("/^[0-9]{10}$/", $phone)) {
    exit("Error: Enter a valid 10 digit phone number.");
}

if (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
    exit("Error: Enter a valid username.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Error: Enter a valid email address.");
}

if (strlen($password) < 6) {
    exit("Error: Password must be at least 6 characters.");
}

if ($password != $confirm) {
    exit("Error: Passwords do not match.");
}

$check = mysqli_prepare($conn, "SELECT student_id FROM students WHERE email = ? OR username = ?");

mysqli_stmt_bind_param($check, "ss", $email, $username);

mysqli_stmt_execute($check);

mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) > 0) {
    exit("Error: Email or username already exists.");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insert = mysqli_prepare($conn, "INSERT INTO students (name, username, email, password, phone, city) VALUES (?, ?, ?, ?, ?, ?)");

mysqli_stmt_bind_param($insert, "ssssss", $name, $username, $email, $hashedPassword, $phone, $city);

if (mysqli_stmt_execute($insert)) {
    echo "Registration successful!";
} else {
    echo "Error: Registration failed.";
}

mysqli_stmt_close($check);
mysqli_stmt_close($insert);

mysqli_close($conn);

?>