
<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: signup.html");
    exit();
}

if (isset($_SESSION["login_time"]) && time() - $_SESSION["login_time"] > 1800) {
    session_unset();
    session_destroy();
    header("Location: signup.html");
    exit();
}

if ($_SESSION["role"] != "admin") {
    header("Location: dashboard.php");
    exit();
}

include "db.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>EduVerse - Admin Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <h1>EduVerse Admin Dashboard</h1>

    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>

    <p>You are logged in as an administrator.</p>

    <h3>Admin Panel</h3>
    <p>You can manage student information from this panel.</p>

    <a href="dashboard.php">Student Dashboard</a>
    <br><br>
    <a href="logout.php">Logout</a>

    <h3>Registered Students</h3>

    <?php
    $result = mysqli_query($conn, "SELECT student_id, name, username, email, role FROM students");

    if ($result && mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th></tr>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["student_id"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["username"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["role"]) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "No students registered.";
    }

    mysqli_close($conn);
    ?>

</body>
</html>
