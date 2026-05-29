<?php
// api/endpoints/candidates.php — CRUD for candidates

$conn = getConnection();

switch ($method) {

    // ── READ ──
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT c.*, p.title AS position_title FROM candidates c JOIN positions p ON c.position_id = p.id WHERE c.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) sendResponse(false, "Candidate not found.", null, 404);
            sendResponse(true, "Candidate retrieved.", $row);
        }

        $where = []; $params = []; $types = "";
        if (!empty($_GET['position_id'])) { $where[] = "c.position_id = ?"; $params[] = (int)$_GET['position_id']; $types .= "i"; }
        if (!empty($_GET['status']))       { $where[] = "c.status = ?";      $params[] = $_GET['status'];            $types .= "s"; }
        if (!empty($_GET['search']))       { $s = "%".$_GET['search']."%"; $where[] = "(c.full_name LIKE ? OR c.student_id LIKE ?)"; $params[] = $s; $params[] = $s; $types .= "ss"; }

        $sql = "SELECT c.*, p.title AS position_title FROM candidates c JOIN positions p ON c.position_id = p.id";
        if ($where) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY c.position_id, c.full_name";

        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        sendResponse(true, count($rows) . " candidate(s) found.", $rows);
        break;

    // ── CREATE ──
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        foreach (['position_id','full_name','student_id','course','year_level'] as $f)
            if (empty($data[$f])) sendResponse(false, "Field '$f' is required.", null, 400);

        $pos_id     = (int) $data['position_id'];
        $full_name  = sanitize($data['full_name']);
        $student_id = sanitize($data['student_id']);
        $course     = sanitize($data['course']);
        $year       = in_array($data['year_level'], ['1st Year','2nd Year','3rd Year','4th Year']) ? $data['year_level'] : '1st Year';
        $platform   = sanitize($data['platform'] ?? '');
        $status     = in_array($data['status'] ?? '', ['Active','Disqualified']) ? $data['status'] : 'Active';

        // Check duplicate student_id
        $chk = $conn->prepare("SELECT id FROM candidates WHERE student_id = ?");
        $chk->bind_param("s", $student_id); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) sendResponse(false, "Student ID already registered as candidate.", null, 409);

        $stmt = $conn->prepare("INSERT INTO candidates (position_id, full_name, student_id, course, year_level, platform, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $pos_id, $full_name, $student_id, $course, $year, $platform, $status);
        if ($stmt->execute()) sendResponse(true, "Candidate added successfully.", ["id" => $conn->insert_id], 201);
        sendResponse(false, "Failed to add candidate.", null, 500);
        break;

    // ── UPDATE ──
    case 'PUT':
        if (!$id) sendResponse(false, "Candidate ID required.", null, 400);
        $data = json_decode(file_get_contents("php://input"), true);

        $chk = $conn->prepare("SELECT id FROM candidates WHERE id = ?");
        $chk->bind_param("i", $id); $chk->execute(); $chk->store_result();
        if ($chk->num_rows === 0) sendResponse(false, "Candidate not found.", null, 404);

        $allowed = ['position_id','full_name','student_id','course','year_level','platform','status'];
        $sets = []; $params = []; $types = "";
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $sets[]   = "$f = ?";
                $params[] = ($f === 'position_id') ? (int)$data[$f] : sanitize($data[$f]);
                $types   .= ($f === 'position_id') ? "i" : "s";
            }
        }
        if (!$sets) sendResponse(false, "No valid fields to update.", null, 400);
        $params[] = $id; $types .= "i";
        $stmt = $conn->prepare("UPDATE candidates SET " . implode(", ", $sets) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) sendResponse(true, "Candidate updated successfully.");
        sendResponse(false, "Failed to update candidate.", null, 500);
        break;

    // ── DELETE ──
    case 'DELETE':
        if (!$id) sendResponse(false, "Candidate ID required.", null, 400);
        $chk = $conn->prepare("SELECT full_name FROM candidates WHERE id = ?");
        $chk->bind_param("i", $id); $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if (!$row) sendResponse(false, "Candidate not found.", null, 404);

        $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) sendResponse(true, "Candidate '{$row['full_name']}' deleted.");
        sendResponse(false, "Failed to delete candidate.", null, 500);
        break;

    default:
        sendResponse(false, "Method not allowed.", null, 405);
}
$conn->close();
