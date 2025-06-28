<?php
session_start();
include_once '../../connect.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}

$guest_id = $_SESSION['guest_id'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['report_title'] ?? '');
    $category = trim($_POST['report_category'] ?? '');
    $description = trim($_POST['report_content'] ?? '');
    $report_date = date('Y-m-d');
    $report_id = uniqid('R');

    $upload_dir = '../../uploads/report_pictures/';
    $image_path = NULL;

    // Create folder if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['upload_image']) && $_FILES['upload_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_tmp = $_FILES['upload_image']['tmp_name'];
        $file_name = basename($_FILES['upload_image']['name']);
        $file_type = mime_content_type($file_tmp);

        if (in_array($file_type, $allowed_types)) {
            $unique_name = uniqid('IMG_') . '_' . $file_name;
            $target_path = $upload_dir . $unique_name;

            if (move_uploaded_file($file_tmp, $target_path)) {
                $image_path = $target_path;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Only JPG, JPEG, and PNG files are allowed.";
        }
    } else {
        $error = "Image upload is required.";
    }

    if (empty($error)) {
        $sql = "INSERT INTO report (report_id, guest_id, report_title, report_content, report_date, image_path, report_category)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $report_id, $guest_id, $title, $description, $report_date, $image_path, $category);

        if ($stmt->execute()) {
            echo "<script>alert('Report submitted successfully.'); window.location.href='GuestDashboard.php';</script>";
            exit();
        } else {
            $error = "Failed to submit report. Please try again.";
        }
        
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Report System</title>
    <link rel="stylesheet" href="../../include/css/footer.css">
    <link rel="stylesheet" href="../Home/css/homeheadersheet.css">
    <link rel="stylesheet" href="css/ReportSystem.css?v7">
</head>
<body>
<header>
    <?php include('../../HTML/Guest/GuestHeader.php'); ?>
</header>

<div class="main-container">
    <h1>Report Page</h1>
    <?php if (!empty($error)) : ?>
        <div class="error-message"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form class="report-form" method="post" enctype="multipart/form-data">
        <label for="report_title">Report Title</label>
        <input type="text" id="report_title" name="report_title" required>

        <label for="report_category">Type of problem category</label>
        <select id="report_category" name="report_category" required>
            <option value="" disabled selected>Please select</option>
            <option value="Technical Issue">Technical Issue</option>
            <option value="Account Issue">Account Issue</option>
            <option value="Payment Issue">Payment Issue</option>
            <option value="Other">Other</option>
        </select>

        <label for="report_content">Describe your issue</label>
        <textarea id="report_content" name="report_content" required></textarea>

        <div class="upload-container">
            <label for="upload_image">Upload Image (required)</label>
            <input type="file" id="upload_image" name="upload_image" required>
        </div>

        <button type="submit">Submit Report</button>
    </form>
</div>

<footer>
<?php include "../../include/footer.html"; ?>
</footer>
</body>
</html>
