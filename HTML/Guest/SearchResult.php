<?php
session_start();
include('../../connect.php');

$keyword = '';
$results = [];

if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/LoginPage.php");
    exit;
}

$guest_id = $_SESSION['guest_id'];

if (isset($_GET['query'])) {
    $keyword = trim($_GET['query']);
    $timestamp = date("Y-m-d H:i:s");

    $countQuery = $conn->query("SELECT COUNT(*) as total FROM search_log");
    $count = $countQuery->fetch_assoc()['total'] + 1;
    $search_id = 'S' . str_pad($count, 3, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO search_log (search_id, guest_id, keyword, search_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $search_id, $guest_id, $keyword, $timestamp);
    $stmt->execute();
    $stmt->close();

    $sql = "SELECT * FROM homestay WHERE 
            title LIKE ? OR 
            description LIKE ? OR 
            address LIKE ? OR 
            amenities LIKE ?";
    $searchTerm = "%$keyword%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
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
  <link rel="stylesheet" href="../../CSS/Guest/GuestDashboard.css" />
  <link rel="stylesheet" href="../../CSS/Guest/SearchResult.css" />
  <link rel="stylesheet" href="../../CSS/Guest/GuestHeader.css?v=4">

</head>
<body>

  
   <!-- HEADER -->
   <?php include('../../HTML/Guest/GuestHeader.php'); ?>


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
          echo '      <img src="/' . ltrim($row['picture1'], '/') . '" alt="Main Image">';
          echo '    </div>';
          echo '    <div class="result-middle">';
          echo '      <div class="text-top">';
          echo '        <h3>' . htmlspecialchars($row['title']) . '</h3>';
          echo '        <p>' . htmlspecialchars($row['address']) . '</p>';
          echo '      </div>';
          echo '      <div class="text-bottom">';
          echo '        <p class="price">RM ' . htmlspecialchars($row['price_per_night']) . ' ++</p>';
          echo '      </div>';
          echo '    </div>';
          echo '    <div class="result-right">';
          echo '      <div class="preview-img"><img src="/' . ltrim($row['picture2'], '/') . '" alt="Preview 1"></div>';
          echo '      <div class="preview-img"><img src="/' . ltrim($row['picture3'], '/') . '" alt="Preview 2"></div>';
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
  <script src="../../JS/Guest/SearchHandler.js?v=1"></script>
</body>
</html>
