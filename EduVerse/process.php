
<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("Please submit the registration form.");
}

$name = trim($_POST["name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirm"] ?? "";
$city = trim($_POST["city"] ?? "");

$name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
$city = htmlspecialchars($city, ENT_QUOTES, "UTF-8");

if ($name == "" || $phone == "" || $email == "" ||
    $password == "" || $confirm == "" || $city == "") {
    exit("Error: Please fill in all fields.");
}

if (!preg_match("/^[0-9]{10}$/", $phone)) {
    exit("Error: Enter a valid 10 digit phone number.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Error: Enter a valid email address.");
}

if ($password != $confirm) {
    exit("Error: Passwords do not match.");
}

$file = fopen("registrations.csv", "a");

if ($file == false) {
    exit("Error: Unable to save registration.");
}

if (flock($file, LOCK_EX)) {
    fputcsv($file, [
        $name,
        $phone,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $city
    ]);
    flock($file, LOCK_UN);
    fclose($file);
    echo "Registration successful!";
} else {
    fclose($file);
    echo "Error: Unable to save registration.";
}
?>