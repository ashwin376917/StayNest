<?php include '../../connect.php'; ?>

<?php
$type = $_GET['type'] ?? 'guest';

if ($type === 'host') {
    $query = "SELECT host_id AS id, host_name AS name, host_email AS email FROM host";
} else {
    $query = "SELECT guest_id AS id, guest_name AS name, guest_email AS email FROM guest";
}

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User List</title>
  <link rel="stylesheet" href="../../CSS/Admin/ViewUserList.css" />
  <link rel="stylesheet" href="../../CSS/Admin/HeaderAdmin.css" />
</head>
<body>

  <?php include '../../HTML/Admin/HeaderAdmin.php'; ?>


  <main class="content">
    <h1>User List</h1>

    <div class="filter-bar">
      <button onclick="filterUsers('guest')" class="<?= $type === 'guest' ? 'active' : '' ?>">Guest</button>
      <button onclick="filterUsers('host')" class="<?= $type === 'host' ? 'active' : '' ?>">Host</button>
    </div>

    <div class="user-container">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="card">
            <div class="avatar"><?= strtoupper(substr($row['name'], 0, 1)) ?></div>
            <div class="user-info">
              <strong><?= htmlspecialchars($row['name']) ?></strong>
              <small><?= htmlspecialchars($row['email']) ?></small>
            </div>
            <button class="ban-btn">🚫 Ban</button>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No users found.</p>
      <?php endif; ?>
    </div>
  </main>

  <script>
    function filterUsers(type) {
      window.location.href = `ViewUserList.php?type=${type}`;
    }
  </script>
</body>
</html>
