
<?php
session_start();
include "db.php";

if (!isset($_SESSION["student_id"]) || ($_SESSION["role"] ?? "") != "admin") {
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

$userCount = 0;
$studentCount = 0;
$eventCount = 0;
$latestRegistrations = [];
$errorMessage = "";

$userResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students");

if ($userResult) {
    $userCount = mysqli_fetch_assoc($userResult)["total"];
} else {
    $errorMessage = "Could not load user statistics.";
}

$studentResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students WHERE role = 'student'");

if ($studentResult) {
    $studentCount = mysqli_fetch_assoc($studentResult)["total"];
}

$eventResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM events");

if ($eventResult) {
    $eventCount = mysqli_fetch_assoc($eventResult)["total"];
} else {
    $errorMessage = "Could not load event statistics.";
}

$registrationQuery = "SELECT r.registration_id, s.name, s.email,
                      e.event_name
                      FROM registrations r
                      LEFT JOIN students s ON r.student_id = s.student_id
                      LEFT JOIN events e ON r.event_id = e.event_id
                      ORDER BY r.registration_id DESC
                      LIMIT 5";

$registrationResult = mysqli_query($conn, $registrationQuery);

if ($registrationResult) {
    while ($row = mysqli_fetch_assoc($registrationResult)) {
        $latestRegistrations[] = $row;
    }
} else {
    $errorMessage = "Could not load latest registrations. Check your registrations table.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>EduVerse - Admin Dashboard</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f4f4;
            color: #333;
        }

        .header {
            background: #007bff;
            color: white;
            padding: 20px;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            padding: 20px;
        }

        .menu {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .menu a {
            display: inline-block;
            background: #007bff;
            color: white;
            text-decoration: none;
            padding: 10px 14px;
            margin: 4px;
            border-radius: 4px;
        }

        .menu a:hover {
            background: #0056b3;
        }

        .menu .logout {
            background: #dc3545;
        }

        .stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            flex: 1;
            min-width: 180px;
            box-shadow: 0 2px 5px #ddd;
        }

        .card h3 {
            margin-top: 0;
            color: #555;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        .message {
            color: #b02a37;
            margin: 10px 0;
        }

        @media (max-width: 600px) {
            .container {
                padding: 10px;
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }
        }
    </style>
</head>

<body>

<div class="header">
    <h1>EduVerse Admin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION["name"] ?? "Admin"); ?>!</p>
    <p>Role: <?php echo htmlspecialchars($_SESSION["role"]); ?></p>
</div>

<div class="container">

    <div class="menu">
        <h2>Dashboard Menu</h2>

        <?php if (($_SESSION["role"] ?? "") == "admin") { ?>
            <a href="admin.php">Dashboard</a>
            <a href="students.php">Manage Students</a>
            <a href="events.php">Manage Events</a>
            <a href="api_events.html">Events API Practical</a>
            <a href="logout.php" class="logout">Logout</a>
        <?php } elseif (($_SESSION["role"] ?? "") == "student") { ?>
            <a href="dashboard.php">Student Dashboard</a>
            <a href="logout.php" class="logout">Logout</a>
        <?php } ?>
    </div>

    <h2>Dashboard Statistics</h2>

    <?php if ($errorMessage != "") { ?>
        <p class="message"><?php echo htmlspecialchars($errorMessage); ?></p>
    <?php } ?>

    <div class="stats">

        <div class="card">
            <h3>Total Users</h3>
            <p class="number"><?php echo (int)$userCount; ?></p>
        </div>

        <div class="card">
            <h3>Total Students</h3>
            <p class="number"><?php echo (int)$studentCount; ?></p>
        </div>

        <div class="card">
            <h3>Total Events</h3>
            <p class="number"><?php echo (int)$eventCount; ?></p>
        </div>

    </div>

    <h2>Latest Registrations</h2>

    <?php if (count($latestRegistrations) > 0) { ?>

        <table>
            <tr>
                <th>Registration ID</th>
                <th>Student Name</th>
                <th>Email</th>
                <th>Event Name</th>
            </tr>

            <?php foreach ($latestRegistrations as $registration) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($registration["registration_id"]); ?></td>
                    <td><?php echo htmlspecialchars($registration["name"] ?? "Unknown"); ?></td>
                    <td><?php echo htmlspecialchars($registration["email"] ?? "Unknown"); ?></td>
                    <td><?php echo htmlspecialchars($registration["event_name"] ?? "Unknown"); ?></td>
                </tr>
            <?php } ?>

        </table>

    <?php } else { ?>

        <p>No registrations found yet.</p>

    <?php } ?>

</div>

</body>
</html>

