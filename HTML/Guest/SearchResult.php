<?php
session_start();
include('../../connect.php');

$keyword = '';
$results = [];

if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/LoginPage.php");
    exit;
}

if (isset($_GET['query'])) {
    $keyword = trim($_GET['query']);
    $_SESSION['last_query'] = $keyword; // ✅ Save query for back button use

    $sql = "SELECT * FROM homestay WHERE 
            title LIKE ? OR 
            description LIKE ? OR 
            state LIKE ? OR 
            district LIKE ? OR 
            amenities LIKE ?";
    $searchTerm = "%$keyword%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Search Results</title>
  <link rel="stylesheet" href="../../HTML/Guest/css/GuestDashboard.css" />
  <link rel="stylesheet" href="../../HTML/Guest/css/SearchResult.css?v=4"/>
  <link rel="stylesheet" href="../Home/css/homeheadersheet.css">
  <link rel="stylesheet" href="../../include/css/footer.css">
</head>
<body>

<?php include('GuestHeader.php'); ?>

<div class="main-container">
    <div class="search-result">
        <h2>Results for "<?php echo htmlspecialchars($keyword); ?>"</h2>
        <div class="result-grid">

        <?php
        if ($results && $results->num_rows > 0) {
            while ($row = $results->fetch_assoc()) {
                $homestayId = htmlspecialchars($row['homestay_id']);
                echo '<a href="ViewPropertyDetail.php?homestay_id=' . $homestayId . '" class="result-link">';
                echo '  <div class="result-card">';
                echo '    <div class="result-left">';
                echo '      <img src="/StayNest/HTML/Host/' . htmlspecialchars($row['picture1']) . '" alt="Main Image">';
                echo '    </div>';
                echo '    <div class="result-middle">';
                echo '      <div class="text-top">';
                echo '        <h3>' . htmlspecialchars($row['title']) . '</h3>';
                echo '        <p>' . htmlspecialchars($row['state'] . ', ' . $row['district']) . '</p>';
                echo '      </div>';
                echo '      <div class="text-bottom">';
                echo '        <p class="price">RM ' . htmlspecialchars($row['price_per_night']) . '</p>';
                echo '      </div>';
                echo '    </div>';
                echo '    <div class="result-right">';
                echo '      <div class="preview-img"><img src="/StayNest/HTML/Host/' . htmlspecialchars($row['picture2']) . '" alt="Preview 1"></div>';
                echo '      <div class="preview-img"><img src="/StayNest/HTML/Host/' . htmlspecialchars($row['picture3']) . '" alt="Preview 2"></div>';
                echo '    </div>';
                echo '  </div>';
                echo '</a>';
            }
        } else {
            echo "<p style='padding: 20px; font-size: 18px;'>No matching homestays found.</p>";
        }
        ?>

        </div>
    </div>
</div>

<?php include "../../include/footer.html"; ?>
<script src="js/SearchHandler.js"></script>
</body>
</html>
