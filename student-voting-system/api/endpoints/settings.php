<?php
// api/endpoints/settings.php — Election settings

$conn = getConnection();

switch ($method) {
    case 'GET':
        $row = $conn->query("SELECT * FROM election_settings LIMIT 1")->fetch_assoc();
        if (!$row) sendResponse(false, "No settings found.", null, 404);
        sendResponse(true, "Settings retrieved.", $row);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $allowed = ['school_name','election_name','start_date','end_date','status'];
        $sets = []; $params = []; $types = "";
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $sets[]   = "$f = ?";
                $params[] = sanitize($data[$f]);
                $types   .= "s";
            }
        }
        if (!$sets) sendResponse(false, "No valid fields to update.", null, 400);

        // Upsert: update if exists, insert if not
        $exists = $conn->query("SELECT id FROM election_settings LIMIT 1")->fetch_assoc();
        if ($exists) {
            $stmt = $conn->prepare("UPDATE election_settings SET " . implode(", ", $sets) . " WHERE id = " . $exists['id']);
            $stmt->bind_param($types, ...$params);
        } else {
            sendResponse(false, "No settings row found. Please seed the database.", null, 500);
        }
        if ($stmt->execute()) sendResponse(true, "Settings updated.");
        sendResponse(false, "Failed to update settings.", null, 500);
        break;

    default:
        sendResponse(false, "Method not allowed.", null, 405);
}
$conn->close();
