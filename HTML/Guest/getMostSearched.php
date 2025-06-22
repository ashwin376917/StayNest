<?php
require_once '../../connect.php'; // Adjust path if needed

// Fetch most searched keywords that match homestay titles
$sql = "
    SELECT h.title, h.picture1
    FROM search_log s
    JOIN homestay h ON s.keyword = h.title
    WHERE h.homestay_status = 1
    GROUP BY h.title
    ORDER BY COUNT(*) DESC
    LIMIT 6
";

$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'title' => $row['title'],
            'picture' => $row['picture1'] ?? ''
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>
