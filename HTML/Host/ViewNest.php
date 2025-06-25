<?php include '../../Header_Footer/Header.php'; ?>
<?php include("connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids'])) {
  $deleteIds = $_POST['delete_ids'];
  $idList = implode(",", array_map('intval', $deleteIds));
  $conn->query("DELETE FROM homestay WHERE nest_Id IN ($idList)");
  header("Location: ViewNest.php");
  exit;
}

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
  <link rel="stylesheet" href="/StayNestTest/Header_Footer/css/Header.css" />
  <style>
    .delete-selected-btn {
      background-color: #e74c3c;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 15px 25px;
      font-size: 14px;
      font-family: 'NType', sans-serif;
      cursor: pointer;
      display: none;
      align-self: flex-end;
    }
    .select-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  </style>
</head>
<body>

<script>
  fetch('../../Header_Footer/Header.html')
    .then(response => response.text())
    .then(data => {
      document.getElementById('header-placeholder').innerHTML = data;
    });
</script>

<div class="host-nav-wrapper">
  <div class="host-nav">
    <a href="Analytics.php" class="host-nav-link">Analytics</a>
    <a href="ViewNest.php" class="host-nav-link active">View Nest</a>
  </div>
</div>

<div class="container">
  <div class="left">
    <div class="host-content">

      <form method="POST">
        <div class="select-add">
          <div class="select-bar">
            <label class="select-all-label">
              <div class="checkbox-container">
                <input type="checkbox" id="select-all" />
                <span>Select All</span>
              </div>
            </label>
            <button type="submit" id="deleteBtn" class="delete-selected-btn">Delete Selected</button>
          </div>

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
            <input type="checkbox" name="delete_ids[]" value="<?= $row['nest_Id'] ?>" class="card-checkbox" />
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
      </form>

    </div>
  </div>
  <div class="right"></div>
</div>

<script>
  const selectAll = document.getElementById('select-all');
  const checkboxes = document.querySelectorAll('.card-checkbox');
  const deleteBtn = document.getElementById('deleteBtn');

  selectAll.addEventListener('change', function () {
    checkboxes.forEach(cb => cb.checked = this.checked);
    toggleDeleteButton();
  });

  checkboxes.forEach(cb => {
    cb.addEventListener('change', toggleDeleteButton);
  });

  function toggleDeleteButton() {
    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
    deleteBtn.style.display = anyChecked ? 'inline-block' : 'none';
  }

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
