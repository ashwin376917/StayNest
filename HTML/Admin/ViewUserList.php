<?php
include '../../connect.php';

// Handle ban/unban form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_email'], $_POST['ban_type'], $_POST['action'])) {
    $email = $_POST['ban_email'];
    $type = $_POST['ban_type'];
    $action = $_POST['action'];

    $table = $type === 'host' ? 'host' : 'guest';
    $emailCol = $type === 'host' ? 'host_email' : 'guest_email';
    $status = $action === 'ban' ? 1 : 0;

    $stmt = $conn->prepare("UPDATE $table SET banned = ? WHERE $emailCol = ?");
    $stmt->bind_param("is", $status, $email);
    $stmt->execute();
}

$type = $_GET['type'] ?? 'guest';

if ($type === 'host') {
    $query = "SELECT host_id AS id, host_name AS name, host_email AS email, 'host' AS user_type FROM host WHERE banned = 0";
} elseif ($type === 'banned') {
    $query = "SELECT guest_name AS name, guest_email AS email, 'guest' AS user_type FROM guest WHERE banned = 1
              UNION
              SELECT host_name AS name, host_email AS email, 'host' AS user_type FROM host WHERE banned = 1";
} else {
    $query = "SELECT guest_id AS id, guest_name AS name, guest_email AS email, 'guest' AS user_type FROM guest WHERE banned = 0";
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
      <button onclick="filterUsers('banned')" class="<?= $type === 'banned' ? 'active' : '' ?>">Banned</button>
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
            <form method="POST" onsubmit="return confirmBan(this);">
              <input type="hidden" name="ban_email" value="<?= htmlspecialchars($row['email']) ?>">
              <input type="hidden" name="ban_type" value="<?= htmlspecialchars($row['user_type']) ?>">
              <?php if ($type === 'banned'): ?>
                <input type="hidden" name="action" value="unban">
                <button type="submit" class="ban-btn" style="background-color: green;">✅ Unban</button>
              <?php else: ?>
                <input type="hidden" name="action" value="ban">
                <button type="submit" class="ban-btn">🚫 Ban</button>
              <?php endif; ?>
            </form>
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

    function confirmBan(form) {
      const email = form.querySelector('input[name="ban_email"]').value;
      return confirm(`Are you sure you want to ${form.action.value === 'unban' ? 'unban' : 'ban'} ${email}?`);
    }
  </script>
</body>
</html>
