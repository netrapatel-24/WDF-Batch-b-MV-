
<?php
session_start();
header("Content-Type: application/json");

include "db.php";

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit();
}

if (!isset($_SESSION["student_id"]) || ($_SESSION["role"] ?? "") != "admin") {
    http_response_code(403);
    sendResponse(false, "Admin login required.");
}

if (isset($_SESSION["login_time"]) && time() - $_SESSION["login_time"] > 1800) {
    session_unset();
    session_destroy();
    http_response_code(401);
    sendResponse(false, "Session expired. Please log in again.");
}

$_SESSION["login_time"] = time();

$method = $_SERVER["REQUEST_METHOD"];

if ($method == "GET") {
    $result = mysqli_query($conn, "SELECT event_id, event_name, event_date, venue, description FROM events ORDER BY event_id DESC");

    if (!$result) {
        http_response_code(500);
        sendResponse(false, "Could not load events.");
    }

    $events = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }

    sendResponse(true, "Events loaded successfully.", $events);
}

if ($method != "POST") {
    http_response_code(405);
    sendResponse(false, "Method not allowed.");
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);
    sendResponse(false, "Invalid JSON data.");
}

$action = $input["action"] ?? "";

if ($action == "add" || $action == "update") {
    $name = trim($input["event_name"] ?? "");
    $date = trim($input["event_date"] ?? "");
    $venue = trim($input["venue"] ?? "");
    $description = trim($input["description"] ?? "");

    if ($name == "" || $date == "") {
        http_response_code(400);
        sendResponse(false, "Event name and date are required.");
    }

    $dateObject = DateTime::createFromFormat("!Y-m-d", $date);

    if (!$dateObject || $dateObject->format("Y-m-d") != $date) {
        http_response_code(400);
        sendResponse(false, "Please enter a valid event date.");
    }

    if (strlen($name) > 100 || strlen($venue) > 100 || strlen($description) > 255) {
        http_response_code(400);
        sendResponse(false, "One or more fields are too long.");
    }

    if ($action == "add") {
        $stmt = mysqli_prepare($conn, "INSERT INTO events (event_name, event_date, venue, description) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $date, $venue, $description);
        $message = "Event added successfully.";
    } else {
        $id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            http_response_code(400);
            sendResponse(false, "Invalid event ID.");
        }

        $stmt = mysqli_prepare($conn, "UPDATE events SET event_name=?, event_date=?, venue=?, description=? WHERE event_id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $date, $venue, $description, $id);
        $message = "Event updated successfully.";
    }

    if (!$stmt) {
        http_response_code(500);
        sendResponse(false, "Could not prepare database query.");
    }

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        sendResponse(false, "Database operation failed.");
    }

    if ($action == "update" && mysqli_stmt_affected_rows($stmt) == 0) {
        $idCheck = mysqli_prepare($conn, "SELECT event_id FROM events WHERE event_id=?");
        mysqli_stmt_bind_param($idCheck, "i", $id);
        mysqli_stmt_execute($idCheck);
        mysqli_stmt_store_result($idCheck);

        if (mysqli_stmt_num_rows($idCheck) == 0) {
            http_response_code(404);
            sendResponse(false, "Event not found.");
        }
    }

    sendResponse(true, $message);
}

if ($action == "delete") {
    $id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        http_response_code(400);
        sendResponse(false, "Invalid event ID.");
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM events WHERE event_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        sendResponse(false, "Could not delete event.");
    }

    if (mysqli_stmt_affected_rows($stmt) == 0) {
        http_response_code(404);
        sendResponse(false, "Event not found.");
    }

    sendResponse(true, "Event deleted successfully.");
}

http_response_code(400);
sendResponse(false, "Invalid action.");
?>
