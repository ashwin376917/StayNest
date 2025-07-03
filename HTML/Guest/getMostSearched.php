<?php
require_once '../../connect.php'; // Path is correct

// Fetch most clicked properties based on total_click in homestay table
$sql = "
    SELECT homestay_id, title, picture1, total_click
    FROM homestay
    WHERE homestay_status = 1
    ORDER BY total_click DESC
    LIMIT 6
";

$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'homestay_id' => $row['homestay_id'],
            'title' => $row['title'],
            'picture1' => $row['picture1'] ?? '',
            'total_click' => $row['total_click']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>
