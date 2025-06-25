<?php
include '../../connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $homestayId = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (!in_array($status, ['0', '1', '2']) || empty($homestayId)) {
        http_response_code(400);
        echo "Invalid request";
        exit;
    }

    $stmt = $conn->prepare("UPDATE homestay SET approval_status = ? WHERE homestay_id = ?");
    $stmt->bind_param("is", $status, $homestayId);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error updating status";
    }

    $stmt->close();
    $conn->close();
}
?>
