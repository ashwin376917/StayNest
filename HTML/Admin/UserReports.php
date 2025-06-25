<?php include '../../connect.php'; ?>
<?php include '../../HTML/Admin/HeaderAdmin.php'; ?>

<?php
function renderReportCard($title, $description, $user, $date, $reportId, $bgUrl, $type) {
    ?>
    <div class="card" data-type="<?= strtolower($type) ?>">
      <div class="card-overlay">
        <div class="user">
          <div class="avatar"><?= strtoupper(substr($user, 0, 1)) ?></div>
          <div>
            <strong><?= htmlspecialchars($user) ?></strong>
            <small><?= htmlspecialchars($date) ?></small>
          </div>
        </div>
        <div class="card-text">
          <h2><?= htmlspecialchars($title) ?></h2>
          <p><?= htmlspecialchars($description) ?></p>
        </div>
      </div>
    </div>
    <?php
}

$sql = "
  SELECT 
    report_id, report_title, report_content, report_date, report_category, image_path,
    COALESCE(g.guest_name, h.host_name) AS username
  FROM report r
  LEFT JOIN guest g ON r.guest_id = g.guest_id
  LEFT JOIN host h ON r.host_id = h.host_id
  ORDER BY report_date DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Reports</title>
  <link rel="stylesheet" href="../../CSS/Admin/HeaderAdmin.css" />
  <link rel="stylesheet" href="../../CSS/Admin/UserReports.css" />
</head>
<body>

  <main class="content">
    <h1>User Reports</h1>

    <div class="report-container">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): 
          $bgUrl = !empty($row['image_path']) ? "/StayNest/upload/" . htmlspecialchars($row['image_path']) : "https://via.placeholder.com/600x400";
          renderReportCard(
            $row['report_title'],
            $row['report_content'],
            $row['username'] ?? 'Unknown',
            $row['report_date'],
            $row['report_id'],
            $bgUrl,
            $row['report_category']
          );
        endwhile; ?>
      <?php else: ?>
        <p>No reports found.</p>
      <?php endif; ?>
    </div>

    <div class="filter-bar">
      <button class="active" onclick="filterReports('all')">All</button>
      <button onclick="filterReports('pending')">Pending</button>
      <button onclick="filterReports('violation')">Violation</button>
      <button onclick="filterReports('bugs')">Bugs</button>
    </div>
  </main>

  <script>
    function filterReports(type) {
      const buttons = document.querySelectorAll('.filter-bar button');
      buttons.forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');

      const cards = document.querySelectorAll('.card');
      cards.forEach(card => {
        const cardType = card.getAttribute('data-type');
        card.classList.toggle('hidden', type !== 'all' && cardType !== type);
      });
    }
  </script>
</body>
</html>
