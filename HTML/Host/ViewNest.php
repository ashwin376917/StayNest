<?php include '../../Header_Footer/Header.php'; ?>
<?php include("connect.php");

$sql = "SELECT * FROM homestay WHERE host_Id = 1 ORDER BY nest_Id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Nest - StayNest Host</title>
  <link rel="stylesheet" href="../../CSS/ViewNest.css" />
  <link rel="stylesheet" href="/StayNest/Header_Footer/css/Header.css" />
</head>
<body>

  <script>
    fetch('../../Header_Footer/Header.html')
      .then(response => response.text())
      .then(data => {
        document.getElementById('header-placeholder').innerHTML = data;
      });
  </script>

  <!-- Navigation Tabs -->
  <div class="host-nav-wrapper">
    <div class="host-nav">
      <a href="Analytics.php" class="host-nav-link">Analytics</a>
      <a href="ViewNest.php" class="host-nav-link active">View Nest</a>
    </div>
  </div>

  <!-- Main Layout -->
  <div class="container">
    <div class="left">
      <div class="host-content">

        <div class="select-add">
          <label class="select-all-label">
            <div class="checkbox-container">
              <input type="checkbox" id="select-all" />
              <span>Select All</span>
            </div>
          </label>

          <a href="AddProperty.php" class="add-property-btn full-width" style="text-decoration: none;">+ Add Property</a>
        </div>

        <div class="property-list">
          <?php while ($row = $result->fetch_assoc()) {
            $status = strtolower($row['nest_Status']);
            $statusLabel = strtoupper($row['nest_Status']);
            $availability = ($status === 'pending') ? 'Waiting Approval' : 'Fully Booked';
            $thumb = !empty($row['picture1']) ? "../../" . $row['picture1'] : "../../assets/staynest_logo.png";
            $location = trim($row['district'] . ' ' . $row['state']);
          ?>
            <div class="property-card clickable-card" data-href="EditNest.php?nest_Id=<?= $row['nest_Id'] ?>">
              <input type="checkbox" />
              <img src="<?= $thumb ?>" alt="property" class="property-thumb" />
              <div class="property-info">
                <h3><?= htmlspecialchars($row['nest_Name']) ?></h3>
                <p><?= htmlspecialchars($location) ?></p>
              </div>
              <span class="status <?= $status ?>"><?= $statusLabel ?></span>
              <span class="availability"><?= $availability ?></span>
            </div>
          <?php } ?>
        </div>

      </div>
    </div>
    <div class="right"></div>
  </div>

  <!-- Make card background clickable -->
  <script>
    document.querySelectorAll('.clickable-card').forEach(card => {
      card.addEventListener('click', function(e) {
        if (!e.target.closest('input')) {
          window.location.href = this.dataset.href;
        }
      });
    });
  </script>

</body>
</html>
