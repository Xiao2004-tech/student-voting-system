<?php
// api/endpoints/results.php — Election results tally

$conn = getConnection();

if ($method !== 'GET') sendResponse(false, "Method not allowed.", null, 405);

$sql = "
    SELECT
        p.id            AS position_id,
        p.title         AS position,
        c.id            AS candidate_id,
        c.full_name     AS candidate,
        c.course,
        c.year_level,
        c.status        AS candidate_status,
        COUNT(v.id)     AS vote_count
    FROM positions p
    LEFT JOIN candidates c ON c.position_id = p.id
    LEFT JOIN votes v      ON v.candidate_id = c.id
    GROUP BY p.id, c.id
    ORDER BY p.id, vote_count DESC
";

$result = $conn->query($sql);
$grouped = [];
while ($row = $result->fetch_assoc()) {
    $pid = $row['position_id'];
    if (!isset($grouped[$pid])) {
        $grouped[$pid] = [
            "position_id" => $pid,
            "position"    => $row['position'],
            "candidates"  => []
        ];
    }
    if ($row['candidate_id']) {
        $grouped[$pid]['candidates'][] = [
            "candidate_id"     => $row['candidate_id'],
            "candidate"        => $row['candidate'],
            "course"           => $row['course'],
            "year_level"       => $row['year_level'],
            "candidate_status" => $row['candidate_status'],
            "vote_count"       => (int) $row['vote_count'],
        ];
    }
}

// Total voters / turnout
$totals = $conn->query("SELECT COUNT(*) AS total, SUM(has_voted) AS voted FROM voters")->fetch_assoc();

sendResponse(true, "Election results retrieved.", [
    "results"       => array_values($grouped),
    "total_voters"  => (int) $totals['total'],
    "total_voted"   => (int) $totals['voted'],
    "turnout_pct"   => $totals['total'] > 0 ? round(($totals['voted'] / $totals['total']) * 100, 1) : 0
]);

$conn->close();
