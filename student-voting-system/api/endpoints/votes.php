<?php
// api/endpoints/votes.php — Cast votes

$conn = getConnection();

switch ($method) {

    // Get all votes (admin view)
    case 'GET':
        $sql = "SELECT v.id, v.voted_at,
                       vt.full_name AS voter_name, vt.student_id AS voter_student_id,
                       c.full_name  AS candidate_name,
                       p.title      AS position_title
                FROM votes v
                JOIN voters     vt ON v.voter_id     = vt.id
                JOIN candidates c  ON v.candidate_id = c.id
                JOIN positions  p  ON v.position_id  = p.id
                ORDER BY v.voted_at DESC";
        $result = $conn->query($sql);
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        sendResponse(true, count($rows) . " vote(s) found.", $rows);
        break;

    // Cast a vote
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['voter_id']) || empty($data['votes']) || !is_array($data['votes']))
            sendResponse(false, "voter_id and votes[] array are required.", null, 400);

        $voter_id = (int) $data['voter_id'];

        // Check voter exists and hasn't voted
        $chk = $conn->prepare("SELECT id, has_voted, full_name FROM voters WHERE id = ?");
        $chk->bind_param("i", $voter_id); $chk->execute();
        $voter = $chk->get_result()->fetch_assoc();
        if (!$voter)          sendResponse(false, "Voter not found.", null, 404);
        if ($voter['has_voted']) sendResponse(false, "This voter has already cast their vote.", null, 403);

        // Each vote: { candidate_id, position_id }
        $conn->begin_transaction();
        try {
            foreach ($data['votes'] as $vote) {
                if (empty($vote['candidate_id']) || empty($vote['position_id']))
                    throw new Exception("Each vote must have candidate_id and position_id.");

                $cand_id = (int) $vote['candidate_id'];
                $pos_id  = (int) $vote['position_id'];

                // Verify candidate belongs to position and is active
                $cv = $conn->prepare("SELECT id FROM candidates WHERE id = ? AND position_id = ? AND status = 'Active'");
                $cv->bind_param("ii", $cand_id, $pos_id); $cv->execute(); $cv->store_result();
                if ($cv->num_rows === 0) throw new Exception("Invalid candidate or position.");

                $ins = $conn->prepare("INSERT INTO votes (voter_id, candidate_id, position_id) VALUES (?,?,?)");
                $ins->bind_param("iii", $voter_id, $cand_id, $pos_id);
                $ins->execute();
            }

            // Mark voter as voted
            $now = date('Y-m-d H:i:s');
            $upd = $conn->prepare("UPDATE voters SET has_voted = 1, voted_at = ? WHERE id = ?");
            $upd->bind_param("si", $now, $voter_id);
            $upd->execute();

            $conn->commit();
            sendResponse(true, "Vote cast successfully for {$voter['full_name']}.");

        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(false, "Voting failed: " . $e->getMessage(), null, 400);
        }
        break;

    default:
        sendResponse(false, "Method not allowed.", null, 405);
}
$conn->close();
