<?php
// api/endpoints/positions.php — CRUD for positions

$conn = getConnection();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM positions WHERE id = ?");
            $stmt->bind_param("i", $id); $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) sendResponse(false, "Position not found.", null, 404);
            sendResponse(true, "Position retrieved.", $row);
        }
        $result = $conn->query("SELECT * FROM positions ORDER BY id");
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        sendResponse(true, count($rows) . " position(s) found.", $rows);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['title'])) sendResponse(false, "Position title is required.", null, 400);
        $title       = sanitize($data['title']);
        $description = sanitize($data['description'] ?? '');
        $max_votes   = (int)($data['max_votes'] ?? 1);

        $stmt = $conn->prepare("INSERT INTO positions (title, description, max_votes) VALUES (?,?,?)");
        $stmt->bind_param("ssi", $title, $description, $max_votes);
        if ($stmt->execute()) sendResponse(true, "Position created.", ["id" => $conn->insert_id], 201);
        sendResponse(false, "Failed to create position.", null, 500);
        break;

    case 'PUT':
        if (!$id) sendResponse(false, "Position ID required.", null, 400);
        $data = json_decode(file_get_contents("php://input"), true);
        $chk = $conn->prepare("SELECT id FROM positions WHERE id = ?");
        $chk->bind_param("i", $id); $chk->execute(); $chk->store_result();
        if ($chk->num_rows === 0) sendResponse(false, "Position not found.", null, 404);

        $sets = []; $params = []; $types = "";
        if (!empty($data['title']))       { $sets[] = "title = ?";       $params[] = sanitize($data['title']);       $types .= "s"; }
        if (!empty($data['description'])) { $sets[] = "description = ?"; $params[] = sanitize($data['description']); $types .= "s"; }
        if (isset($data['max_votes']))    { $sets[] = "max_votes = ?";   $params[] = (int)$data['max_votes'];        $types .= "i"; }

        if (!$sets) sendResponse(false, "No valid fields to update.", null, 400);
        $params[] = $id; $types .= "i";
        $stmt = $conn->prepare("UPDATE positions SET " . implode(", ", $sets) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) sendResponse(true, "Position updated.");
        sendResponse(false, "Failed to update position.", null, 500);
        break;

    case 'DELETE':
        if (!$id) sendResponse(false, "Position ID required.", null, 400);
        $chk = $conn->prepare("SELECT title FROM positions WHERE id = ?");
        $chk->bind_param("i", $id); $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if (!$row) sendResponse(false, "Position not found.", null, 404);

        $stmt = $conn->prepare("DELETE FROM positions WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) sendResponse(true, "Position '{$row['title']}' deleted.");
        sendResponse(false, "Failed to delete position.", null, 500);
        break;

    default:
        sendResponse(false, "Method not allowed.", null, 405);
}
$conn->close();
