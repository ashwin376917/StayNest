<?php include '../../connect.php'; ?>

<?php
$sql = "SELECT * FROM homestay";
$result = $conn->query($sql);

function mapStatus($status) {
    if ($status == 0) return ["Pending", "orange"];
    if ($status == 1) return ["Approved", "green"];
    if ($status == 2) return ["Banned", "red"];
    return ["Unknown", "gray"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Property List</title>
  <link rel="stylesheet" href="../../CSS/Admin/HeaderAdmin.css" />
  <link rel="stylesheet" href="../../CSS/Admin/ViewPropertyInfo.css" />
</head>
<body>

  <?php include '../../HTML/Admin/HeaderAdmin.php'; ?>

  <main class="content">
    <h1>Property List</h1>

    <div class="property-container">
      <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()):
          [$statusText, $statusColor] = mapStatus($row['approval_status']);
          $picture1 = htmlspecialchars($row['picture1']);
          $bgUrl = !empty($picture1) ? "/StayNest/upload/{$picture1}" : "https://via.placeholder.com/600x400";
        ?>
          <div class="card" data-status="<?= strtolower($statusText) ?>" data-id="<?= htmlspecialchars($row['homestay_id']) ?>" style="background-image: url('<?= $bgUrl ?>');">
            <h2><?= htmlspecialchars($row['title']) ?></h2>
            <div class="actions">
              <?php if (strtolower($statusText) === 'pending'): ?>
                <button class="approve-btn" onclick="setStatus(this, 'Approved', 'green')">✔ Approve</button>
              <?php endif; ?>
              <button class="view-btn">👁 View Page</button>
              <button class="ban-btn" onclick="setStatus(this, 'Banned', 'red')">🚫 Ban</button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No properties found.</p>
      <?php endif; ?>
    </div>

    <div class="filter-bar">
      <button class="active" onclick="filterProperties('all')">All</button>
      <button onclick="filterProperties('pending')">Pending</button>
      <button onclick="filterProperties('approved')">Approved</button>
      <button onclick="filterProperties('banned')">Banned</button>
    </div>
  </main>

  <script src="../../JS/Admin/ViewPropertyInfo.js"></script>
</body>
</html>
