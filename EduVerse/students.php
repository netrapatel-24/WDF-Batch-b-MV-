
<?php
session_start();
include "db.php";

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

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: dashboard.php");
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$message = "";
$editStudent = null;

if (isset($_GET["msg"])) {
    $messages = [
        "added" => "Student added successfully!",
        "updated" => "Student updated successfully!",
        "deleted" => "Student deleted successfully!",
        "failed" => "Operation failed. Please try again."
    ];

    $message = $messages[$_GET["msg"]] ?? "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"] ?? "";

    if ($action == "add") {
        $name = trim($_POST["name"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "student";

        if ($name == "" || $username == "" || $email == "" ||
            $phone == "" || $city == "" || $password == "") {
            $message = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email.";
        } elseif (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
            $message = "Username must contain 3 to 20 letters, numbers or underscores.";
        } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
            $message = "Phone number must contain 10 digits.";
        } elseif (strlen($password) < 6) {
            $message = "Password must be at least 6 characters.";
        } elseif (!in_array($role, ["student", "admin"], true)) {
            $message = "Invalid role.";
        } else {
            $check = mysqli_prepare($conn,
                "SELECT student_id FROM students WHERE email = ? OR username = ?");

            mysqli_stmt_bind_param($check, "ss", $email, $username);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $message = "Email or username already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = mysqli_prepare($conn,
                    "INSERT INTO students (name, username, email, password, phone, city, role)
                     VALUES (?, ?, ?, ?, ?, ?, ?)");

                mysqli_stmt_bind_param($stmt, "sssssss",
                    $name, $username, $email, $hash, $phone, $city, $role);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    mysqli_stmt_close($check);
                    header("Location: students.php?msg=added");
                    exit();
                } else {
                    $message = "Unable to add student.";
                }

                mysqli_stmt_close($stmt);
            }

            mysqli_stmt_close($check);
        }
    }

    if ($action == "edit") {
        $id = (int)($_POST["student_id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "student";

        if ($id <= 0 || $name == "" || $username == "" ||
            $email == "" || $phone == "" || $city == "") {
            $message = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email.";
        } elseif (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
            $message = "Please enter a valid username.";
        } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
            $message = "Phone number must contain 10 digits.";
        } elseif ($password != "" && strlen($password) < 6) {
            $message = "New password must be at least 6 characters.";
        } elseif (!in_array($role, ["student", "admin"], true)) {
            $message = "Invalid role.";
        } else {
            $check = mysqli_prepare($conn,
                "SELECT student_id FROM students
                 WHERE (email = ? OR username = ?) AND student_id != ?");

            mysqli_stmt_bind_param($check, "ssi", $email, $username, $id);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $message = "Email or username already belongs to another student.";
            } else {
                if ($password != "") {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = mysqli_prepare($conn,
                        "UPDATE students
                         SET name=?, username=?, email=?, phone=?, city=?, role=?, password=?
                         WHERE student_id=?");

                    mysqli_stmt_bind_param($stmt, "sssssssi",
                        $name, $username, $email, $phone, $city, $role, $hash, $id);
                } else {
                    $stmt = mysqli_prepare($conn,
                        "UPDATE students
                         SET name=?, username=?, email=?, phone=?, city=?, role=?
                         WHERE student_id=?");

                    mysqli_stmt_bind_param($stmt, "ssssssi",
                        $name, $username, $email, $phone, $city, $role, $id);
                }

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    mysqli_stmt_close($check);
                    header("Location: students.php?msg=updated");
                    exit();
                } else {
                    $message = "Unable to update student.";
                }

                mysqli_stmt_close($stmt);
            }

            mysqli_stmt_close($check);
        }
    }

    if ($action == "delete") {
        $id = (int)($_POST["student_id"] ?? 0);

        $stmt = mysqli_prepare($conn,
            "DELETE FROM students WHERE student_id = ?");

        mysqli_stmt_bind_param($stmt, "i", $id);

        if ($id > 0 && mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                mysqli_stmt_close($stmt);
                header("Location: students.php?msg=deleted");
                exit();
            } else {
                $message = "Student not found.";
            }
        } else {
            $message = "Unable to delete student. The record may be linked to registrations.";
        }

        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];

    $stmt = mysqli_prepare($conn,
        "SELECT student_id, name, username, email, phone, city, role
         FROM students WHERE student_id = ?");

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editStudent = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$search = trim($_GET["search"] ?? "");
$roleFilter = $_GET["role"] ?? "";

if (!in_array($roleFilter, ["", "student", "admin"], true)) {
    $roleFilter = "";
}

$sql = "SELECT student_id, name, username, email, phone, city, role FROM students";
$conditions = [];
$params = [];
$types = "";

if ($search != "") {
    $conditions[] = "(name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if ($roleFilter != "") {
    $conditions[] = "role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY student_id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$students = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 25px;
            background: #f4f6f9;
        }

        h1, h2 {
            color: #263b5e;
        }

        input, select, button {
            padding: 8px;
            margin: 5px 0;
        }

        input, select {
            max-width: 100%;
        }

        button {
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 9px;
            text-align: left;
        }

        th {
            background: #263b5e;
            color: white;
        }

        .message {
            color: green;
            font-weight: bold;
        }

        .error {
            color: #b00020;
            font-weight: bold;
        }

        .form-box {
            background: white;
            padding: 15px;
            margin-bottom: 20px;
        }

        a {
            margin-right: 8px;
        }
    </style>
</head>
<body>

<h1>Student Management Module</h1>

<p>Welcome, <?php echo e($_SESSION["name"] ?? "Admin"); ?>!</p>

<a href="admin.php">Admin Dashboard</a>
<a href="dashboard.php">Student Dashboard</a>
<a href="logout.php">Logout</a>

<?php if ($message != ""): ?>
    <p class="<?php echo strpos($message, "successfully") !== false ? "message" : "error"; ?>">
        <?php echo e($message); ?>
    </p>
<?php endif; ?>

<div class="form-box">
    <h2><?php echo $editStudent ? "Edit Student" : "Add Student"; ?></h2>

    <form method="POST" action="students.php">
        <input type="hidden" name="action"
            value="<?php echo $editStudent ? "edit" : "add"; ?>">

        <?php if ($editStudent): ?>
            <input type="hidden" name="student_id"
                value="<?php echo e($editStudent["student_id"]); ?>">
        <?php endif; ?>

        <label>Full Name</label><br>
        <input type="text" name="name" required
            value="<?php echo e($editStudent["name"] ?? ""); ?>"><br>

        <label>Username</label><br>
        <input type="text" name="username" required
            value="<?php echo e($editStudent["username"] ?? ""); ?>"><br>

        <label>Email</label><br>
        <input type="email" name="email" required
            value="<?php echo e($editStudent["email"] ?? ""); ?>"><br>

        <label>Phone (10 digits)</label><br>
        <input type="text" name="phone" required
            value="<?php echo e($editStudent["phone"] ?? ""); ?>"><br>

        <label>City</label><br>
        <input type="text" name="city" required
            value="<?php echo e($editStudent["city"] ?? ""); ?>"><br>

        <label>
            <?php echo $editStudent ? "New Password (leave blank to keep current password)" : "Password"; ?>
        </label><br>
        <input type="password" name="password"
            <?php echo $editStudent ? "" : "required"; ?>><br>

        <label>Role</label><br>
        <select name="role" required>
            <option value="student"
                <?php echo (($editStudent["role"] ?? "student") == "student") ? "selected" : ""; ?>>
                Student
            </option>
            <option value="admin"
                <?php echo (($editStudent["role"] ?? "") == "admin") ? "selected" : ""; ?>>
                Admin
            </option>
        </select><br>

        <button type="submit">
            <?php echo $editStudent ? "Update Student" : "Add Student"; ?>
        </button>

        <?php if ($editStudent): ?>
            <a href="students.php">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="form-box">
    <h2>Search and Filter Students</h2>

    <form method="GET" action="students.php">
        <input type="text" name="search"
            placeholder="Search name, username or email"
            value="<?php echo e($search); ?>">

        <select name="role">
            <option value="">All Roles</option>
            <option value="student"
                <?php echo $roleFilter == "student" ? "selected" : ""; ?>>
                Student
            </option>
            <option value="admin"
                <?php echo $roleFilter == "admin" ? "selected" : ""; ?>>
                Admin
            </option>
        </select>

        <button type="submit">Search / Filter</button>
        <a href="students.php">Show All</a>
    </form>
</div>

<h2>Student Records</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Phone</th>
        <th>City</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>

    <?php if (mysqli_num_rows($students) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($students)): ?>
            <tr>
                <td><?php echo e($row["student_id"]); ?></td>
                <td><?php echo e($row["name"]); ?></td>
                <td><?php echo e($row["username"]); ?></td>
                <td><?php echo e($row["email"]); ?></td>
                <td><?php echo e($row["phone"]); ?></td>
                <td><?php echo e($row["city"]); ?></td>
                <td><?php echo e($row["role"]); ?></td>
                <td>
                    <a href="students.php?edit=<?php echo e($row["student_id"]); ?>">
                        Edit
                    </a>

                    <form method="POST" action="students.php"
                        onsubmit="return confirm('Delete this student?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="student_id"
                            value="<?php echo e($row["student_id"]); ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8">No student records found.</td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
