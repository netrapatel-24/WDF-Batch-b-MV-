
<?php
session_start();
include "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: signup.html");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: dashboard.php");
    exit();
}

if (isset($_SESSION["login_time"]) && time() - $_SESSION["login_time"] > 1800) {
    session_unset();
    session_destroy();
    header("Location: signup.html");
    exit();
}

$folder = "uploads/event_posters/";
$maxSize = 2 * 1024 * 1024;
$message = "";
$editEvent = null;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function uploadPoster($file, $folder, $maxSize) {
    if (!isset($file) || $file["error"] == UPLOAD_ERR_NO_FILE) {
        return ["name" => "", "error" => ""];
    }

    if ($file["error"] != UPLOAD_ERR_OK) {
        return ["name" => "", "error" => "Poster upload failed."];
    }

    if ($file["size"] > $maxSize) {
        return ["name" => "", "error" => "Poster must be 2 MB or smaller."];
    }

    $info = getimagesize($file["tmp_name"]);

    if ($info === false) {
        return ["name" => "", "error" => "Please upload a valid image."];
    }

    $mime = $info["mime"];
    $types = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($types[$mime])) {
        return ["name" => "", "error" => "Only JPG, PNG and WEBP posters are allowed."];
    }

    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        return ["name" => "", "error" => "Poster folder could not be created."];
    }

    $filename = bin2hex(random_bytes(16)) . "." . $types[$mime];
    $destination = $folder . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        return ["name" => "", "error" => "Could not save the poster."];
    }

    return ["name" => $filename, "error" => ""];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"] ?? "";
    $name = trim($_POST["event_name"] ?? "");
    $date = trim($_POST["event_date"] ?? "");
    $venue = trim($_POST["venue"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($action == "add" || $action == "edit") {
        if ($name == "" || $date == "" || $venue == "") {
            $message = "Please fill in the event name, date and venue.";
        } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date) ||
            !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
            $message = "Please enter a valid event date.";
        } else {
            $upload = uploadPoster($_FILES["poster"] ?? null, $folder, $maxSize);

            if ($upload["error"] != "") {
                $message = $upload["error"];
            } else {
                $poster = $upload["name"];

                if ($action == "add") {
                    $stmt = mysqli_prepare($conn, "INSERT INTO events (event_name, event_date, venue, description, poster) VALUES (?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "sssss", $name, $date, $venue, $description, $poster);

                    if (mysqli_stmt_execute($stmt)) {
                        header("Location: events.php?msg=added");
                        exit();
                    }

                    if ($poster != "") {
                        @unlink($folder . $poster);
                    }
                    $message = "Could not add the event.";
                    mysqli_stmt_close($stmt);
                } else {
                    $id = (int)($_POST["event_id"] ?? 0);

                    $check = mysqli_prepare($conn, "SELECT poster FROM events WHERE event_id = ?");
                    mysqli_stmt_bind_param($check, "i", $id);
                    mysqli_stmt_execute($check);
                    $result = mysqli_stmt_get_result($check);
                    $oldEvent = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($check);

                    if (!$oldEvent) {
                        if ($poster != "") {
                            @unlink($folder . $poster);
                        }
                        $message = "Event not found.";
                    } else {
                        if ($poster == "") {
                            $poster = $oldEvent["poster"];
                        }

                        $stmt = mysqli_prepare($conn, "UPDATE events SET event_name=?, event_date=?, venue=?, description=?, poster=? WHERE event_id=?");
                        mysqli_stmt_bind_param($stmt, "sssssi", $name, $date, $venue, $description, $poster, $id);

                        if (mysqli_stmt_execute($stmt)) {
                            if ($upload["name"] != "" && !empty($oldEvent["poster"])) {
                                @unlink($folder . basename($oldEvent["poster"]));
                            }
                            header("Location: events.php?msg=updated");
                            exit();
                        }

                        if ($upload["name"] != "") {
                            @unlink($folder . $upload["name"]);
                        }
                        $message = "Could not update the event.";
                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    } elseif ($action == "delete") {
        $id = (int)($_POST["event_id"] ?? 0);

        $stmt = mysqli_prepare($conn, "SELECT poster FROM events WHERE event_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$event) {
            $message = "Event not found.";
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM events WHERE event_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);

            if (mysqli_stmt_execute($stmt)) {
                if (!empty($event["poster"])) {
                    @unlink($folder . basename($event["poster"]));
                }
                header("Location: events.php?msg=deleted");
                exit();
            } else {
                $message = "Could not delete the event.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_GET["msg"])) {
    $messages = [
        "added" => "Event added successfully!",
        "updated" => "Event updated successfully!",
        "deleted" => "Event deleted successfully!"
    ];
    $message = $messages[$_GET["msg"]] ?? "";
}

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE event_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editEvent = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$events = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>EduVerse - Event Management</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        body { padding: 20px; }
        form { margin-bottom: 20px; }
        input, textarea { display: block; margin: 8px 0 15px; padding: 8px; max-width: 100%; }
        button { padding: 8px 14px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #aaa; padding: 10px; text-align: left; }
        img { width: 100px; max-height: 100px; object-fit: contain; }
        .message { margin: 15px 0; color: #176b32; }
    </style>
</head>
<body>

<h1>Event Management</h1>
<p>Welcome, <?php echo e($_SESSION["name"] ?? "Admin"); ?>!</p>
<p><a href="admin.php">Back to Admin Dashboard</a> | <a href="logout.php">Logout</a></p>

<?php if ($message != "") { ?>
    <p class="message"><?php echo e($message); ?></p>
<?php } ?>

<h2><?php echo $editEvent ? "Update Event" : "Add Event"; ?></h2>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="<?php echo $editEvent ? "edit" : "add"; ?>">
    <?php if ($editEvent) { ?>
        <input type="hidden" name="event_id" value="<?php echo e($editEvent["event_id"]); ?>">
    <?php } ?>

    <label>Event Name</label>
    <input type="text" name="event_name" required maxlength="100" value="<?php echo e($editEvent["event_name"] ?? ""); ?>">

    <label>Event Date</label>
    <input type="date" name="event_date" required value="<?php echo e($editEvent["event_date"] ?? ""); ?>">

    <label>Venue</label>
    <input type="text" name="venue" required maxlength="100" value="<?php echo e($editEvent["venue"] ?? ""); ?>">

    <label>Description</label>
    <textarea name="description" maxlength="255"><?php echo e($editEvent["description"] ?? ""); ?></textarea>

    <label>Event Poster (JPG, PNG or WEBP; maximum 2 MB)</label>
    <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
    <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

    <?php if ($editEvent && !empty($editEvent["poster"])) { ?>
        <p>Current poster:</p>
        <img src="<?php echo e($folder . basename($editEvent["poster"])); ?>" alt="Current event poster">
        <p>Choose a new image only if you want to replace the current poster.</p>
    <?php } ?>

    <button type="submit"><?php echo $editEvent ? "Update Event" : "Add Event"; ?></button>
    <?php if ($editEvent) { ?>
        <a href="events.php">Cancel</a>
    <?php } ?>
</form>

<h2>All Events</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Event</th>
        <th>Date</th>
        <th>Venue</th>
        <th>Description</th>
        <th>Poster</th>
        <th>Actions</th>
    </tr>

    <?php if ($events && mysqli_num_rows($events) > 0) { ?>
        <?php while ($row = mysqli_fetch_assoc($events)) { ?>
            <tr>
                <td><?php echo e($row["event_id"]); ?></td>
                <td><?php echo e($row["event_name"]); ?></td>
                <td><?php echo e($row["event_date"]); ?></td>
                <td><?php echo e($row["venue"]); ?></td>
                <td><?php echo e($row["description"]); ?></td>
                <td>
                    <?php if (!empty($row["poster"]) && is_file($folder . basename($row["poster"]))) { ?>
                        <img src="<?php echo e($folder . basename($row["poster"])); ?>" alt="Event poster">
                    <?php } else { ?>
                        No poster
                    <?php } ?>
                </td>
                <td>
                    <a href="events.php?edit=<?php echo e($row["event_id"]); ?>">Edit</a>
                    <form method="POST" onsubmit="return confirm('Delete this event?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="event_id" value="<?php echo e($row["event_id"]); ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr><td colspan="7">No events available.</td></tr>
    <?php } ?>
</table>

</body>
</html>
<?php mysqli_close($conn); ?>
