<?php
require_once '../../connect.php'; // Adjust path if needed

// Fetch most clicked properties with their image and title
$sql = "
    SELECT h.homestay_id, h.title, h.picture1, COUNT(c.click_id) AS click_count
    FROM property_clicks c
    JOIN homestay h ON c.homestay_id = h.homestay_id
    WHERE h.homestay_status = 1
    GROUP BY h.homestay_id
    ORDER BY click_count DESC
    LIMIT 6
";

$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'homestay_id' => $row['homestay_id'],
            'title' => $row['title'],
            'picture1' => $row['picture1'] ?? ''
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>
