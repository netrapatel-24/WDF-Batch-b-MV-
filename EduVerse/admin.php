
<?php
session_start();
include "db.php";

if (!isset($_SESSION["student_id"]) || $_SESSION["role"] != "admin") {
    header("Location: signup.html");
    exit();
}

if (isset($_SESSION["login_time"]) && time() - $_SESSION["login_time"] > 1800) {
    session_unset();
    session_destroy();
    header("Location: signup.html");
    exit();
}

$_SESSION["login_time"] = time();

$sql = "SELECT student_id, name, email, phone, city, role FROM students";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - EduVerse</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
        }

        h1, h2 {
            color: #333;
        }

        .links {
            margin: 20px 0;
        }

        .links a {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            margin: 5px 5px 5px 0;
            text-decoration: none;
            border-radius: 4px;
        }

        .links a:hover {
            background: #0056b3;
        }

        .logout {
            background: #dc3545 !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        @media (max-width: 700px) {
            .container {
                padding: 12px;
                overflow-x: auto;
            }

            table {
                min-width: 650px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <h1>Admin Dashboard</h1>

    <p>Welcome, <?php echo htmlspecialchars($_SESSION["name"] ?? "Admin"); ?>!</p>
    <p>You are logged in as an administrator.</p>

    <div class="links">
        <a href="students.php">Manage Students</a>
        <a href="events.php">Manage Events</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <h2>Registered Students</h2>

    <?php if ($result && mysqli_num_rows($result) > 0) { ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>City</th>
                <th>Role</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["student_id"]); ?></td>
                    <td><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["email"]); ?></td>
                    <td><?php echo htmlspecialchars($row["phone"] ?? ""); ?></td>
                    <td><?php echo htmlspecialchars($row["city"] ?? ""); ?></td>
                    <td><?php echo htmlspecialchars($row["role"]); ?></td>
                </tr>
            <?php } ?>

        </table>

    <?php } else { ?>

        <p>No students found.</p>

    <?php } ?>

</div>

</body>
</html>

