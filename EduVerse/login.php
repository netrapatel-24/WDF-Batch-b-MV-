<?php

session_start();

include "db.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("Please login using the login form.");
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email == "" || $password == "") {
    exit("Please enter email and password.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Please enter a valid email address.");
}

$stmt = mysqli_prepare($conn, "SELECT student_id, name, username, email, password, role FROM students WHERE email = ?");

mysqli_stmt_bind_param($stmt, "s", $email);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    exit("Invalid email or password.");
}

$user = mysqli_fetch_assoc($result);

if (!password_verify($password, $user["password"])) {
    exit("Invalid email or password.");
}

session_regenerate_id(true);

$_SESSION["student_id"] = $user["student_id"];
$_SESSION["name"] = $user["name"];
$_SESSION["username"] = $user["username"];
$_SESSION["email"] = $user["email"];
$_SESSION["role"] = $user["role"];
$_SESSION["login_time"] = time();

if ($user["role"] == "admin") {
    header("Location: admin.php");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}

?>