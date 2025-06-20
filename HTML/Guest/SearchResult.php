<?php
session_start();
include('../../connect.php'); // Adjust if needed

$keyword = '';
$results = [];

if (isset($_GET['query']) && isset($_SESSION['guest_id'])) {
    $keyword = trim($_GET['query']);
    $guest_id = $_SESSION['guest_id'];
    $timestamp = date("Y-m-d H:i:s");

    // Generate a unique search_id (basic version)
    $countQuery = $conn->query("SELECT COUNT(*) as total FROM search_log");
    $count = $countQuery->fetch_assoc()['total'] + 1;
    $search_id = 'S' . str_pad($count, 3, '0', STR_PAD_LEFT);

    // Log the search
    $stmt = $conn->prepare("INSERT INTO search_log (search_id, guest_id, keyword, search_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $search_id, $guest_id, $keyword, $timestamp);
    $stmt->execute();
    $stmt->close();

    // Search homestay
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
} else {
    echo "<p style='padding: 20px; font-size: 18px;'>Please log in and enter a search query.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Search Results</title>
  <link rel="stylesheet" href="../../CSS/Guest/GuestDashboard.css" />
  <link rel="stylesheet" href="../../CSS/Guest/SearchResults.css" />
</head>
<body>

  <!-- NAVBAR -->
  <header class="main-header">
    <div class="left">
      <img src="../../assets/staynest_logo.png" alt="StayNest Logo" class="logo" />
      <span class="WebName">StayNest</span>
      <a href="../../HTML/Guest/GuestDashboard.html" class="nav-link">Dashboard</a>
      <a href="../../HTML/Guest/BookingManagement.html" class="nav-link">My Booking</a>
    </div>

    <div class="center">
      <form method="GET" action="SearchResults.php">
        <input type="text" name="query" placeholder="Find your stay..." class="search-bar" value="<?php echo htmlspecialchars($keyword); ?>" />
      </form>
    </div>

    <div class="right">
      <img src="../../assets/Guest/notification.png" alt="Notification" class="icon" />
      <img src="../../assets/Guest/message.png" alt="Messages" class="icon" />
      <a href="../../HTML/Host/HostDashboard.html" class="be-a-host">+ Be a Host</a>
      <div class="profile-wrapper">
        <img src="path/to/profile-image.jpg" alt="Profile" class="profile-icon" />
      </div>
    </div>
  </header>

  <!-- SEARCH RESULT SECTION -->
  <div class="main-container">
    <div class="search-result">
      <h2>Search Results for "<?php echo htmlspecialchars($keyword); ?>"</h2>
      <div class="result-grid">

        <?php
        if ($results && $results->num_rows > 0) {
          while ($row = $results->fetch_assoc()) {
            echo '<div class="result-card">';
            echo '<img src="../../assets/Guest/sample1.jpg" alt="Property Image">'; // replace with real image if stored
            echo '<div class="result-info">';
            echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
            echo '<p>' . htmlspecialchars($row['description']) . '</p>';
            echo '<p><strong>RM' . htmlspecialchars($row['price_per_night']) . '/night</strong></p>';
            echo '<a href="ViewPropertyListing.php?id=' . $row['homestay_id'] . '" class="view-btn">View Property</a>';
            echo '</div></div>';
          }
        } else {
          echo "<p>No matching homestays found.</p>";
        }
        ?>

      </div>
    </div>
  </div>

  <footer>
    <!-- Footer content -->
  </footer>

</body>
</html>
