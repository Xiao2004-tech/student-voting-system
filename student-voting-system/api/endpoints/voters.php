<?php
// api/endpoints/voters.php — CRUD for voters

$conn = getConnection();

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM voters WHERE id = ?");
            $stmt->bind_param("i", $id); $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) sendResponse(false, "Voter not found.", null, 404);
            sendResponse(true, "Voter retrieved.", $row);
        }

        $where = []; $params = []; $types = "";
        if (!empty($_GET['search'])) { $s = "%".$_GET['search']."%"; $where[] = "(full_name LIKE ? OR student_id LIKE ? OR email LIKE ?)"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= "sss"; }
        if (isset($_GET['has_voted'])) { $where[] = "has_voted = ?"; $params[] = (int)$_GET['has_voted']; $types .= "i"; }
        if (!empty($_GET['course']))    { $where[] = "course = ?"; $params[] = $_GET['course']; $types .= "s"; }

        $sql = "SELECT * FROM voters";
        if ($where) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY created_at DESC";

        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        sendResponse(true, count($rows) . " voter(s) found.", $rows);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        foreach (['full_name','student_id','email','course','year_level'] as $f)
            if (empty($data[$f])) sendResponse(false, "Field '$f' is required.", null, 400);

        $full_name  = sanitize($data['full_name']);
        $student_id = sanitize($data['student_id']);
        $email      = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
        $course     = sanitize($data['course']);
        $year       = in_array($data['year_level'], ['1st Year','2nd Year','3rd Year','4th Year']) ? $data['year_level'] : '1st Year';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendResponse(false, "Invalid email.", null, 400);

        $chk = $conn->prepare("SELECT id FROM voters WHERE student_id = ? OR email = ?");
        $chk->bind_param("ss", $student_id, $email); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) sendResponse(false, "Student ID or email already registered.", null, 409);

        $stmt = $conn->prepare("INSERT INTO voters (full_name, student_id, email, course, year_level) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $full_name, $student_id, $email, $course, $year);
        if ($stmt->execute()) sendResponse(true, "Voter registered successfully.", ["id" => $conn->insert_id], 201);
        sendResponse(false, "Failed to register voter.", null, 500);
        break;

    case 'PUT':
        if (!$id) sendResponse(false, "Voter ID required.", null, 400);
        $data = json_decode(file_get_contents("php://input"), true);

        $chk = $conn->prepare("SELECT id FROM voters WHERE id = ?");
        $chk->bind_param("i", $id); $chk->execute(); $chk->store_result();
        if ($chk->num_rows === 0) sendResponse(false, "Voter not found.", null, 404);

        $allowed = ['full_name','student_id','email','course','year_level'];
        $sets = []; $params = []; $types = "";
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $sets[]   = "$f = ?";
                $params[] = sanitize($data[$f]);
                $types   .= "s";
            }
        }
        if (!$sets) sendResponse(false, "No valid fields to update.", null, 400);
        $params[] = $id; $types .= "i";
        $stmt = $conn->prepare("UPDATE voters SET " . implode(", ", $sets) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) sendResponse(true, "Voter updated successfully.");
        sendResponse(false, "Failed to update voter.", null, 500);
        break;

    case 'DELETE':
        if (!$id) sendResponse(false, "Voter ID required.", null, 400);
        $chk = $conn->prepare("SELECT full_name, has_voted FROM voters WHERE id = ?");
        $chk->bind_param("i", $id); $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if (!$row) sendResponse(false, "Voter not found.", null, 404);
        if ($row['has_voted']) sendResponse(false, "Cannot delete a voter who has already voted.", null, 403);

        $stmt = $conn->prepare("DELETE FROM voters WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) sendResponse(true, "Voter '{$row['full_name']}' deleted.");
        sendResponse(false, "Failed to delete voter.", null, 500);
        break;

    default:
        sendResponse(false, "Method not allowed.", null, 405);
}
$conn->close();
